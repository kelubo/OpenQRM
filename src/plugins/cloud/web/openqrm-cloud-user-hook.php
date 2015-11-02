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


// this hook allows to run custom actions when user activates themselves

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
// special cloud classes
require_once $RootDir."/plugins/cloud/class/clouduser.class.php";

global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;

function openqrm_cloud_user($cloud_user_id, $action) {
	global $event;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $openqrm_server;
	global $BaseDir;
	global $RootDir;

	$cloud_user = new clouduser();
	$cloud_user->get_instance_by_id($cloud_user_id);
	if (!strlen($cloud_user->name)) {
		$event->log("cloud", $_SERVER['REQUEST_TIME'], 2, "zones-user-hook", "No such Cloud User with ID ".$cloud_user_id, "", "", 0, 0, 0);
		return;
	}

	switch($action) {
		case 'activate':
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "zones-user-hook", "Activating Cloud User ".$cloud_user->name, "", "", 0, 0, 0);

			
			
			
			break;
	}





}


?>