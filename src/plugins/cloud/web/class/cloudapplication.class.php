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


// This class represents an application in the cloud of openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once $RootDir."/include/openqrm-database-functions.php";
require_once $RootDir."/class/resource.class.php";
require_once $RootDir."/class/appliance.class.php";
require_once $RootDir."/class/virtualization.class.php";
require_once $RootDir."/class/openqrm_server.class.php";
require_once $RootDir."/class/plugin.class.php";
require_once $RootDir."/class/event.class.php";
// special cloud classes
require_once $RootDir."/plugins/cloud/class/clouduser.class.php";
require_once $RootDir."/plugins/cloud/class/cloudusergroup.class.php";
require_once $RootDir."/plugins/cloud/class/cloudconfig.class.php";
require_once $RootDir."/plugins/cloud/class/cloudappliance.class.php";

$event = new event();
global $event;

global $OPENQRM_SERVER_BASE_DIR;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $RESOURCE_INFO_TABLE;



class cloudapplication {

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @access public
	 */
	//--------------------------------------------
	function __construct() {
		$this->event = new event();
		$this->webdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
	}

	// ---------------------------------------------------------------------------------
	// general cloudapplication methods
	// ---------------------------------------------------------------------------------


	// get all applications by plugins providing apps
	function get_application_list() {
		$application_list = array();
		$plugin = new plugin();
		$enabled_plugins = $plugin->enabled();
		foreach ($enabled_plugins as $index => $plugin_name) {
			$plugin_cloud_application_hook = $this->webdir."/plugins/".$plugin_name."/openqrm-".$plugin_name."-cloud-application-hook.php";
			if (file_exists($plugin_cloud_application_hook)) {
				$this->event->log("get_application_list", $_SERVER['REQUEST_TIME'], 5, "cloudapplication.class.php", "Found plugin ".$plugin_name." providing a Cloud-application hook.", "", "", 0, 0, 0);
				require_once $plugin_cloud_application_hook;
				$application_function="openqrm_"."$plugin_name"."_get_cloud_applications";
				$application_function=str_replace("-", "_", $application_function);
				$application_array = $application_function();
				foreach ($application_array as $index => $app) {
					$application_list[] .= $app;
				}
			}
		}
		return $application_list;
	}




	// finds which application deployment plugins are used
	// returns an array of deployment plugin names
	function get_deployment_plugins($application_array) {
		$deployment_plugin_list = array();
		foreach ($application_array as $index => $app) {
			$deployment_arr = explode("/", $app);
			if (!in_array($deployment_arr[0], $deployment_plugin_list)) {
				$deployment_plugin_list[] .= $deployment_arr[0];
			}
		}
		return $deployment_plugin_list;
	}


	// finds which applications are set by a specific deployment plugins
	// returns an array of applications by deployment plugin name
	function get_applications_by_deployment_plugin($deployment_plugin, $application_array) {
		$application_list = array();
		foreach ($application_array as $index => $app) {
			$deployment_arr = explode("/", $app);
			if ($deployment_arr[0] ==  $deployment_plugin) {
				$application_list[] .= $deployment_arr[1];
			}
		}
		return $application_list;
	}





	// set appliacations for an appliance
	function set_applications($appliance_name, $application_array) {
		$deployment_plugin_list = $this->get_deployment_plugins($application_array);
		foreach ($deployment_plugin_list as $index => $deployment_plugin) {
			$application_array_by_deployment_plugin = $this->get_applications_by_deployment_plugin($deployment_plugin, $application_array);
			$plugin_cloud_application_hook = $this->webdir."/plugins/".$deployment_plugin."/openqrm-".$deployment_plugin."-cloud-application-hook.php";
			if (file_exists($plugin_cloud_application_hook)) {
				$this->event->log("set_applications", $_SERVER['REQUEST_TIME'], 5, "cloudapplication.class.php", "Found plugin ".$deployment_plugin." providing a Cloud-application hook.", "", "", 0, 0, 0);
				require_once $plugin_cloud_application_hook;
				$application_function="openqrm_"."$deployment_plugin"."_set_cloud_applications";
				$application_function=str_replace("-", "_", $application_function);
				$application_function($appliance_name, $application_array_by_deployment_plugin);
			}
		}
	}

	// remove appliacations for an appliance
	function remove_applications($appliance_name, $application_array) {
		$deployment_plugin_list = $this->get_deployment_plugins($application_array);
		foreach ($deployment_plugin_list as $index => $deployment_plugin) {
			$plugin_cloud_application_hook = $this->webdir."/plugins/".$deployment_plugin."/openqrm-".$deployment_plugin."-cloud-application-hook.php";
			if (file_exists($plugin_cloud_application_hook)) {
				$this->event->log("remove_applications", $_SERVER['REQUEST_TIME'], 5, "cloudapplication.class.php", "Found plugin ".$deployment_plugin." providing a Cloud-application hook.", "", "", 0, 0, 0);
				require_once $plugin_cloud_application_hook;
				$application_function="openqrm_"."$deployment_plugin"."_remove_cloud_applications";
				$application_function=str_replace("-", "_", $application_function);
				$application_function($appliance_name);
			}
		}
	}




// ---------------------------------------------------------------------------------

}

?>