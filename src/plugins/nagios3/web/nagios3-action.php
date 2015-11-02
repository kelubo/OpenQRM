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

// user/role authentication
if ($OPENQRM_USER->role != "administrator") {
	$event->log("authorization", $_SERVER['REQUEST_TIME'], 1, "nagios3-action", "Un-Authorized access to nagios3-actions from $OPENQRM_USER->name", "", "", 0, 0, 0);
	exit();
}
// get command
$nagios3_command = $request->get('nagios3_command');

// main
$event->log("$nagios3_command", $_SERVER['REQUEST_TIME'], 5, "nagios3-action", "Processing nagios3 command $nagios3_command", "", "", 0, 0, 0);
switch ($nagios3_command) {

	case 'init':
		// this command creates the following tables
		// -> nagios3_services
		// nagios3_service_id BIGINT
		// nagios3_service_name VARCHAR(50)
		// nagios3_service_port VARCHAR(50)
		// nagios3_service_type VARCHAR(50)
		// nagios3_service_description VARCHAR(255)
		$create_nagios3_service_table = "create table nagios3_services(nagios3_service_id BIGINT, nagios3_service_name VARCHAR(50), nagios3_service_port VARCHAR(50), nagios3_service_type VARCHAR(50), nagios3_service_description VARCHAR(255))";
		// -> nagios3_hosts
		// nagios3_host_id BIGINT
		// nagios3_appliance_id BIGINT
		// nagios3_appliance_services VARCHAR(255)
		$create_nagios3_host_table = "create table nagios3_hosts(nagios3_host_id BIGINT, nagios3_appliance_id BIGINT, nagios3_appliance_services VARCHAR(255))";
		$db=openqrm_get_db_connection();
		$recordSet = $db->Execute($create_nagios3_service_table);
		$recordSet = $db->Execute($create_nagios3_host_table);

		$db->Close();
		break;

	case 'uninstall':
		$drop_nagios3_service_table = "drop table nagios3_services";
		$drop_nagios3_host_table = "drop table nagios3_hosts";
		$db=openqrm_get_db_connection();
		$recordSet = $db->Execute($drop_nagios3_service_table);
		$recordSet = $db->Execute($drop_nagios3_host_table);
		$db->Close();
		break;


	default:
		$event->log("$nagios3_command", $_SERVER['REQUEST_TIME'], 3, "nagios3-action", "No such event command ($nagios3_command)", "", "", 0, 0, 0);
		break;


}

?>
