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


// This class represents a cloud resource pool in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";

$CLOUD_RESOURCE_POOL_TABLE="cloud_respool";
global $CLOUD_RESOURCE_POOL_TABLE;
$event = new event();
global $event;

class cloudrespool {

	var $id = '';
	var $resource_id = '';
	var $cg_id = '';

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudrespool() {
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
		$this->_db_table = 'cloud_respool';
	}




	// ---------------------------------------------------------------------------------
	// methods to create an instance of a cloudrespool object filled from the db
	// ---------------------------------------------------------------------------------

	// returns an appliance from the db selected by id or name
	function get_instance($id, $resource_id) {
		$db=openqrm_get_db_connection();
		if ("$id" != "") {
			$clouduser_array = $db->Execute("select * from ".$this->_db_table." where rp_id=$id");
		} else if ("$resource_id" != "") {
			$clouduser_array = $db->Execute("select * from ".$this->_db_table." where rp_resource_id='$resource_id'");
		} else {
			$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", "Could not create instance of event without data", "", "", 0, 0, 0);
			return;
		}

		foreach ($clouduser_array as $index => $clouduser) {
			$this->id = $clouduser["rp_id"];
			$this->resource_id = $clouduser["rp_resource_id"];
			$this->cg_id = $clouduser["rp_cg_id"];
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
	// general cloudrespool methods
	// ---------------------------------------------------------------------------------




	// checks if given cloudrespool id is free in the db
	function is_id_free($cloudrespool_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select rp_id from ".$this->_db_table." where rp_id=$cloudrespool_id");
		if (!$rs)
			$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}

	// checks if given cloudrespool exist by resource id
	function exists_by_resource_id($resource_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select rp_id from ".$this->_db_table." where rp_resource_id=$resource_id");
		if (!$rs)
			$this->_event->log("exists_by_resource_id", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return false;
		} else {
			return true;
		}
	}


	// adds cloudrespool to the database
	function add($cloudrespool_fields) {
		if (!is_array($cloudrespool_fields)) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", "cloudrespool_fields not well defined", "", "", 0, 0, 0);
			return 1;
		}
		// set stop time and status to now
		$now=$_SERVER['REQUEST_TIME'];
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($this->_db_table, $cloudrespool_fields, 'INSERT');
		if (! $result) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", "Failed adding new cloudrespool to database", "", "", 0, 0, 0);
		}
	}


	// updates cloudrespool in the database
	function update($cloudrespool_id, $cloudrespool_fields) {
		if (!is_array($cloudrespool_fields)) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", "Unable to update cloudrespool $cloudrespool_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($cloudrespool_fields["clouduser_id"]);
		$result = $db->AutoExecute($this->_db_table, $cloudrespool_fields, 'UPDATE', "rp_id = $cloudrespool_id");
		if (! $result) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", "Failed updating cloudrespool $cloudrespool_id", "", "", 0, 0, 0);
		}
	}


	// removes cloudrespool from the database
	function remove($cloudrespool_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from ".$this->_db_table." where rp_id=$cloudrespool_id");
	}




	// returns the number of cloudrespool for an clouduser type
	function get_count() {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(rp_id) as num from ".$this->_db_table);
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}



	// returns a list of all cloudrespool ids
	function get_all_ids() {
		$clouduser_list = array();
		$query = "select rp_id from ".$this->_db_table;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$clouduser_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $clouduser_list;

	}





	// displays the clouduser-overview
	function display_overview($offset, $limit, $sort, $order) {
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
		$clouduser_array = array();
		if (!$recordSet) {
			$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudrespool.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($clouduser_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $clouduser_array;
	}









// ---------------------------------------------------------------------------------

}

?>