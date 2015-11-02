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
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";

$event = new event();
global $event;

global $OPENQRM_SERVER_BASE_DIR;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $RESOURCE_INFO_TABLE;

// ---------------------------------------------------------------------------------
// general kvm cloudvm methods
// ---------------------------------------------------------------------------------


// creates a vm 
function create_kvm_vm($host_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str, $vm_type, $vncpassword) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $event;
	$event->log("create_kvm_vm_local", $_SERVER['REQUEST_TIME'], 5, "kvm-cloud-hook", "Creating KVM VM $name on Host resource $host_resource_id", "", "", 0, 0, 0);
	// start the vm on the host
	$host_resource = new resource();
	$host_resource->get_instance_by_id($host_resource_id);
	// we need to have an openQRM server object too since some of the
	// virtualization commands are sent from openQRM directly
	$openqrm = new openqrm_server();
	$openqrm_admin_user = new user("openqrm");
	$openqrm_admin_user->set_user();
	
	$vncpassword_parameter = "";
	if ($vncpassword != '') {
		$vncpassword_parameter = " -v ".$vncpassword;
	}
	// send command to create vm
	$vm_create_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm/bin/openqrm-kvm-vm create -n ".$name." -y ".$vm_type." -m ".$mac." -r ".$memory." -c ".$cpu." -b local ".$additional_nic_str." ".$vncpassword_parameter." -u ".$openqrm_admin_user->name." -p ".$openqrm_admin_user->password;
	$host_resource->send_command($host_resource->ip, $vm_create_cmd);
	$event->log("create_kvm_vm_local", $_SERVER['REQUEST_TIME'], 5, "kvm-cloud-hook", "Running $vm_create_cmd", "", "", 0, 0, 0);
}



// removes a vm
function remove_kvm_vm($host_resource_id, $name, $mac) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $event;

	// remove the vm from host
	$host_resource = new resource();
	$host_resource->get_instance_by_id($host_resource_id);
	$event->log("remove_kvm_vm_local", $_SERVER['REQUEST_TIME'], 5, "kvm-cloud-hook", "Removing KVM VM $name/$mac from Host resource $host_resource_id", "", "", 0, 0, 0);
	// we need to have an openQRM server object too since some of the
	// virtualization commands are sent from openQRM directly
	$openqrm = new openqrm_server();
	// send command to create the vm on the host
	$vm_remove_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm/bin/openqrm-kvm-vm delete -n ".$name." --openqrm-cmd-mode background";
	$event->log("remove_kvm_vm_local", $_SERVER['REQUEST_TIME'], 5, "kvm-cloud-hook", "Running $vm_remove_cmd", "", "", 0, 0, 0);
	$host_resource->send_command($host_resource->ip, $vm_remove_cmd);
}


// Cloud hook methods

function create_kvm_vm_local($host_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str, $vncpassword) {
	global $event;
	create_kvm_vm($host_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str, "kvm-vm-local", $vncpassword);
}

function create_kvm_vm_net($host_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str, $vncpassword) {
	global $event;
	create_kvm_vm($host_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str, "kvm-vm-net", $vncpassword);
}

function remove_kvm_vm_local($host_resource_id, $name, $mac) {
	global $event;
	remove_kvm_vm($host_resource_id, $name, $mac);
}

function remove_kvm_vm_net($host_resource_id, $name, $mac) {
	global $event;
	remove_kvm_vm($host_resource_id, $name, $mac);
}




// ---------------------------------------------------------------------------------


?>