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


// This class represents a cloudcreatevmlc object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

$CLOUD_CREATE_VM_LC_TABLE="cloud_create_vm_lc";
global $CLOUD_CREATE_VM_LC_TABLE;
global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;


class cloudcreatevmlc {

var $id = '';
var $resource_id = '';
var $cr_id = '';
var $cr_resource_number = '';
var $request_time = '';
var $vm_create_timeout = '';
var $state = '';
// 0 - created
// 1 - starting, not idle yet
// 2 - idle

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudcreatevmlc() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		global $CLOUD_CREATE_VM_LC_TABLE, $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = "cloud_create_vm_lc";
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
	}

// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudcreatevmlc object filled from the db
// ---------------------------------------------------------------------------------

// returns an appliance from the db selected by id or name
function get_instance($id, $resource_id, $cr_id, $cr_resource_number) {
	global $CLOUD_CREATE_VM_LC_TABLE;
	global $event;
	$db=openqrm_get_db_connection();
	if ("$id" != "") {
		$cloudcreatevmlc_array = $db->Execute("select * from ".$this->_db_table." where vc_id=$id");
	} else if ("$resource_id" != "") {
		$cloudcreatevmlc_array = $db->Execute("select * from ".$this->_db_table." where vc_resource_id=$resource_id");
	} else if ("$cr_id" != "") {
		$cloudcreatevmlc_array = $db->Execute("select * from ".$this->_db_table." where vc_cr_id=$cr_id and vc_cr_resource_number=$cr_resource_number");
	} else {
		$event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudcreatevmlc.class.php", "Could not create instance of cloudcreatevmlc without data", "", "", 0, 0, 0);
		return;
	}

	foreach ($cloudcreatevmlc_array as $index => $cloudcreatevmlc) {
		$this->id = $cloudcreatevmlc["vc_id"];
		$this->resource_id = $cloudcreatevmlc["vc_resource_id"];
		$this->cr_id = $cloudcreatevmlc["vc_cr_id"];
		$this->cr_resource_number = $cloudcreatevmlc["vc_cr_resource_number"];
		$this->request_time = $cloudcreatevmlc["vc_request_time"];
		$this->vm_create_timeout = $cloudcreatevmlc["vc_vm_create_timeout"];
		$this->state = $cloudcreatevmlc["vc_state"];
	}
	return $this;
}


// returns an cloudcreatevmlc from the db selected by id
function get_instance_by_id($id) {
	$this->get_instance($id, "", "", "");
	return $this;
}

// returns an cloudcreatevmlc from the db selected by the resource_id
function get_instance_by_resource_id($resource_id) {
	$this->get_instance("", $resource_id, "", "");
	return $this;
}

// returns an cloudcreatevmlc from the db selected by the cr details
function get_instance_by_cr_details($cr_id, $cr_resource_number) {
	$this->get_instance("", "", $cr_id, $cr_resource_number);
	return $this;
}


// ---------------------------------------------------------------------------------
// general cloudcreatevmlc methods
// ---------------------------------------------------------------------------------




// checks if given cloudcreatevmlc id is free in the db
function is_id_free($cloudcreatevmlc_id) {
	global $CLOUD_CREATE_VM_LC_TABLE;
	global $event;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select vc_id from ".$this->_db_table." where vc_id=$cloudcreatevmlc_id");
	if (!$rs)
		$event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudcreatevmlc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}


// adds cloudcreatevmlc to the database
function add($cloudcreatevmlc_fields) {
	global $CLOUD_CREATE_VM_LC_TABLE;
	global $event;
	if (!is_array($cloudcreatevmlc_fields)) {
		$event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudcreatevmlc.class.php", "cloudcreatevmlc_field not well defined", "", "", 0, 0, 0);
		return 1;
	}
	// set stop time and status to now
	$now=$_SERVER['REQUEST_TIME'];
	$db=openqrm_get_db_connection();
	$result = $db->AutoExecute($this->_db_table, $cloudcreatevmlc_fields, 'INSERT');
	if (! $result) {
		$event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudcreatevmlc.class.php", "Failed adding new cloudcreatevmlc to database", "", "", 0, 0, 0);
	}
}



// removes cloudcreatevmlc from the database
function remove($cloudcreatevmlc_id) {
	global $CLOUD_CREATE_VM_LC_TABLE;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("delete from ".$this->_db_table." where vc_id=$cloudcreatevmlc_id");
}


// updates a cloudcreatevmlc
function update($vc_id, $vc_fields) {
	global $CLOUD_CREATE_VM_LC_TABLE;
	global $event;
	if ($vc_id < 0 || ! is_array($vc_fields)) {
		$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudcreatevmlc.class.php", "Unable to update cloudcreatevmlc $vc_id", "", "", 0, 0, 0);
		return 1;
	}
	$db=openqrm_get_db_connection();
	unset($vc_fields["vc_id"]);
	$result = $db->AutoExecute($this->_db_table, $vc_fields, 'UPDATE', "vc_id = $vc_id");
	if (! $result) {
		$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudcreatevmlc.class.php", "Failed updating cloudcreatevmlc $vc_id", "", "", 0, 0, 0);
	}
}



// returns the number of cloudcreatevmlcs for an cloudcreatevmlc type
function get_count() {
	global $CLOUD_CREATE_VM_LC_TABLE;
	$count=0;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select count(vc_id) as num from ".$this->_db_table);
	if (!$rs) {
		print $db->ErrorMsg();
	} else {
		$count = $rs->fields["num"];
	}
	return $count;
}



// returns a list of all cloudcreatevmlc ids
function get_all_ids() {
	global $CLOUD_CREATE_VM_LC_TABLE;
	global $event;
	$cloudcreatevmlc_list = array();
	$query = "select vc_id from ".$this->_db_table;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute($query);
	if (!$rs)
		$event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudcreatevmlc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	while (!$rs->EOF) {
		$cloudcreatevmlc_list[] = $rs->fields;
		$rs->MoveNext();
	}
	return $cloudcreatevmlc_list;

}




// displays the cloudcreatevmlc-overview
function display_overview($offset, $limit, $sort, $order) {
	global $CLOUD_CREATE_VM_LC_TABLE;
	global $event;
	$db=openqrm_get_db_connection();
	$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
	$cloudcreatevmlc_array = array();
	if (!$recordSet) {
		$event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudcreatevmlc.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	} else {
		while (!$recordSet->EOF) {
			array_push($cloudcreatevmlc_array, $recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}
	return $cloudcreatevmlc_array;
}









// ---------------------------------------------------------------------------------

}


?>