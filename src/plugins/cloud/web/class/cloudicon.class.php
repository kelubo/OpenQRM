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


// This class represents a cloudicon object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

$CLOUD_ICON_TABLE = "cloud_icons";
global $CLOUD_ICON_TABLE;
$event = new event();
global $event;


class cloudicon {

var $id = '';
var $cu_id = '';
var $type = '';
var $object_id = '';
var $filename = '';

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudicon() {
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
		$this->_db_table = "cloud_icons";;
	}

// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudicon object filled from the db
// ---------------------------------------------------------------------------------

// returns an appliance from the db selected by id or name
function get_instance($id, $cu_id, $type, $object_id) {
	$db=openqrm_get_db_connection();
	if ("$id" != "") {
		$cloudicon_array = $db->Execute("select * from ".$this->_db_table." where ic_id=$id");
	} else if ("$cu_id" != "") {
		$cloudicon_array = $db->Execute("select * from ".$this->_db_table." where ic_cu_id=$cu_id and ic_type=$type and ic_object_id=$object_id");
	} else if ("$object_id" != "") {
		$cloudicon_array = $db->Execute("select * from ".$this->_db_table." where ic_type=$type and ic_object_id=$object_id");
	} else {
		$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudicon.class.php", "Could not create instance of cloudicon without data", "", "", 0, 0, 0);
		return;
	}

	foreach ($cloudicon_array as $index => $cloudicon) {
		$this->id = $cloudicon["ic_id"];
		$this->cu_id = $cloudicon["ic_cu_id"];
		$this->type = $cloudicon["ic_type"];
		$this->object_id = $cloudicon["ic_object_id"];
		$this->filename = $cloudicon["ic_filename"];
	}
	return $this;
}

// returns an cloudicon from the db selected by id
function get_instance_by_id($id) {
	$this->get_instance($id, "", "", "", "");
	return $this;
}

// returns an cloudicon from the db selected by the cu_id, type and object_id
function get_instance_by_details($cu_id, $type, $object_id) {
	$this->get_instance("", $cu_id, $type, $object_id);
	return $this;
}




// ---------------------------------------------------------------------------------
// general cloudicon methods
// ---------------------------------------------------------------------------------




// checks if given cloudicon id is free in the db
function is_id_free($cloudicon_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select ic_id from ".$this->_db_table." where ic_id=$cloudicon_id");
	if (!$rs)
		$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudicon.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}


// adds cloudicon to the database
function add($cloudicon_fields) {
	if (!is_array($cloudicon_fields)) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudicon.class.php", "cloudicon_field not well defined", "", "", 0, 0, 0);
		return 1;
	}
	// set stop time and status to now
	$now=$_SERVER['REQUEST_TIME'];
	$db=openqrm_get_db_connection();
	$result = $db->AutoExecute($this->_db_table, $cloudicon_fields, 'INSERT');
	if (! $result) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudicon.class.php", "Failed adding new cloudicon to database", "", "", 0, 0, 0);
	}
}



// removes cloudicon from the database
function remove($cloudicon_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("delete from ".$this->_db_table." where ic_id=$cloudicon_id");
}


// updates a cloudicon
function update($ic_id, $ic_fields) {
	if ($ic_id < 0 || ! is_array($ic_fields)) {
		$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudicon.class.php", "Unable to update Cloudimage $ic_id", "", "", 0, 0, 0);
		return 1;
	}
	$db=openqrm_get_db_connection();
	unset($ic_fields["ic_id"]);
	$result = $db->AutoExecute($this->_db_table, $ic_fields, 'UPDATE', "ic_id = $ic_id");
	if (! $result) {
		$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudicon.class.php", "Failed updating Cloudimage $ic_id", "", "", 0, 0, 0);
	}
}



// returns the number of cloudicons for an cloudicon type
function get_count() {
	$count=0;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select count(ic_id) as num from ".$this->_db_table);
	if (!$rs) {
		print $db->ErrorMsg();
	} else {
		$count = $rs->fields["num"];
	}
	return $count;
}



// returns a list of all cloudicon ids
function get_all_ids() {
	$cloudicon_list = array();
	$query = "select ic_id from ".$this->_db_table;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute($query);
	if (!$rs)
		$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudicon.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	while (!$rs->EOF) {
		$cloudicon_list[] = $rs->fields;
		$rs->MoveNext();
	}
	return $cloudicon_list;

}




// displays the cloudicon-overview
function display_overview($offset, $limit, $sort, $order) {
	$db=openqrm_get_db_connection();
	$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
	$cloudicon_array = array();
	if (!$recordSet) {
		$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudicon.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	} else {
		while (!$recordSet->EOF) {
			array_push($cloudicon_array, $recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}
	return $cloudicon_array;
}









// ---------------------------------------------------------------------------------

}


?>