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

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/openqrm-server-config.php";
# class for the hybrid cloud accounts
require_once $RootDir."/plugins/hybrid-cloud/class/hybrid-cloud.class.php";


global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
global $IMAGE_AUTHENTICATION_TABLE;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $openqrm_server;
$event = new event();
global $event;



function openqrm_hybrid_cloud_appliance($cmd, $appliance_fields) {
	global $event;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $IMAGE_AUTHENTICATION_TABLE;
	global $RootDir;

	$openqrm_server = new openqrm_server();

	$appliance_id=$appliance_fields["appliance_id"];
	$appliance_name=$appliance_fields["appliance_name"];
	$resource = new resource();
	$resource->get_instance_by_id($appliance_fields["appliance_resources"]);
	$appliance_ip=$resource->ip;
	$appliance = new appliance();
	$appliance->get_instance_by_id($appliance_id);
	// check appliance values, maybe we are in update and they are incomplete
	if ($appliance->imageid == 1) {
		return;
	}
	if (($resource->id == "-1") || ($resource->id == "") || (!isset($resource->vtype))) {
		return;
	}

	$event->log("openqrm_hybrid_cloud_appliance", $_SERVER['REQUEST_TIME'], 5, "openqrm-hybrid-cloud-appliance-hook.php", "Handling ".$cmd." event ".$appliance_id."/".$appliance_name."/".$appliance_ip, "", "", 0, 0, $appliance_id);

	// check resource type -> hybrid-cloud-strorage-vm
	$virtualization = new virtualization();
	$virtualization->get_instance_by_id($resource->vtype);

	switch($virtualization->type) {
		case "hybrid-cloud-vm-local":
			$image = new image();
			$image->get_instance_by_id($appliance->imageid);

			// get hybrid-cloud account
			$hybrid_cloud_acl_id = $resource->get_resource_capabilities("HCACL");
			if ($hybrid_cloud_acl_id == '') {
				$event->log("openqrm_hybrid_cloud_appliance", $_SERVER['REQUEST_TIME'], 2, "openqrm-hybrid-cloud-appliance-hook.php", "Could not find Hybrid-Cloud Account for resource ".$resource->id, "", "", 0, 0, $appliance_id);
				return;
			}
			$hc = new hybrid_cloud();
			$hc->get_instance_by_id($hybrid_cloud_acl_id);
			$hc_authentication = '';
			if (($hc->account_type == 'aws') || ($hc->account_type == 'euca')) {
				$hc_authentication .= ' -O '.$hc->access_key;
				$hc_authentication .= ' -W '.$hc->secret_key;
			}
			if ($hc->account_type == 'lc-openstack') {
				$hc_authentication .= ' -u '.$hc->username;
				$hc_authentication .= ' -p '.$hc->password;
				$hc_authentication .= ' -q '.$hc->host;
				$hc_authentication .= ' -x '.$hc->port;
				$hc_authentication .= ' -g '.$hc->tenant;
				$hc_authentication .= ' -e '.$hc->endpoint;
			}

			$statfile = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/web/hybrid-cloud-stat/".$hybrid_cloud_acl_id.".run_instances.hostname";

			switch($cmd) {
				case "start":
					// send command to assign image and start instance
					$command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm run ";
					$command .= ' -i '.$hc->id;
					$command .= ' -n '.$hc->account_name;
					$command .= ' -t '.$hc->account_type;
					$command .= $hc_authentication;
					$command .= ' -in '.$resource->hostname;
					$command .= ' -a '.$image->rootdevice;
					$command .= ' -ii '.$image->id;
					$command .= ' -ia '.$appliance->name;
					$command .= ' --openqrm-cmd-mode background';
					// wait for hostname statfile
					if (file_exists($statfile))	{
						unlink($statfile);
					}
					$openqrm_server->send_command($command, NULL, true);
					while (!file_exists($statfile))	{
					  usleep(10000);
					  clearstatcache();
					}
					// special hostname handling for aws + euca
					if (($hc->account_type == 'aws') || ($hc->account_type == 'euca')) {
						$resource_new_hostname = file_get_contents($statfile);
						$resource_new_hostname = trim($resource_new_hostname);
						unlink($statfile);
						// update hostname in resource
						$resource_fields["resource_hostname"]=$resource_new_hostname;
						$resource->update_info($resource->id, $resource_fields);
					}
					// reset image_isactive -> AMI are cloned anyway
					$image->set_active(1);
					break;

				case "stop":
					// send command to stop the vm and deassign image
					$command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm terminate ";
					$command .= ' -i '.$hc->id;
					$command .= ' -n '.$hc->account_name;
					$command .= ' -t '.$hc->account_type;
					$command .= $hc_authentication;
					$command .= ' -in '.$resource->hostname;
					$command .= ' --openqrm-cmd-mode background';
					$openqrm_server->send_command($command, NULL, true);

					// special hostname handling for aws + euca
					if (($hc->account_type == 'aws') || ($hc->account_type == 'euca')) {
						$resource_new_hostname = $hc->account_type.$resource->id;
						// update hostname in resource
						$resource_fields["resource_hostname"]=$resource_new_hostname;
						$resource->update_info($resource->id, $resource_fields);
					}
					break;
			}
			break;
	}
}



?>


