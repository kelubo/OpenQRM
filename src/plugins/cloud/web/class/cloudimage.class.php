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


// This class represents a cloudimage object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

$CLOUD_IMAGE_TABLE="cloud_image";
global $CLOUD_IMAGE_TABLE;
$event = new event();
global $event;


class cloudimage {

var $id = '';
var $cr_id = '';
var $image_id = '';
var $appliance_id = '';
var $resource_id = '';
var $disk_size = '';
var $disk_rsize = '';
var $clone_name = '';
var $state = '';

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudimage() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		global $CLOUD_IMAGE_TABLE;
		$this->_event = new event();
		$this->_db_table = "cloud_image";
	}

// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudimage object filled from the db
// ---------------------------------------------------------------------------------

// returns an appliance from the db selected by id or name
function get_instance($id, $image_id) {
	$db=openqrm_get_db_connection();
	if ("$id" != "") {
		$cloudimage_array = $db->Execute("select * from ".$this->_db_table." where ci_id=$id");
	} else if ("$image_id" != "") {
		$cloudimage_array = $db->Execute("select * from ".$this->_db_table." where ci_image_id=$image_id");
	} else {
		$error = '';
		foreach(debug_backtrace() as $key => $msg) {
			if($key === 1) {
				$error .= '( '.basename($msg['file']).' '.$msg['line'].' )';
			}
		}
		$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", "Could not create instance of cloudimage without data ".$error, "", "", 0, 0, 0);
		return;
	}

	foreach ($cloudimage_array as $index => $cloudimage) {
		$this->id = $cloudimage["ci_id"];
		$this->cr_id = $cloudimage["ci_cr_id"];
		$this->image_id = $cloudimage["ci_image_id"];
		$this->appliance_id = $cloudimage["ci_appliance_id"];
		$this->resource_id = $cloudimage["ci_resource_id"];
		$this->disk_size = $cloudimage["ci_disk_size"];
		$this->disk_rsize = $cloudimage["ci_disk_rsize"];
		$this->clone_name = $cloudimage["ci_clone_name"];
		$this->state = $cloudimage["ci_state"];
	}
	return $this;
}

// returns an cloudimage from the db selected by id
function get_instance_by_id($id) {
	$this->get_instance($id, "");
	return $this;
}

// returns an cloudimage from the db selected by the image_id
function get_instance_by_image_id($image_id) {
	$this->get_instance("", $image_id);
	return $this;
}

// ---------------------------------------------------------------------------------
// general cloudimage methods
// ---------------------------------------------------------------------------------




// checks if given cloudimage id is free in the db
function is_id_free($cloudimage_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select ci_id from ".$this->_db_table." where ci_id=$cloudimage_id");
	if (!$rs)
		$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}


// adds cloudimage to the database
function add($cloudimage_fields) {
	if (!is_array($cloudimage_fields)) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", "cloudimage_field not well defined", "", "", 0, 0, 0);
		return 1;
	}
	// set stop time and status to now
	$now=$_SERVER['REQUEST_TIME'];
	$db=openqrm_get_db_connection();
	$result = $db->AutoExecute($this->_db_table, $cloudimage_fields, 'INSERT');
	if (! $result) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", "Failed adding new cloudimage to database", "", "", 0, 0, 0);
	}
}



// removes cloudimage from the database
function remove($cloudimage_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("delete from ".$this->_db_table." where ci_id=$cloudimage_id");
}


// updates a cloudimage
function update($ci_id, $ci_fields) {
	if ($ci_id < 0 || ! is_array($ci_fields)) {
		$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", "Unable to update Cloudimage $ci_id", "", "", 0, 0, 0);
		return 1;
	}
	$db=openqrm_get_db_connection();
	unset($ci_fields["ci_id"]);
	$result = $db->AutoExecute($this->_db_table, $ci_fields, 'UPDATE', "ci_id = $ci_id");
	if (! $result) {
		$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", "Failed updating Cloudimage $ci_id", "", "", 0, 0, 0);
	}
}



// sets the state of a cloudimage
function set_state($cloudimage_id, $state_str) {
	$cloudimage_state = 0;
	switch ($state_str) {
		case "remove":
			$cloudimage_state = 0;
			break;
		case "active":
			$cloudimage_state = 1;
			break;
		case "resizing":
			$cloudimage_state = 2;
			break;
		case "private":
			$cloudimage_state = 3;
			break;
	}
	$db=openqrm_get_db_connection();
	$cloudimage_set = $db->Execute("update ".$this->_db_table." set ci_state=$cloudimage_state where ci_id=$cloudimage_id");
	if (!$cloudimage_set) {
		$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	}
}


// sets the resource of a cloudimage
function set_resource($cloudimage_id, $resource_id) {
	$db=openqrm_get_db_connection();
	$cloudimage_set = $db->Execute("update ".$this->_db_table." set ci_resource_id=$resource_id where ci_id=$cloudimage_id");
	if (!$cloudimage_set) {
		$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	}
}


// returns the number of cloudimages for an cloudimage type
function get_count() {
	$count=0;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select count(ci_id) as num from ".$this->_db_table);
	if (!$rs) {
		print $db->ErrorMsg();
	} else {
		$count = $rs->fields["num"];
	}
	return $count;
}



// returns a list of all cloudimage names
function get_list() {
	$query = "select ci_id, ci_cr_id from ".$this->_db_table;
	$cloudimage_name_array = array();
	$cloudimage_name_array = openqrm_db_get_result_double ($query);
	return $cloudimage_name_array;
}


// returns a list of all cloudimage ids
function get_all_ids() {
	$cloudimage_list = array();
	$query = "select ci_id from ".$this->_db_table;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute($query);
	if (!$rs)
		$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	while (!$rs->EOF) {
		$cloudimage_list[] = $rs->fields;
		$rs->MoveNext();
	}
	return $cloudimage_list;

}




// displays the cloudimage-overview
function display_overview($offset, $limit, $sort, $order) {
	$db=openqrm_get_db_connection();
	$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
	$cloudimage_array = array();
	if (!$recordSet) {
		$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudimage.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	} else {
		while (!$recordSet->EOF) {
			array_push($cloudimage_array, $recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}
	return $cloudimage_array;
}









// ---------------------------------------------------------------------------------

}


?>
