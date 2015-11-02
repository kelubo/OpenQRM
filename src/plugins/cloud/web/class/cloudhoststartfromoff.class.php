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


// This class represents a physical system starting-from-poweroff in the cloud of openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
// special cloud classes
if(class_exists('clouduser') === false) {
	require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
}
if(class_exists('cloudusergroup') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudusergroup.class.php";
}
if(class_exists('cloudconfig') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
}
require_once "$RootDir/plugins/cloud/class/cloudrespool.class.php";
require_once "$RootDir/plugins/cloud/class/cloudhostlimit.class.php";

$event = new event();
global $event;

global $OPENQRM_SERVER_BASE_DIR;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $RESOURCE_INFO_TABLE;



class cloudhoststartfromoff {

var $resource_id = '';
var $timeout = '';


function init($timeout) {
	$this->resource_id=0;
	$this->timeout=$timeout;
}

// ---------------------------------------------------------------------------------
// general cloudhoststartfromoff methods
// ---------------------------------------------------------------------------------



// searches for a virtualization host which can be started from power-off
function find_host_to_start_from_off($virtualization_type_id, $resource_pools_enabled, $cu_id, $timeout) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $vmware_mac_address_space;
	global $RootDir;
	$this->init($timeout);
	global $event;
	// find out the host virtualization type via the plugin name
	$vhost_type = new virtualization();
	$vhost_type->get_instance_by_id($virtualization_type_id);
	$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 5, "cloudhoststartfromoff.class.php", "Trying to find a powered-off virtualization host from type $vhost_type->type $vhost_type->name", "", "", 0, 0, 0);

	// for all in appliance list, find virtualization host appliances
	$appliance_tmp = new appliance();
	$appliance_id_list = $appliance_tmp->get_all_ids();
	$in_active_appliance_list = array();
	foreach($appliance_id_list as $id_arr) {
		foreach($id_arr as $id) {
			$appliance = new appliance();
			$appliance->get_instance_by_id($id);
			$sfo_resource = new resource();
			$sfo_resource->get_instance_by_id($appliance->resources);
			// state off ?
			if ((!strcmp($appliance->state, "stopped")) && (!strcmp($sfo_resource->state, "off"))) {
				if ($appliance->virtualization == $virtualization_type_id) {
					// we have found an active appliance from the right virtualization type
					//
					// here we check if there is still enough space
					// to create the new vm -> max_vm setting per resource
					$res_hostlimit = new cloudhostlimit();
					$res_hostlimit->get_instance_by_resource($appliance->resources);
					if (strlen($res_hostlimit->id)) {
						if ($res_hostlimit->max_vms >= 0) {
							$new_current_vms = $res_hostlimit->current_vms + 1;
							if ($new_current_vms >= $res_hostlimit->max_vms) {
								$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Hostlimit max_vm is reached for resource $appliance->resources", "", "", 0, 0, $appliance->resources);
								continue;
							}
						}
					}
					// resource pooling enabled ?
					if (strcmp($resource_pools_enabled, "true")) {
						// disabled, add any appliance from the right virtualization type
						// $event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 5, "cloudhoststartfromoff.class.php", "resource pooling is disabled", "", "", 0, 0, 0);
						// check if the resource can start-from-off
						$can_start_from_off = $sfo_resource->get_resource_capabilities('SFO');
						if ($can_start_from_off == 1) {
							$in_active_appliance_list[] .= $id;
							$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 5, "cloudhoststartfromoff.class.php", "Resource pooling is disabled, adding appliance $id", "", "", 0, 0, 0);
						} else {
							$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Resource pooling is disabled, resource of appliance $id cannot start-from-off", "", "", 0, 0, 0);
						}
					} else {
						// $event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "resource pooling is enabled $appliance->resources", "", "", 0, 0, 0);
						// resource pooling enabled, check to which user group the resource belongs to
						$private_resource = new cloudrespool();
						$private_resource->get_instance_by_resource($appliance->resources);
						// is this resource configured in the resource pools ?
						// $event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "resource pool id $private_resource->id ", "", "", 0, 0, 0);
						if (strlen($private_resource->id)) {
							// $event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "resource $appliance->resources is in a resource pool", "", "", 0, 0, 0);
							// is it hidden ?
							if ($private_resource->cg_id >= 0) {
								// $event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "resource $appliance->resources is also configured in resource pool (not hidden)", "", "", 0, 0, 0);
								$cloud_user = new clouduser();
								$cloud_user->get_instance_by_id($cu_id);
								$cloud_user_group = new cloudusergroup();
								$cloud_user_group->get_instance_by_id($cloud_user->cg_id);
								// $event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "we have found the users group $cloud_user_group->id", "", "", 0, 0, 0);
								// does it really belongs to the users group ?
								if ($private_resource->cg_id == $cloud_user_group->id) {
									// resource belongs to the users group, add appliance to list

									// check if the resource can start-from-off
									$sfo_resource = new resource();
									$sfo_resource->get_instance_by_id($appliance->resources);
									$can_start_from_off = $sfo_resource->get_resource_capabilities('SFO');
									if ($can_start_from_off == 1) {
										$in_active_appliance_list[] .= $id;
										$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 5, "cloudhoststartfromoff.class.php", "Adding appliance $id", "", "", 0, 0, 0);
									} else {
										$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Resource of appliance $id cannot start-from-off", "", "", 0, 0, 0);
									}
								} else {
									$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Appliance $id (resource $appliance->resources) is NOT in dedicated for the users group", "", "", 0, 0, 0);
								}
							} else {
								$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Appliance $id (resource $appliance->resources) is marked as hidden", "", "", 0, 0, 0);
							}
						} else {
							$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Appliance $id (resource $appliance->resources) is NOT member of any resource pools", "", "", 0, 0, 0);
						}
					}
				}
			}
		}
	}

	// did we found any active host ?
	if (count($in_active_appliance_list) < 1) {
		$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Warning ! There is no virtualization host type $vhost_type->name available to start-from-off", "", "", 0, 0, 0);
		$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Warning : Giving up to start a virtualization host type $vhost_type->name from power-off state .....", "", "", 0, 0, 0);
		return 0;
	}
	// simply take the first one
	foreach($in_active_appliance_list as $in_active_id) {
		$in_active_appliance = new appliance();
		$in_active_appliance->get_instance_by_id($in_active_id);
		break;
	}

	// simply start the appliance, the rest will be done by the appliance start hook sending power-on
	// monitor until it is fully up or timeout
	$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 5, "cloudhoststartfromoff.class.php", "Notice: Starting host appliance $in_active_id, waiting until it is fully active ...", "", "", 0, 0, 0);
	$in_active_appliance->start();

	// check until it is full up
	$in_active_resource = new resource();

	$sec_loops = 0;
	while (0 == 0) {
		echo " ";
		flush();
		sleep(2);
		$sec_loops++;
		$sec_loops++;

		// check if the resource is active
		$in_active_resource->get_instance_by_id($in_active_appliance->resources);
		if (!strcmp($in_active_resource->state, "active")) {
			// the host is up :) return the appliance id of the host
			$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 5, "cloudhoststartfromoff.class.php", "Notice: Host resource id $in_active_resource->id successfully started from power-off", "", "", 0, 0, 0);
			return $in_active_id;
		}
		if ($this->timeout <= $sec_loops) {
			$event->log("find_host_to_start_from_off", $_SERVER['REQUEST_TIME'], 2, "cloudhoststartfromoff.class.php", "Error:Timeout while waiting for resource id $in_active_resource->id to start-from-off", "", "", 0, 0, 0);
			return 0;
		}
	}

}




// ---------------------------------------------------------------------------------

}

?>
