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
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
#require_once "$RootDir/include/htmlobject.inc.php";
// special cloud classes
require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
require_once "$RootDir/plugins/cloud/class/cloudusergroup.class.php";
require_once "$RootDir/plugins/cloud/class/clouduserslimits.class.php";
require_once "$RootDir/plugins/cloud/class/cloudrequest.class.php";
require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
require_once "$RootDir/plugins/cloud/class/cloudmailer.class.php";

// filter inputs
require_once $RootDir.'/class/htmlobjects/htmlobject.class.php';
require_once $RootDir.'/include/requestfilter.inc.php';
$html = new htmlobject($RootDir.'/class/htmlobjects/');
$request = $html->request();
$request->filter = $requestfilter;

global $CLOUD_USER_TABLE;
global $CLOUD_REQUEST_TABLE;
global $CLOUD_USER_GROUPS_TABLE;
global $OPENQRM_SERVER_BASE_DIR;
$refresh_delay=5;
$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;

$cloud_command = $request->get('cloud_command');

// user/role authentication
if ($OPENQRM_USER->role != "administrator") {
	$event->log("authorization", $_SERVER['REQUEST_TIME'], 1, "cloud-action", "Un-Authorized access to cloud-actions from $OPENQRM_USER->name", "", "", 0, 0, 0);
	exit();
}

// gather user parameter in array
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "cu_", 3) == 0) {
		$user_fields[$key] = $value;
	}
}
// gather user group parameter in array
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "cg_", 3) == 0) {
		$user_group_fields[$key] = $value;
	}
}

// gather request parameter in array
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "cr_", 3) == 0) {
		$request_fields[$key] = $value;
	}
}
// set ha clone-on deploy
// set ha clone-on deploy
$cr_ha_req = $request->get('cr_ha_req');
if (!strcmp($cr_ha_req, "on")) {
	$request_fields['cr_ha_req']=1;
} else {
	$request_fields['cr_ha_req']=0;
}
$cr_shared_req = $request->get('cr_shared_req');
if (!strcmp($cr_shared_req, "on")) {
	$request_fields['cr_shared_req']=1;
} else {
	$request_fields['cr_shared_req']=0;
}

function date_to_timestamp($date) {
	$day = substr($date, 0, 2);
	$month = substr($date, 3, 2);
	$year = substr($date, 6, 4);
	$hour = substr($date, 11, 2);
	$minute = substr($date, 14, 2);
	$sec = 0;
	$timestamp = mktime($hour, $minute, $sec, $month, $day, $year);
	return $timestamp;
}



function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	} else {
		$url = $url.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}


// functions to check user input
function is_allowed($text) {
	for ($i = 0; $i<strlen($text); $i++) {
		if (!ctype_alpha($text[$i])) {
			if (!ctype_digit($text[$i])) {
				if (!ctype_space($text[$i])) {
					return false;
				}
			}
		}
	}
	return true;
}



function check_param($param, $value) {
	global $c_error;
	if (!strlen($value)) {
		$strMsg = "$param is empty <br>";
		$c_error = 1;
		redirect($strMsg, 'tab0', "cloud-user.php");
		exit(0);
	}
	// remove whitespaces
	$value = trim($value);
	// remove any non-violent characters
	$value = str_replace(".", "", $value);
	$value = str_replace(",", "", $value);
	$value = str_replace("-", "", $value);
	$value = str_replace("_", "", $value);
	$value = str_replace("(", "", $value);
	$value = str_replace(")", "", $value);
	$value = str_replace("/", "", $value);
	if(!is_allowed($value)){
		$strMsg = "$param contains special characters <br>";
		$c_error = 1;
		redirect($strMsg, 'tab0', "cloud-user.php");
		exit(0);
	}
}


// main
$event->log("$cloud_command", $_SERVER['REQUEST_TIME'], 5, "cloud-action", "Processing cloud command $cloud_command", "", "", 0, 0, 0);

	switch ($cloud_command) {

		case 'init':
			// this command creates the following tables
			// -> cloudrequests
			// cr_id BIGINT
			// cr_cu_id BIGINT
			// cr_status SMALLINT
			// cr_request_time VARCHAR(20)
			// cr_start VARCHAR(20)
			// cr_stop VARCHAR(20)
			// cr_kernel_id BIGINT
			// cr_image_id BIGINT
			// cr_ram_req VARCHAR(20)
			// cr_cpu_req VARCHAR(20)
			// cr_disk_req VARCHAR(20)
			// cr_network_req VARCHAR(255)
			// cr_resource_quantity SMALLINT
			// cr_resource_type_req VARCHAR(20)
			// cr_deployment_type_req VARCHAR(50)
			// cr_ha_req VARCHAR(5)
			// cr_shared_req VARCHAR(5)
			// cr_puppet_groups VARCHAR(255)
			// cr_ip_mgmt VARCHAR(255)
			// cr_appliance_id VARCHAR(255)
			// cr_appliance_hostname VARCHAR(255)
			// cr_lastbill VARCHAR(20)
			// cr_image_password VARCHAR(255)
			// cr_appliance_capabilities VARCHAR(1000)
			//
			// -> cloudusers
			// cu_id BIGINT
			// cu_cg_id BIGINT
			// cu_name VARCHAR(20)
			// cu_password VARCHAR(50)
			// cu_forename VARCHAR(50)
			// cu_lastname VARCHAR(50)
			// cu_email VARCHAR(50)
			// cu_street VARCHAR(100)
			// cu_city VARCHAR(100)
			// cu_country VARCHAR(100)
			// cu_phone VARCHAR(100)
			// cu_status SMALLINT
			// cu_token VARCHAR(100)
			// cu_ccunits INTEGER
			// cu_lang VARCHAR(4)
			//
			// -> cloudusergroup
			// cg_id BIGINT
			// cg_name VARCHAR(20)
			// cg_role_id BIGINT
			// cg_description VARCHAR(255)
			//
			// -> clouduserslimits
			// cl_id BIGINT
			// cl_cu_id BIGINT
			// cl_resource_limit SMALLINT
			// cl_memory_limit INTEGER
			// cl_disk_limit INTEGER
			// cl_cpu_limit SMALLINT
			// cl_network_limit SMALLINT
			//
			// -> cloudconfig
			// cc_id BIGINT
			// cc_key VARCHAR(50)
			// cc_value VARCHAR(255)

			// -> cloudimage
			// ci_id BIGINT
			// ci_cr_id BIGINT
			// ci_image_id BIGINT
			// ci_appliance_id BIGINT
			// ci_resource_id BIGINT
			// ci_disk_size VARCHAR(20)
			// ci_disk_rsize VARCHAR(20)
			// ci_clone_name VARCHAR(50)
			// ci_state SMALLINT

			// -> cloudappliance
			// ca_id BIGINT
			// ca_appliance_id BIGINT
			// ca_cr_id BIGINT
			// ca_cmd SMALLINT
			// ca_state SMALLINT

			// -> cloudnat
			// cn_id BIGINT
			// cn_internal_net VARCHAR(50)
			// cn_external_net VARCHAR(50)

			// -> cloudtransaction
			// ct_id BIGINT
			// ct_time VARCHAR(50),
			// ct_cr_id BIGINT
			// ct_cu_id BIGINT
			// ct_ccu_charge SMALLINT
			// ct_ccu_balance SMALLINT
			// ct_reason VARCHAR(20)
			// ct_comment VARCHAR(255)

			// -> cloudtransactionfailed
			// tf_id BIGINT
			// tf_ct_id BIGINT

			// -> cloudirlc
			// cd_id BIGINT
			// cd_appliance_id BIGINT
			// cd_state SMALLINT

			// -> cloudiplc
			// cp_id BIGINT
			// cp_appliance_id BIGINT
			// cp_cu_id BIGINT
			// cp_state SMALLINT
			// cp_start_private VARCHAR(20)

			// -> cloudprivateimage
			// co_id BIGINT
			// co_image_id BIGINT
			// co_cu_id BIGINT
			// co_state SMALLINT
			// co_comment VARCHAR(255)

			// -> cloudselector
			// id BIGINT
			// type VARCHAR(255)
			// sort_id BIGINT
			// quantity VARCHAR(255)
			// price SMALLINT
			// name VARCHAR(255)
			// description VARCHAR(255)
			// state SMALLINT

			// -> cloudrespool
			// rp_id BIGINT
			// rp_resource_id BIGINT
			// rp_cg_id BIGINT

			// -> cloudhostlimit
			// hl_id BIGINT
			// hl_resource_id BIGINT
			// hl_current_vms SMALLINT
			// hl_max_vms SMALLINT

			// -> cloudpowersaver
			// ps_id BIGINT
			// ps_frequence VARCHAR(50)
			// ps_last_check VARCHAR(50)

			// -> cloud_profiles
			// pr_id BIGINT
			// pr_name VARCHAR(20)
			// pr_cu_id BIGINT
			// pr_status SMALLINT
			// pr_request_time VARCHAR(20)
			// pr_start VARCHAR(20)
			// pr_stop VARCHAR(20)
			// pr_kernel_id BIGINT
			// pr_image_id BIGINT
			// pr_ram_req VARCHAR(20)
			// pr_cpu_req VARCHAR(20)
			// pr_disk_req VARCHAR(20)
			// pr_network_req VARCHAR(255)
			// pr_resource_quantity SMALLINT
			// pr_resource_type_req VARCHAR(20)
			// pr_deployment_type_req VARCHAR(50)
			// pr_ha_req VARCHAR(5)
			// pr_shared_req VARCHAR(5)
			// pr_puppet_groups VARCHAR(255)
			// pr_ip_mgmt VARCHAR(255)
			// pr_appliance_id VARCHAR(255)
			// pr_appliance_hostname VARCHAR(255)
			// pr_lastbill VARCHAR(20)
			// pr_description VARCHAR(255)
			// pr_appliance_capabilities VARCHAR(1000)

			// -> cloud_icons
			// ic_id BIGINT
			// ic_cu_id BIGINT
			// ic_type SMALLINT
			// ic_object_id BIGINT
			// ic_filename VARCHAR(255)

			// -> cloud_matrix
			// cm_id BIGINT
			// cm_cu_id BIGINT
			// cm_description VARCHAR(255)
			// cm_row01 VARCHAR(255)
			// cm_row02 VARCHAR(255)
			// cm_row03 VARCHAR(255)
			// cm_row04 VARCHAR(255)
			// cm_row05 VARCHAR(255)
			// cm_row06 VARCHAR(255)
			// cm_row07 VARCHAR(255)
			// cm_row08 VARCHAR(255)
			// cm_row09 VARCHAR(255)
			// cm_row10 VARCHAR(255)
			// cm_row11 VARCHAR(255)
			// cm_row12 VARCHAR(255)

			// -> cloud_matrix_object
			// mo_id BIGINT
			// mo_pr_id BIGINT
			// mo_cr_id BIGINT
			// mo_ca_id BIGINT
			// mo_ne_id BIGINT
			// mo_table SMALLINT
			// mo_x SMALLINT
			// mo_y SMALLINT
			// mo_state SMALLINT

			// -> cloud_create_vm_lc
			// vc_id BIGINT
			// vc_resource_id BIGINT
			// vc_cr_id BIGINT
			// vc_cr_resource_number BIGINT
			// vc_request_time VARCHAR(20)
			// vc_vm_create_timeout SMALLINT
			// vc_state SMALLINT

			$create_cloud_requests = "create table cloud_requests(cr_id BIGINT, cr_cu_id BIGINT, cr_status SMALLINT, cr_request_time VARCHAR(20), cr_start VARCHAR(20), cr_stop VARCHAR(20), cr_kernel_id BIGINT, cr_image_id BIGINT, cr_ram_req VARCHAR(20), cr_cpu_req VARCHAR(20), cr_disk_req VARCHAR(20), cr_network_req VARCHAR(255), cr_resource_quantity SMALLINT, cr_resource_type_req VARCHAR(20), cr_deployment_type_req VARCHAR(50), cr_ha_req VARCHAR(5), cr_shared_req VARCHAR(5), cr_appliance_id VARCHAR(255), cr_appliance_hostname VARCHAR(255), cr_puppet_groups VARCHAR(255), cr_ip_mgmt VARCHAR(255), cr_lastbill VARCHAR(20), cr_image_password VARCHAR(255), cr_appliance_capabilities VARCHAR(1000))";
			$create_cloud_users = "create table cloud_users(cu_id BIGINT, cu_cg_id BIGINT, cu_name VARCHAR(50), cu_password VARCHAR(50), cu_forename VARCHAR(50), cu_lastname VARCHAR(50), cu_email VARCHAR(50), cu_street VARCHAR(100), cu_city VARCHAR(100), cu_country VARCHAR(100), cu_phone VARCHAR(100), cu_status SMALLINT, cu_token VARCHAR(100), cu_ccunits INTEGER, cu_lang VARCHAR(4))";
			$create_cloud_usergroups = "create table cloud_usergroups(cg_id BIGINT, cg_name VARCHAR(50), cg_role_id BIGINT, cg_description VARCHAR(255))";
			$create_cloud_users_limit = "create table cloud_users_limits(cl_id BIGINT, cl_cu_id BIGINT, cl_resource_limit SMALLINT, cl_memory_limit INTEGER, cl_disk_limit INTEGER, cl_cpu_limit SMALLINT, cl_network_limit SMALLINT)";
			$create_cloud_config = "create table cloud_config(cc_id BIGINT, cc_key VARCHAR(50), cc_value VARCHAR(255))";
			$create_cloud_image = "create table cloud_image(ci_id BIGINT, ci_cr_id BIGINT, ci_image_id BIGINT, ci_appliance_id BIGINT, ci_resource_id BIGINT, ci_disk_size VARCHAR(20), ci_disk_rsize VARCHAR(20), ci_clone_name VARCHAR(50), ci_state SMALLINT)";
			$create_cloud_appliance = "create table cloud_appliance(ca_id BIGINT, ca_appliance_id BIGINT, ca_cr_id BIGINT, ca_cmd SMALLINT, ca_state SMALLINT)";
			$create_cloud_nat = "create table cloud_nat(cn_id BIGINT, cn_internal_net VARCHAR(50), cn_external_net VARCHAR(50))";
			$create_cloud_transaction = "create table cloud_transaction(ct_id BIGINT, ct_time VARCHAR(50), ct_cr_id BIGINT, ct_cu_id BIGINT, ct_ccu_charge SMALLINT, ct_ccu_balance SMALLINT, ct_reason VARCHAR(20), ct_comment VARCHAR(255))";
			$create_cloud_transactionfailed = "create table cloud_transaction_failed(tf_id BIGINT, tf_ct_id BIGINT)";
			$create_cloud_image_resize_life_cycle = "create table cloud_irlc(cd_id BIGINT, cd_appliance_id BIGINT, cd_state SMALLINT)";
			$create_cloud_image_private_life_cycle = "create table cloud_iplc(cp_id BIGINT, cp_appliance_id BIGINT, cp_cu_id BIGINT, cp_state SMALLINT, cp_start_private VARCHAR(20))";
			$create_cloud_image_private = "create table cloud_private_image(co_id BIGINT, co_image_id BIGINT, co_cu_id BIGINT, co_clone_on_deploy SMALLINT, co_comment VARCHAR(255), co_state SMALLINT)";
			$create_cloud_selector = "create table cloud_selector(id BIGINT, type VARCHAR(255), sort_id BIGINT, quantity VARCHAR(255), price SMALLINT, name VARCHAR(255), description VARCHAR(255), state SMALLINT)";
			$create_cloud_resource_pool = "create table cloud_respool(rp_id BIGINT, rp_resource_id BIGINT, rp_cg_id BIGINT)";
			$create_cloud_host_limit = "create table cloud_hostlimit(hl_id BIGINT, hl_resource_id BIGINT, hl_current_vms SMALLINT, hl_max_vms SMALLINT)";
			$create_cloud_power_saver = "create table cloud_power_saver(ps_id BIGINT, ps_frequence VARCHAR(50), ps_last_check VARCHAR(50))";
			$create_cloud_profiles = "create table cloud_profiles(pr_id BIGINT, pr_name VARCHAR(20), pr_cu_id BIGINT, pr_status SMALLINT, pr_request_time VARCHAR(20), pr_start VARCHAR(20), pr_stop VARCHAR(20), pr_kernel_id BIGINT, pr_image_id BIGINT, pr_ram_req VARCHAR(20), pr_cpu_req VARCHAR(20), pr_disk_req VARCHAR(20), pr_network_req VARCHAR(255), pr_resource_quantity SMALLINT, pr_resource_type_req VARCHAR(20), pr_deployment_type_req VARCHAR(50), pr_ha_req VARCHAR(5), pr_shared_req VARCHAR(5), pr_appliance_id VARCHAR(255), pr_appliance_hostname VARCHAR(255), pr_puppet_groups VARCHAR(255), pr_ip_mgmt VARCHAR(255), pr_lastbill VARCHAR(20), pr_description VARCHAR(255), pr_appliance_capabilities VARCHAR(1000))";
			$create_cloud_icons = "create table cloud_icons(ic_id BIGINT, ic_cu_id BIGINT, ic_type SMALLINT, ic_object_id BIGINT, ic_filename VARCHAR(255))";
			$create_cloud_matrix = "create table cloud_matrix(cm_id BIGINT, cm_cu_id BIGINT, cm_description VARCHAR(255), cm_row01 VARCHAR(255), cm_row02 VARCHAR(255), cm_row03 VARCHAR(255), cm_row04 VARCHAR(255), cm_row05 VARCHAR(255), cm_row06 VARCHAR(255), cm_row07 VARCHAR(255), cm_row08 VARCHAR(255), cm_row09 VARCHAR(255), cm_row10 VARCHAR(255), cm_row11 VARCHAR(255), cm_row12 VARCHAR(255))";
			$create_cloud_matrix_object = "create table cloud_matrix_object(mo_id BIGINT, mo_pr_id BIGINT, mo_cr_id BIGINT, mo_ca_id BIGINT, mo_ne_id BIGINT, mo_table SMALLINT, mo_x SMALLINT, mo_y SMALLINT, mo_state SMALLINT)";
			$create_cloud_create_vm_lc = "create table cloud_create_vm_lc(vc_id BIGINT, vc_resource_id BIGINT, vc_cr_id BIGINT, vc_cr_resource_number BIGINT, vc_request_time VARCHAR(20), vc_vm_create_timeout SMALLINT, vc_state SMALLINT)";
			$db=openqrm_get_db_connection();
			$recordSet = $db->Execute($create_cloud_requests);
			$recordSet = $db->Execute($create_cloud_users);
			$recordSet = $db->Execute($create_cloud_usergroups);
			$recordSet = $db->Execute($create_cloud_users_limit);
			$recordSet = $db->Execute($create_cloud_config);
			$recordSet = $db->Execute($create_cloud_image);
			$recordSet = $db->Execute($create_cloud_appliance);
			$recordSet = $db->Execute($create_cloud_nat);
			$recordSet = $db->Execute($create_cloud_transaction);
			$recordSet = $db->Execute($create_cloud_transactionfailed);
			$recordSet = $db->Execute($create_cloud_image_resize_life_cycle);
			$recordSet = $db->Execute($create_cloud_image_private_life_cycle);
			$recordSet = $db->Execute($create_cloud_image_private);
			$recordSet = $db->Execute($create_cloud_selector);
			$recordSet = $db->Execute($create_cloud_resource_pool);
			$recordSet = $db->Execute($create_cloud_host_limit);
			$recordSet = $db->Execute($create_cloud_power_saver);
			$recordSet = $db->Execute($create_cloud_profiles);
			$recordSet = $db->Execute($create_cloud_icons);
			$recordSet = $db->Execute($create_cloud_matrix);
			$recordSet = $db->Execute($create_cloud_matrix_object);
			$recordSet = $db->Execute($create_cloud_create_vm_lc);

			// create the default configuration
			$create_default_cloud_config1 = "insert into cloud_config(cc_id, cc_key, cc_value) values (1, 'cloud_admin_email', 'root@localhost')";
			$recordSet = $db->Execute($create_default_cloud_config1);
			$create_default_cloud_config2 = "insert into cloud_config(cc_id, cc_key, cc_value) values (2, 'auto_provision', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config2);
			$create_default_cloud_config3 = "insert into cloud_config(cc_id, cc_key) values (3, 'external_portal_url', 'http://localhost/cloud-portal')";
			$recordSet = $db->Execute($create_default_cloud_config3);
			$create_default_cloud_config4 = "insert into cloud_config(cc_id, cc_key, cc_value) values (4, 'request_physical_systems', 'false')";
			$recordSet = $db->Execute($create_default_cloud_config4);
			$create_default_cloud_config5 = "insert into cloud_config(cc_id, cc_key, cc_value) values (5, 'default_clone_on_deploy', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config5);
			$create_default_cloud_config6 = "insert into cloud_config(cc_id, cc_key, cc_value) values (6, 'max_resources_per_cr', '1')";
			$recordSet = $db->Execute($create_default_cloud_config6);
			$create_default_cloud_config7 = "insert into cloud_config(cc_id, cc_key, cc_value) values (7, 'auto_create_vms', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config7);
			$create_default_cloud_config8 = "insert into cloud_config(cc_id, cc_key, cc_value) values (8, 'max_disk_size', '100000')";
			$recordSet = $db->Execute($create_default_cloud_config8);
			$create_default_cloud_config9 = "insert into cloud_config(cc_id, cc_key, cc_value) values (9, 'max_network_interfaces', '4')";
			$recordSet = $db->Execute($create_default_cloud_config9);
			$create_default_cloud_config10 = "insert into cloud_config(cc_id, cc_key, cc_value) values (10, 'show_ha_checkbox', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config10);
			$create_default_cloud_config11 = "insert into cloud_config(cc_id, cc_key, cc_value) values (11, 'show_puppet_groups', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config11);
			$create_default_cloud_config12 = "insert into cloud_config(cc_id, cc_key, cc_value) values (12, 'auto_give_ccus', '1000')";
			$recordSet = $db->Execute($create_default_cloud_config12);
			$create_default_cloud_config13 = "insert into cloud_config(cc_id, cc_key, cc_value) values (13, 'max_apps_per_user', '10')";
			$recordSet = $db->Execute($create_default_cloud_config13);
			$create_default_cloud_config14 = "insert into cloud_config(cc_id, cc_key, cc_value) values (14, 'public_register_enabled', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config14);
			$create_default_cloud_config15 = "insert into cloud_config(cc_id, cc_key, cc_value) values (15, 'cloud_enabled', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config15);
			$create_default_cloud_config16 = "insert into cloud_config(cc_id, cc_key, cc_value) values (16, 'cloud_billing_enabled', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config16);
			$create_default_cloud_config17 = "insert into cloud_config(cc_id, cc_key, cc_value) values (17, 'show_sshterm_login', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config17);
			$create_default_cloud_config18 = "insert into cloud_config(cc_id, cc_key, cc_value) values (18, 'cloud_nat', 'false')";
			$recordSet = $db->Execute($create_default_cloud_config18);
			$create_default_cloud_config19 = "insert into cloud_config(cc_id, cc_key, cc_value) values (19, 'show_collectd_graphs', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config19);
			$create_default_cloud_config20 = "insert into cloud_config(cc_id, cc_key, cc_value) values (20, 'show_disk_resize', 'false')";
			$recordSet = $db->Execute($create_default_cloud_config20);
			$create_default_cloud_config21 = "insert into cloud_config(cc_id, cc_key, cc_value) values (21, 'show_private_image', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config21);
			$create_default_cloud_config22 = "insert into cloud_config(cc_id, cc_key, cc_value) values (22, 'cloud_selector', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config22);
			$create_default_cloud_config23 = "insert into cloud_config(cc_id, cc_key, cc_value) values (23, 'cloud_currency', 'USD')";
			$recordSet = $db->Execute($create_default_cloud_config23);
			$create_default_cloud_config24 = "insert into cloud_config(cc_id, cc_key, cc_value) values (24, 'cloud_1000_ccus', '1')";
			$recordSet = $db->Execute($create_default_cloud_config24);
			$create_default_cloud_config25 = "insert into cloud_config(cc_id, cc_key, cc_value) values (25, 'resource_pooling', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config25);
			$create_default_cloud_config26 = "insert into cloud_config(cc_id, cc_key, cc_value) values (26, 'ip-management', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config26);
			$create_default_cloud_config27 = "insert into cloud_config(cc_id, cc_key, cc_value) values (27, 'max-parallel-phase-one-actions', '0')";
			$recordSet = $db->Execute($create_default_cloud_config27);
			$create_default_cloud_config28 = "insert into cloud_config(cc_id, cc_key, cc_value) values (28, 'max-parallel-phase-two-actions', '3')";
			$recordSet = $db->Execute($create_default_cloud_config28);
			$create_default_cloud_config29 = "insert into cloud_config(cc_id, cc_key, cc_value) values (29, 'max-parallel-phase-three-actions', '0')";
			$recordSet = $db->Execute($create_default_cloud_config29);
			$create_default_cloud_config30 = "insert into cloud_config(cc_id, cc_key, cc_value) values (30, 'max-parallel-phase-four-actions', '0')";
			$recordSet = $db->Execute($create_default_cloud_config30);
			$create_default_cloud_config31 = "insert into cloud_config(cc_id, cc_key, cc_value) values (31, 'max-parallel-phase-five-actions', '0')";
			$recordSet = $db->Execute($create_default_cloud_config31);
			$create_default_cloud_config32 = "insert into cloud_config(cc_id, cc_key, cc_value) values (32, 'max-parallel-phase-six-actions', '0')";
			$recordSet = $db->Execute($create_default_cloud_config32);
			$create_default_cloud_config33 = "insert into cloud_config(cc_id, cc_key, cc_value) values (33, 'max-parallel-phase-seven-actions', '0')";
			$recordSet = $db->Execute($create_default_cloud_config33);
			$create_default_cloud_config34 = "insert into cloud_config(cc_id, cc_key, cc_value) values (34, 'appliance_hostname', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config34);
			$create_default_cloud_config35 = "insert into cloud_config(cc_id, cc_key, cc_value) values (35, 'cloud_zones_client', 'false')";
			$recordSet = $db->Execute($create_default_cloud_config35);
			$create_default_cloud_config36 = "insert into cloud_config(cc_id, cc_key, cc_value) values (36, 'cloud_zones_master_ip', '')";
			$recordSet = $db->Execute($create_default_cloud_config36);
			$create_default_cloud_config37 = "insert into cloud_config(cc_id, cc_key, cc_value) values (37, 'cloud_external_ip', '')";
			$recordSet = $db->Execute($create_default_cloud_config37);
			$create_default_cloud_config38 = "insert into cloud_config(cc_id, cc_key, cc_value) values (38, 'deprovision_warning', '100')";
			$recordSet = $db->Execute($create_default_cloud_config38);
			$create_default_cloud_config39 = "insert into cloud_config(cc_id, cc_key, cc_value) values (39, 'deprovision_pause', '50')";
			$recordSet = $db->Execute($create_default_cloud_config39);
			$create_default_cloud_config40 = "insert into cloud_config(cc_id, cc_key, cc_value) values (40, 'vm_provision_delay', '0')";
			$recordSet = $db->Execute($create_default_cloud_config40);
			$create_default_cloud_config41 = "insert into cloud_config(cc_id, cc_key, cc_value) values (41, 'vm_loadbalance_algorithm', '0')";
			$recordSet = $db->Execute($create_default_cloud_config41);
			$create_default_cloud_config42 = "insert into cloud_config(cc_id, cc_key, cc_value) values (42, 'allow_vnc_access', 'true')";
			$recordSet = $db->Execute($create_default_cloud_config42);
			$create_default_cloud_config43 = "insert into cloud_config(cc_id, cc_key, cc_value) values (43, 'max_network', '1000')";
			$recordSet = $db->Execute($create_default_cloud_config43);
			$create_default_cloud_config44 = "insert into cloud_config(cc_id, cc_key, cc_value) values (44, 'max_memory', '10000')";
			$recordSet = $db->Execute($create_default_cloud_config44);
			$create_default_cloud_config45 = "insert into cloud_config(cc_id, cc_key, cc_value) values (45, 'max_cpu', '100')";
			$recordSet = $db->Execute($create_default_cloud_config45);

			// fill in default cloud products for the cloudselector
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'cpu',0,'1',1,'1','1 CPU',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'cpu',1,'2',2,'2','2 CPUs',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'cpu',2,'4',4,'4','4 CPUs',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'disk',0,'2000',2,'2 GB','2 GB Disk Space',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'disk',1,'5000',5,'5 GB','5 GB Disk Space',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'disk',2,'10000',10,'10 GB','10 GB Disk Space',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'disk',3,'20000',20,'20 GB','20 GB Disk Space',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'disk',4,'50000',50,'50 GB','50 GB Disk Space',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'disk',5,'100000',100,'100 GB','100 GB Disk Space',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
 			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'memory',0,'256',1,'256 MB','256 MB Memory',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'memory',1,'512',2,'512 MB','512 MB Memory',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'memory',2,'1024',4,'1 GB','1 GB Memory',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'memory',3,'2048',8,'2 GB','2 GB Memory',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'network',0,'1',1,'1','1 Network Card',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'network',1,'2',2,'2','2 Network Cards',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'network',2,'3',3,'3','3 Network Cards',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'network',3,'4',4,'4','4 Network Cards',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_cloudselector_config = "insert into cloud_selector VALUES (".$cloud_product_id.",'quantity',0,'1',1,'1','1 CloudAppliance',1);";
			$recordSet = $db->Execute($create_default_cloudselector_config);
			
			// create kernel products
			$next_sort_id = 0;
			$kernel = new kernel();
			$kernel_id_ar = $kernel->get_list();
			unset($kernel_id_ar[0]);
			foreach ($kernel_id_ar as $key => $value) {
				$id = $value['value'];
				$kernel->get_instance_by_id($id);
				$pos = strpos($kernel->name, 'resource');
				if ($pos === false) {
					$cloud_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$create_kernel_cloudselector_config = "insert into cloud_selector (id, type, sort_id, quantity, price, name, description, state) VALUES (".$cloud_product_id.", 'kernel', ".$next_sort_id.", '".$kernel->id."', 1, '".$kernel->name."', '".$kernel->version."', 1);";
					$recordSet = $db->Execute($create_kernel_cloudselector_config);
					$next_sort_id++;
				}
			}
			
			// create default projects
			$cloud_hook_config = array();
			$cloud_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_usergroup = "insert into cloud_usergroups VALUES (".$cloud_id.",'Default',".$cloud_id.",'The Default Cloud Project');";
			$recordSet = $db->Execute($create_default_usergroup);
			$cloud_admin_usergroup_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$create_default_usergroup = "insert into cloud_usergroups VALUES (".$cloud_admin_usergroup_id.",'Admin',".$cloud_admin_usergroup_id.",'The Admin Cloud Project');";
			$recordSet = $db->Execute($create_default_usergroup);
			// create openqrm admin user
			$OPENQRM_ADMIN = new user('openqrm');
			$OPENQRM_ADMIN->set_user();
			$cloud_user = new clouduser();
			$cloud_user_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$clouduser_fields["cu_id"] = $cloud_user_id;
			$clouduser_fields["cu_cg_id"] = $cloud_admin_usergroup_id;
			$clouduser_fields["cu_name"] = 'openqrm';
			$clouduser_fields["cu_password"] = $OPENQRM_ADMIN->password;
			$clouduser_fields["cu_forename"] = 'openQRM';
			$clouduser_fields["cu_lastname"] = 'Adminstrator';
			$clouduser_fields["cu_email"] = 'root@localhost';
			$clouduser_fields["cu_street"] = '-';
			$clouduser_fields["cu_city"] = '-';
			$clouduser_fields["cu_country"] = '-';
			$clouduser_fields["cu_phone"] = '-';
			$clouduser_fields["cu_status"] = 1;
			$clouduser_fields["cu_ccunits"] = 1000;
			$clouduser_fields["cu_lang"] = 'en';
			$cloud_user->add($clouduser_fields);
			$cloud_hook_config['cloud_admin_procect'] = $cloud_admin_usergroup_id;

			// set user permissions and limits, set to 0 (infinite) by default
			$cloud_user_limits = new clouduserlimits();
			$cloud_user_limits_fields['cl_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$cloud_user_limits_fields['cl_cu_id'] = $cloud_user_id;
			$cloud_user_limits_fields['cl_resource_limit'] = 0;
			$cloud_user_limits_fields['cl_memory_limit'] = 0;
			$cloud_user_limits_fields['cl_disk_limit'] = 0;
			$cloud_user_limits_fields['cl_cpu_limit'] = 0;
			$cloud_user_limits_fields['cl_network_limit'] = 0;
			$cloud_user_limits->add($cloud_user_limits_fields);
                        
			// default power-saver config
			$create_default_power_saver_config = "insert into cloud_power_saver VALUES (0,'1800','');";
			$recordSet = $db->Execute($create_default_power_saver_config);

			// let plugins add cloud products
			$plugin = new plugin();
			$enabled_plugins = $plugin->enabled();
			foreach ($enabled_plugins as $index => $plugin_name) {
				$plugin_cloud_product_hook = $RootDir."/plugins/".$plugin_name."/openqrm-".$plugin_name."-cloud-product-hook.php";
				if (file_exists($plugin_cloud_product_hook)) {
					$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "init", "Found plugin ".$plugin_name." handling cloud-product event.", "", "", 0, 0, 0);
					require_once "$plugin_cloud_product_hook";
					$cloud_product_function="openqrm_"."$plugin_name"."_cloud_product";
					$cloud_product_function=str_replace("-", "_", $cloud_product_function);
					$cloud_product_function("add", $cloud_hook_config);
				}
			}

			$db->Close();
			break;

		case 'uninstall':
			$drop_cloud_requests = "drop table cloud_requests";
			$drop_cloud_users = "drop table cloud_users";
			$drop_cloud_usergroups = "drop table cloud_usergroups";
			$drop_cloud_users_limit = "drop table cloud_users_limits";
			$drop_cloud_config = "drop table cloud_config";
			$drop_cloud_image = "drop table cloud_image";
			$drop_cloud_appliance = "drop table cloud_appliance";
			$drop_cloud_nat = "drop table cloud_nat";
			$drop_cloud_transaction = "drop table cloud_transaction";
			$drop_cloud_transaction_failed = "drop table cloud_transaction_failed";
			$drop_cloud_image_resize_life_cycle = "drop table cloud_irlc";
			$drop_cloud_image_private_life_cycle = "drop table cloud_iplc";
			$drop_cloud_image_private = "drop table cloud_private_image";
			$drop_cloud_selector = "drop table cloud_selector";
			$drop_cloud_resource_pool = "drop table cloud_respool";
			$drop_cloud_hostlimit = "drop table cloud_hostlimit";
			$drop_cloud_power_saver = "drop table cloud_power_saver";
			$drop_cloud_profiles = "drop table cloud_profiles";
			$drop_cloud_icons = "drop table cloud_icons";
			$drop_cloud_matrix = "drop table cloud_matrix";
			$drop_cloud_matrix_object = "drop table cloud_matrix_object";
			$drop_cloud_create_vm_lc = "drop table cloud_create_vm_lc";
			$db=openqrm_get_db_connection();
			$recordSet = $db->Execute($drop_cloud_requests);
			$recordSet = $db->Execute($drop_cloud_users);
			$recordSet = $db->Execute($drop_cloud_usergroups);
			$recordSet = $db->Execute($drop_cloud_users_limit);
			$recordSet = $db->Execute($drop_cloud_config);
			$recordSet = $db->Execute($drop_cloud_image);
			$recordSet = $db->Execute($drop_cloud_appliance);
			$recordSet = $db->Execute($drop_cloud_nat);
			$recordSet = $db->Execute($drop_cloud_transaction);
			$recordSet = $db->Execute($drop_cloud_transaction_failed);
			$recordSet = $db->Execute($drop_cloud_image_resize_life_cycle);
			$recordSet = $db->Execute($drop_cloud_image_private_life_cycle);
			$recordSet = $db->Execute($drop_cloud_image_private);
			$recordSet = $db->Execute($drop_cloud_selector);
			$recordSet = $db->Execute($drop_cloud_resource_pool);
			$recordSet = $db->Execute($drop_cloud_hostlimit);
			$recordSet = $db->Execute($drop_cloud_power_saver);
			$recordSet = $db->Execute($drop_cloud_profiles);
			$recordSet = $db->Execute($drop_cloud_icons);
			$recordSet = $db->Execute($drop_cloud_matrix);
			$recordSet = $db->Execute($drop_cloud_matrix_object);
			$recordSet = $db->Execute($drop_cloud_create_vm_lc);
			$db->Close();
			break;

		case 'create_user':
			$user_fields['cu_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			// enabled by default
			$user_fields['cu_status'] = 1;
			$username = $user_fields['cu_name'];
			$password = $user_fields['cu_password'];
			$c_error = 0;
			// checks
			check_param("Username", $user_fields['cu_name']);
			check_param("Password", $user_fields['cu_password']);
			check_param("Lastname", $user_fields['cu_lastname']);
			check_param("Forename", $user_fields['cu_forename']);
			check_param("Street", $user_fields['cu_street']);
			check_param("City", $user_fields['cu_city']);
			check_param("Country", $user_fields['cu_country']);
			check_param("Phone", $user_fields['cu_phone']);

			// email valid ?
			$cloud_email = new clouduser();
			if (strcmp($user_fields['cu_email'], "@localhost")) {
				if (!$cloud_email->checkEmail($user_fields['cu_email'])) {
					$strMsg = "Email address is invalid. <br>";
					$c_error = 1;
					redirect($strMsg, 'tab0', "cloud-user.php");
					exit(0);
				}
			}

			// password min 6 characters
			if (strlen($user_fields['cu_password'])<6) {
				$strMsg .= "Password must be at least 6 characters long <br>";
				$c_error = 1;
				redirect($strMsg, 'tab0', "cloud-user.php");
				exit(0);
			}
			// username min 4 characters
			if (strlen($user_fields['cu_name'])<4) {
				$strMsg .= "Username must be at least 4 characters long <br>";
				$c_error = 1;
				redirect($strMsg, 'tab0', "cloud-user.php");
				exit(0);
			}
			// does username already exists ?
			$c_user = new clouduser();
			if (!$c_user->is_name_free($user_fields['cu_name'])) {
				$uname = $user_fields['cu_name'];
				$strMsg .= "A user with the name $uname already exist. Please choose another username <br>";
				$c_error = 1;
				redirect($strMsg, 'tab0', "cloud-user.php");
				exit(0);
			}

			if ($c_error == 0) {
				// check how many ccunits to give for a new user
				$cc_conf = new cloudconfig();
				$cc_auto_give_ccus = $cc_conf->get_value(12);  // 12 is auto_give_ccus
				$user_fields['cu_ccunits'] = $cc_auto_give_ccus;
				$cl_user = new clouduser();
				$cl_user->add($user_fields);
				// add user to htpasswd
				$cloud_htpasswd = "$CloudDir/user/.htpasswd";
				if (file_exists($cloud_htpasswd)) {
					$openqrm_server_command="htpasswd -b $CloudDir/user/.htpasswd $username $password";
				} else {
					$openqrm_server_command="htpasswd -c -b $CloudDir/user/.htpasswd $username $password";
				}
				$output = shell_exec($openqrm_server_command);

				// set user permissions and limits, set to 0 (infinite) by default
				$cloud_user_limit = new clouduserlimits();
				$cloud_user_limits_fields['cl_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
				$cloud_user_limits_fields['cl_cu_id'] = $user_fields['cu_id'];
				$cloud_user_limits_fields['cl_resource_limit'] = 0;
				$cloud_user_limits_fields['cl_memory_limit'] = 0;
				$cloud_user_limits_fields['cl_disk_limit'] = 0;
				$cloud_user_limits_fields['cl_cpu_limit'] = 0;
				$cloud_user_limits_fields['cl_network_limit'] = 0;
				$cloud_user_limit->add($cloud_user_limits_fields);

				// send mail to user
				// get admin email
				$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email
				// get external name
				$external_portal_name = $cc_conf->get_value(3);  // 3 is the external name
				if (!strlen($external_portal_name)) {
					$external_portal_name = "http://$OPENQRM_SERVER_IP_ADDRESS/cloud-portal";
				}
				$email = $user_fields['cu_email'];
				$forename = $user_fields['cu_forename'];
				$lastname = $user_fields['cu_lastname'];
				$rmail = new cloudmailer();
				$rmail->to = "$email";
				$rmail->from = "$cc_admin_email";
				$rmail->subject = "openQRM Cloud: Your account has been created";
				$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/welcome_new_cloud_user.mail.tmpl";
				$arr = array('@@USER@@'=>"$username", '@@PASSWORD@@'=>"$password", '@@EXTERNALPORTALNAME@@'=>"$external_portal_name", '@@FORENAME@@'=>"$forename", '@@LASTNAME@@'=>"$lastname", '@@CLOUDADMIN@@'=>"$cc_admin_email");
				$rmail->var_array = $arr;
				$rmail->send();

				$strMsg = "Added user $username";
				redirect($strMsg, 'tab0', "cloud-user.php");
			}
			break;


		default:
			$event->log("$cloud_command", $_SERVER['REQUEST_TIME'], 3, "cloud-action", "No such event command ($cloud_command)", "", "", 0, 0, 0);
			break;


	}






?>
