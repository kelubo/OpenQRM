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


// This class represents a cloudappliance object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

require_once "$RootDir/plugins/cloud/class/cloudicon.class.php";
if(class_exists('clouduser') === false) {
	require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
}
if(class_exists('cloudusergroup') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudusergroup.class.php";
}
if(class_exists('cloudconfig') === false) {
	require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
}
require_once "$RootDir/plugins/cloud/class/cloudrespool.class.php";

$CLOUD_APPLIANCE_TABLE="cloud_appliance";
global $CLOUD_APPLIANCE_TABLE;
$event = new event();
global $event;


class cloudappliance {

var $id = '';
var $appliance_id = '';
var $cr_id = '';
var $cmd = '';
	// cmd = 0  -> noop
	// cmd = 1	-> start
	// cmd = 2	-> stop
	// cmd = 3	-> restart
var $state = '';
	// state = 0	-> paused
	// state = 1	-> active


	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudappliance() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		$this->_event = new event();
		$this->_db_table = "cloud_appliance";
	}


// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudappliance object filled from the db
// ---------------------------------------------------------------------------------

// returns an appliance from the db selected by id or name
function get_instance($id, $appliance_id) {
	$db=openqrm_get_db_connection();
	if ("$id" != "") {
		$cloudappliance_array = $db->Execute("select * from ".$this->_db_table." where ca_id=$id");
	} else if ("$appliance_id" != "") {
		$cloudappliance_array = $db->Execute("select * from ".$this->_db_table." where ca_appliance_id=$appliance_id");
	} else {
		$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", "Could not create instance of event without data", "", "", 0, 0, 0);
		return;
	}

	foreach ($cloudappliance_array as $index => $cloudappliance) {
		$this->id = $cloudappliance["ca_id"];
		$this->appliance_id = $cloudappliance["ca_appliance_id"];
		$this->cr_id = $cloudappliance["ca_cr_id"];
		$this->cmd = $cloudappliance["ca_cmd"];
		$this->state = $cloudappliance["ca_state"];
	}
	return $this;
}

// returns an cloudappliance from the db selected by id
function get_instance_by_id($id) {
	$this->get_instance($id, "");
	return $this;
}

// returns an cloudappliance from the db selected by the appliance_id
function get_instance_by_appliance_id($appliance_id) {
	$this->get_instance("", $appliance_id);
	return $this;
}

// ---------------------------------------------------------------------------------
// general cloudappliance methods
// ---------------------------------------------------------------------------------




// checks if given cloudappliance id is free in the db
function is_id_free($cloudappliance_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select ca_id from ".$this->_db_table." where ca_id=".$cloudappliance_id);
	if (!$rs)
		$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}

// checks if given cloudappliance cr id is free in the db
function is_cr_id_free($ca_cr_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select ca_id from ".$this->_db_table." where ca_cr_id=".$ca_cr_id);
	if (!$rs)
		$this->_event->log("is_cr_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}

// checks if given cloudappliance cr id is free in the db
function is_appliance_id_free($ca_appliance_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select ca_id from ".$this->_db_table." where ca_appliance_id=".$ca_appliance_id);
	if (!$rs)
		$this->_event->log("is_appliance_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}




// adds cloudappliance to the database
function add($cloudappliance_fields) {
	if (!is_array($cloudappliance_fields)) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", "cloudappliance_field not well defined", "", "", 0, 0, 0);
		return 1;
	}
	// check that cr_id + app_id is uniq
	$ca_cr_id = $cloudappliance_fields['ca_cr_id'];
	$ca_appliance_id = $cloudappliance_fields['ca_appliance_id'];
	if ($ca_cr_id == '') {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", "Not adding cloudappliance. Request ID empty", "", "", 0, 0, 0);
		return 1;
	}
	if ($ca_cr_id == '') {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", "Not adding cloudappliance. Appliance ID empty", "", "", 0, 0, 0);
		return 1;
	}
	if (!$this->is_cr_id_free($ca_cr_id)) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", "Not adding cloudappliance. Already existing cloudappliance by cr_id ".$ca_cr_id, "", "", 0, 0, 0);
		return 1;
	}
	if (!$this->is_appliance_id_free($ca_appliance_id)) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", "Not adding cloudappliance. Already existing cloudappliance by appliance_id ".$ca_appliance_id, "", "", 0, 0, 0);
		return 1;
	}
	// set stop time and status to now
	$now=$_SERVER['REQUEST_TIME'];
	$db=openqrm_get_db_connection();
	$result = $db->AutoExecute($this->_db_table, $cloudappliance_fields, 'INSERT');
	if (! $result) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", "Failed adding new cloudappliance to database", "", "", 0, 0, 0);
	}
}



// removes cloudappliance from the database
function remove($cloudappliance_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("delete from ".$this->_db_table." where ca_id=$cloudappliance_id");
	// check if there is an icon to remove
	$IconDir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/web/user/custom-icons/';
	$ca_icon = new cloudicon();
	$ca_icon->get_instance('', '', 2, $cloudappliance_id);
	if (strlen($ca_icon->filename)) {
		$ca_icon_file = $IconDir.$ca_icon->filename;
		unlink($ca_icon_file);
		$ca_icon->remove($ca_icon->id);
	}
}



// sets the state of a cloudappliance
function set_state($cloudappliance_id, $state_str) {
	$cloudappliance_state = 0;
	switch ($state_str) {
		case "paused":
			$cloudappliance_state = 0;
			break;
		case "active":
			$cloudappliance_state = 1;
			break;
	}
	$db=openqrm_get_db_connection();
	$cloudappliance_set = $db->Execute("update ".$this->_db_table." set ca_state=$cloudappliance_state where ca_id=$cloudappliance_id");
	if (!$cloudappliance_set) {
		$this->_event->log("set_state", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	}
}


// sets the cmd of a cloudappliance
function set_cmd($cloudappliance_id, $cmd_str) {
	$cloudappliance_cmd = 0;
	switch ($cmd_str) {
		case "noop":
			$cloudappliance_cmd = 0;
			break;
		case "start":
			$cloudappliance_cmd = 1;
			break;
		case "stop":
			$cloudappliance_cmd = 2;
			break;
		case "restart":
			$cloudappliance_cmd = 3;
			break;
	}
	$db=openqrm_get_db_connection();
	$cloudappliance_set = $db->Execute("update ".$this->_db_table." set ca_cmd=$cloudappliance_cmd where ca_id=$cloudappliance_id");
	if (!$cloudappliance_set) {
		$this->_event->log("set_cmd", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	}
}




// returns true/false if it finds a resouce (phys or existing idle vm) which fits to the cr
// updates the resource id in the appliance object on success
function find_existing_resource($cr_appliance, $cr_virtualization, $clouduser_id) {
	$cp_conf = new cloudconfig();
	$show_resource_pools = $cp_conf->get_value(25);	// resource_pools enabled ?
	// resource pooling enabled ?
	if (strcmp($show_resource_pools, "true")) {
		// disabled
		$cr_appliance->find_resource($cr_virtualization);
		$cr_appliance->get_instance_by_id($cr_appliance->id);
		if ($cr_appliance->resources == -1) {
			return false;
		} else {
			return true;
		}
	} else {
		$found_new_resource=0;
		$new_resource_id=-1;
		$resource_tmp = new resource();
		$resource_list = array();
		$resource_list = $resource_tmp->get_list();
		$resource = new resource();
		foreach ($resource_list as $index => $resource_db) {
			$resource->get_instance_by_id($resource_db["resource_id"]);
			if (($resource->id > 0) && ("$resource->imageid" == "1") && ("$resource->state" == "active")) {
				$new_resource_id = $resource->id;
				// check resource-type
				$restype_id = $resource->vtype;
				if ($restype_id == $cr_virtualization) {
					// check the rest of the required parameters for the appliance
					// cpu-number
					if ((strlen($cr_appliance->cpunumber)) && (strcmp($cr_appliance->cpunumber, "0"))) {
						if (strcmp($cr_appliance->cpunumber, $resource->cpunumber)) {
							$this->_event->log("find_existing_resource", $_SERVER['REQUEST_TIME'], 5, "cloudappliance.class.php", "Found new resource $resource->id type $cr_virtualization for appliance $cr_appliance->name but it has the wrong CPU-number, skipping.", "", "", 0, 0, 0);
							continue;
						}
					}
					// memtotal
					if ((strlen($cr_appliance->memtotal)) && (strcmp($cr_appliance->memtotal, "0"))) {
						if (strcmp($cr_appliance->memtotal, $resource->memtotal)) {
							$this->_event->log("find_existing_resource", $_SERVER['REQUEST_TIME'], 5, "cloudappliance.class.php", "Found new resource $resource->id type $cr_virtualization for appliance $cr_appliance->name but it has the wrong amount of Memory, skipping.", "", "", 0, 0, 0);
							continue;
						}
					}
					// nics
					if ((strlen($cr_appliance->nics)) && (strcmp($cr_appliance->nics, "0"))) {
						if (strcmp($cr_appliance->nics, $resource->nics)) {
							$this->_event->log("find_existing_resource", $_SERVER['REQUEST_TIME'], 5, "cloudappliance.class.php", "Found new resource $resource->id type $cr_virtualization for appliance $cr_appliance->name but it has the wrong nic count, skipping.", "", "", 0, 0, 0);
							continue;
						}
					}

					// check to which user group the resource belongs to
					$private_resource = new cloudrespool();
					$private_resource->get_instance_by_resource($new_resource_id);
					// is this resource configured in the resource pools ?
					if (!strlen($private_resource->id)) {
						$this->_event->log("find_existing_resource", $_SERVER['REQUEST_TIME'], 5, "cloudappliance.class.php", "Found new resource ".$resource->id." type ".$cr_virtualization." for appliance ".$cr_appliance->name." but it not configured in the a resource pool, skipping.", "", "", 0, 0, 0);
						continue;
					}
					if ($private_resource->cg_id >= 0) {
						$cloud_user = new clouduser();
						$cloud_user->get_instance_by_id($clouduser_id);
						$cloud_user_group = new cloudusergroup();
						$cloud_user_group->get_instance_by_id($cloud_user->cg_id);
						// does it really belongs to the users group ?
						if ($private_resource->cg_id != $cloud_user_group->id) {
							// resource does not belong to the users group
							$this->_event->log("find_existing_resource", $_SERVER['REQUEST_TIME'], 5, "cloudappliance.class.php", "Found new resource ".$resource->id." type ".$cr_virtualization." for appliance ".$cr_appliance->name." but it is does not belong to the users resource pool, skipping.", "", "", 0, 0, 0);
							continue;
						}
					} else {
						$this->_event->log("find_existing_resource", $_SERVER['REQUEST_TIME'], 5, "cloudappliance.class.php", "Found new resource ".$resource->id." type ".$cr_virtualization." for appliance ".$cr_appliance->name." but it is marked as hidden, skipping.", "", "", 0, 0, 0);
						continue;
					}
					// if we have reached this point we have found an existing resource fitting to cr + resource pool
					$found_new_resource=1;
					$this->_event->log("find_existing_resource", $_SERVER['REQUEST_TIME'], 5, "cloudappliance.class.php", "Found new resource $resource->id type $cr_virtualization for appliance $cr_appliance->name .", "", "", 0, 0, 0);
					break;
				}
			}
		}
		// in case no resources are available log another ha-error event !
		if ($found_new_resource == 0) {
			$this->_event->log("find_existing_resource", $_SERVER['REQUEST_TIME'], 4, "cloudappliance.class.php", "Could not find a free resource type $cr_virtualization for appliance $cr_appliance->name !", "", "", 0, 0, 0);
			return false;
		}
		// if we find an resource which fits to the appliance we update it
		$appliance_fields = array();
		$appliance_fields['appliance_resources'] = $new_resource_id;
		$cr_appliance->update($cr_appliance->id, $appliance_fields);
		return true;
	}
}






// returns the number of cloudappliances for an cloudappliance type
function get_count() {
	$count=0;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select count(ca_id) as num from ".$this->_db_table);
	if (!$rs) {
		print $db->ErrorMsg();
	} else {
		$count = $rs->fields["num"];
	}
	return $count;
}



// returns a list of all cloudappliance names
function get_list() {
	$query = "select ca_id, ca_cr_id from ".$this->_db_table;
	$cloudappliance_name_array = array();
	$cloudappliance_name_array = openqrm_db_get_result_double ($query);
	return $cloudappliance_name_array;
}


// returns a list of all cloudappliance ids
function get_all_ids() {
	$cloudappliance_list = array();
	$query = "select ca_id from ".$this->_db_table;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute($query);
	if (!$rs)
		$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	while (!$rs->EOF) {
		$cloudappliance_list[] = $rs->fields;
		$rs->MoveNext();
	}
	return $cloudappliance_list;

}




// displays the cloudappliance-overview
function display_overview($offset, $limit, $sort, $order) {
	$db=openqrm_get_db_connection();
	$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
	$cloudappliance_array = array();
	if (!$recordSet) {
		$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudappliance.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	} else {
		while (!$recordSet->EOF) {
			array_push($cloudappliance_array, $recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}
	return $cloudappliance_array;
}



// returns ca_id by cr_id
function get_id_by_cr($cr_id) {
	$list = array();
	$query = 'SELECT ca_id FROM '.$this->_db_table.' WHERE ca_cr_id="'.$cr_id.'"';
	$db = openqrm_get_db_connection();
	$rs = $db->Execute($query);
	if(is_object($rs)) {
		while (!$rs->EOF) {
			$list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $list[0]['ca_id'];
	}
}





// ---------------------------------------------------------------------------------

}

?>
