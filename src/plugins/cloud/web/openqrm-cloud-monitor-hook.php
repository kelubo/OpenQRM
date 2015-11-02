<?php
/*
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


// error_reporting(E_ALL);
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
$CloudDir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/';
require_once $RootDir."/include/user.inc.php";
require_once $RootDir."/class/image.class.php";
require_once $RootDir."/class/image_authentication.class.php";
require_once $RootDir."/class/resource.class.php";
require_once $RootDir."/class/virtualization.class.php";
require_once $RootDir."/class/appliance.class.php";
require_once $RootDir."/class/storage.class.php";
require_once $RootDir."/class/deployment.class.php";
require_once $RootDir."/class/openqrm_server.class.php";
require_once $RootDir."/class/event.class.php";
require_once $RootDir."/class/authblocker.class.php";
// special cloud classes
require_once $RootDir."/plugins/cloud/class/clouduser.class.php";
require_once $RootDir."/plugins/cloud/class/cloudrequest.class.php";
require_once $RootDir."/plugins/cloud/class/cloudconfig.class.php";
require_once $RootDir."/plugins/cloud/class/cloudmailer.class.php";
require_once $RootDir."/plugins/cloud/class/cloudvm.class.php";
require_once $RootDir."/plugins/cloud/class/cloudimage.class.php";
require_once $RootDir."/plugins/cloud/class/cloudappliance.class.php";
require_once $RootDir."/plugins/cloud/class/cloudirlc.class.php";
require_once $RootDir."/plugins/cloud/class/cloudiplc.class.php";
require_once $RootDir."/plugins/cloud/class/cloudtransaction.class.php";
require_once $RootDir."/plugins/cloud/class/cloudprivateimage.class.php";
require_once $RootDir."/plugins/cloud/class/cloudselector.class.php";
require_once $RootDir."/plugins/cloud/class/cloudstorage.class.php";
require_once $RootDir."/plugins/cloud/class/cloudpowersaver.class.php";
require_once $RootDir."/plugins/cloud/class/cloudcreatevmlc.class.php";
require_once $RootDir."/plugins/cloud/class/cloudapplication.class.php";

// custom billing hook, please fill in your custom-billing function 
require_once "$RootDir/plugins/cloud/openqrm-cloud-billing-hook.php";
// ip mgmt class, only if enabled
$ip_mgmt_class = "$RootDir/plugins/ip-mgmt/class/ip-mgmt.class.php";
if (file_exists($ip_mgmt_class)) {
	require_once $ip_mgmt_class;
}
// ldap class, only if enabled
$ldap_class = "$RootDir/plugins/ldap/class/ldapconfig.class.php";
if (file_exists($ldap_class)) {
	require_once $ldap_class;
}


global $CLOUD_USER_TABLE;
global $CLOUD_REQUEST_TABLE;
global $CLOUD_IMAGE_TABLE;
global $CLOUD_APPLIANCE_TABLE;
global $APPLIANCE_INFO_TABLE;
global $IMAGE_INFO_TABLE;

global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
$vm_create_timout=300;
global $vm_create_timout;

$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;


$refresh_delay=1;
$refresh_loop_max=60;
function wait_for_statfile($sfile) {
	global $refresh_delay;
	global $refresh_loop_max;
	$refresh_loop=0;
	while (!file_exists($sfile)) {
		sleep($refresh_delay);
		$refresh_loop++;
		flush();
		if ($refresh_loop > $refresh_loop_max)  {
			return false;
		}
	}
	return true;
}

function substract_until_zero($a, $b) {
	$x = abs(intval($a)) - abs(intval($b));
	if ($x < 0) {
		$x = 0;
	}
	return $x;
}


// request status
// 1 = new
// 2 = approved
// 3 = active (provisioned)
// 4 = denied
// 5 = deprovisioned
// 6 = done

// this function is going to be called by the monitor-hook in the resource-monitor
// It handles the cloud requests

function openqrm_cloud_monitor() {
	global $event;
	global $APPLIANCE_INFO_TABLE;
	global $IMAGE_INFO_TABLE;
	global $CLOUD_IMAGE_TABLE;
	global $CLOUD_APPLIANCE_TABLE;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $openqrm_server;
	global $BaseDir;
	global $RootDir;
	global $vm_create_timout;
	$vmware_mac_address_space = "00:50:56";
	$cloud_monitor_lock = "$OPENQRM_SERVER_BASE_DIR/openqrm/web/action/cloud-conf/cloud-monitor.lock";
	$cloud_monitor_timeout = "600";
	$cloud_volume_clone_timeout = "4800";

	// lock to prevent running multiple times in parallel
	if (file_exists($cloud_monitor_lock)) {
		// check from when it is, if it is too old we remove it and start
		$cloud_monitor_lock_date = file_get_contents($cloud_monitor_lock);
		$now=$_SERVER['REQUEST_TIME'];
		if (($now - $cloud_monitor_lock_date) > $cloud_monitor_timeout) {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "monitor-hook", "Timeout for the cloud-monitor-lock reached, creating new lock", "", "", 0, 0, 0);
			$cloud_lock_fp = fopen($cloud_monitor_lock, 'w');
			fwrite($cloud_lock_fp, $now);
			fclose($cloud_lock_fp);
		} else {
			return 0;
		}
	} else {
		$now=$_SERVER['REQUEST_TIME'];
		$cloud_lock_fp = fopen($cloud_monitor_lock, 'w');
		fwrite($cloud_lock_fp, $now);
		fclose($cloud_lock_fp);
	}
	// prepare performance parameter
	$cloud_performance_config = new cloudconfig();
	$max_parallel_phase_one_actions = $cloud_performance_config->get_value(27);  // 27 max-parallel-phase-one-actions
	$max_parallel_phase_two_actions = $cloud_performance_config->get_value(28);  // 28 max-parallel-phase-two-actions
	$max_parallel_phase_three_actions = $cloud_performance_config->get_value(29);  // 29 max-parallel-phase-three-actions
	$max_parallel_phase_four_actions = $cloud_performance_config->get_value(30);  // 30 max-parallel-phase-four-actions
	$max_parallel_phase_five_actions = $cloud_performance_config->get_value(31);  // 31 max-parallel-phase-five-actions
	$max_parallel_phase_six_actions = $cloud_performance_config->get_value(32);  // 32 max-parallel-phase-six-actions
	$max_parallel_phase_seven_actions = $cloud_performance_config->get_value(33);  // 33 max-parallel-phase-seven-actions
	$parallel_phase_one_actions = 0;
	$parallel_phase_two_actions = 0;
	$parallel_phase_three_actions = 0;
	$parallel_phase_four_actions = 0;
	$parallel_phase_five_actions = 0;
	$parallel_phase_six_actions = 0;
	$parallel_phase_seven_actions = 0;
	// appliance hostname
	$cloud_appliance_hostname_enabled = $cloud_performance_config->get_value(34);  // 34 appliance-hostname

	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Cloud Phase I - Image actions, VM-removal", "", "", 0, 0, 0);

	// #################### clone-on-deploy image resize / remove ################################
	// here we check if we have any clone-on-deploy images to resize or to remove
	// get cloudimage ids
	$cil = new cloudimage();
	$cloud_image_list = $cil->get_all_ids();

	foreach($cloud_image_list as $ci_list) {
		$phase_one_actions = 0;
		$ci_id = $ci_list['ci_id'];
		$ci = new cloudimage();
		$ci->get_instance_by_id($ci_id);
		$ci_state = $ci->state;
		$ci_image_id = $ci->image_id;
		$ci_appliance_id = $ci->appliance_id;
		$ci_resource_id = $ci->resource_id;
		$ci_cr_id = $ci->cr_id;
		$ci_resource = new resource();
		$ci_resource->get_instance_by_id($ci_resource_id);
		$ci_appliance = new appliance();
		$ci_appliance->get_instance_by_id($ci->appliance_id);

		// not the openQRM server resource, accept 0 only for private image remove
		if ($ci_cr_id != 0) {
			if ($ci_resource_id == 0) {
				continue;
			}
			// not when the cr is in starting phase
			$ci_request = new cloudrequest();
			$ci_request->get_instance_by_id($ci_cr_id);
			if ($ci_request->status == 8) {
				continue;
			}
		}

		// image still in use ?
		if ($ci_state == 1) {
			// its resource its active with the idle image ? sounds like pause
			if ((!strcmp($ci_resource->state, "active")) && ($ci_resource->imageid == 1)) {
				// ####################### remove auto createed vm #################
				// check for auto-create vms, if yes remove the resource if it is virtual
				$app_stop_autovm_remove_conf = new cloudconfig();
				$app_stop_auto_remove_vms = $app_stop_autovm_remove_conf->get_value(7);  // 7 is auto_create_vms
				if (!strcmp($app_stop_auto_remove_vms, "true")) {
					// we only remove virtual machines
					if ($ci_resource->vtype != 1) {
						// check if we still wait for the image_authentication stop hook
						unset($ci_image_authentication);
						$ci_image_authentication = new image_authentication();
						$ci_image_authentication->get_instance_by_image_id($ci_image_id);
						if (strlen($ci_image_authentication->id)) {
							// we still wait for the image_authentication hook to run
							continue;
						}
						// cloudvm->remove .....
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Auto-removing resource $ci_resource_id", "", "", 0, 0, 0);
						$auto_cloudvm = new cloudvm();
						$auto_cloudvm->remove($ci_resource_id, $ci_resource->vtype, $ci_appliance->name, $ci_resource->mac);
						// update cloudimage with resource -1
						$ar_ci_update = array(
							'ci_resource_id' => "-1",
						);
						$ci->update($ci->id, $ar_ci_update);
						$phase_one_actions = 1;
					}
				}

				// ####################### end remove auto createed vm #############
			}
			// the image is still in use
			continue;
		}

		// image not in use any more and resource active (idle) again ?
		if ($ci_resource_id > 0) {
			if (strcmp($ci_resource->state, "active")) {
				// not yet active again
				continue;
			}
			if ($ci_resource->imageid != 1) {
				// not yet idle
				continue;
			}
		}

		// get image definition
		$image = new image();
		$image->get_instance_by_id($ci_image_id);
		$image_name = $image->name;
		$image_type = $image->type;
		$image_rootdevice = $image->rootdevice;
		$image_storageid = $image->storageid;
		$image_deployment_parameter = $image->deployment_parameter;

		// get image storage
		$storage = new storage();
		$storage->get_instance_by_id($image_storageid);
		$storage_resource_id = $storage->resource_id;
		// get storage resource
		$resource = new resource();
		$resource->get_instance_by_id($storage_resource_id);
		$resource_id = $resource->id;
		$resource_ip = $resource->ip;


		// resize ?
		if ($ci_state == 2) {
			// calculate the resize
			$resize_value = $ci->disk_rsize;
			$storage_clone_timeout=60;
			$cloudstorage = new cloudstorage();
			$cloudstorage->resize($ci_id, $resize_value, $storage_clone_timeout);
			// re-set the cloudimage state to active
			$ci->set_state($ci->id, "active");
			$phase_one_actions = 1;
		}

		// private ?
		if ($ci_state == 3) {
			// calculate the private disk size
			$private_disk = $ci->disk_rsize;
			$private_image_name = $ci->clone_name;
			$storage_private_timeout=60;
			// private storage method returns new rootdevice
			$cloudstorage = new cloudstorage();
			$clone_image_fields["image_rootdevice"] = $cloudstorage->create_private($ci_id, $private_disk, $private_image_name, $storage_private_timeout);

			// here we logical create the image in openQRM, we have all data available
			// the private image relation will be created after this step in the private lc
			if (strlen($clone_image_fields["image_rootdevice"])) {
				$clone_image = new image();
				$clone_image_fields["image_id"]=(int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
				$clone_image_fields["image_name"] = $ci->clone_name;
				$clone_image_fields["image_version"] = "Private Cloud";
				$clone_image_fields["image_type"] = $image->type;
				$clone_image_fields["image_rootfstype"] = $image->rootfstype;
				$clone_image_fields["image_storageid"] = $image->storageid;
				$clone_image_fields["image_deployment_parameter"] = $image->deployment_parameter;
				// !! we create the private image as non-shared
				// this will prevent cloning when it is requested
				$clone_image_fields["image_isshared"] = 0;
				$clone_image_fields["image_comment"] = $image->comment;
				$clone_image_fields["image_capabilities"] = $image->capabilities;
				$clone_image->add($clone_image_fields);
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Created new private Cloud image $ci->clone_name", "", "", 0, 0, 0);
			}

			// re-set the cloudimage state to active
			$ci->set_state($ci->id, "active");
			$phase_one_actions = 1;

		}


		// remove ?
		if ($ci_state == 0) {
			$physical_remove = false;
			// only remove physically if the cr was set to shared
			$ci_cr = new cloudrequest();
			$ci_cr->get_instance_by_id($ci->cr_id);
			if ($ci_cr->shared_req == 1) {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Cloud request set to shared, removing Image ".$ci_image_id.".", "", "", 0, 0, 0);
				$physical_remove = true;
			}
			// or if the remove request came from a user for a private image
			if ($ci_cr_id == 0) {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Cloud user requested to remove Image ".$ci_image_id.".", "", "", 0, 0, 0);
				$physical_remove = true;
			} else {
				// re-check if this is a private image with clone-on-deploy set to false
				if ($ci_cr->image_id > 0) {
					$ci_private_image = new cloudprivateimage();
					if ($ci_private_image->exists_by_image_id($ci_cr->image_id)) {
						$ci_private_image->get_instance_by_image_id($ci_cr->image_id);
						if ($ci_private_image->clone_on_deploy == 0) {
							$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Image ".$ci_image_id." is private and set to NOT clone-on-deploy. Not removing.", "", "", 0, 0, 0);
							$physical_remove = false;
						}
					}
				}
			}

			if ($physical_remove) {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Removing Image $ci_image_id !", "", "", 0, 0, 0);
				$storage_remove_timeout=60;
				$cloudstorage = new cloudstorage();
				$cloudstorage->remove($ci_id, $storage_remove_timeout);
				// remove any image_authentication for the image
				// since we remove the image a image_authentication won't
				// find it anyway
				$image_authentication = new image_authentication();
				$ia_id_ar = $image_authentication->get_all_ids();
				foreach($ia_id_ar as $ia_list) {
					$ia_auth_id = $ia_list['ia_id'];
					$ia_auth = new image_authentication();
					$ia_auth->get_instance_by_id($ia_auth_id);
					if ($ia_auth->image_id == $ci_image_id) {
						// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Removing image_authentication $ia_auth_id for cloud image $ci_image_id since we are on going to remove the image itself", "", "", 0, 0, $resource_id);
						$ia_auth->remove($ia_auth_id);
					}
				}

				// remove the image in openQRM
				$image->remove($ci_image_id);
				$phase_one_actions = 1;
				// we do not remove non-shared images but just its cloudimage
			}

			// ####################### remove auto createed vm #################
			// check for auto-create vms, if yes remove the resource if it is virtual
			$cc_autovm_remove_conf = new cloudconfig();
			$cc_auto_remove_vms = $cc_autovm_remove_conf->get_value(7);  // 7 is auto_create_vms
			if (!strcmp($cc_auto_remove_vms, "true")) {
				// if it had a resource, it has none e.g. in case of cloudappliance pause
				if ($ci_resource_id >0) {
					// check virtualization type
					$auto_resource = new resource();
					$auto_resource->get_instance_by_id($ci_resource_id);
					$auto_vm_virtualization=$auto_resource->vtype;
					// we only remove virtual machines
					if ($auto_vm_virtualization != 1) {
						// gather name
						$auto_remove_appliance = new appliance();
						$auto_remove_appliance->get_instance_by_id($ci_appliance_id);
						// cloudvm->remove .....
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Auto-removing resource $ci_resource_id", "", "", 0, 0, 0);
						$auto_cloudvm = new cloudvm();
						$auto_cloudvm->remove($ci_resource_id, $auto_vm_virtualization, $auto_remove_appliance->name, $auto_resource->mac);
					}
				}
			}

			// ####################### end remove auto createed vm #############

			// remove the appliance
			if ($ci_appliance_id > 0) {
				$rapp = new appliance();
				$rapp->remove($ci_appliance_id);
			}
			// remove the image in the cloud
			$ci->remove($ci_id);
			$phase_one_actions = 1;

			// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Removing the cloned image $ci_image_id and the appliance $ci_appliance_id !", "", "", 0, 0, 0);
		}

		// check if we continue or go on
		if ($phase_one_actions == 1) {
			$parallel_phase_one_actions++;
			if ($max_parallel_phase_one_actions > 0 && $parallel_phase_one_actions >= $max_parallel_phase_one_actions) {
				break;
			}
		}
		// end remove
	}	// end cloudimage loop


	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Cloud Phase II - Main provisioning loop", "", "", 0, 0, 0);

	// #################### main cloud request loop ################################

	$crl = new cloudrequest();
	$cr_list = $crl->get_all_new_and_approved_ids();

	foreach($cr_list as $list) {
		$cr_id = $list['cr_id'];
		$cr = new cloudrequest();
		$cr->get_instance_by_id($cr_id);
		$cr_status = $cr->status;

// source image

		// Set Source Image ID
		$SOURCE_IMAGE_ID = $cr->image_id;

		$cu = new clouduser();
		$cr_cu_id = $cr->cu_id;
		$cu->get_instance_by_id($cr_cu_id);
		$cu_name = $cu->name;

		// #################### auto-provisioning ################################
		// here we only care about the requests status new and set them to approved (2)
		if ($cr_status == 1) {
			// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Found new request ID $cr_id. Checking if Auto-provisioning is enabled", "", "", 0, 0, 0);
			$cc_conf = new cloudconfig();
			$cc_auto_provision = $cc_conf->get_value(2);  // 2 is auto_provision
			if (!strcmp($cc_auto_provision, "true")) {
				// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Found new request ID $cr_id. Auto-provisioning is enabled! Approving the request", "", "", 0, 0, 0);
				$cr->setstatus($cr_id, "approve");
				$cr_status=2;
			}
		}
		// care about the next approved cr in the list
		if ($cr_status != 2) {
			continue;
		}
		// check for start time
		$now=$_SERVER['REQUEST_TIME'];
		$cr_start = $cr->start;
		if ($cr_start > $now) {
			continue;
		}

		// #################### provisioning ################################
		// provision, only care about approved requests
		$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Provisioning request ID $cr_id", "", "", 0, 0, 0);

		// ################################## quantity loop provisioning ###############################
		// from 4.9 on we do not support more than one appliance per request.
		// $resource_quantity = $cr->resource_quantity;
		$resource_quantity = 1;

		// check for max_apps_per_user
		$cloud_user_apps_arr = array();
		$cloud_user_app = new cloudappliance();
		$cloud_user_apps_arr = $cloud_user_app->get_all_ids();
		$users_appliance_count=0;
		foreach ($cloud_user_apps_arr as $capp) {
			$tmp_cloud_app = new cloudappliance();
			$tmp_cloud_app_id = $capp['ca_id'];
			$tmp_cloud_app->get_instance_by_id($tmp_cloud_app_id);
			// active ?
			if ($tmp_cloud_app->state == 0) {
				continue;
			}
			// check if the cr is ours
			$rc_tmp_cr = new cloudrequest();
			$rc_tmp_cr->get_instance_by_id($tmp_cloud_app->cr_id);
			if ($rc_tmp_cr->cu_id != $cr_cu_id) {
				continue;
			}
			$users_appliance_count++;
		}
		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "User $cr_cu_id has already $users_appliance_count appliance(s) running.", "", "", 0, 0, 0);

		$cc_max_app = new cloudconfig();
		$max_apps_per_user = $cc_max_app->get_value(13);  // 13 is max_apps_per_user
		if (($users_appliance_count + $resource_quantity) > $max_apps_per_user) {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Not provisining CR $cr_id from user $cr_cu_id who has already $users_appliance_count appliance(s) running.", "", "", 0, 0, 0);
			$cr->setstatus($cr_id, 'deny');
			continue;
		}

		$cr_resource_number = 1;
		// ################################## create appliance ###############################
		// set hostname
		$appliance_name = "cloud-".$cr_id."-".$cr_resource_number."-x";
		if (!strcmp($cloud_appliance_hostname_enabled, "true")) {
			if (strlen($cr->appliance_hostname)) {
				$appliance_requested_hostname = $cr->appliance_hostname;
				// check if the hostname is free, if not return to regular naming convention
				$appliance_chk_hostname = new appliance();
				$appliance_chk_hostname->get_instance_by_name($appliance_requested_hostname);
				if ($appliance_chk_hostname->id > 0) {
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Requested hostname ".$appliance_requested_hostname." already in use. Reverting to ".$appliance_name." (CR ID ".$cr_id.").", "", "", 0, 0, 0);
				} else {
					// set requested hostname
					$appliance_name = $appliance_requested_hostname;
				}
			}
		}
		// update cr with new hostname
		$cr_update_hostname_fields=array();
		$cr_update_hostname_fields["cr_appliance_hostname"]=$appliance_name;
		$cr->update($cr->id, $cr_update_hostname_fields);
		$cr->get_instance_by_id($cr->id);
		// get new appliance id
		$appliance_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
		// we
		$user_network_cards = $cr->network_req+1;
		// prepare array to add appliance
		$ar_request = array(
			'appliance_id' => $appliance_id,
			'appliance_resources' => "-1",
			'appliance_name' => $appliance_name,
			'appliance_kernelid' => $cr->kernel_id,
			'appliance_imageid' => $cr->image_id,
			'appliance_virtualization' => $cr->resource_type_req,
			'appliance_cpunumber' => $cr->cpu_req,
			'appliance_memtotal' => $cr->ram_req,
			'appliance_nics' => $user_network_cards,
			'appliance_comment' => "Requested by user $cu_name",
			'appliance_ssi' => $cr->shared_req,
			'appliance_highavailable' => $cr->ha_req,
			'appliance_capabilities' => $cr->appliance_capabilities,
		);

		// create + start the appliance :)
		$appliance = new appliance();
		$appliance->add($ar_request);
		// first get admin email
		$cc_acr_conf = new cloudconfig();
		$cc_acr_admin_email = $cc_acr_conf->get_value(1);  // 1 is admin_email
		// and the user details
		$cu_name = $cu->name;
		$cu_forename = $cu->forename;
		$cu_lastname = $cu->lastname;
		$cu_email = $cu->email;
		// now lets find a resource for this new appliance
		$appliance->get_instance_by_id($appliance_id);
		$appliance_virtualization=$cr->resource_type_req;

		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "#### Cloud Phase II-1 - Getting a resource", "", "", 0, 0, 0);

		// ################################## phys. res. ###############################

		if ($appliance_virtualization == 1) {

			$cloud_app_physical_resource = new cloudappliance();
			if (!$cloud_app_physical_resource->find_existing_resource($appliance, $appliance_virtualization, $cu->id)) {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Could not find a resource (type physical system) for request ID $cr_id!", "", "", 0, 0, 0);
				$appliance->remove($appliance_id);
				$cr->setstatus($cr_id, 'no-res');

				// send mail to user
				$rmail = new cloudmailer();
				$rmail->to = "$cu_email";
				$rmail->from = "$cc_acr_admin_email";
				$rmail->subject = "openQRM Cloud: Not enough resources to provision a  system for request ".$cr_id;
				$rmail->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
				$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu_forename", '@@LASTNAME@@'=>"$cu_lastname", '@@RESNUMBER@@'=>"$cr_resource_number", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $cc_acr_admin_email);
				$rmail->var_array = $arr;
				$rmail->send();
				// send mail to admin
				$rmail_admin = new cloudmailer();
				$rmail_admin->to = "$cc_acr_admin_email";
				$rmail_admin->from = "$cc_acr_admin_email";
				$rmail_admin->subject = "openQRM Cloud: Not enough resources to provision a  system for request ".$cr_id;
				$rmail_admin->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
				$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@RESNUMBER@@'=>"$cr_resource_number", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $cc_acr_admin_email);
				$rmail_admin->var_array = $arr;
				$rmail_admin->send();
				continue;
			}
			// we have a phys. resource, update object
			$appliance->get_instance_by_id($appliance_id);


		} else {

			// ################################## auto create vm ###############################
			// check if we should try to create one

			// request type vm
			$cc_autovm_conf = new cloudconfig();
			$cc_auto_create_vms = $cc_autovm_conf->get_value(7);  // 7 is auto_create_vms
			if (!strcmp($cc_auto_create_vms, "true")) {
				// check if createvmlc exists for this cr + res-quantity
				unset($cvmlc);
				$cvmlc = new cloudcreatevmlc();
				$cvmlc->get_instance_by_cr_details($cr_id, $cr_resource_number);
				if (!strlen($cvmlc->request_time)) {
					// if no createvmlc exists so far create it and the vm
					// generate a mac address
					$mac_res = new resource();
					// check if we need to generate the first nics mac address in the vmware address space
					$new_vm_mac="";
					$vm_virt = new virtualization();
					$vm_virt->get_instance_by_id($cr->resource_type_req);
					$virt_name = $vm_virt->get_plugin_name();

					switch ($virt_name) {
						case 'vmware-esx':
						case 'vmware-server':
						case 'vmware-server2':
							$mac_res->generate_mac();
							$suggested_mac = $mac_res->mac;
							$new_forth_byte_first_bit = rand(1, 3);
							$new_vm_mac = strtolower($vmware_mac_address_space.":".substr($suggested_mac, 9));
							$new_vm_mac = substr_replace($new_vm_mac , $new_forth_byte_first_bit, 9, 1);
							break;
						default:
							$mac_res->generate_mac();
							$new_vm_mac = $mac_res->mac;
							break;
					}
					// additional_nics
					$new_additional_nics = $cr->network_req;
					// cpu
					$new_vm_cpu = $cr->cpu_req;
					// memory
					$new_vm_memory = 256;
					if ($cr->ram_req != 0) {
						$new_vm_memory = $cr->ram_req;
					}
					// disk size
					$new_vm_disk = 5000;
					if ($cr->disk_req != 0) {
						$new_vm_disk = $cr->disk_req;
					}

					// here we generate the password for the Image and optional vnc of the VM resource
					$image = new image();
					$vm_vnc_password = trim($image->generatePassword(12));
					// put the password in the cr for the image later
					$cr_update_fields = array();
					$cr_update_fields['cr_image_password'] = $vm_vnc_password;
					$cr->update($cr_id, $cr_update_fields);
// Create VM
					// here we start the new vm !
					$cloudvm = new cloudvm();
					// this method returns the resource-id
					$cloudvm->create($cr_cu_id, $appliance_virtualization, $appliance_name, $new_vm_mac, $new_additional_nics, $new_vm_cpu, $new_vm_memory, $new_vm_disk, $vm_create_timout, $vm_vnc_password, $SOURCE_IMAGE_ID);
					$new_vm_resource_id = $cloudvm->resource_id;

					// check if creating the new VM resource failed
					if ($new_vm_resource_id == 0) {

						$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Failed creating a new resource for request ID $cr_id!", "", "", 0, 0, 0);
						$cr->setstatus($cr_id, 'no-res');

						// send mail to user
						$rmail = new cloudmailer();
						$rmail->to = "$cu_email";
						$rmail->from = "$cc_acr_admin_email";
						$rmail->subject = "openQRM Cloud: Not enough resources to provision a system for request ".$cr_id;
						$rmail->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
						$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu_forename", '@@LASTNAME@@'=>"$cu_lastname", '@@RESNUMBER@@'=>"$cr_resource_number", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $cc_acr_admin_email);
						$rmail->var_array = $arr;
						$rmail->send();
						// send mail to admin
						$rmail_admin = new cloudmailer();
						$rmail_admin->to = "$cc_acr_admin_email";
						$rmail_admin->from = "$cc_acr_admin_email";
						$rmail_admin->subject = "openQRM Cloud: Error creating new resource for request ".$cr_id;
						$rmail_admin->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
						$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@RESNUMBER@@'=>"$cr_resource_number", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $cc_acr_admin_email);
						$rmail_admin->var_array = $arr;
						$rmail_admin->send();

						// refresh object
						$appliance->get_instance_by_id($appliance_id);
						// remove app and cvmlc
						$appliance->remove($appliance_id);
						continue;
					}

					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Created VM with resource_id $new_vm_resource_id", "", "", 0, 0, 0);

					// create cvmlc after we got a resource_id
					$vm_create_time=$_SERVER['REQUEST_TIME'];
					$cvmlc_resource_fields["vc_resource_id"]=$new_vm_resource_id;
					$cvmlc_resource_fields["vc_cr_id"]=$cr_id;
					$cvmlc_resource_fields["vc_cr_resource_number"]=$cr_resource_number;
					$cvmlc_resource_fields["vc_request_time"]=$vm_create_time;
					$cvmlc_resource_fields["vc_vm_create_timeout"]=$vm_create_timout;
					$cvmlc_resource_fields["vc_state"]=0;
					// get the new resource id from the db
					$new_vc_id=(int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$cvmlc_resource_fields["vc_id"]=$new_vc_id;
					$cvmlc->add($cvmlc_resource_fields);
					// here we go on to the next cr or resource_number, remove app before
					$appliance->remove($appliance_id);
					continue;

				} else {
					// we have a cvmlc, check its resource and set its state
					$cvm_resource = new resource();
					$cvm_resource->get_instance_by_id($cvmlc->resource_id);
					// idle ?
					if (($cvm_resource->imageid == 1) && ($cvm_resource->state == 'active') && (strcmp($cvm_resource->ip, "0.0.0.0"))) {
						// we have a new idle vm as resource :) update it in the appliance
						$new_vm_resource_id = $cvmlc->resource_id;
						$appliance_fields = array();
						$appliance_fields['appliance_resources'] = $new_vm_resource_id;
						// update and refresh the appliance object
						$appliance->update($appliance->id, $appliance_fields);
						$appliance->get_instance_by_id($appliance_id);
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Created resource $new_vm_resource_id /cr $cr_id now idle, continue provisioning.", "", "", 0, 0, 0);
						// remove cvmlc
						$cvmlc->remove($cvmlc->id);

					} else {

						// check timeout
						$vm_check_time=$_SERVER['REQUEST_TIME'];
						$vm_c_timeout = $cvmlc->request_time + $cvmlc->vm_create_timeout;
						if ($vm_check_time > $vm_c_timeout) {

							$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Could not create a new resource for request ID $cr_id!", "", "", 0, 0, 0);
							$cr->setstatus($cr_id, 'no-res');

							// send mail to user
							$rmail = new cloudmailer();
							$rmail->to = "$cu_email";
							$rmail->from = "$cc_acr_admin_email";
							$rmail->subject = "openQRM Cloud: Not enough resources to provision a system for request ".$cr_id;
							$rmail->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
							$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu_forename", '@@LASTNAME@@'=>"$cu_lastname", '@@RESNUMBER@@'=>"$cr_resource_number", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $cc_acr_admin_email);
							$rmail->var_array = $arr;
							$rmail->send();
							// send mail to admin
							$rmail_admin = new cloudmailer();
							$rmail_admin->to = "$cc_acr_admin_email";
							$rmail_admin->from = "$cc_acr_admin_email";
							$rmail_admin->subject = "openQRM Cloud: Not enough resources to provision a system from request ".$cr_id;
							$rmail_admin->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
							$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@RESNUMBER@@'=>"$cr_resource_number", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $cc_acr_admin_email);
							$rmail_admin->var_array = $arr;
							$rmail_admin->send();

							// refresh object
							$appliance->get_instance_by_id($appliance_id);
							// try removing the cloudvm
							$clean_cloudvm = new cloudvm();
							$clean_cloudvm->remove($cvm_resource->id, $cvm_resource->vtype, $appliance->name, $cvm_resource->mac);
							// remove app and cvmlc
							$appliance->remove($appliance_id);
							$cvmlc->remove($cvmlc->id);
							continue;
						}
						// still waiting within  the timeout
						// update state to 1 (starting)
						// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Still waiting for cr $cr_id / res. ".$cvmlc->resource_id." to get idle", "", "", 0, 0, 0);
						$cvm_state_fields['vc_state'] = 1;
						$cvmlc->update($cvmlc->id, $cvm_state_fields);
						// remove app
						$appliance->remove($appliance_id);
						// continue with the next cr/res-nr
						continue;
					}
				}

			// ################################## no auto create vm ###############################

			} else {
				// not set to auto-create vms
				// try to find a fitting idle vm
				$cloud_app_vm_resource = new cloudappliance();
				if (!$cloud_app_vm_resource->find_existing_resource($appliance, $appliance_virtualization, $cu->id)) {
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Not creating a new resource for request ID ".$cr_id." since auto-create-vms is disabled.", "", "", 0, 0, 0);
					$appliance->remove($appliance_id);
					$cr->setstatus($cr_id, 'no-res');

					// send mail to user
					$rmail = new cloudmailer();
					$rmail->to = "$cu_email";
					$rmail->from = "$cc_acr_admin_email";
					$rmail->subject = "openQRM Cloud: Not enough resources to provision a system for request ".$cr_id;
					$rmail->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
					$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu_forename", '@@LASTNAME@@'=>"$cu_lastname", '@@RESNUMBER@@'=>"$cr_resource_number", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $cc_acr_admin_email);
					$rmail->var_array = $arr;
					$rmail->send();
					// send mail to admin
					$rmail_admin = new cloudmailer();
					$rmail_admin->to = "$cc_acr_admin_email";
					$rmail_admin->from = "$cc_acr_admin_email";
					$rmail_admin->subject = "openQRM Cloud: Not enough resources to provision a system for request ".$cr_id;
					$rmail_admin->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
					$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@RESNUMBER@@'=>"$cr_resource_number", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $cc_acr_admin_email);
					$rmail_admin->var_array = $arr;
					$rmail_admin->send();
					continue;
				}
				$appliance->get_instance_by_id($appliance_id);
				$appliance->remove($appliance_id);
			}
		}

		// ################################## end auto create vm ###############################

		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Found resource ".$appliance->resources." (type $appliance_virtualization) for request ID $cr_id", "", "", 0, 0, 0);
		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "#### Cloud Phase II-2 - Got resource, Clone Image", "", "", 0, 0, 0);

		// ################################## clone on deploy ###############################

		// here we have a resource but
		// do we have to clone the image before deployment ?
		// get image definition
		$image = new image();
		$image->get_instance_by_id($cr->image_id);
		$image_name = $image->name;
		$image_type = $image->type;
		$image_version = $image->version;
		$image_rootdevice = $image->rootdevice;
		$image_rootfstype = $image->rootfstype;
		$image_storageid = $image->storageid;
		$image_isshared = $image->isshared;
		$image_comment = $image->comment;
		$image_capabilities = $image->capabilities;
		$image_deployment_parameter = $image->deployment_parameter;

		// check if this is a private cloud image belonging to the user
		// if yes, check if clone_on_deploy is enabled
		$provision_private_image = new cloudprivateimage();
		$provision_private_image->get_instance_by_image_id($image->id);
		if ($provision_private_image->cu_id == $cr_cu_id) {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Image ".$image_name." belongs to cloud user ".$cr_cu_id.".", "", "", 0, 0, 0);
			if ($provision_private_image->clone_on_deploy == 0) {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Image ".$image_name." is private and set to NOT clone-on-deploy.", "", "", 0, 0, 0);
				$cr->shared_req = 0;
				// update in the db
				$cr_update_fields=array();
				$cr_update_fields["cr_shared_req"]="0";
				$cr->update($cr->id, $cr_update_fields);
			} else {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Image ".$image_name." is private and set to clone-on-deploy.", "", "", 0, 0, 0);
			}
		}

		// we clone ?
		if ($cr->shared_req == 1) {
			// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Request ID $cr_id has clone-on-deploy activated. Cloning the image", "", "", 0, 0, 0);
			// assign new name
			$image_clone_name = $cr->image_id.".cloud_".$cr_id."_".$cr_resource_number."_";
			// get new image id
			$image_id  = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));

			// add the new image to the openQRM db
			$ar_request = array(
				'image_id' => $image_id,
				'image_name' => $image_clone_name,
				'image_version' => $image_version,
				'image_type' => $image_type,
				'image_rootdevice' => $image_rootdevice,
				'image_rootfstype' => $image_rootfstype,
				'image_storageid' => $image_storageid,
				'image_isshared' => $image_isshared,
				'image_comment' => "Requested by user $cu_name",
				'image_capabilities' => $image_capabilities,
				'image_deployment_parameter' => $image_deployment_parameter,
			);
			$image->add($ar_request);
			$image->get_instance_by_id($image_id);

			// set the new image in the appliance !
			// prepare array to update appliance
			$ar_appliance_update = array(
				'appliance_imageid' => $image_id,
			);
			$appliance->update($appliance_id, $ar_appliance_update);
			// refresh the appliance object
			$appliance->get_instance_by_id($appliance_id);

			// here we put the image + resource definition into an cloudimage
			// this cares e.g. later to remove the image after the resource gets idle again
			// -> the check for the resource-idle state happens at the beginning
			//    of every cloud-monitor loop
			$ci_disk_size=5000;
			if (strlen($cr->disk_req)) {
				$ci_disk_size=$cr->disk_req;
			}
			// get a new ci_id
			$cloud_image_id  = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$cloud_image_arr = array(
					'ci_id' => $cloud_image_id,
					'ci_cr_id' => $cr->id,
					'ci_image_id' => $appliance->imageid,
					'ci_appliance_id' => $appliance->id,
					'ci_resource_id' => $appliance->resources,
					'ci_disk_size' => $ci_disk_size,
					'ci_state' => 1,
			);
			$cloud_image = new cloudimage();
			$cloud_image->add($cloud_image_arr);

			// get image storage
			$storage = new storage();
			$storage->get_instance_by_id($image_storageid);
			$storage_resource_id = $storage->resource_id;
			// get storage resource
			$resource = new resource();
			$resource->get_instance_by_id($storage_resource_id);
			$resource_id = $resource->id;
			$resource_ip = $resource->ip;

			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Sending clone command to $resource_ip to create Image $image_clone_name", "", "", 0, 0, 0);
			$storage_clone_timeout=60;
			$cloudstorage = new cloudstorage();
			$cloudstorage->create_clone($cloud_image_id, $image_clone_name, $ci_disk_size, $storage_clone_timeout);
			// be sure to have the create command run before appliance start / storage auth hook
			sleep(5);

		} else {

			// non shared !
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Not cloning Image ".$image_name.".", "", "", 0, 0, 0);
			// we put it into an cloudimage too but it won't get removed
			$ci_disk_size=5000;
			if (strlen($cr->disk_req)) {
				$ci_disk_size=$cr->disk_req;
			}
			// get a new ci_id
			$cloud_image_id  = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$cloud_image_arr = array(
					'ci_id' => $cloud_image_id,
					'ci_cr_id' => $cr->id,
					'ci_image_id' => $appliance->imageid,
					'ci_appliance_id' => $appliance->id,
					'ci_resource_id' => $appliance->resources,
					'ci_disk_size' => $ci_disk_size,
					'ci_state' => 1,
			);
			$cloud_image = new cloudimage();
			$cloud_image->add($cloud_image_arr);
		}



		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "#### Cloud Phase II-3 - Appliance start", "", "", 0, 0, 0);

		// ################################## start appliance ###############################

		// assign the resource
		$kernel = new kernel();
		$kernel->get_instance_by_id($appliance->kernelid);
		$resource = new resource();
		$resource->get_instance_by_id($appliance->resources);
		// in case we do not have an external ip-config send the resource ip to the user
		$resource_external_ip=$resource->ip;
		// ################################## ip-mgmt assing  ###############################
		// check ip-mgmt
		$cc_conf = new cloudconfig();
		$show_ip_mgmt = $cc_conf->get_value(26);	// ip-mgmt enabled ?
		if (!strcmp($show_ip_mgmt, "true")) {
			if (file_exists("$RootDir/plugins/ip-mgmt/.running")) {
				require_once "$RootDir/plugins/ip-mgmt/class/ip-mgmt.class.php";
				$ip_mgmt_array = explode(",", $cr->ip_mgmt);
				$ip_mgmt_assign_loop = 1;
				foreach($ip_mgmt_array as $ip_mgmt_config_str) {
					$collon_pos = strpos($ip_mgmt_config_str, ":");
					$nic_id = substr($ip_mgmt_config_str, 0, $collon_pos);
					$ip_mgmt_id = substr($ip_mgmt_config_str, $collon_pos+1);
					if (!strlen($ip_mgmt_id)) {
						continue;
					}
					$orginal_ip_mgmt_id = $ip_mgmt_id;
					$ip_mgmt_assign = new ip_mgmt();
					$ip_mgmt_id_final = $ip_mgmt_id;
					// we need to check if the ip is still free
					$ip_mgmt_object_arr = $ip_mgmt_assign->get_instance('id', $ip_mgmt_id);
					$ip_app_id = $ip_mgmt_object_arr['ip_mgmt_appliance_id'];
					if ($ip_app_id > 0) {
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "WARNING: ip-mgmt id ".$ip_mgmt_id." is already in use. Trying to find the next free ip..", "", "", 0, 0, 0);
						$ip_mgmt_id = -2;
					} else {
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "SUCCESS: ip-mgmt id ".$ip_mgmt_id." is free.", "", "", 0, 0, 0);
					}

					// if ip_mgmt_id == auto (-2) search the next free ip for the user
					if ($ip_mgmt_id == -2) {
						$ip_mgmt_list_per_user = $ip_mgmt_assign->get_list_by_user($cu->cg_id);
						$next_free_ip_mgmt_id = 0;
						foreach($ip_mgmt_list_per_user as $list) {
							$possible_next_ip_mgmt_id = $list['ip_mgmt_id'];
							$possible_next_ip_mgmt_object_arr = $ip_mgmt_assign->get_instance('id', $possible_next_ip_mgmt_id);
							if ($possible_next_ip_mgmt_object_arr['ip_mgmt_appliance_id'] == NULL) {
								// we have found the next free ip-mgmt id
								$next_free_ip_mgmt_id = $possible_next_ip_mgmt_id;
								$ip_mgmt_id_final = $possible_next_ip_mgmt_id;
								break;
							}
						}
						if ($next_free_ip_mgmt_id == 0) {
							$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "WARNING: Could not find the next free ip-mgmt id for appliance ".$appliance_id.".", "", "", 0, 0, 0);
							continue;
						} else {
							$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "SUCCESS: Found the next free ip-mgmt id ".$next_free_ip_mgmt_id." for appliance ".$appliance_id.".", "", "", 0, 0, 0);
							$ip_mgmt_id = $next_free_ip_mgmt_id;
							// here we have to update the cr with the new ip-mgmt-id
							$new_cr_ip_mgmt_str = str_replace($nic_id.":".$orginal_ip_mgmt_id, $nic_id.":".$ip_mgmt_id, $cr->ip_mgmt);
							$new_cr_ip_mgmt_fields=array();
							$new_cr_ip_mgmt_fields["cr_ip_mgmt"]=$new_cr_ip_mgmt_str;
							$cr->update($cr->id, $new_cr_ip_mgmt_fields);
							$cr->get_instance_by_id($cr->id);
						}
					}

					// here we have a valid ip-mgmt opbject to update
					$ip_mgmt_fields=array();
					$ip_mgmt_fields["ip_mgmt_appliance_id"]=$appliance_id;
					$ip_mgmt_fields["ip_mgmt_nic_id"]=$nic_id;
					$ip_mgmt_assign->update_ip($ip_mgmt_id, $ip_mgmt_fields);
					// set resource_external_ip
					if ($ip_mgmt_assign_loop == 1) {
						$ip_mgmt_assign_arr = $ip_mgmt_assign->get_config_by_id($ip_mgmt_id_final);
						$resource_external_ip = $ip_mgmt_assign_arr[0]['ip_mgmt_address'];
					}
					$ip_mgmt_assign_loop++;
				}
			}
		}

		// #####################################################################################

		// assign kernel
		$openqrm_server->send_command("openqrm_assign_kernel $resource->id $resource->mac $kernel->name");
		//refresh the object before in case of clone-on-deploy
		$appliance->get_instance_by_id($appliance_id);
		// hybrid-cloud deployment needs image password + application classes set before appliance start
		// get the password to send to the user from the request
		$cr->get_instance_by_id($cr_id);
		$appliance_password = $cr->image_password;
		$image = new image();
		$image->set_root_password($appliance->imageid, $appliance_password);
		$image->get_instance_by_id($appliance->imageid);
		$appliance_login_user = "root";
		if ($image->version == 'Windows') {
			$appliance_login_user = "Administrator";
		}
		// add the user + image_password to the request
		$cr_update_fields = array();
		$cr_update_fields['cr_image_password'] = $appliance_login_user.":".$appliance_password.":".$resource_external_ip;
		$cr->update($cr_id, $cr_update_fields);

		// ################################## apply application groups ###############################

		// check if application is enabled
		$application_conf = new cloudconfig();
		$show_application_groups = $application_conf->get_value(11);	// show_puppet_groups
		if (!strcmp($show_application_groups, "true")) {
			// check if we have a application config in the request
			$application_appliance = $appliance->name;
			if (strlen($cr->puppet_groups)) {
				$application_groups_str = $cr->puppet_groups;
				$application_appliance = $appliance->name;
				$application_debug = "Applying $application_groups_str to appliance $application_appliance";
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", $application_debug, "", "", 0, 0, 0);
				$application_group_array = explode(",", $cr->puppet_groups);
				$cloudapplication = new cloudapplication();
				$cloudapplication->set_applications($appliance->name, $application_group_array);
			}
		}

		// update appliance id in request
		$cr->get_instance_by_id($cr->id);
		$cr->setappliance("add", $appliance_id);
		// update request status to starting
		$cr->setstatus($cr_id, "starting");
		// wait until the resource got the new kernel and application assigned
		sleep(2);

		// check if we continue or go on
		$parallel_phase_two_actions++;
		if ($max_parallel_phase_two_actions > 0 && $parallel_phase_two_actions >= $max_parallel_phase_two_actions) {
			break;
		}

	}



	// #################### appliance starting phase ################################

	$parallel_phase_two_actions = 0;
	$crl = new cloudrequest();
	$cr_list = $crl->get_all_starting_ids();

	foreach($cr_list as $list) {
		$cr_id = $list['cr_id'];
		$cr = new cloudrequest();
		$cr->get_instance_by_id($cr_id);
		$cr_status = $cr->status;

		$cu = new clouduser();
		$cr_cu_id = $cr->cu_id;
		$cu->get_instance_by_id($cr_cu_id);
		$cu_name = $cu->name;

		// update appliance id in request
		unset($appliance_id);
		$appliance_id = $cr->appliance_id;
		if (!strlen($appliance_id)) {
			continue;
		}
		$appliance = new appliance();
		$appliance->get_instance_by_id($appliance_id);
		$image = new image();
		$image->get_instance_by_id($appliance->imageid);

		// run authblocker from plugin hook if existing
		$deployment = new deployment();
		$deployment->get_instance_by_type($image->type);
		$deployment_type = $deployment->type;
		$deployment_plugin_name = $deployment->storagetype;
		$storage_auth_hook = $RootDir."/plugins/".$deployment_plugin_name."/openqrm-".$deployment_type."-auth-hook.php";
		if (file_exists($storage_auth_hook)) {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Found deployment type ".$deployment_type." handling the start auth hook.", "", "", 0, 0, $appliance->resources);
			if ($appliance->ssi == 2) {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Already created authblocker for image ".$image->name." / app id ".$appliance->id.".", "", "", 0, 0, $appliance->resources);
			} else {
				// create storage_auth_blocker if not existing already
				unset($authblocker);
				$authblocker = new authblocker();
				$authblocker->get_instance_by_image_name($image->name);
				if (!strlen($authblocker->id)) {
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Creating new authblocker for image ".$image->name." / app id ".$appliance->id.".", "", "", 0, 0, $appliance->resources);
					$ab_start_time = $_SERVER['REQUEST_TIME'];
					$ab_create_fields['ab_image_id'] = $image->id;
					$ab_create_fields['ab_image_name'] = $image->name;
					$ab_create_fields['ab_start_time'] = $ab_start_time;
					$ab_create_fields['ab_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$authblocker->add($ab_create_fields);
					// update appliance->ssi saving that the authblocker was created
					$ab_appliance_update = array(
						'appliance_ssi' => 2,
					);
					$appliance->update($appliance_id, $ab_appliance_update);
					$appliance->get_instance_by_id($appliance_id);
					// run the auth hook
					require_once "$storage_auth_hook";
					storage_auth_function("start", $appliance->id);
					continue;
				} else {
					$ab_appliance_update = array(
						'appliance_ssi' => 2,
					);
					$appliance->update($appliance_id, $ab_appliance_update);
					$appliance->get_instance_by_id($appliance_id);
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Found existing authblocker for image ".$image->name." / app id ".$appliance->id.".", "", "", 0, 0, $appliance->resources);
				}
			}
			$storage_auth_blocker_created = true;
		} else {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "No storage-auth hook (".$storage_auth_hook.") available for deployment type ".$deployment_type." for start auth hook.", "", "", 0, 0, $appliance->resources);
			$storage_auth_blocker_created = false;
		}


		// if authblocker was created
		if ($storage_auth_blocker_created) {
			if ($appliance->ssi == 2) {
				unset($check_authblocker);
				$check_authblocker = new authblocker();
				$check_authblocker->get_instance_by_image_name($image->name);
				if (strlen($check_authblocker->id)) {
					// ab still existing, check timeout
					$ab_check_now = $_SERVER['REQUEST_TIME'];
					$ab_check_timeout = $check_authblocker->start_time + $cloud_volume_clone_timeout;
					if ($ab_check_now > $ab_check_timeout) {
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Failed starting server ".$appliance->id.". Storage-authentication timed out!", "", "", 0, 0, $appliance->resources);
						// remove authblocker
						$check_authblocker->remove($check_authblocker->id);
						// set cr to dreprovision to automatically clean up the created objects
						$cr->setstatus($cr_id, 'deprovision');
						// send mail to user and admin
						$ab_check_conf = new cloudconfig();
						$ab_check_admin_email = $ab_check_conf->get_value(1);  // 1 is admin_email
						$rmail = new cloudmailer();
						$rmail->to = "$cu->email";
						$rmail->from = "$ab_check_admin_email";
						$rmail->subject = "openQRM Cloud: Not enough resources to provision storage for request ".$cr_id.". Deprovisioning request ".$cr_id."!";
						$rmail->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
						$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu->forename", '@@LASTNAME@@'=>"$cu->lastname", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $ab_check_admin_email);
						$rmail->var_array = $arr;
						$rmail->send();
						$rmail_admin = new cloudmailer();
						$rmail_admin->to = "$ab_check_admin_email";
						$rmail_admin->from = "$ab_check_admin_email";
						$rmail_admin->subject = "openQRM Cloud: Not enough resources to provision storage for request ".$cr_id.". Deprovisioning request ".$cr_id."!";
						$rmail_admin->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
						$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $ab_check_admin_email);
						$rmail_admin->var_array = $arr;
						$rmail_admin->send();
						continue;
					} else {
						// we are still waiting for the auth-blocker to be removed
						$still_wait = $ab_check_timeout - $ab_check_now;
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Still waiting ".$still_wait." sec. for storage-auth for server ".$appliance->id.".", "", "", 0, 0, $appliance->resources);
						continue;
					}
				} else {
					// here we got the remove-auth-blocker message from the storage-auth hook
					// now we can be sure that storage auth ran before rebooting the resource
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Storage authentication for image ".$image->name." succeeded, assigning the resource now.", "", "", 0, 0, $appliance->resources);
				}
			}
		}


		// update request status to active
		$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Starting server ".$appliance_id." for request ID ".$cr_id, "", "", 0, 0, 0);
		$cr->setstatus($cr_id, "active");
		$ab_appliance_update = array(
			'appliance_ssi' => $cr->shared_req,
		);
		$appliance->update($appliance_id, $ab_appliance_update);
		$appliance->get_instance_by_id($appliance_id);
		// run appliance start with no auth hook
		$appliance->start(false);

		// here we insert the new appliance into the cloud-appliance table
		$cloud_appliance_id  = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
		$cloud_appliance_arr = array(
				'ca_id' => $cloud_appliance_id,
				'ca_cr_id' => $cr->id,
				'ca_appliance_id' => $appliance_id,
				'ca_cmd' => 0,
				'ca_state' => 1,
		);
		$cloud_appliance = new cloudappliance();
		$cloud_appliance->add($cloud_appliance_arr);



		// ################################## mail user provisioning ###############################

		// get the user + image_password from the request
		$appliance_login_credentials = explode(':', $cr->image_password);
		$login_user = $appliance_login_credentials[0];
		$login_password = $appliance_login_credentials[1];
		$login_ip = $appliance_login_credentials[2];
		// remove user + pass from request
		$cr_update_fields = array();
		$cr_update_fields['cr_image_password'] = '';
		$cr->update($cr_id, $cr_update_fields);


		// send mail to user
		// get admin email
		$cc_conf = new cloudconfig();
		$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email
		$cc_allow_vnc = $cc_conf->get_value(42);  // 1 is admin_email
		// get user + request + appliance details
		$cu_id = $cr->cu_id;
		$cu = new clouduser();
		$cu->get_instance_by_id($cu_id);
		$cu_name = $cu->name;
		$cu_forename = $cu->forename;
		$cu_lastname = $cu->lastname;
		$cu_email = $cu->email;
		// start/stop time
		$cr_start = $cr->start;
		$start = date("d-m-Y H-i", $cr_start);
		$cr_stop = $cr->stop;
		$stop = date("d-m-Y H-i", $cr_stop);

		// check for allow_vnc
		$vnc_informations = "";
		if (!strcmp($cc_allow_vnc, "true")) {
			$vnc_resource = new resource();
			$vnc_resource->get_instance_by_id($appliance->resources);
			$vnc_informations_file = $OPENQRM_SERVER_BASE_DIR."/openqrm/web/action/cloud-conf/".$vnc_resource->mac.".vnc";
			if (file_exists($vnc_informations_file)) {
				$vnc_infos = file_get_contents($vnc_informations_file);
				$vnc_informations = "You can login to the System console via VNC at ".trim($vnc_infos);
				unlink($vnc_informations_file);
			} else {
				$vnc_informations = "You can login to the System console via VNC using the above password";
			}
		}
		$arr = array('@@ID@@'=> $cr_id, '@@FORENAME@@'=> $cu_forename, '@@LASTNAME@@'=> $cu_lastname, '@@START@@'=> $start, '@@STOP@@'=> $stop, '@@PASSWORD@@'=> $login_password, '@@LOGINUSER@@'=> $login_user, '@@IP@@'=> $login_ip, '@@RESNUMBER@@'=> '1', '@@HOSTNAME@@'=> $appliance->name, '@@VNC@@'=> $vnc_informations, '@@CLOUDADMIN@@' => $cc_admin_email);

		// send mail to the user
		$rmail = new cloudmailer();
		$rmail->to = "$cu_email";
		$rmail->from = "$cc_admin_email";
		$rmail->subject = "openQRM Cloud: Your resource for request ".$cr_id." is now active";
		$rmail->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/active_cloud_request.mail.tmpl";
		$rmail->var_array = $arr;
		$rmail->send();

		// mail the ip + root password to the cloud admin
		$rmail_admin = new cloudmailer();
		$rmail_admin->to = "$cc_admin_email";
		$rmail_admin->from = "$cc_admin_email";
		$rmail_admin->subject = "openQRM Cloud: The resource for request ".$cr_id." is now active";
		$rmail_admin->template = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/etc/mail/active_cloud_request_admin.mail.tmpl";
		$rmail_admin->var_array = $arr;
		$rmail_admin->send();


		// ################################## setup access to collectd graphs ####################

		// check if collectd is enabled
		$collectd_conf = new cloudconfig();
		$show_collectd_graphs = $collectd_conf->get_value(19);	// show_collectd_graphs
		if (!strcmp($show_collectd_graphs, "true")) {
			// is collectd enabled ?
			if (file_exists("$RootDir/plugins/collectd/.running")) {
				// ldap or regular user ?
				$collectd_appliance = $appliance->name;
				if (file_exists("$RootDir/plugins/ldap/.running")) {
					$collectd_debug = "Setting up access to the collectd graphs of appliance $collectd_appliance for ldap Cloud user $cu_name";
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", $collectd_debug, "", "", 0, 0, 0);
					// get ldap from db config
					$ldap_conf = new ldapconfig();
					$ldap_conf->get_instance_by_id(1);
					$ldap_host = $ldap_conf->value;
					$ldap_conf->get_instance_by_id(2);
					$ldap_port = $ldap_conf->value;
					$ldap_conf->get_instance_by_id(3);
					$ldap_base_dn = $ldap_conf->value;
					$ldap_conf->get_instance_by_id(4);
					$ldap_admin = $ldap_conf->value;
					$ldap_conf->get_instance_by_id(5);
					$ldap_password = $ldap_conf->value;
					// send command to the openQRM-server
					$setup_collectd = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/bin/openqrm-cloud-manager setup-graph-ldap ".$collectd_appliance." ".$cu_name." ".$ldap_host." ".$ldap_port." ".$ldap_base_dn." ".$ldap_password;
					$openqrm_server->send_command($setup_collectd);

				} else {
					// regular basic auth user
					$collectd_debug = "Setting up access to the collectd graphs of appliance $collectd_appliance for Cloud user $cu_name";
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", $collectd_debug, "", "", 0, 0, 0);
					// here we still have the valid user object, get the password
					$cu_pass = $cu->password;
					// send command to the openQRM-server
					$setup_collectd = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/bin/openqrm-cloud-manager setup-graph ".$collectd_appliance." ".$cu_name." ".$cu_pass;
					$openqrm_server->send_command($setup_collectd);
				}

			}
		}

		// ################################## provision finished ####################
		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "#### Cloud Phase II-4 - Provisioning $cr_resource_number finished", "", "", 0, 0, 0);

		// check if we continue or go on
		$parallel_phase_two_actions++;
		if ($max_parallel_phase_two_actions > 0 && $parallel_phase_two_actions >= $max_parallel_phase_two_actions) {
			break;
		}

	}


	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Cloud Phase III - Billing loop", "", "", 0, 0, 0);
	// new active cr loop
	$cr_list = $crl->get_all_active_ids();
	foreach($cr_list as $list) {
		$phase_three_actions = 0;
		$cr_id = $list['cr_id'];
		$cr = new cloudrequest();
		$cr->get_instance_by_id($cr_id);
		$cr_status = $cr->status;

		// #################### monitoring for billing ################################
		// billing, only care about active requests

		$cb_config = new cloudconfig();
		$cloud_billing_enabled = $cb_config->get_value(16);	// 16 is cloud_billing_enabled
		$cloud_deprovision_warning = $cb_config->get_value(38);	// 38 is deprovision_warning
		$cloud_deprovision_pause = $cb_config->get_value(39);	// 38 is deprovision_pause
		if ($cloud_billing_enabled != 'true') {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Cloud-billing is disabled. Not charging User $cu->name for request ID $cr_id", "", "", 0, 0, 0);
		} else {

			$one_hour = 3600;
			$one_day = 43200; // every 12 hours
			$now=$_SERVER['REQUEST_TIME'];
			$cu_id = $cr->cu_id;
			$cu = new clouduser();
			$cu->get_instance_by_id($cu_id);
			$no_ccu_mail_conf = new cloudconfig();
			$no_ccu_mail_admin_email = $no_ccu_mail_conf->get_value(1);  // 1 is admin_email
			$cu_ccunits = $cu->ccunits;
			// be sure to get a value from the db
			if (!is_numeric($cu_ccunits)) {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Got empty CCU value /".$cu_ccunits."/ for User ".$cu->name."/".$cu_id." request ID ".$cr_id, "", "", 0, 0, 0);
				continue;
			}

			// in case the user has no ccunits any more we set the status to deprovision
			if ($cu_ccunits <= 0) {
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "No CCUs left for User $cu->name, setting CR $cr_id to deprovisioning", "", "", 0, 0, 0);
				$cr->setstatus($cr_id, "deprovision");
				// send mail to user
				$deprovision_no_ccu_mail = new cloudmailer();
				$deprovision_no_ccu_mail->to = $cu->email;
				$deprovision_no_ccu_mail->from = $no_ccu_mail_admin_email;
				$deprovision_no_ccu_mail->subject = "openQRM Cloud: No CCUs left! Deprovisioning request ".$cr_id;
				$deprovision_no_ccu_mail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/deprovision_no_ccu.mail.tmpl";
				$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu->forename", '@@LASTNAME@@'=>"$cu->lastname", '@@CLOUDADMIN@@' => $no_ccu_mail_admin_email);
				$deprovision_no_ccu_mail->var_array = $arr;
				$deprovision_no_ccu_mail->send();
				continue;
			}

			// should we pause the user appliance now ?
			else if ($cu_ccunits <= $cloud_deprovision_pause) {
				$pause_app_array = explode(",", $cr->appliance_id);
				if (is_array($pause_app_array)) {
					foreach($pause_app_array as $pause_app_id) {
						$pause_app = new appliance();
						$pause_app->get_instance_by_id($pause_app_id);
						if (!strcmp($pause_app->state, "active")) {
							// inform the user
							$deprovision_pause_mail = new cloudmailer();
							$deprovision_pause_mail->to = $cu->email;
							$deprovision_pause_mail->from = $no_ccu_mail_admin_email;
							$deprovision_pause_mail->subject = "openQRM Cloud: Warning! Pausing the Appliance of Cloud request ".$cr_id;
							$deprovision_pause_mail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/deprovision_pause.mail.tmpl";
							$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu->forename", '@@LASTNAME@@'=>"$cu->lastname", '@@CCUS@@'=>"$cu_ccunits", '@@CLOUDADMIN@@' => $no_ccu_mail_admin_email);
							$deprovision_pause_mail->var_array = $arr;
							$deprovision_pause_mail->send();
							// pause appliance
							$pause_cloud_appliance = new cloudappliance();
							$pause_cloud_appliance->get_instance_by_appliance_id($pause_app_id);
							if ($pause_cloud_appliance->cmd == 0) {
								if ($pause_cloud_appliance->state == 1) {
									$pause_cloud_appliance->set_cmd($pause_cloud_appliance->id, "stop");
									$pause_cloud_appliance->set_state($pause_cloud_appliance->id, "paused");
								}
							}
						}
					}
				}
			}

			// should we send a warning to the user ?
			else if ($cu_ccunits <= $cloud_deprovision_warning) {
				// once per day per cr we send mail for deprovisioning warning
				$check_cr_now = false;
				$cloud_user_lock_per_day = dirname($cloud_monitor_lock).'/'.$cu->name.'.'.$cr_id.'.per_day.lock';
				if (!file_exists($cloud_user_lock_per_day)) {
					file_put_contents($cloud_user_lock_per_day, time());
					$check_cr_now = true;
				}
				$lastwarning = file_get_contents($cloud_user_lock_per_day);
				$currenttimestamp = time();
				if (($currenttimestamp - $lastwarning >= $one_day) || ($check_cr_now == 'true')) {
					file_put_contents($cloud_user_lock_per_day, time());
					$deprovision_warning_mail = new cloudmailer();
					$deprovision_warning_mail->to = $cu->email;
					$deprovision_warning_mail->from = $no_ccu_mail_admin_email;
					$deprovision_warning_mail->subject = "openQRM Cloud: Warning! Your CCUs are running low for Cloud request ".$cr_id;
					$deprovision_warning_mail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/deprovision_warning.mail.tmpl";
					$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu->forename", '@@LASTNAME@@'=>"$cu->lastname", '@@CCUS@@'=>"$cu_ccunits", '@@CLOUDADMIN@@' => $no_ccu_mail_admin_email);
					$deprovision_warning_mail->var_array = $arr;
					$deprovision_warning_mail->send();
				}
			}

			// check if to charge
			$charge = false;
			$cr_lastbill = $cr->lastbill;
			if (!strlen($cr_lastbill)) {
				// we set the last-bill time to now and bill
				$cr->set_requests_lastbill($cr_id, $now);
				$charge = true;
			} else {
				// we check if we need to bill according the last-bill var
				$active_cr_time = $now - $cr_lastbill;
				if ($active_cr_time >= $one_hour) {
					// set lastbill to now
					$cr->set_requests_lastbill($cr_id, $now);
					$charge = true;
				}
			}
			if ($charge) {
				// here we calculate what to charge
				// cloudselector enabled ?
				$show_cloud_selector = $cb_config->get_value(22);	// cloud_selector
				if (!strcmp($show_cloud_selector, "true")) {
					$ct = new cloudtransaction();
					$cloudselector = new cloudselector();
					// we need to loop through all appliances of this request
					// and only charge for active ones
					$cs_active_apps = 0;
					$new_cu_ccunits = $cu_ccunits;
					$cs_app_array = explode(",", $cr->appliance_id);
					if (is_array($cs_app_array)) {
						foreach($cs_app_array as $cs_app_id) {
							$cs_app = new appliance();
							$cs_app->get_instance_by_id($cs_app_id);
							if (!strcmp($cs_app->state, "active")) {
								// cpu
								$cpu_cost = $cloudselector->get_price($cr->cpu_req, "cpu");
								$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $cpu_cost);
								$ct->push($cr->id, $cr->cu_id, $cpu_cost, $new_cu_ccunits, "Cloud Billing", "$cpu_cost CCUs for $cr->cpu_req CPU(s) Appliance $cs_app_id (CR $cr->id)");
								// disk
								$disk_cost = $cloudselector->get_price($cr->disk_req, "disk");
								$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $disk_cost);
								$ct->push($cr->id, $cr->cu_id, $disk_cost, $new_cu_ccunits, "Cloud Billing", "$disk_cost CCUs for $cr->disk_req MB Disk Space Appliance $cs_app_id (CR $cr->id)");
								// ha
								if (strlen($cr->ha_req)) {
									$ha_cost = $cloudselector->get_price($cr->ha_req, "ha");
									$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $ha_cost);
									$ct->push($cr->id, $cr->cu_id, $ha_cost, $new_cu_ccunits, "Cloud Billing", "$ha_cost CCUs for High-Availability Appliance $cs_app_id (CR $cr->id)");
								}
								// kernel
								$kernel_cost = $cloudselector->get_price($cr->kernel_id, "kernel");
								$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $kernel_cost);
								$ct->push($cr->id, $cr->cu_id, $kernel_cost, $new_cu_ccunits, "Cloud Billing", "$kernel_cost CCUs for Kernel $cr->kernel_id Appliance $cs_app_id (CR $cr->id)");
								// memory
								$memory_cost = $cloudselector->get_price($cr->ram_req, "memory");
								$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $memory_cost);
								$ct->push($cr->id, $cr->cu_id, $memory_cost, $new_cu_ccunits, "Cloud Billing", "$memory_cost CCUs for $cr->ram_req MB Memory Appliance $cs_app_id (CR $cr->id)");
								// network
								$network_cost = $cloudselector->get_price($cr->network_req, "network");
								$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $network_cost);
								$ct->push($cr->id, $cr->cu_id, $network_cost, $new_cu_ccunits, "Cloud Billing", "$network_cost CCUs for $cr->network_req Network Card(s) Appliance $cs_app_id (CR $cr->id)");
								// application
								$application_groups_array = explode(",", $cr->puppet_groups);
								if (is_array($application_groups_array)) {
									foreach($application_groups_array as $application_group) {
										if (strlen($application_group)) {
											$application_group_cost = $cloudselector->get_price($application_group, "application");
											$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $application_group_cost);
											$ct->push($cr->id, $cr->cu_id, $application_group_cost, $new_cu_ccunits, "Cloud Billing", "$application_group_cost CCUs for Application $application_group Appliance $cs_app_id (CR $cr->id)");
										}
									}
								}
								// resource type
								$cs_virtualization = new virtualization();
								$cs_virtualization->get_instance_by_id($cr->resource_type_req);
								$resource_cost = $cloudselector->get_price($cr->resource_type_req, "resource");
								$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $resource_cost);
								$ct->push($cr->id, $cr->cu_id, $resource_cost, $new_cu_ccunits, "Cloud Billing", "$resource_cost CCUs for Type $cs_virtualization->name Appliance $cs_app_id (CR $cr->id)");
								$cs_active_apps++;
							} else {
								// we only charge for the diskspace used
								// disk
								$disk_cost = $cloudselector->get_price($cr->disk_req, "disk");
								$new_cu_ccunits = substract_until_zero($new_cu_ccunits, $disk_cost);
								$ct->push($cr->id, $cr->cu_id, $disk_cost, $new_cu_ccunits, "Cloud Billing", "$disk_cost CCUs for $cr->disk_req MB Disk Space Appliance $cs_app_id (CR $cr->id)");
							}
						}
					}

				} else {
					// or custom billing
					$new_cu_ccunits = openqrm_custom_cloud_billing($cr_id, $cu_id, $cu_ccunits);
				}

				$cu->set_users_ccunits($cu_id, $new_cu_ccunits);
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Charging User $cu->name for request ID $cr_id", "", "", 0, 0, 0);
				$phase_three_actions = 1;
			}
		}

		// #################### check for deprovisioning ################################
		// de-provision, check if it is time or if status deprovisioning
		$cr = new cloudrequest();
		$cr->get_instance_by_id($cr_id);

		// check for stop time
		$now=$_SERVER['REQUEST_TIME'];
		$cr_stop = $cr->stop;
		if ($cr_stop < $now) {
			// set to deprovisioning
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "CR $cr_id stop time reached, setting to deprovisioning", "", "", 0, 0, 0);
			$cr->setstatus($cr_id, "deprovision");
		}

		// check if we continue or go on
		if ($phase_three_actions == 1) {
			$parallel_phase_three_actions++;
			if ($max_parallel_phase_three_actions > 0 && $parallel_phase_three_actions >= $max_parallel_phase_three_actions) {
				break;
			}
		}

	}


	// #################### deprovisioning ################################
	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Cloud Phase IV - Deprovisioning", "", "", 0, 0, 0);
	// new deprovision cr loop
	$cr_list = $crl->get_all_deprovisioned_ids();
	foreach($cr_list as $list) {
		$cr_id = $list['cr_id'];
		$cr = new cloudrequest();
		$cr->get_instance_by_id($cr_id);
		$cu_id = $cr->cu_id;
		$cu = new clouduser();
		$cu->get_instance_by_id($cu_id);
		$cr_has_appliance = 1;
		$cloud_user_lock_per_day = dirname($cloud_monitor_lock).'/'.$cu->name.'.'.$cr_id.'.per_day.lock';

		$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Deprovisioning of Cloud request ID $cr_id", "", "", 0, 0, 0);
		if (file_exists($cloud_user_lock_per_day)) {
			unlink($cloud_user_lock_per_day);
		}

		// get the requests appliance
		$cr_appliance_id = $cr->appliance_id;
		if (!strlen($cr_appliance_id)) {
			// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Request $cr_id does not have an active appliance!", "", "", 0, 0, 0);
			$cr_has_appliance = 0;
		}
		if ($cr_appliance_id == 0) {
			// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Request $cr_id does not have an active appliance!", "", "", 0, 0, 0);
			$cr_has_appliance = 0;
		}
		// in case a cr got deprovisioned with an active creaet-vm-lc but no cloud_appliance and/or cloud_image yet
		// this will remove the left over vm + resource
		if ($cr_has_appliance == 0) {
			// check if a vm was requested
			if ($cr->resource_type_req != 1) {
				// check if we have a create-vm-lc, if we have one auto-create-vm is true
				$deprovision_cr_resource=1;
				$deprovision_cr_create_vm_lc = new cloudcreatevmlc();
				$deprovision_cr_create_vm_lc->get_instance_by_cr_details($cr_id, $deprovision_cr_resource);
				if (strlen($deprovision_cr_create_vm_lc->id)) {
					if ($deprovision_cr_create_vm_lc->resource_id != 0) {
						// remove the vm
						$auto_deprovision_resource = new resource();
						$auto_deprovision_resource->get_instance_by_id($deprovision_cr_create_vm_lc->resource_id);
						$auto_deprovision_resource_name = "cloud-".$cr_id."-".$deprovision_cr_resource."-x";
						// cloudvm->remove .....
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Auto-removing resource $deprovision_cr_create_vm_lc->resource_id - $cr->resource_type_req, $auto_deprovision_resource_name, $auto_deprovision_resource->mac", "", "", 0, 0, 0);
						$auto_cloudvm = new cloudvm();
						$auto_cloudvm->remove($deprovision_cr_create_vm_lc->resource_id, $cr->resource_type_req, $auto_deprovision_resource_name, $auto_deprovision_resource->mac);
					}
					// remove the create-vm-lx
					$deprovision_cr_create_vm_lc->remove($deprovision_cr_create_vm_lc->id);
				}
			}
			$cr->setstatus($cr_id, "done");
			continue;
		}


		// ################################## de-provisioning ###############################
		// count the resource we deprovision for the request
		$deprovision_resource_number=1;
		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Deprovisioning appliance $cr_appliance_id from request ID $cr_id", "", "", 0, 0, 0);

		// stop the appliance, first de-assign its resource
		$appliance = new appliance();
		$appliance->get_instance_by_id($cr_appliance_id);
		// .. only if active and not stopped already by the user
		$cloud_appliance = new cloudappliance();
		$cloud_appliance->get_instance_by_appliance_id($appliance->id);
		if ($cloud_appliance->state == 0) {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Appliance $cr_appliance_id from request ID $cr_id stopped already", "", "", 0, 0, 0);
		} else {
			if ($appliance->resources != -1)  {
				$resource = new resource();
				$resource->get_instance_by_id($appliance->resources);
				$resource_external_ip=$resource->ip;
				$openqrm_server->send_command("openqrm_assign_kernel $resource->id $resource->mac default");
				// let the kernel assign command finish
				sleep(2);
				// now stop
				// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Stopping Appliance $cr_appliance_id from request ID $cr_id", "", "", 0, 0, 0);
				$appliance->stop();
			}
		}


		// ################################## de-assign ip-mgmt ###############################
		// here we free up the ip addresses used by the appliance again
		// check ip-mgmt
		$cc_conf = new cloudconfig();
		$show_ip_mgmt = $cc_conf->get_value(26);	// ip-mgmt enabled ?
		if (!strcmp($show_ip_mgmt, "true")) {
			if (file_exists("$RootDir/plugins/ip-mgmt/.running")) {
				require_once "$RootDir/plugins/ip-mgmt/class/ip-mgmt.class.php";
				$ip_mgmt_array = explode(",", $cr->ip_mgmt);
				foreach($ip_mgmt_array as $ip_mgmt_config_str) {
					$collon_pos = strpos($ip_mgmt_config_str, ":");
					$nic_id = substr($ip_mgmt_config_str, 0, $collon_pos);
					$ip_mgmt_id = substr($ip_mgmt_config_str, $collon_pos+1);
					$ip_mgmt_fields=array();
					$ip_mgmt_fields["ip_mgmt_appliance_id"]=NULL;
					$ip_mgmt_fields["ip_mgmt_nic_id"]=NULL;
					$ip_mgmt_assign = new ip_mgmt();
					$ip_mgmt_assign->update_ip($ip_mgmt_id, $ip_mgmt_fields);
				}
			}
		}

		// #####################################################################################

		// here we remove the appliance from the cloud-appliance table
		$cloud_appliance = new cloudappliance();
		$cloud_appliance->get_instance_by_appliance_id($appliance->id);
		if (strlen($cloud_appliance->id)) {
			$cloud_appliance->remove($cloud_appliance->id);
		}

		// ################################## remove application groups ###############################

		// check if application is enabled
		$application_conf = new cloudconfig();
		$show_application_groups = $application_conf->get_value(11);	// show_puppet_groups
		if (!strcmp($show_application_groups, "true")) {
			$application_appliance = $appliance->name;
			if (strlen($cr->puppet_groups)) {
				$application_debug = "Removing applications from appliance ".$application_appliance.".";
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", $application_debug, "", "", 0, 0, 0);
				$application_group_array = explode(",", $cr->puppet_groups);
				$cloudapplication = new cloudapplication();
				$cloudapplication->remove_applications($appliance->name, $application_group_array);
			}
		}


		// ################################## deprovisioning clone-on-deploy ###############################

		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Removing cloudimage for request ID $cr_id", "", "", 0, 0, 0);
		// here we set the state of the cloud-image to remove
		// this will check the state of the resource which still has
		// the image as active rootfs. If the resource is idle again the
		// image will be removed.
		// The check for this mechanism is being executed at the beginning
		// of each cloud-monitor loop
		if ($appliance->imageid > 0) {
			$cloud_image = new cloudimage();
			$cloud_image->get_instance_by_image_id($appliance->imageid);
			$cloud_image->set_state($cloud_image->id, "remove");

		}

		// ################################## deprovisioning mail user ###############################

		// remove appliance_id from request
		$cr->get_instance_by_id($cr->id);
		$cr->setappliance("remove", $appliance->id);
		// when we are at the last resource for the request set status to 6 = done
		if ($deprovision_resource_number == $cr->resource_quantity) {
			$cr->setstatus($cr_id, "done");
			// set lastbill empty
			$cr->set_requests_lastbill($cr_id, '');
		}

		// send mail to user for deprovision started
		// get admin email
		$cc_conf = new cloudconfig();
		$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email
		// get user + request + appliance details
		$cu_name = $cu->name;
		$cu_forename = $cu->forename;
		$cu_lastname = $cu->lastname;
		$cu_email = $cu->email;
		// start/stop time
		$cr_start = $cr->start;
		$start = date("d-m-Y H-i", $cr_start);
		$cr_stop = $cr->stop;
		$stop = date("d-m-Y H-i", $cr_stop);

		$eip = '';
		if (isset($resource_external_ip)) {
			$eip = $resource_external_ip;
		}

		$rmail = new cloudmailer();
		$rmail->to = "$cu_email";
		$rmail->from = "$cc_admin_email";
		$rmail->subject = "openQRM Cloud: Your resource from request $cr_id is fully deprovisioned now";
		$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/done_cloud_request.mail.tmpl";
		$arr = array('@@ID@@'=>"$cr_id", '@@FORENAME@@'=>"$cu_forename", '@@LASTNAME@@'=>"$cu_lastname", '@@START@@'=>"$start", '@@STOP@@'=>"$stop", '@@IP@@'=>"$eip", '@@RESNUMBER@@'=>"$deprovision_resource_number", '@@CLOUDADMIN@@' => $cc_admin_email);
		$rmail->var_array = $arr;
		$rmail->send();


		// ################################## remove access to collectd graphs ####################

		// check if collectd is enabled
		$collectd_conf = new cloudconfig();
		$show_collectd_graphs = $collectd_conf->get_value(19);	// show_collectd_graphs
		if (!strcmp($show_collectd_graphs, "true")) {
			// is collectd enabled ?
			if (file_exists("$RootDir/plugins/collectd/.running")) {
				// check if we have a collectd config in the request
				$collectd_appliance = $appliance->name;
				$collectd_debug = "Removing access to Collectd graphs of appliance $collectd_appliance for Cloud user $cu_name";
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", $collectd_debug, "", "", 0, 0, 0);
				// send command to the openQRM-server
				$remove_collectd = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/cloud/bin/openqrm-cloud-manager remove-graph $collectd_appliance $cu_name";
				$openqrm_server->send_command($remove_collectd);
			}
		}

		// ################################## finsihed de-provision ####################


		// we cannot remove the appliance here because its image is still in use
		// and the appliance (id) is needed for the removal
		// so the image-remove mechanism also cares to remove the appliance
		// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Deprovisioning request ID $cr_id finished", "", "", 0, 0, 0);

		$deprovision_resource_number++;

		// ################################## end  de-provisioning ###############################

		// #################### end deprovisioning cr-loop ################################
		// check if we continue or go on
		$parallel_phase_four_actions++;
		if ($max_parallel_phase_four_actions > 0 && $parallel_phase_four_actions >= $max_parallel_phase_four_actions) {
			break;
		}

	}

	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Cloud Phase V - Cloudappliance commands", "", "", 0, 0, 0);

	// ################################## run cloudappliance commands ###############################

	$cloudapp = new cloudappliance();
	$cloudapp_list = $cloudapp->get_all_ids();

	foreach($cloudapp_list as $list) {
		$phase_five_actions = 0;
		$ca_id = $list['ca_id'];
		$ca = new cloudappliance();
		$ca->get_instance_by_id($ca_id);
		$ca_appliance_id = $ca->appliance_id;
		$ca_cr_id = $ca->cr_id;
		$ca_cmd = $ca->cmd;
		$ca_state = $ca->state;

		switch ($ca_cmd) {
			case 1:
				// start
				// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Appliance start (ca $ca_id / app $ca_appliance_id / cr $ca_cr_id)", "", "", 0, 0, 0);
				$tappliance = new appliance();
				$tappliance->get_instance_by_id($ca_appliance_id);
				$cloud_image_start = new cloudimage();
				$cloud_image_start->get_instance_by_image_id($tappliance->imageid);

				// resource active (idle) again or ci resource set to -1 (removed)
				if ($cloud_image_start->resource_id != -1) {
					$ca_resource = new resource();
					$ca_resource->get_instance_by_id($cloud_image_start->resource_id);
					$tcaid = $cloud_image_start->resource_id;
					if ((strcmp($ca_resource->state, "active")) || (!strcmp($ca_resource->ip, "0.0.0.0"))) {
						// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Appliance start (ca $ca_id / app $ca_appliance_id / cr $ca_cr_id) : resource $tcaid Not yet active again", "", "", 0, 0, 0);
						// resource not yet automatic removed in case it is ia vm or not yet active again
						continue;
					}
				}

				// prepare array to update appliance, be sure to set to auto-select resource
				$ar_update = array(
					'appliance_resources' => "-1",
				);
				// update appliance
				$ca_appliance = new appliance();
				$ca_appliance->update($ca_appliance_id, $ar_update);

				// lets find a resource for this new appliance according the cr, update the object first
				$ca_appliance->get_instance_by_id($ca_appliance_id);
				// get the cr
				$ca_cr = new cloudrequest();
				$ca_cr->get_instance_by_id($ca_cr_id);
				$appliance_virtualization=$ca_cr->resource_type_req;

				// prepare mail data
				$unpause_ca_conf = new cloudconfig();
				$unpause_ca_admin_email = $unpause_ca_conf->get_value(1);  // 1 is admin_email
				// and the user details
				$unpause_cloud_user = new clouduser();
				$unpause_cloud_user->get_instance_by_id($ca_cr->cu_id);
				$unpause_cu_forename = $unpause_cloud_user->forename;
				$unpause_cu_lastname = $unpause_cloud_user->lastname;
				$unpause_cu_email = $unpause_cloud_user->email;

				// ################################## phys. res. ###############################

				if ($appliance_virtualization == 1) {

					$ca_appliance->find_resource($appliance_virtualization);
					// check if we got a resource !
					$ca_appliance->get_instance_by_id($ca_appliance_id);
					if ($ca_appliance->resources == -1) {
						$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Could not find a resource (type physical system) for request ID $ca_cr_id", "", "", 0, 0, 0);
						$ca_cr->setstatus($ca_cr_id, 'no-res');

						// send mail to user
						$rmail = new cloudmailer();
						$rmail->to = "$unpause_cu_email";
						$rmail->from = "$unpause_ca_admin_email";
						$rmail->subject = "openQRM Cloud: Not enough resources to unpause your Cloudappliance ".$ca_id." from request ".$ca_cr_id;
						$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
						$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"$unpause_cu_forename", '@@LASTNAME@@'=>"$unpause_cu_lastname", '@@RESNUMBER@@'=>"0", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
						$rmail->var_array = $arr;
						$rmail->send();
						// send mail to admin
						$rmail_admin = new cloudmailer();
						$rmail_admin->to = "$unpause_ca_admin_email";
						$rmail_admin->from = "$unpause_ca_admin_email";
						$rmail_admin->subject = "openQRM Cloud: Not enough resources to unpause Cloudappliance ".$ca_id." from request ".$ca_cr_id;
						$rmail_admin->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
						$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@RESNUMBER@@'=>"0", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
						$rmail_admin->var_array = $arr;
						$rmail_admin->send();
						continue;
					}
					// we have a phys. resource


				} else {

					// ################################## auto create vm ###############################
					// check if we should try to create one

					// request type vm
					$unpause_auto_create_vms = $unpause_ca_conf->get_value(7);  // 7 is auto_create_vms
					if (!strcmp($unpause_auto_create_vms, "true")) {
						// check if createvmlc exists for this cr + res-quantity
						unset($cvmlc);
						// use ca id as res.no
						$cvmlc = new cloudcreatevmlc();
						$cvmlc->get_instance_by_cr_details($ca_cr_id, $ca_appliance_id);
						if (!strlen($cvmlc->request_time)) {
							// if no createvmlc exists so far create it and the vm
							// generate a mac address
							$mac_res = new resource();
							// check if we need to generate the first nics mac address in the vmware address space
							$new_vm_mac="";
							$vm_virt = new virtualization();
							$vm_virt->get_instance_by_type($ca_cr->resource_type_req);
							$virt_name = $vm_virt->get_plugin_name();

							switch ($virt_name) {
								case 'vmware-esx':
								case 'vmware-server':
								case 'vmware-server2':
									$mac_res->generate_mac();
									$suggested_mac = $mac_res->mac;
									$new_forth_byte_first_bit = rand(1, 3);
									$new_vm_mac = strtolower($vmware_mac_address_space.":".substr($suggested_mac, 9));
									$new_vm_mac = substr_replace($new_vm_mac , $new_forth_byte_first_bit, 9, 1);
									break;
								default:
									$mac_res->generate_mac();
									$new_vm_mac = $mac_res->mac;
									break;
							}
							// additional_nics
							$new_additional_nics = $ca_cr->network_req;
							// cpu
							$new_vm_cpu = $ca_cr->cpu_req;
							// memory
							$new_vm_memory = 256;
							if ($ca_cr->ram_req != 0) {
								$new_vm_memory = $ca_cr->ram_req;
							}
							// disk size
							$new_vm_disk = 5000;
							if ($ca_cr->disk_req != 0) {
								$new_vm_disk = $ca_cr->disk_req;
							}
							// generate vnc password and store in cr
							$pass_gen_image = new image();
							$vm_vnc_password = trim($pass_gen_image->generatePassword(12));
							$ca_cr_update_fields = array();
							$ca_cr_update_fields['cr_image_password'] = $vm_vnc_password;
							$ca_cr->update($ca_cr->id, $ca_cr_update_fields);
// Create VM
							// here we start the new vm !
							$cloudvm = new cloudvm();
							// this method returns the resource-id
							$cloudvm->create($ca_cr->cu_id, $appliance_virtualization, $ca_appliance->name, $new_vm_mac, $new_additional_nics, $new_vm_cpu, $new_vm_memory, $new_vm_disk, $vm_create_timout, $vm_vnc_password, $SOURCE_IMAGE_ID);
							$new_vm_resource_id = $cloudvm->resource_id;
							// if we did not find a host resource id will be 0
							if ($new_vm_resource_id == 0) {

								$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Could not find a VM Host for request ID ".$ca_cr_id."(unpause)", "", "", 0, 0, 0);
								$ca_cr->setstatus($ca_cr_id, 'no-res');
								// send mail to user
								$rmail = new cloudmailer();
								$rmail->to = "$unpause_cu_email";
								$rmail->from = "$unpause_ca_admin_email";
								$rmail->subject = "openQRM Cloud: No VM Host available to unpause your Cloudappliance ".$ca_id." from request ".$ca_cr_id;
								$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
								$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"$unpause_cu_forename", '@@LASTNAME@@'=>"$unpause_cu_lastname", '@@RESNUMBER@@'=>"0", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
								$rmail->var_array = $arr;
								$rmail->send();
								// send mail to admin
								$rmail_admin = new cloudmailer();
								$rmail_admin->to = "$unpause_ca_admin_email";
								$rmail_admin->from = "$unpause_ca_admin_email";
								$rmail_admin->subject = "openQRM Cloud: No VM Host available to unpause Cloudappliance ".$ca_id." from request ".$ca_cr_id;
								$rmail_admin->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
								$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@RESNUMBER@@'=>"0", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
								$rmail_admin->var_array = $arr;
								$rmail_admin->send();

								// reset the cmd field
								$ca->set_cmd($ca_id, "noop");
								// set state to paused
								$ca->set_state($ca_id, "paused");
								continue;
							}

							$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Auto-created VM with resource_id $new_vm_resource_id", "", "", 0, 0, 0);

							// create cvmlc after we got a resource_id
							$vm_create_time=$_SERVER['REQUEST_TIME'];
							$cvmlc_resource_fields["vc_resource_id"]=$new_vm_resource_id;
							$cvmlc_resource_fields["vc_cr_id"]=$ca_cr_id;
							$cvmlc_resource_fields["vc_cr_resource_number"]=$ca_appliance_id;
							$cvmlc_resource_fields["vc_request_time"]=$vm_create_time;
							$cvmlc_resource_fields["vc_vm_create_timeout"]=$vm_create_timout;
							$cvmlc_resource_fields["vc_state"]=0;
							// get the new resource id from the db
							$new_vc_id=(int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
							$cvmlc_resource_fields["vc_id"]=$new_vc_id;
							$cvmlc->add($cvmlc_resource_fields);
							// here we go on
							continue;

						} else {
							// we have a cvmlc, check its resource and set its state
							$cvm_resource = new resource();
							$cvm_resource->get_instance_by_id($cvmlc->resource_id);
							// idle ?
							if (($cvm_resource->imageid == 1) && ($cvm_resource->state == 'active') && (strcmp($cvm_resource->ip, "0.0.0.0"))) {
								// we have a new idle vm as resource :) update it in the appliance
								$new_vm_resource_id = $cvmlc->resource_id;
								unset($appliance_fields);
								$appliance_fields = array();
								$appliance_fields['appliance_resources'] = $new_vm_resource_id;
								$ca_appliance->update($ca_appliance_id, $appliance_fields);
								$ca_appliance->get_instance_by_id($ca_appliance_id);
								// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Created new resource $new_vm_resource_id for appliance $ca_appliance->name start event", "", "", 0, 0, 0);
								// update cloudimage with resource -1
								$ar_ci_update = array(
									'ci_resource_id' => $new_vm_resource_id,
									'ci_state' => 1,
								);
								$cloud_image_start->update($cloud_image_start->id, $ar_ci_update);
								// remove cvmlc
								$cvmlc->remove($cvmlc->id);


							} else {

								// check timeout
								$vm_check_time=$_SERVER['REQUEST_TIME'];
								$vm_c_timeout = $cvmlc->request_time + $cvmlc->vm_create_timeout;
								if ($vm_check_time > $vm_c_timeout) {

									$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Could not create a new resource for request ID ".$ca_cr_id."(unpause)", "", "", 0, 0, 0);
									$ca_cr->setstatus($ca_cr_id, 'no-res');
									// send mail to user
									$rmail = new cloudmailer();
									$rmail->to = "$unpause_cu_email";
									$rmail->from = "$unpause_ca_admin_email";
									$rmail->subject = "openQRM Cloud: Not enough resources to unpause your Cloudappliance ".$ca_id." from request ".$ca_cr_id;
									$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
									$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"$unpause_cu_forename", '@@LASTNAME@@'=>"$unpause_cu_lastname", '@@RESNUMBER@@'=>"0", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
									$rmail->var_array = $arr;
									$rmail->send();
									// send mail to admin
									$rmail_admin = new cloudmailer();
									$rmail_admin->to = "$unpause_ca_admin_email";
									$rmail_admin->from = "$unpause_ca_admin_email";
									$rmail_admin->subject = "openQRM Cloud: Not enough resources to unpause Cloudappliance ".$ca_id." from request ".$ca_cr_id;
									$rmail_admin->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
									$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@RESNUMBER@@'=>"0", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
									$rmail_admin->var_array = $arr;
									$rmail_admin->send();

									// refresh object
									$ca_appliance->get_instance_by_id($ca_appliance_id);
									// try removing the cloudvm
									$clean_cloudvm = new cloudvm();
									$clean_cloudvm->remove($cvm_resource->id, $cvm_resource->vtype, $ca_appliance->name, $cvm_resource->mac);
									// remove cvmlc
									$cvmlc->remove($cvmlc->id);
									// reset the cmd field
									$ca->set_cmd($ca_id, "noop");
									// set state to paused
									$ca->set_state($ca_id, "paused");
									// go on
									continue;
								}
								// still waiting within  the timeout
								// update state to 1 (starting)
								// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Still waiting for cr $ca_cr_id / res. ".$cvmlc->resource_id." to get idle (unpause)", "", "", 0, 0, 0);
								$cvm_state_fields['vc_state'] = 1;
								$cvmlc->update($cvmlc->id, $cvm_state_fields);
								// continue with the next cr/res-nr
								continue;
							}
						}

					// ################################## no auto create vm ###############################

					} else {
						// not set to auto-create vms
						// try to find a fitting idle vm
						$ca_appliance->find_resource($appliance_virtualization);
						// check if we got a resource !
						$ca_appliance->get_instance_by_id($ca_appliance_id);
						if ($ca_appliance->resources == -1) {
							$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "cloud-monitor", "Not creating a new resource for request ID $ca_cr_id, auto-create-vms is disabled.", "", "", 0, 0, 0);
							$ca_cr->setstatus($ca_cr_id, 'no-res');

							// send mail to user
							$rmail = new cloudmailer();
							$rmail->to = "$unpause_cu_email";
							$rmail->from = "$unpause_ca_admin_email";
							$rmail->subject = "openQRM Cloud: Not enough resources to unpause your Cloudappliance ".$ca_id." from request ".$ca_cr_id;
							$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
							$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"$unpause_cu_forename", '@@LASTNAME@@'=>"$unpause_cu_lastname", '@@RESNUMBER@@'=>"0", '@@YOUR@@'=>"your", '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
							$rmail->var_array = $arr;
							$rmail->send();
							// send mail to admin
							$rmail_admin = new cloudmailer();
							$rmail_admin->to = "$unpause_ca_admin_email";
							$rmail_admin->from = "$unpause_ca_admin_email";
							$rmail_admin->subject = "openQRM Cloud: Not enough resources to unpause Cloudappliance ".$ca_id." from request ".$ca_cr_id;
							$rmail_admin->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/not_enough_resources.mail.tmpl";
							$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"Cloudadmin", '@@LASTNAME@@'=>"", '@@RESNUMBER@@'=>"0", '@@YOUR@@'=>"the", '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
							$rmail_admin->var_array = $arr;
							$rmail_admin->send();

							// reset the cmd field
							$ca->set_cmd($ca_id, "noop");
							// set state to paused
							$ca->set_state($ca_id, "paused");
							continue;
						}
					}
				}


				// ################################## end auto create vm ###############################

				// assign the resource
				$ca_appliance->get_instance_by_id($ca_appliance_id);
				$kernel = new kernel();
				$kernel->get_instance_by_id($ca_appliance->kernelid);
				$resource = new resource();
				$resource->get_instance_by_id($ca_appliance->resources);
				// in case we do not have an external ip-config send the resource ip to the user
				$resource_external_ip=$resource->ip;
				// send command to the openQRM-server
				$openqrm_server->send_command("openqrm_assign_kernel $resource->id $resource->mac $kernel->name");
				// wait until the resource got the new kernel assigned
				sleep(2);

				//start the appliance, refresh the object before in case of clone-on-deploy
				$ca_appliance->get_instance_by_id($ca_appliance_id);
				$ca_appliance->start();

				// ######################## ip-mgmt find users ips ###############################
				// here we check which ip to send to the user
				// check ip-mgmt
				$cc_conf = new cloudconfig();
				$show_ip_mgmt = $cc_conf->get_value(26);	// ip-mgmt enabled ?
				if (!strcmp($show_ip_mgmt, "true")) {
					if (file_exists("$RootDir/plugins/ip-mgmt/.running")) {
						require_once "$RootDir/plugins/ip-mgmt/class/ip-mgmt.class.php";
						$ip_mgmt_array = explode(",", $ca_cr->ip_mgmt);
						foreach($ip_mgmt_array as $ip_mgmt_config_str) {
							$collon_pos = strpos($ip_mgmt_config_str, ":");
							$nic_id = substr($ip_mgmt_config_str, 0, $collon_pos);
							$ip_mgmt_id = substr($ip_mgmt_config_str, $collon_pos+1);
							if (!strlen($ip_mgmt_id)) {
								continue;
							}
							$ip_mgmt_unpause = new ip_mgmt();
							$ip_mgmt_config_arr = $ip_mgmt_unpause->get_config_by_id($ip_mgmt_id);
							$cloud_ip = $ip_mgmt_config_arr[0]['ip_mgmt_address'];
							$resource_external_ip = $cloud_ip.",";
						}
						$resource_external_ip = rtrim($resource_external_ip, ",");
					}
				}

				// ################################################################################
			
				// get the vnc console infos - check for allow_vnc
				$cc_allow_vnc = $cc_conf->get_value(42);  // 1 is admin_email
				$vnc_informations = "";
				if (!strcmp($cc_allow_vnc, "true")) {
					$vnc_resource = new resource();
					$vnc_resource->get_instance_by_id($ca_appliance->resources);
					$vnc_informations_file = $OPENQRM_SERVER_BASE_DIR."/openqrm/web/action/cloud-conf/".$vnc_resource->mac.".vnc";
					if (file_exists($vnc_informations_file)) {
						$vnc_infos = file_get_contents($vnc_informations_file);
						$vnc_informations = "You can login to the System console via VNC at ".trim($vnc_infos)." with the password: ".trim($ca_cr->image_password);
						unlink($vnc_informations_file);
					} else {
						$vnc_informations = "You can login to the System console via VNC with the password: ".trim($ca_cr->image_password);
					}
				}
				// remove pass from request
				$cr_update_fields1 = array();
				$cr_update_fields1['cr_image_password'] = '';
				$ca_cr->update($ca_cr->id, $cr_update_fields1);
				
				// update the cloud-image with new resource
				$cloud_image_start->set_resource($cloud_image_start->id, $resource->id);

				// reset the cmd field
				$ca->set_cmd($ca_id, "noop");
				// set state to active
				$ca->set_state($ca_id, "active");
				// make sure the request is set to active again
				$ca_cr->setstatus($ca_cr_id, 'active');

				// send mail to user
				// get admin email
				$cc_conf = new cloudconfig();
				$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email
				// get user + request + appliance details
				$cu_id = $ca_cr->cu_id;
				$cu = new clouduser();
				$cu->get_instance_by_id($cu_id);
				$cu_name = $cu->name;
				$unpause_cu_forename = $cu->forename;
				$unpause_cu_lastname = $cu->lastname;
				$unpause_cu_email = $cu->email;
				// start/stop time
				$cr_start = $ca_cr->start;
				$start = date("d-m-Y H-i", $cr_start);
				$cr_stop = $ca_cr->stop;
				$stop = date("d-m-Y H-i", $cr_stop);

				$rmail = new cloudmailer();
				$rmail->to = "$unpause_cu_email";
				$rmail->from = "$unpause_ca_admin_email";
				$rmail->subject = "openQRM Cloud: Your unpaused appliance $ca_appliance_id from request $ca_cr_id is now active";
				$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/active_cloud_request.mail.tmpl";
				$arr = array('@@ID@@'=>"$ca_cr_id", '@@FORENAME@@'=>"$unpause_cu_forename", '@@LASTNAME@@'=>"$unpause_cu_lastname", '@@START@@'=>"$start", '@@STOP@@'=>"$stop", '@@PASSWORD@@'=>"(as before)", '@@LOGINUSER@@'=> $cu_name, '@@IP@@'=>"$resource_external_ip", '@@RESNUMBER@@'=>"", '@@HOSTNAME@@'=> $ca_appliance->name, '@@VNC@@'=> $vnc_informations, '@@CLOUDADMIN@@' => $unpause_ca_admin_email);
				$rmail->var_array = $arr;
				$rmail->send();
				$phase_five_actions = 1;
				break;


			case 2:
				// stop/pause
				$ca_appliance = new appliance();
				$ca_appliance->get_instance_by_id($ca_appliance_id);
				$ca_resource_id = $ca_appliance->resources;
				$ca_resource_stop = new resource();
				$ca_resource_stop->get_instance_by_id($ca_appliance->resources);
				$resource_external_ip=$ca_resource_stop->ip;
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Pausing Appliance $ca_appliance->name", "", "", 0, 0, 0);
				$openqrm_server->send_command("openqrm_assign_kernel $ca_resource_stop->id $ca_resource_stop->mac default");
				// now stop
				$ca_appliance->stop();
				// remove resource
				$ar_update = array(
					'appliance_resources' => "-1",
				);
				// update appliance
				$ca_appliance->update($ca_appliance_id, $ar_update);
				// reset the cmd field
				$ca->set_cmd($ca_id, "noop");
				// set state to paused
				$ca->set_state($ca_id, "paused");
				$phase_five_actions = 1;
				break;

			case 3:
				// restart
				$ca_appliance = new appliance();
				$ca_appliance->get_instance_by_id($ca_appliance_id);
				$ca_resource_id = $ca_appliance->resources;
				$ca_resource_restart = new resource();
				$ca_resource_restart->get_instance_by_id($ca_resource_id);
				$ca_resource_ip = $ca_resource_restart->ip;
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Restarting Appliance $ca_appliance->name", "", "", 0, 0, 0);
				$ca_resource_restart->send_command("$ca_resource_ip", "reboot");
				// reset the cmd field
				$ca->set_cmd($ca_id, "noop");
				sleep(2);
				// set state to transition
				$resource_fields=array();
				$resource_fields["resource_state"]="transition";
				$ca_resource_restart->update_info($ca_resource_id, $resource_fields);
				$phase_five_actions = 1;
				break;
		}

		// check if we continue or go on
		if ($phase_five_actions == 1) {
			$parallel_phase_five_actions++;
			if ($max_parallel_phase_five_actions > 0 && $parallel_phase_five_actions >= $max_parallel_phase_five_actions) {
				break;
			}
		}

	}
	// ###################### end cloudappliance commands ######################


	// ##################### start cloudimage-resize-life-cycle ################
	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Cloud Phase VI - Cloud Image resize live-cycle", "", "", 0, 0, 0);

	$cirlc = new cloudirlc();
	$cirlc_list = $cirlc->get_all_ids();

	foreach($cirlc_list as $cdlist) {
		$cd_id = $cdlist['cd_id'];
		$cd = new cloudirlc();
		$cd->get_instance_by_id($cd_id);
		$cd_appliance_id = $cd->appliance_id;
		$cd_state = $cd->state;

		switch ($cd_state) {
			case 0:
				// remove
				$cd->remove($cd_id);
				// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudirlc", "(REMOVE) Resize life-cycle of Appliance $cd_appliance_id", "", "", 0, 0, 0);
				break;

			case 1:
				// pause
				// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudirlc", "(PAUSE) Resize life-cycle of Appliance $cd_appliance_id", "", "", 0, 0, 0);
				$cloud_app_resize = new cloudappliance();
				$cloud_app_resize->get_instance_by_id($cd_appliance_id);
				$cloud_app_resize->set_cmd($cloud_app_resize->id, "stop");
				$cloud_app_resize->set_state($cloud_app_resize->id, "paused");
				$cd->set_state($cd_id, "start_resize");
				break;

			case 2:
				// start_resize
				// set the cloudimage to state resize
				$cloud_app_resize = new cloudappliance();
				$cloud_app_resize->get_instance_by_id($cd_appliance_id);
				$appliance = new appliance();
				$appliance->get_instance_by_id($cloud_app_resize->appliance_id);
				$cloud_im = new cloudimage();
				$cloud_im->get_instance_by_image_id($appliance->imageid);
				// make sure that we wait until the cloud image has no resource,
				// otherwise we risk doing things while the volume is still in use.
				if($cloud_im->resource_id == -1) {
					$cloud_im->set_state($cloud_im->id, "resizing");
					$cd->set_state($cd_id, "resizing");
					// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudirlc", "(START_RESIZE) Resize life-cycle of Appliance $cd_appliance_id", "", "", 0, 0, 0);
				}
				break;

			case 3:
				// resizing
				// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudirlc", "(RESIZING) Resize life-cycle of Appliance $cd_appliance_id", "", "", 0, 0, 0);
				// remove any existing image-authentication to avoid kicking the auth into the resize phase
				$cloud_app_resize = new cloudappliance();
				$cloud_app_resize->get_instance_by_id($cd_appliance_id);
				$appliance = new appliance();
				$appliance->get_instance_by_id($cloud_app_resize->appliance_id);
				$image_auth = new image_authentication();
				$image_auth->get_instance_by_image_id($appliance->imageid);
				$image_auth->remove($image_auth->id);
				$cd->set_state($cd_id, "end_resize");
				break;

		   case 4:
				// end_resize
				// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudirlc", "(END_RESIZE) Resize life-cycle of Appliance $cd_appliance_id", "", "", 0, 0, 0);
				$cd->set_state($cd_id, "unpause");
				break;

			case 5:
				// unpause
				// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudirlc", "(UNPAUSE) Resize life-cycle of Appliance $cd_appliance_id", "", "", 0, 0, 0);
				// unpause appliance
				$cloud_app_resize = new cloudappliance();
				$cloud_app_resize->get_instance_by_id($cd_appliance_id);
				$cloud_app_resize->set_cmd($cloud_app_resize->id, "start");
				$cloud_app_resize->set_state($cloud_app_resize->id, "active");
				// set new disk size in cloudimage
				$appliance = new appliance();
				$appliance->get_instance_by_id($cloud_app_resize->appliance_id);
				$cloud_im = new cloudimage();
				$cloud_im->get_instance_by_image_id($appliance->imageid);
				$ar_cl_image_update = array(
					'ci_disk_size' => $cloud_im->disk_rsize,
					'ci_disk_rsize' => "",
				);
				$cloud_im->update($cloud_im->id, $ar_cl_image_update);
				$cd->set_state($cd_id, "remove");
				break;
		}

		// check if we continue or go on
		$parallel_phase_six_actions++;
		if ($max_parallel_phase_six_actions > 0 && $parallel_phase_six_actions >= $max_parallel_phase_six_actions) {
			break;
		}

	}
	// ##################### end cloudimage-resize-life-cycle ##################



	// ##################### start cloudimage-private-life-cycle ################
	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "monitor-hook", "Cloud Phase VII - Cloud Image private live-cycle", "", "", 0, 0, 0);

	$max_clone_time = 4800;

	$ciplc = new cloudiplc();
	$ciplc_list = $ciplc->get_all_ids();

	foreach($ciplc_list as $cplist) {
		$cp_id = $cplist['cp_id'];
		$cp = new cloudiplc();
		$cp->get_instance_by_id($cp_id);
		$cp_appliance_id = $cp->appliance_id;
		$cp_state = $cp->state;

		switch ($cp_state) {
			case 0:
				// remove
				$cp->remove($cp_id);
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudiplc", "(REMOVE) Private life-cycle of Appliance $cp_appliance_id", "", "", 0, 0, 0);
				break;

			case 1:
				// pause
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudiplc", "(PAUSE) Private life-cycle of Appliance $cp_appliance_id", "", "", 0, 0, 0);
				$cloud_app_private = new cloudappliance();
				$cloud_app_private->get_instance_by_id($cp_appliance_id);
				$cloud_app_private->set_cmd($cloud_app_private->id, "stop");
				$cloud_app_private->set_state($cloud_app_private->id, "paused");
				$cp->set_state($cp_id, "start_private");
				break;

			case 2:
				// start_private
				// set the cloudimage to state resize
				$cloud_app_private = new cloudappliance();
				$cloud_app_private->get_instance_by_id($cp_appliance_id);
				$appliance = new appliance();
				$appliance->get_instance_by_id($cloud_app_private->appliance_id);
				$cloud_im = new cloudimage();
				$cloud_im->get_instance_by_image_id($appliance->imageid);
				// make sure that we wait until the cloud image has no resource,
				// otherwise we risk doing things while the volume is still in use.
				if($cloud_im->resource_id == -1) {
					$cloud_im->set_state($cloud_im->id, "private");
					$cp->set_state($cp_id, "cloning");
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudiplc", "(START_PRIVATE) Private life-cycle of Appliance $cp_appliance_id", "", "", 0, 0, 0);
				}
				break;

			case 3:
				// cloning
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudiplc", "(CLONING) Private life-cycle of Appliance $cp_appliance_id", "", "", 0, 0, 0);
				// remove any existing image-authentication to avoid kicking the auth into the private phase
				$cloud_app_private = new cloudappliance();
				$cloud_app_private->get_instance_by_id($cp_appliance_id);
				$appliance = new appliance();
				$appliance->get_instance_by_id($cloud_app_private->appliance_id);
				$image_auth = new image_authentication();
				$image_auth->get_instance_by_image_id($appliance->imageid);
				$image_auth->remove($image_auth->id);
				$cp->set_state($cp_id, "end_private");
				break;

		   case 4:
				// end_private
				// check timeout
				$start_private = $cp->start_private;
				$current_time = $_SERVER['REQUEST_TIME'];
				$private_runtime = $current_time - $start_private;
				// check notifcation from storage
				// get the cloudappliance
				$cloud_app_private = new cloudappliance();
				$cloud_app_private->get_instance_by_id($cp_appliance_id);
				// get the real appliance
				$appliance = new appliance();
				$appliance->get_instance_by_id($cloud_app_private->appliance_id);
				// get the cloudimage
				$cloud_im = new cloudimage();
				$cloud_im->get_instance_by_image_id($appliance->imageid);
				// get image_id
				$pimage = new image();
				$pimage->get_instance_by_name($cloud_im->clone_name);
				// get deployment type
				$pdeployment = new deployment();
				if (strlen($pimage->type)) {
					$pdeployment->get_instance_by_type($pimage->type);
				}
				// notification filename
				$clone_notification_file = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/plugins/'.$pdeployment->storagetype.'/storage/'.$cloud_im->clone_name.'.clone';

				// start checking
				if ($private_runtime > $max_clone_time) {
					// ran too long
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 3, "cloudiplc", "(END_PRIVATE) Time-out private life-cycle of Appliance $cp_appliance_id", "", "", 0, 0, 0);
					$cp->set_state($cp_id, "unpause");
				} else if (file_exists($clone_notification_file)) {
					// got notification from storage server
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudiplc", "(END_PRIVATE) Got notified to finsish private life-cycle of Appliance $cp_appliance_id", "", "", 0, 0, 0);
					unlink($clone_notification_file);
					$cp->set_state($cp_id, "unpause");
				}
				break;

			case 5:
				// unpause
				$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudiplc", "(UNPAUSE) Private life-cycle of Appliance $cp_appliance_id", "", "", 0, 0, 0);
				// get the cloudappliance
				$cloud_app_private = new cloudappliance();
				$cloud_app_private->get_instance_by_id($cp_appliance_id);
				// get the real appliance
				$appliance = new appliance();
				$appliance->get_instance_by_id($cloud_app_private->appliance_id);
				// get the cloudimage
				$cloud_im = new cloudimage();
				$cloud_im->get_instance_by_image_id($appliance->imageid);
				// here we create the private cloud image in openQRM after the clone procedure
				$private_cloud_image = new cloudprivateimage();
				// get image_id
				$pimage = new image();
				$pimage->get_instance_by_name($cloud_im->clone_name);
				// get cu_id
				$crequest = new cloudrequest();
				$crequest->get_instance_by_id($cloud_app_private->cr_id);
				$cuser = new clouduser();
				$cuser->get_instance_by_id($crequest->cu_id);
				// create array for add
				$private_cloud_image_fields["co_id"]=(int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
				$private_cloud_image_fields["co_image_id"] = $pimage->id;
				$private_cloud_image_fields["co_cu_id"] = $cuser->id;
				$private_cloud_image_fields["co_state"] = 1;
				$private_cloud_image->add($private_cloud_image_fields);

				// unpause appliance
				$cloud_app_private->set_cmd($cloud_app_private->id, "start");
				$cloud_app_private->set_state($cloud_app_private->id, "active");

				// array for updating the cloudimage
				$ar_cl_image_update = array(
					'ci_disk_rsize' => "",
					'ci_clone_name' => "",
				);
				$cloud_im->update($cloud_im->id, $ar_cl_image_update);
				$cp->set_state($cp_id, "remove");
				break;
		}

		// check if we continue or go on
		$parallel_phase_seven_actions++;
		if ($max_parallel_phase_seven_actions > 0 && $parallel_phase_seven_actions >= $max_parallel_phase_seven_actions) {
			break;
		}

	}
	// ##################### end cloudimage-private-life-cycle ##################


	// ##################### checking for power-saving ##################
	$cloudpowersaver = new cloudpowersaver();
	$cloudpowersaver->trigger();

	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloud-monitor", "Removing the cloud-monitor lock", "", "", 0, 0, 0);
	unlink($cloud_monitor_lock);
}


// DEBUG
// openqrm_cloud_monitor();




?>
