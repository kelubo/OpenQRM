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
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

// filter inputs
require_once $RootDir.'/class/htmlobjects/htmlobject.class.php';
require_once $RootDir.'/include/requestfilter.inc.php';
$html = new htmlobject($RootDir.'/class/htmlobjects/');
$request = $html->request();
$request->filter = $requestfilter;

$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;

$cloud_product_hook = $RootDir.'/plugins/hybrid-cloud/openqrm-hybrid-cloud-cloud-product-hook.php';
$cloud_selector_class = $RootDir.'/plugins/cloud/class/cloudselector.class.php';
$cloud_usergroup_class = $RootDir.'/plugins/cloud/class/cloudusergroup.class.php';

// user/role authentication
if ($OPENQRM_USER->role != "administrator") {
	$event->log("authorization", $_SERVER['REQUEST_TIME'], 1, "hybrid-cloud-action", "Un-Authorized access to hybrid-cloud-actions from $OPENQRM_USER->name", "", "", 0, 0, 0);
	exit();
}
// get command
$hybrid_cloud_command = $request->get('hybrid_cloud_command');

// main
$event->log("$hybrid_cloud_command", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-action", "Processing hybrid-cloud command $hybrid_cloud_command", "", "", 0, 0, 0);
switch ($hybrid_cloud_command) {

	case 'init':
		// this command creates the following table
		// -> hybrid_cloud_accounts
		// hybrid_cloud_id BIGINT
		// hybrid_cloud_account_name VARCHAR(50)
		// hybrid_cloud_account_type VARCHAR(50)
		// hybrid_cloud_access_key VARCHAR(255)
		// hybrid_cloud_secret_key VARCHAR(255)
		// hybrid_cloud_username VARCHAR(255)
		// hybrid_cloud_password VARCHAR(255)
		// hybrid_cloud_host VARCHAR(255)
		// hybrid_cloud_port VARCHAR(255)
		// hybrid_cloud_tenant VARCHAR(255)
		// hybrid_cloud_endpoint VARCHAR(255)

		// hybrid_cloud_description VARCHAR(255)
		$create_hybrid_cloud_table = "create table hybrid_cloud_accounts(hybrid_cloud_id BIGINT, hybrid_cloud_account_name VARCHAR(50), hybrid_cloud_account_type VARCHAR(50), hybrid_cloud_access_key VARCHAR(255), hybrid_cloud_secret_key VARCHAR(255), hybrid_cloud_username VARCHAR(255), hybrid_cloud_password VARCHAR(255), hybrid_cloud_host VARCHAR(255), hybrid_cloud_port VARCHAR(255), hybrid_cloud_tenant VARCHAR(255), hybrid_cloud_endpoint VARCHAR(255), hybrid_cloud_description VARCHAR(255))";
		$db=openqrm_get_db_connection();
		$recordSet = $db->Execute($create_hybrid_cloud_table);
		$db->Close();

		// auto create Cloud Host Server and Storage
		$virtualization = new virtualization();
		$virtualization->get_instance_by_type("hybrid-cloud");
		$appliance = new appliance();
		$fields['appliance_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
		$fields['appliance_name'] = 'cloud-host-manager';
		$fields['appliance_resources'] = '0';
		$fields['appliance_kernelid'] = '0';
		$fields['appliance_imageid'] = '0';
		$fields["appliance_virtual"]= 0;
		$fields["appliance_virtualization"]=$virtualization->id;
		$fields['appliance_comment'] = 'Hybrid-Cloud Host Manager';
		$appliance->add_no_hook($fields);
		$event->log("init", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-ation", "Created Cloud Host Manager Object.", "", "", 0, 0, 0);

		$deployment = new deployment();
		$deployment->get_instance_by_name('ami-deployment');
		$storage = new storage();
		$new_hc_id=(int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
		$storage_fields['storage_id']=$new_hc_id;
		$storage_fields['storage_name']='ami-image-storage';
		$storage_fields['storage_type']=$deployment->id;
		$storage_fields['storage_comment']='Hybrid-Cloud AMI Image Storage Object';
		$storage_fields['storage_resource_id']=0;
		$storage_fields['storage_capabilities'] = '';
		$storage->add($storage_fields);
		$event->log("init", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-ation", "Created AMI Image Storage Object.", "", "", 0, 0, 0);
		
		// add cloud products
		if (file_exists($cloud_usergroup_class)) {
			require_once $cloud_usergroup_class;
			$cloud_project = new cloudusergroup();
			$cloud_project->get_instance_by_name('Admin');
			if (file_exists($cloud_selector_class)) {
				if (file_exists($cloud_product_hook)) {
					$cloud_hook_config = array();
					$cloud_hook_config['cloud_admin_procect'] = $cloud_project->id;
					require_once $cloud_product_hook;
					openqrm_hybrid_cloud_cloud_product("add", $cloud_hook_config);
				}
			}
		}
		
		break;

	case 'uninstall':
		$drop_hybrid_cloud_table = "drop table hybrid_cloud_accounts";
		$db=openqrm_get_db_connection();
		$recordSet = $db->Execute($drop_hybrid_cloud_table);
		$db->Close();

		// remove Cloud Host Server and Storage
		$virtualization = new virtualization();
		$virtualization->get_instance_by_type("hybrid-cloud");
		$appliance = new appliance();
		$appliance->get_instance_by_virtualization_and_resource($virtualization->id, '0');
		if (strlen($appliance->id)) {
			$appliance->remove($appliance->id);
		}

		$deployment = new deployment();
		$deployment->get_instance_by_name('ami-deployment');
		$storage = new storage();
		$hc_id_list = $storage->get_ids_by_storage_type($deployment->id);
		$found_hc = false;
		$found_hc_id = -1;
		foreach ($hc_id_list as $list) {
			foreach ($list as $hc_id) {
				$storage->get_instance_by_id($hc_id);
				if ($storage->resource_id == 0) {
					$found_hc = true;
					$found_hc_id = $storage->id;
					break;
				}
			}
		}
		if ($found_hc) {
			// remove all AMI Images, then the storage
			$image = new image();
			$hc_image_list = $image->get_ids_by_storage($found_hc_id);
			foreach ($hc_image_list as $list) {
				foreach ($list as $hc_image_id) {
					$image->remove($hc_image_id);
					$event->log("init", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-ation", "Removed AMI Image Object ".$hc_image_id.".", "", "", 0, 0, 0);
				}
			}
			$storage->remove($found_hc_id);
		}

		// remove cloud products
		if (file_exists($cloud_usergroup_class)) {
			require_once $cloud_usergroup_class;
			$cloud_project = new cloudusergroup();
			$cloud_project->get_instance_by_name('Admin');
			if (file_exists($cloud_selector_class)) {
				if (file_exists($cloud_product_hook)) {
					$cloud_hook_config = array();
					$cloud_hook_config['cloud_admin_procect'] = $cloud_project->id;
					require_once $cloud_product_hook;
					openqrm_hybrid_cloud_cloud_product("remove", $cloud_hook_config);
				}
			}
		}
		break;


	default:
		$event->log("$hybrid_cloud_command", $_SERVER['REQUEST_TIME'], 3, "hybrid-cloud-action", "No such event command ($hybrid_cloud_command)", "", "", 0, 0, 0);
		break;


}

?>
