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


// This file implements the virtual machine abstraction in the cloud of openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
# class for the hybrid cloud accounts
require_once $RootDir."/plugins/hybrid-cloud/class/hybrid-cloud.class.php";

$event = new event();
global $event;

global $OPENQRM_SERVER_BASE_DIR;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $RESOURCE_INFO_TABLE;

// ---------------------------------------------------------------------------------
// general hybrid-cloud cloudvm methods
// ---------------------------------------------------------------------------------


// creates a vm 
function create_hybrid_cloud_vm_local($host_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $event;
	$max_loop = 30;
	$ip_wait_loop = 0;

	$event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-cloud-hook", "Creating Cloud VM $name on Host resource $host_resource_id", "", "", 0, 0, 0);
	
	$file = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/etc/openqrm-plugin-hybrid-cloud.conf";
	$ini = openqrm_parse_conf($file);
	$hc_default_account_id = $ini['OPENQRM_PLUGIN_HYBRID_CLOUD_DEFAULT_ACCOUNT'];
	$hc = new hybrid_cloud();
	$hc->get_instance_by_id($hc_default_account_id);

	$hc_authentication = '';
	if (($hc->account_type == 'aws') || ($hc->account_type == 'euca')) {
		$hc_authentication .= ' -O '.$hc->access_key;
		$hc_authentication .= ' -W '.$hc->secret_key;
		$hc_authentication .= ' -iz '.$ini['OPENQRM_PLUGIN_HYBRID_CLOUD_DEFAULT_AVAILABILITY_ZONE'];
		$hc_authentication .= ' -ir '.$ini['OPENQRM_PLUGIN_HYBRID_CLOUD_DEFAULT_REGION'];
		$hc_authentication .= ' -it '.$hc->translate_resource_components_to_instance_type($cpu, $memory);
	}
	if ($hc->account_type == 'lc-openstack') {
		$hc_authentication .= ' -u '.$hc->username;
		$hc_authentication .= ' -p '.$hc->password;
		$hc_authentication .= ' -q '.$hc->host;
		$hc_authentication .= ' -x '.$hc->port;
		$hc_authentication .= ' -g '.$hc->tenant;
		$hc_authentication .= ' -e '.$hc->endpoint;
		$hc_authentication .= ' -it '.$hc->translate_resource_components_to_instance_type($cpu, $memory);
	}

	// set hybrid-cloud account in new resource
	$cloud_resource = new resource();
	$cloud_resource->get_instance_by_mac($mac);
	$cloud_resource->set_resource_capabilities("HCACL", $hc_default_account_id);

	// $event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-cloud-hook", "!!! Found new resource ".$cloud_resource->id." and set HC account id", "", "", 0, 0, 0);

	// set resource hostname + ip config
	// check to have a ip from the dhcpd-resource hook

	while ($cloud_resource->ip == "0.0.0.0") {
		sleep(1);
		clearstatcache();
		$cloud_resource->get_instance_by_mac($cloud_resource->mac);
		$ip_wait_loop++;
		if ($ip_wait_loop > $max_loop) {
			break;
		}
		// $event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-cloud-hook", "!!! Waiting for ip address ".$ip_wait_loop."!", "", "", 0, 0, 0);
	}
	// save the mgmt ip in the resource network field
	$rufields["resource_network"] = $cloud_resource->ip;
	$rufields["resource_hostname"] = $hc->account_type.$cloud_resource->id;
	$cloud_resource->update_info($cloud_resource->id, $rufields);

	// $event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-cloud-hook", "!!! Updated resource with hostname + ip", "", "", 0, 0, 0);

	// we need to find the ami name from the appliance->image->root-device
	$appliance = new appliance();
	$appliance->get_instance_by_name($name);
	$image = new image();
	$image->get_instance_by_id($appliance->imageid);

	// $event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-cloud-hook", "!!! Found AMI ".$image->name." root-dev: ".$image->rootdevice, "", "", 0, 0, 0);
	$openqrm = new openqrm_server();
	$command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm create ";
	$command .= ' -i '.$hc->id;
	$command .= ' -n '.$hc->account_name;
	$command .= ' -t '.$hc->account_type;
	$command .= $hc_authentication;
	$command .= ' -in '.$hc->account_type.$cloud_resource->id;
	$command .= ' -im '.$mac;
	$command .= ' -a '.$image->rootdevice;
	$command .= ' -ig '.$ini['OPENQRM_PLUGIN_HYBRID_CLOUD_DEFAULT_SECURITY_GROUP'];
	$command .= ' -ik '.$ini['OPENQRM_PLUGIN_HYBRID_CLOUD_DEFAULT_KEYPAIR'];
	$command .= ' --openqrm-cmd-mode background';
	// $event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-cloud-hook", "Running $command", "", "", 0, 0, 0);

	$openqrm->send_command($command, NULL, true);
}



// removes a vm
function remove_hybrid_cloud_vm_local($host_resource_id, $name, $mac) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $event;

	$event->log("remove_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-cloud-hook", "Removing Cloud VM $name/$mac from Host resource $host_resource_id", "", "", 0, 0, 0);
	$openqrm = new openqrm_server();

	$cloud_resource = new resource();
	$cloud_resource->get_instance_by_mac($mac);
	$hc_default_account_id = $cloud_resource->get_resource_capabilities("HCACL");
	$cloud_resource_hostname = $cloud_resource->hostname;

	if (strlen($hc_default_account_id)) {
	// $event->log("remove_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-cloud-hook", "!!! found Cloud Account ".$hc_default_account_id." from resource config", "", "", 0, 0, 0);
	} else {
		$file = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/etc/openqrm-plugin-hybrid-cloud.conf";
		$ini = openqrm_parse_conf($file);
		$hc_default_account_id = $ini['OPENQRM_PLUGIN_HYBRID_CLOUD_DEFAULT_ACCOUNT'];
		// $event->log("remove_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-cloud-hook", "!!! got Cloud Account ".$hc_default_account_id." from plugin config", "", "", 0, 0, 0);
	}
	$hc = new hybrid_cloud();
	$hc->get_instance_by_id($hc_default_account_id);

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


	$openqrm = new openqrm_server();
	$command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm remove ";
	$command .= ' -i '.$hc->id;
	$command .= ' -n '.$hc->account_name;
	$command .= ' -t '.$hc->account_type;
	$command .= $hc_authentication;
	$command .= ' -in '.$cloud_resource_hostname;
	$command .= ' --openqrm-cmd-mode background';
	// $event->log("remove_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-cloud-hook", "Running $command", "", "", 0, 0, 0);
	$openqrm->send_command($command, NULL, true);
}



// ---------------------------------------------------------------------------------


?>