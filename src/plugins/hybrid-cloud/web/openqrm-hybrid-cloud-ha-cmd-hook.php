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
require_once $RootDir."/include/openqrm-database-functions.php";
require_once $RootDir."/class/resource.class.php";
require_once $RootDir."/class/appliance.class.php";
require_once $RootDir."/class/virtualization.class.php";
require_once $RootDir."/class/openqrm_server.class.php";
require_once $RootDir."/class/plugin.class.php";
require_once $RootDir."/class/event.class.php";
require_once $RootDir.'/plugins/hybrid-cloud/class/hybrid-cloud.class.php';

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
function create_hybrid_cloud_vm_local($host_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str, $origin_resource_id) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $event;
	global $RootDir;
	$event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-ha-hook", "Creating Cloud VM $name on Host resource $host_resource_id", "", "", 0, 0, 0);
	$file = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/etc/openqrm-plugin-hybrid-cloud.conf";
	$ini = openqrm_parse_conf($file);

	$origin_resource = new resource();
	$origin_resource->get_instance_by_id($origin_resource_id);
	// get hybrid-cloud account from origin resource
	$hybrid_acl_id = $origin_resource->get_resource_capabilities("HCACL");
	$hc = new hybrid_cloud();
	$hc->get_instance_by_id($hybrid_acl_id);

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

	$statfile = $RootDir."/plugins/hybrid-cloud/hybrid-cloud-stat/".$hc->id.".".$origin_resource->hostname.".ha_configuration.log";
	if(file_exists($statfile)) {
		unlink($statfile);
	}
	$openqrm = new openqrm_server();
	$command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm ha_configuration ";
	$command .= ' -i '.$hc->id;
	$command .= ' -n '.$hc->account_name;
	$command .= ' -t '.$hc->account_type;
	$command .= $hc_authentication;
	$command .= ' -in '.$origin_resource->hostname;
	$command .= ' --openqrm-cmd-mode background';
	$event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-ha-hook", "Running $command", "", "", 0, 0, 0);
	$openqrm->send_command($command, NULL, true);

	// debug
	// echo "1:".$command."<br>";

	while (!file_exists($statfile))	{
	  usleep(10000);
	  clearstatcache();
	}

	$new_vm_config = openqrm_parse_conf($statfile);

	$hc_authentication = '';
	if (($hc->account_type == 'aws') || ($hc->account_type == 'euca')) {
		$hc_authentication .= ' -O '.$hc->access_key;
		$hc_authentication .= ' -W '.$hc->secret_key;
		$hc_authentication .= ' -iz '.$new_vm_config['OPENQRM_HA_AVAILABILITY_ZONE'];
		$hc_authentication .= ' -ir '.$new_vm_config['OPENQRM_HA_REGION'];
		$hc_authentication .= ' -in '.$hc->account_type.$new_resource->id;
	}
	if ($hc->account_type == 'lc-openstack') {
		$hc_authentication .= ' -u '.$hc->username;
		$hc_authentication .= ' -p '.$hc->password;
		$hc_authentication .= ' -q '.$hc->host;
		$hc_authentication .= ' -x '.$hc->port;
		$hc_authentication .= ' -g '.$hc->tenant;
		$hc_authentication .= ' -e '.$hc->endpoint;
		$hc_authentication .= ' -in '.$origin_resource->hostname;
	}

	// set hybrid-cloud account in new resource
	$new_resource = new resource();
	$new_resource->get_instance_by_mac($mac);
	$new_resource->set_resource_capabilities("HCACL", $hybrid_acl_id);

	$openqrm = new openqrm_server();
	$command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm create ";
	$command .= ' -i '.$hc->id;
	$command .= ' -n '.$hc->account_name;
	$command .= ' -t '.$hc->account_type;
	$command .= $hc_authentication;
	$command .= ' -a '.$new_vm_config['OPENQRM_HA_AMI'];
	$command .= ' -it '.$new_vm_config['OPENQRM_HA_TYPE'];
	$command .= ' -ig '.$new_vm_config['OPENQRM_HA_SECURITY_GROUP'];
	$command .= ' -ik '.$new_vm_config['OPENQRM_HA_KEYPAIR'];
	$command .= ' -im '.$new_resource->mac;
	$custom_script_url = $new_vm_config['OPENQRM_HA_CUSTOM_SCRIPT_URL'];
	if (strlen($custom_script_url)) {
		$command .= ' -ic '.$custom_script_url;
	}
	$command .= ' --openqrm-cmd-mode background';
	$event->log("create_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-ha-hook", "Running $command", "", "", 0, 0, 0);
	$openqrm->send_command($command, NULL, true);

	// set resource hostname
	// check to have a ip from the dhcpd-resource hook
	while ($new_resource->ip == "0.0.0.0") {
		sleep(1);
		clearstatcache();
		$new_resource->get_instance_by_mac($new_resource->mac);
	}

	// save the mgmt ip in the resource network field
	$new_resource_hostname = $hc->account_type.$new_resource->id;
	$rufields["resource_network"] = $new_resource->ip;
	if (($hc->account_type == 'aws') || ($hc->account_type == 'euca')) {
		$rufields["resource_hostname"] = $hc->account_type.$new_resource->id;
	}
	if ($hc->account_type == 'lc-openstack') {
		$rufields["resource_hostname"] = $origin_resource->hostname;
	}
	// set resource to idle/active to make the HA hook continue directly
	$rufields["resource_state"] = 'active';
	$new_resource->update_info($new_resource->id, $rufields);

	// debug
	// echo "2:".$command."<br>";
}



// fences a vm
function fence_hybrid_cloud_vm_local($host_resource_id, $mac) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $event;
	
	$event->log("fence_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-ha-hook", "Fencing Cloud VM ".$mac, "", "", 0, 0, 0);
	$origin_resource = new resource();
	$origin_resource->get_instance_by_mac($mac);

	// get hybrid-cloud account from origin resource
	$hybrid_acl_id = $origin_resource->get_resource_capabilities("HCACL");
	$hc = new hybrid_cloud();
	$hc->get_instance_by_id($hybrid_acl_id);

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
	$command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm fence ";
	$command .= ' -i '.$hc->id;
	$command .= ' -n '.$hc->account_name;
	$command .= ' -t '.$hc->account_type;
	$command .= $hc_authentication;
	$command .= ' -im '.$mac;
	$command .= ' --openqrm-cmd-mode background';
	$event->log("fence_hybrid_cloud_vm_local", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-ha-hook", "Running $command", "", "", 0, 0, 0);
	$openqrm->send_command($command, NULL, true);

	$rufields["resource_hostname"] = $hc->account_type.$origin_resource->id;
	$origin_resource->update_info($origin_resource->id, $rufields);

	// debug
	// echo "1:".$command;
}


// debug
// $host_resource_id = "0";
// $name = "aws1";
// $mac = "00:50:68:76:f8:ed";
// $memory = "512";
// $cpu = "1";
// $swap = "0";
// $additional_nic_str = " -m1 00:11:22:33:44";
// $origin_resource_id = 1;
// create_hybrid_cloud_vm_local($host_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str, $origin_resource_id);
// fence_hybrid_cloud_vm_local($host_resource_id, $mac);


// ---------------------------------------------------------------------------------


?>