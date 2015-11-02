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


// This class represents a cloud hostlimit in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";

$CLOUD_HOSTLIMIT_TABLE="cloud_hostlimit";
global $CLOUD_HOSTLIMIT_TABLE;
$event = new event();
global $event;

class cloudhostlimit {

var $id = '';
var $resource_id = '';
var $max_vms = '';
var $current_vms = '';


	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudhostlimit() {
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
		$this->_db_table = 'cloud_hostlimit';
	}




// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudhostlimit object filled from the db
// ---------------------------------------------------------------------------------

// returns an appliance from the db selected by id or name
function get_instance($id, $resource_id) {
	$db=openqrm_get_db_connection();
	if ("$id" != "") {
		$cloudhostlimit_array = $db->Execute("select * from ".$this->_db_table." where hl_id=$id");
	} else if ("$resource_id" != "") {
		$cloudhostlimit_array = $db->Execute("select * from ".$this->_db_table." where hl_resource_id=$resource_id");
	} else {
		$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", "Could not create instance of event without data", "", "", 0, 0, 0);
		return;
	}

	foreach ($cloudhostlimit_array as $index => $cloudhostlimit) {
		$this->id = $cloudhostlimit["hl_id"];
		$this->resource_id = $cloudhostlimit["hl_resource_id"];
		$this->current_vms = $cloudhostlimit["hl_current_vms"];
		$this->max_vms = $cloudhostlimit["hl_max_vms"];
	}
	return $this;
}

// returns an appliance from the db selected by id
function get_instance_by_id($id) {
	$this->get_instance($id, "");
	return $this;
}

// returns an appliance from the db selected by iname
function get_instance_by_resource($resource_id) {
	$this->get_instance("", $resource_id);
	return $this;
}



// ---------------------------------------------------------------------------------
// general cloudhostlimit methods
// ---------------------------------------------------------------------------------




// checks if given cloudhostlimit id is free in the db
function is_id_free($cloudhostlimit_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select hl_id from ".$this->_db_table." where hl_id=$cloudhostlimit_id");
	if (!$rs)
		$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}


// checks if given cloudhostlimit exist by resource id
function exists_by_resource_id($resource_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select hl_id from ".$this->_db_table." where hl_resource_id=$resource_id");
	if (!$rs)
		$this->_event->log("exists_by_resource_id", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	if ($rs->EOF) {
		return false;
	} else {
		return true;
	}
}


// adds cloudhostlimit to the database
function add($cloudhostlimit_fields) {
	if (!is_array($cloudhostlimit_fields)) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", "cloudhostlimit_fields not well defined", "", "", 0, 0, 0);
		return 1;
	}
	// set stop time and status to now
	$now=$_SERVER['REQUEST_TIME'];
	$db=openqrm_get_db_connection();
	$result = $db->AutoExecute($this->_db_table, $cloudhostlimit_fields, 'INSERT');
	if (! $result) {
		$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", "Failed adding new cloudhostlimit to database", "", "", 0, 0, 0);
	}
}


// updates cloudhostlimit in the database
function update($cloudhostlimit_id, $cloudhostlimit_fields) {
	if (!is_array($cloudhostlimit_fields)) {
		$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", "Unable to update cloudhostlimit $cloudhostlimit_id", "", "", 0, 0, 0);
		return 1;
	}
	$db=openqrm_get_db_connection();
	unset($cloudhostlimit_fields["hl_id"]);
	$result = $db->AutoExecute($this->_db_table, $cloudhostlimit_fields, 'UPDATE', "hl_id = $cloudhostlimit_id");
	if (! $result) {
		$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", "Failed updating cloudhostlimit $cloudhostlimit_id", "", "", 0, 0, 0);
	}
}


// removes cloudhostlimit from the database
function remove($cloudhostlimit_id) {
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("delete from ".$this->_db_table." where hl_id=$cloudhostlimit_id");
}




// returns the number of cloudhostlimit for an cloudhostlimit type
function get_count() {
	$count=0;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select count(hl_id) as num from ".$this->_db_table);
	if (!$rs) {
		print $db->ErrorMsg();
	} else {
		$count = $rs->fields["num"];
	}
	return $count;
}



// returns a list of all cloudhostlimit ids
function get_all_ids() {
	$cloudhostlimit_list = array();
	$query = "select hl_id from ".$this->_db_table;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute($query);
	if (!$rs)
		$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	while (!$rs->EOF) {
		$cloudhostlimit_list[] = $rs->fields;
		$rs->MoveNext();
	}
	return $cloudhostlimit_list;

}





// displays the cloudhostlimit-overview
function display_overview($offset, $limit, $sort, $order) {
	$db=openqrm_get_db_connection();
	$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
	$cloudhostlimit_array = array();
	if (!$recordSet) {
		$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudhostlimit.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	} else {
		while (!$recordSet->EOF) {
			array_push($cloudhostlimit_array, $recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}
	return $cloudhostlimit_array;
}









// ---------------------------------------------------------------------------------

}

?>