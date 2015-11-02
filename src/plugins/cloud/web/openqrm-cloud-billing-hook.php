
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


// the function of the cloud-billing-hook gets executed
// when the Cloud charges customers CCU's
// -> please insert your custom Cloud-billing calculation here

// error_reporting(E_ALL);
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
$CloudDir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/image_authentication.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/event.class.php";
// special cloud classes
require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
require_once "$RootDir/plugins/cloud/class/cloudrequest.class.php";
require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
require_once "$RootDir/plugins/cloud/class/cloudmailer.class.php";
require_once "$RootDir/plugins/cloud/class/cloudvm.class.php";
require_once "$RootDir/plugins/cloud/class/cloudimage.class.php";
require_once "$RootDir/plugins/cloud/class/cloudappliance.class.php";
require_once "$RootDir/plugins/cloud/class/cloudtransaction.class.php";



$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;




function openqrm_custom_cloud_billing($cr_id, $cu_id, $cu_ccunits) {
	global $event;
	global $openqrm_server;
	global $BaseDir;
	global $RootDir;

	// please implement your custom calcuation here
	// -> in the following example we just substract 1 for each active cloud appliance per hour
	$custom_costs = 1;
	$new_cu_ccunits = $cu_ccunits-$custom_costs;

	// check if CCU credits are going low
	if ($new_cu_ccunits < 0) {
		$new_cu_ccunits = 0;
	}
	// $event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "openqrm-cloud-billing-hook", "Applying basic charge $new_cu_ccunits = $cu_ccunits-$basic_costs for request ID $cr_id", "", "", 0, 0, 0);
	// transaction logging
	$ct = new cloudtransaction();
	$ct->push($cr_id, $cu_id, $custom_costs, $new_cu_ccunits, "Custom Cloud billing", "Custom CCU charge for a Cloud appliance");

	return $new_cu_ccunits;
}

?>
