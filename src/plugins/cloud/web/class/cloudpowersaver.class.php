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


// This class represents the cloud-power-saver config in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
// special cloud classes
require_once "$RootDir/plugins/cloud/class/cloudhostlimit.class.php";

$CLOUD_POWERSAVER_TABLE="cloud_power_saver";
global $CLOUD_POWERSAVER_TABLE;
$event = new event();
global $event;

class cloudpowersaver {

	var $id = '';
	var $frequence = '';
	var $last_check = '';


	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudpowersaver() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		global $CLOUD_POWERSAVER_TABLE, $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = $CLOUD_POWERSAVER_TABLE;
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
	}




	// ---------------------------------------------------------------------------------
	// methods to create an instance of a cloudpowersaver object filled from the db
	// ---------------------------------------------------------------------------------

	// returns an cloud_power_saver from the db selected by id or name
	function get_instance() {
		global $CLOUD_POWERSAVER_TABLE;
		$CLOUD_POWERSAVER_TABLE="cloud_power_saver";
		global $event;
		$db=openqrm_get_db_connection();
		$cloudpowersaver_array = $db->Execute("select * from $CLOUD_POWERSAVER_TABLE where ps_id=0");

		foreach ($cloudpowersaver_array as $index => $cloudpowersaver) {
			$this->id = $cloudpowersaver["ps_id"];
			$this->frequence = $cloudpowersaver["ps_frequence"];
			$this->last_check = $cloudpowersaver["ps_last_check"];
		}
		return $this;
	}


	// ---------------------------------------------------------------------------------
	// general cloudpowersaver methods
	// ---------------------------------------------------------------------------------


	// updates cloudpowersaver in the database
	function update($cloudpowersaver_id, $cloudpowersaver_fields) {
		global $CLOUD_POWERSAVER_TABLE;
		global $event;
		if (!is_array($cloudpowersaver_fields)) {
			$event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudpowersaver.class.php", "Unable to update cloudpowersaver $cloudpowersaver_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($cloudpowersaver_fields["ps_id"]);
		$result = $db->AutoExecute($CLOUD_POWERSAVER_TABLE, $cloudpowersaver_fields, 'UPDATE', "ps_id = $cloudpowersaver_id");
		if (! $result) {
			$event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudpowersaver.class.php", "Failed updating cloudpowersaver $cloudpowersaver_id", "", "", 0, 0, 0);
		}
	}



	// triggers checking for power-saving options
	function trigger() {
		global $CLOUD_POWERSAVER_TABLE;
		global $event;
		$now = $_SERVER['REQUEST_TIME'];
		// check if it time to run the trigger
		$this->get_instance();
		if ($now - $this->frequence > $this->last_check ) {
			// check all appliances
			$appliance_tmp = new appliance();
			$appliance_id_list = $appliance_tmp->get_all_ids();
			$active_appliance_list = array();
			foreach($appliance_id_list as $id_arr) {
				foreach($id_arr as $id) {
					$appliance = new appliance();
					$appliance->get_instance_by_id($id);
					$appliance = new appliance();
					$appliance->get_instance_by_id($id);
					// not the openQRM server resource
					if ($appliance->resources == 0) {
						continue;
					}
					$resource = new resource();
					$resource->get_instance_by_id($appliance->resources);
					$ps_resource_parameter = $resource->get_resource_capabilities('CPS');
					if ((!strlen($ps_resource_parameter)) || ($ps_resource_parameter == 0)) {
						// CPS resource_capabilities not set or 0
						// $event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Skipping appliance $id, CPS not set in resource or 0", "", "", 0, 0, 0);
						continue;
					}
					// for all host appliances which are active and unused, stop them
					if (!strcmp($appliance->state, "active")) {
						$event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Checking for active appliance $id", "", "", 0, 0, 0);
						if (!strcmp($resource->state, "active")) {
							// check how many vms are on this host, only stop its appliance when there are no vms on it
							$cloudhostlimit = new cloudhostlimit();
							$cloudhostlimit->get_instance_by_resource($appliance->resources);
							if ((!strlen($cloudhostlimit->id)) || ($cloudhostlimit->current_vms > 0)) {
								$event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Resource of appliance $id is active, hosts has vms, NOT stopping appliance", "", "", 0, 0, 0);
								continue;
							}
							$event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Resource of appliance $id is active, hosts does not have any vms, stopping appliance", "", "", 0, 0, 0);
							$appliance->stop();
						} else {
							$event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Resource of appliance $id is not active, skipping ...", "", "", 0, 0, 0);
						}
					}
					// for all host appliances which are stopped and their resource is idle, power them down
					if (!strcmp($appliance->state, "stopped")) {
						$event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Checking for stopped appliance $id", "", "", 0, 0, 0);
						if ((!strcmp($resource->state, "active")) && ($resource->imageid == "1")) {
							$event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Resource of stopped appliance $id is idle, power-down resource", "", "", 0, 0, 0);
							$resource->send_command($resource->ip, "halt");
							// set state to transition
							$resource_fields=array();
							$resource_fields["resource_state"]="off";
							$resource->update_info($resource->id, $resource_fields);
							$event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Powerd down resource of stopped appliance $id", "", "", 0, 0, 0);
						} else {
							$event->log("trigger", $now, 5, "cloudpowersaver.class.php", "Resource of appliance $id is not idle, skipping ...", "", "", 0, 0, 0);
						}
					}
				}
			}

			// update lastcheck
			$cloudpowersaver_fields=array();
			$cloudpowersaver_fields["ps_last_check"]=$now;
			$this->update(0, $cloudpowersaver_fields);

		}

	}





// ---------------------------------------------------------------------------------

}

?>