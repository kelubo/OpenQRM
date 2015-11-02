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


// This class represents a cloudirlc-resize-life-cycle object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

$CLOUD_IMAGE_RESIZE_LIVE_CYCLE_TABLE="cloud_irlc";
global $CLOUD_IMAGE_RESIZE_LIVE_CYCLE_TABLE;
$event = new event();
global $event;


class cloudirlc {

var $id = '';
var $appliance_id = '';
var $state = '';
var $_db_table;
var $_base_dir;
var $_event;

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudirlc() {
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
		$this->_db_table = "cloud_irlc";
	}



// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudirlc object filled from the db
// ---------------------------------------------------------------------------------

// returns an appliance from the db selected by id or appliance_id
function get_instance($id, $appliance_id) {
	$db=openqrm_get_db_connection();
	if ("$id" != "") {
		$cloudirlc_array = $db->Execute("select * from ".$this->_db_table." where cd_id=$id");
	} else if ("$appliance_id" != "") {
		$cloudirlc_array = $db->Execute("select * from ".$this->_db_table." where cd_appliance_id=$appliance_id");
	} else {
		$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudirlc.class.php", "Could not create instance of cloudirlc without data", "", "", 0, 0, 0);
		return;
	}

	foreach ($cloudirlc_array as $index => $cloudirlc) {
		$this->id = $cloudirlc["cd_id"];
		$this->appliance_id = $cloudirlc["cd_appliance_id"];
		$this->state = $cloudirlc["cd_state"];
	}
	return $this;
}

// returns an cloudirlc from the db selected by id
function get_instance_by_id($id) {
	$this->get_instance($id, "");
	return $this;
}

// returns an cloudirlc from the db selected by the appliance_id
function get_instance_by_appliance_id($appliance_id) {
	$this->get_instance("", $appliance_id);
	return $this;
}

// ---------------------------------------------------------------------------------
// general cloudirlc methods
// ---------------------------------------------------------------------------------




// checks if given cloudirlc id is free in the db
function is_id_free($cloudirlc_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select cd_id from ".$this->_db_table." where cd_id=$cloudirlc_id");
	if (!$rs)
		$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudirlc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}


// adds cloudirlc to the database
function add($cloudirlc_fields) {
	if (!is_array($cloudirlc_fields)) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudirlc.class.php", "cloudirlc_field not well defined", "", "", 0, 0, 0);
		return 1;
	}
	// set stop time and status to now
	$now=$_SERVER['REQUEST_TIME'];
	$db=openqrm_get_db_connection();
	$result = $db->AutoExecute($this->_db_table, $cloudirlc_fields, 'INSERT');
	if (! $result) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudirlc.class.php", "Failed adding new cloudirlc to database", "", "", 0, 0, 0);
	}
}



// removes cloudirlc from the database
function remove($cloudirlc_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("delete from ".$this->_db_table." where cd_id=$cloudirlc_id");
}



// sets the state of a cloudirlc
function set_state($cloudirlc_id, $state_str) {
	$cloudirlc_state = 0;
	switch ($state_str) {
		case "remove":
			$cloudirlc_state = 0;
			break;
		case "pause":
			$cloudirlc_state = 1;
			break;
		case "start_resize":
			$cloudirlc_state = 2;
			break;
		case "resizing":
			$cloudirlc_state = 3;
			break;
		case "end_resize":
			$cloudirlc_state = 4;
			break;
		case "unpause":
			$cloudirlc_state = 5;
			break;
	}
	$db=openqrm_get_db_connection();
	$cloudirlc_set = $db->Execute("update ".$this->_db_table." set cd_state=$cloudirlc_state where cd_id=$cloudirlc_id");
	if (!$cloudirlc_set) {
		$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "cloudirlc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	}
}


// sets the resource of a cloudirlc
function set_resource($cloudirlc_id, $resource_id) {
	$db=openqrm_get_db_connection();
	$cloudirlc_set = $db->Execute("update ".$this->_db_table." set cd_resource_id=$resource_id where cd_id=$cloudirlc_id");
	if (!$cloudirlc_set) {
		$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "cloudirlc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	}
}


// returns the number of cloudirlcs for an cloudirlc type
function get_count() {
	$count=0;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select count(cd_id) as num from ".$this->_db_table);
	if (!$rs) {
		print $db->ErrorMsg();
	} else {
		$count = $rs->fields["num"];
	}
	return $count;
}



// returns a list of all cloudirlc names
function get_list() {
	$query = "select cd_id, cd_appliance_id from ".$this->_db_table;
	$cloudirlc_name_array = array();
	$cloudirlc_name_array = openqrm_db_get_result_double ($query);
	return $cloudirlc_name_array;
}


// returns a list of all cloudirlc ids
function get_all_ids() {
	$cloudirlc_list = array();
	$query = "select cd_id from ".$this->_db_table;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute($query);
	if (!$rs)
		$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudirlc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	while (!$rs->EOF) {
		$cloudirlc_list[] = $rs->fields;
		$rs->MoveNext();
	}
	return $cloudirlc_list;

}




// displays the cloudirlc-overview
function display_overview($offset, $limit, $sort, $order) {
	$db=openqrm_get_db_connection();
	$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
	$cloudirlc_array = array();
	if (!$recordSet) {
		$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudirlc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	} else {
		while (!$recordSet->EOF) {
			array_push($cloudirlc_array, $recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}
	return $cloudirlc_array;
}









// ---------------------------------------------------------------------------------

}


?>
