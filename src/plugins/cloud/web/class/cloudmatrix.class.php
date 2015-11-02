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


// This class represents a cloudmatrix object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

$CLOUD_MATRIX_TABLE="cloud_matrix";
global $CLOUD_MATRIX_TABLE;
global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;


class cloudmatrix {

	var $id = '';
	var $cu_id = '';
	var $description = '';
	var $row01 = '';
	var $row02 = '';
	var $row03 = '';
	var $row04 = '';
	var $row05 = '';
	var $row06 = '';
	var $row07 = '';
	var $row08 = '';
	var $row09 = '';
	var $row10 = '';
	var $row11 = '';
	var $row12 = '';

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudmatrix() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		global $CLOUD_MATRIX_TABLE, $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = $CLOUD_MATRIX_TABLE;
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
	}

	// ---------------------------------------------------------------------------------
	// methods to create an instance of a cloudmatrix object filled from the db
	// ---------------------------------------------------------------------------------

	// returns an appliance from the db selected by id or name
	function get_instance($id, $cu_id) {
		global $CLOUD_MATRIX_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		if ("$id" != "") {
			$cloudmatrix_array = $db->Execute("select * from $CLOUD_MATRIX_TABLE where cm_id=$id");
		} else if ("$cu_id" != "") {
			$cloudmatrix_array = $db->Execute("select * from $CLOUD_MATRIX_TABLE where cm_cu_id=$cu_id");
		} else {
			$event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudmatrix.class.php", "Could not create instance of cloudmatrix without data", "", "", 0, 0, 0);
			return;
		}

		foreach ($cloudmatrix_array as $index => $cloudmatrix) {
			$this->id = $cloudmatrix["cm_id"];
			$this->cu_id = $cloudmatrix["cm_cu_id"];
			$this->description = $cloudmatrix["cm_description"];
			$this->row01 = $cloudmatrix["cm_row01"];
			$this->row02 = $cloudmatrix["cm_row02"];
			$this->row03 = $cloudmatrix["cm_row03"];
			$this->row04 = $cloudmatrix["cm_row04"];
			$this->row05 = $cloudmatrix["cm_row05"];
			$this->row06 = $cloudmatrix["cm_row06"];
			$this->row07 = $cloudmatrix["cm_row07"];
			$this->row08 = $cloudmatrix["cm_row08"];
			$this->row09 = $cloudmatrix["cm_row09"];
			$this->row10 = $cloudmatrix["cm_row10"];
			$this->row11 = $cloudmatrix["cm_row11"];
			$this->row12 = $cloudmatrix["cm_row12"];
		}
		return $this;
	}

	// returns an cloudmatrix from the db selected by id
	function get_instance_by_id($id) {
		$this->get_instance($id, "");
		return $this;
	}

	// returns an cloudmatrix from the db selected by the cu_id
	function get_instance_by_cloud_user_id($cu_id) {
		global $event;
		$this->get_instance("", $cu_id);
		// in case the user does not have a cloudmatrix yet, create it
		if ($this->cu_id != $cu_id) {
			$event->log("get_instance_by_cloud_user_id", $_SERVER['REQUEST_TIME'], 5, "cloudmatrix.class.php", "Creating new infrastructure matrix for User $cu_id", "", "", 0, 0, 0);
			// create table entry
			$cloud_matrix_id  = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$cloud_matrix_arr = array(
					'cm_id' => $cloud_matrix_id,
					'cm_cu_id' => $cu_id,
					'cm_description' => "Cloud Infrastructure by CloudUser ".$cu_id,
					'cm_row01' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row02' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row03' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row04' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row05' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row06' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row07' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row08' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row09' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row10' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row11' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
					'cm_row12' => "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0",
			);
			$this->add($cloud_matrix_arr);
			// refresh the matrix object
			$this->get_instance("", $cu_id);
		}
		return $this;
	}

	// ---------------------------------------------------------------------------------
	// general cloudmatrix methods
	// ---------------------------------------------------------------------------------




	// checks if given cloudmatrix id is free in the db
	function is_id_free($cloudmatrix_id) {
		global $CLOUD_MATRIX_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select cm_id from $CLOUD_MATRIX_TABLE where cm_id=$cloudmatrix_id");
		if (!$rs)
			$event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudmatrix.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// adds cloudmatrix to the database
	function add($cloudmatrix_fields) {
		global $CLOUD_MATRIX_TABLE;
		global $event;
		if (!is_array($cloudmatrix_fields)) {
			$event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudmatrix.class.php", "cloudmatrix_field not well defined", "", "", 0, 0, 0);
			return 1;
		}
		// set stop time and status to now
		$now=$_SERVER['REQUEST_TIME'];
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($CLOUD_MATRIX_TABLE, $cloudmatrix_fields, 'INSERT');
		if (! $result) {
			$event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudmatrix.class.php", "Failed adding new cloudmatrix to database", "", "", 0, 0, 0);
		}
	}



	// removes cloudmatrix from the database
	function remove($cloudmatrix_id) {
		global $CLOUD_MATRIX_TABLE;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $CLOUD_MATRIX_TABLE where cm_id=$cloudmatrix_id");
	}


	// updates a cloudmatrix
	function update($cm_id, $cm_fields) {
		global $CLOUD_MATRIX_TABLE;
		global $event;
		if ($cm_id < 0 || ! is_array($cm_fields)) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudmatrix.class.php", "Unable to update cloudmatrix $cm_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($cm_fields["cm_id"]);
		$result = $db->AutoExecute($this->_db_table, $cm_fields, 'UPDATE', "cm_id = $cm_id");
		if (! $result) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudmatrix.class.php", "Failed updating cloudmatrix $cm_id", "", "", 0, 0, 0);
		}
	}



	// returns the number of cloudmatrixs for an cloudmatrix type
	function get_count() {
		global $CLOUD_MATRIX_TABLE;
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(cm_id) as num from $CLOUD_MATRIX_TABLE");
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}



	// returns a list of all cloudmatrix ids
	function get_all_ids() {
		global $CLOUD_MATRIX_TABLE;
		global $event;
		$cloudmatrix_list = array();
		$query = "select cm_id from $CLOUD_MATRIX_TABLE";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudmatrix.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudmatrix_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudmatrix_list;

	}




	// displays the cloudmatrix-overview
	function display_overview($offset, $limit, $sort, $order) {
		global $CLOUD_MATRIX_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from $CLOUD_MATRIX_TABLE order by $sort $order", $limit, $offset);
		$cloudmatrix_array = array();
		if (!$recordSet) {
			$event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudmatrix.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($cloudmatrix_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $cloudmatrix_array;
	}









// ---------------------------------------------------------------------------------

}


?>