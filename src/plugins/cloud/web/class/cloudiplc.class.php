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


// This class represents a cloud image-private-life-cycle object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

$CLOUD_IMAGE_PRIVATE_LIVE_CYCLE_TABLE="cloud_iplc";
global $CLOUD_IMAGE_PRIVATE_LIVE_CYCLE_TABLE;
$event = new event();
global $event;


class cloudiplc {

var $id = '';
var $appliance_id = '';
var $cu_id = '';
var $state = '';
var $start_private = '';
var $_db_table;
var $_base_dir;
var $_event;


	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudiplc() {
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
		$this->_db_table = "cloud_iplc";
	}



// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudiplc object filled from the db
// ---------------------------------------------------------------------------------

// returns an appliance from the db selected by id or appliance_id
function get_instance($id, $appliance_id) {
	$db=openqrm_get_db_connection();
	if ("$id" != "") {
		$cloudiplc_array = $db->Execute("select * from ".$this->_db_table." where cp_id=$id");
	} else if ("$appliance_id" != "") {
		$cloudiplc_array = $db->Execute("select * from ".$this->_db_table." where cp_appliance_id=$appliance_id");
	} else {
		$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudiplc.class.php", "Could not create instance of cloudiplc without data", "", "", 0, 0, 0);
		return;
	}

	foreach ($cloudiplc_array as $index => $cloudiplc) {
		$this->id = $cloudiplc["cp_id"];
		$this->appliance_id = $cloudiplc["cp_appliance_id"];
		$this->cu_id = $cloudiplc["cp_cu_id"];
		$this->state = $cloudiplc["cp_state"];
		$this->start_private = $cloudiplc["cp_start_private"];
	}
	return $this;
}

// returns an cloudiplc from the db selected by id
function get_instance_by_id($id) {
	$this->get_instance($id, "");
	return $this;
}

// returns an cloudiplc from the db selected by the appliance_id
function get_instance_by_appliance_id($appliance_id) {
	$this->get_instance("", $appliance_id);
	return $this;
}

// ---------------------------------------------------------------------------------
// general cloudiplc methods
// ---------------------------------------------------------------------------------




// checks if given cloudiplc id is free in the db
function is_id_free($cloudiplc_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select cp_id from ".$this->_db_table." where cp_id=$cloudiplc_id");
	if (!$rs)
		$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudiplc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}


// adds cloudiplc to the database
function add($cloudiplc_fields) {
	if (!is_array($cloudiplc_fields)) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudiplc.class.php", "cloudiplc_field not well defined", "", "", 0, 0, 0);
		return 1;
	}
	// set stop time and status to now
	$now=$_SERVER['REQUEST_TIME'];
	$db=openqrm_get_db_connection();
	$result = $db->AutoExecute($this->_db_table, $cloudiplc_fields, 'INSERT');
	if (! $result) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudiplc.class.php", "Failed adding new cloudiplc to database", "", "", 0, 0, 0);
	}
}



// removes cloudiplc from the database
function remove($cloudiplc_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("delete from ".$this->_db_table." where cp_id=$cloudiplc_id");
}



// sets the state of a cloudiplc
function set_state($cloudiplc_id, $state_str) {
	$cloudiplc_state = 0;
	switch ($state_str) {
		case "remove":
			$cloudiplc_state = 0;
			break;
		case "pause":
			$cloudiplc_state = 1;
			break;
		case "start_private":
			$cloudiplc_state = 2;
			break;
		case "cloning":
			$cloudiplc_state = 3;
			break;
		case "end_private":
			$cloudiplc_state = 4;
			break;
		case "unpause":
			$cloudiplc_state = 5;
			break;
	}
	$db=openqrm_get_db_connection();
	$cloudiplc_set = $db->Execute("update ".$this->_db_table." set cp_state=$cloudiplc_state where cp_id=$cloudiplc_id");
	if (!$cloudiplc_set) {
		$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "cloudiplc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	}
}


// sets the resource of a cloudiplc
function set_resource($cloudiplc_id, $resource_id) {
	$db=openqrm_get_db_connection();
	$cloudiplc_set = $db->Execute("update ".$this->_db_table." set cp_resource_id=$resource_id where cp_id=$cloudiplc_id");
	if (!$cloudiplc_set) {
		$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "cloudiplc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	}
}


// returns the number of cloudiplcs for an cloudiplc type
function get_count() {
	$count=0;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select count(cp_id) as num from ".$this->_db_table);
	if (!$rs) {
		print $db->ErrorMsg();
	} else {
		$count = $rs->fields["num"];
	}
	return $count;
}



// returns a list of all cloudiplc names
function get_list() {
	$query = "select cp_id, cp_appliance_id from ".$this->_db_table;
	$cloudiplc_name_array = array();
	$cloudiplc_name_array = openqrm_db_get_result_double ($query);
	return $cloudiplc_name_array;
}


// returns a list of all cloudiplc ids
function get_all_ids() {
	$cloudiplc_list = array();
	$query = "select cp_id from ".$this->_db_table;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute($query);
	if (!$rs)
		$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudiplc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	while (!$rs->EOF) {
		$cloudiplc_list[] = $rs->fields;
		$rs->MoveNext();
	}
	return $cloudiplc_list;

}




// displays the cloudiplc-overview
function display_overview($offset, $limit, $sort, $order) {
	$db=openqrm_get_db_connection();
	$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
	$cloudiplc_array = array();
	if (!$recordSet) {
		$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudiplc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	} else {
		while (!$recordSet->EOF) {
			array_push($cloudiplc_array, $recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}
	return $cloudiplc_array;
}









// ---------------------------------------------------------------------------------

}


?>
