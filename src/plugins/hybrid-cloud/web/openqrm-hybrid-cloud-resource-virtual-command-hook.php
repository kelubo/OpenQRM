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
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/openqrm-server-config.php";
# class for the hybrid cloud accounts
require_once $RootDir."/plugins/hybrid-cloud/class/hybrid-cloud.class.php";

global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
global $OPENQRM_SERVER_IP_ADDRESS;
$event = new event();
global $event;



function openqrm_hybrid_cloud_resource_virtual_command($cmd, $resource_fields) {
	global $event;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;

	$resource_id = $resource_fields["resource_id"];
	$resource = new resource();
	$resource->get_instance_by_id($resource_id);
	$virtualization = new virtualization();
	$virtualization->get_instance_by_id($resource->vtype);


	switch($virtualization->type) {
		case "hybrid-cloud":
			$event->log("openqrm_hybrid_cloud_resource_virtual_command", $_SERVER['REQUEST_TIME'], 5, "openqrm-hybrid-cloud-resource-virtual-command-hook.php", "Handling ".$cmd." command of resource ".$resource->id, "", "", 0, 0, 0);
			// noop
			break;

		case "hybrid-cloud-vm-local":
			$event->log("openqrm_hybrid_cloud_resource_virtual_command", $_SERVER['REQUEST_TIME'], 5, "openqrm-hybrid-cloud-resource-virtual-command-hook.php", "Handling ".$cmd." command of resource ".$resource->id, "", "", 0, 0, 0);
			$openqrm_server = new openqrm_server();

			// get hybrid-cloud account
			$hybrid_cloud_acl_id = $resource->get_resource_capabilities("HCACL");
			if ($hybrid_cloud_acl_id == '') {
				$event->log("openqrm_hybrid_cloud_resource_virtual_command", $_SERVER['REQUEST_TIME'], 2, "openqrm-hybrid-cloud-resource-virtual-command-hook.php", "Could not find Hybrid-Cloud Account for resource ".$resource->id, "", "", 0, 0, $appliance_id);
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


			switch($cmd) {
				case "reboot":
					$command = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm restart ";
					$command .= ' -i '.$hc->id;
					$command .= ' -n '.$hc->account_name;
					$command .= ' -t '.$hc->account_type;
					$command .= ' -in '.$resource->hostname;
					$command .= $hc_authentication;
					$command .= ' --openqrm-cmd-mode background';
					$openqrm_server->send_command($command, NULL, true);
					break;
				case "halt":
					$command = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm stop ";
					$command .= ' -i '.$hc->id;
					$command .= ' -n '.$hc->account_name;
					$command .= ' -t '.$hc->account_type;
					$command .= ' -in '.$resource->hostname;
					$command .= $hc_authentication;
					$command .= ' --openqrm-cmd-mode background';
					$openqrm_server->send_command($command, NULL, true);
					break;
			}
			break;


	}

}



?>