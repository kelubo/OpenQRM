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


// This class represents a cloudmatrixobject object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

$CLOUD_MATRIX_OBJECT_TABLE="cloud_matrix_object";
global $CLOUD_MATRIX_OBJECT_TABLE;
global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;


class cloudmatrixobject {

	var $id = '';
	var $pr_id = '';
	var $cr_id = '';
	var $ca_id = '';
	var $ne_id = '';
	var $table = '';
	var $x = '';
	var $y = '';
	var $state = '';

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudmatrixobject() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		global $CLOUD_MATRIX_OBJECT_TABLE, $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = $CLOUD_MATRIX_OBJECT_TABLE;
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
	}

	// ---------------------------------------------------------------------------------
	// methods to create an instance of a cloudmatrixobject object filled from the db
	// ---------------------------------------------------------------------------------

	// returns an appliance from the db selected by id or name
	function get_instance($id, $pr_id, $cr_id, $ca_id) {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		if ("$id" != "") {
			$cloudmatrixobject_array = $db->Execute("select * from $CLOUD_MATRIX_OBJECT_TABLE where mo_id=$id");
		} else if ("$pr_id" != "") {
			$cloudmatrixobject_array = $db->Execute("select * from $CLOUD_MATRIX_OBJECT_TABLE where mo_pr_id=$pr_id");
		} else if ("$cr_id" != "") {
			$cloudmatrixobject_array = $db->Execute("select * from $CLOUD_MATRIX_OBJECT_TABLE where mo_cr_id=$cr_id");
		} else if ("$ca_id" != "") {
			$cloudmatrixobject_array = $db->Execute("select * from $CLOUD_MATRIX_OBJECT_TABLE where mo_ca_id=$ca_id");
		} else {
			//$event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", "Could not create instance of cloudmatrixobject without data", "", "", 0, 0, 0);
			return;
		}

		foreach ($cloudmatrixobject_array as $index => $cloudmatrixobject) {
			$this->id = $cloudmatrixobject["mo_id"];
			$this->pr_id = $cloudmatrixobject["mo_pr_id"];
			$this->cr_id = $cloudmatrixobject["mo_cr_id"];
			$this->ca_id = $cloudmatrixobject["mo_ca_id"];
			$this->ne_id = $cloudmatrixobject["mo_ne_id"];
			$this->table = $cloudmatrixobject["mo_table"];
			$this->x = $cloudmatrixobject["mo_x"];
			$this->y = $cloudmatrixobject["mo_y"];
			$this->state = $cloudmatrixobject["mo_state"];
		}
		return $this;
	}

	// returns an cloudmatrixobject from the db selected by id
	function get_instance_by_id($id) {
		$this->get_instance($id, "", "", "");
		return $this;
	}

	// returns an cloudmatrixobject from the db selected by the pr_id
	function get_instance_by_pr_id($pr_id) {
		$this->get_instance("", $pr_id, "", "");
		return $this;
	}

	// returns an cloudmatrixobject from the db selected by the cr_id
	function get_instance_by_cr_id($cr_id) {
		$this->get_instance("", "", $cr_id, "");
		return $this;
	}

	// returns an cloudmatrixobject from the db selected by the ca_id
	function get_instance_by_ca_id($ca_id) {
		$this->get_instance("", "", "", $ca_id);
		return $this;
	}




	// special get_instance_by_cr_with_empty_ca
	function get_instance_by_cr_with_empty_ca($cr_id) {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		if ("$cr_id" != "") {
			$cloudmatrixobject_array = $db->SelectLimit("select * from $CLOUD_MATRIX_OBJECT_TABLE where mo_cr_id=$cr_id and mo_ca_id=0", 1, 0);
		} else {
			$event->log("get_instance_by_cr_with_empty_ca", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", "Could not create instance of cloudmatrixobject without data", "", "", 0, 0, 0);
			return;
		}

		foreach ($cloudmatrixobject_array as $index => $cloudmatrixobject) {
			$this->id = $cloudmatrixobject["mo_id"];
			$this->pr_id = $cloudmatrixobject["mo_pr_id"];
			$this->cr_id = $cloudmatrixobject["mo_cr_id"];
			$this->ca_id = $cloudmatrixobject["mo_ca_id"];
			$this->ne_id = $cloudmatrixobject["mo_ne_id"];
			$this->table = $cloudmatrixobject["mo_table"];
			$this->x = $cloudmatrixobject["mo_x"];
			$this->y = $cloudmatrixobject["mo_y"];
			$this->state = $cloudmatrixobject["mo_state"];
		}
		return $this;
	}



	// special get_instance_by_pr_x_y
	function get_instance_by_pr_x_y($pr_id, $pos_x, $pos_y) {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		if ("$pr_id" != "") {

			$event->log("get_instance_by_pr_x_y", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", "select * from $CLOUD_MATRIX_OBJECT_TABLE where mo_pr_id=$pr_id and mo_x=$pos_x and mo_y=$pos_y", "", "", 0, 0, 0);

			$cloudmatrixobject_array = $db->Execute("select * from $CLOUD_MATRIX_OBJECT_TABLE where mo_pr_id=$pr_id and mo_x=$pos_x and mo_y=$pos_y");
		} else {
			$event->log("get_instance_by_pr_x_y", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", "Could not create instance of cloudmatrixobject without data", "", "", 0, 0, 0);
			return;
		}
		foreach ($cloudmatrixobject_array as $index => $cloudmatrixobject) {
			$this->id = $cloudmatrixobject["mo_id"];
			$this->pr_id = $cloudmatrixobject["mo_pr_id"];
			$this->cr_id = $cloudmatrixobject["mo_cr_id"];
			$this->ca_id = $cloudmatrixobject["mo_ca_id"];
			$this->ne_id = $cloudmatrixobject["mo_ne_id"];
			$this->table = $cloudmatrixobject["mo_table"];
			$this->x = $cloudmatrixobject["mo_x"];
			$this->y = $cloudmatrixobject["mo_y"];
			$this->state = $cloudmatrixobject["mo_state"];
		}
		return $this;
	}


	// ---------------------------------------------------------------------------------
	// general cloudmatrixobject methods
	// ---------------------------------------------------------------------------------




	// checks if given cloudmatrixobject id is free in the db
	function is_id_free($cloudmatrixobject_id) {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select mo_id from $CLOUD_MATRIX_OBJECT_TABLE where mo_id=$cloudmatrixobject_id");
		if (!$rs)
			$event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// adds cloudmatrixobject to the database
	function add($cloudmatrixobject_fields) {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		global $event;
		if (!is_array($cloudmatrixobject_fields)) {
			$event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", "cloudmatrixobject_field not well defined", "", "", 0, 0, 0);
			return 1;
		}
		// set stop time and status to now
		$now=$_SERVER['REQUEST_TIME'];
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($CLOUD_MATRIX_OBJECT_TABLE, $cloudmatrixobject_fields, 'INSERT');
		if (! $result) {
			$event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", "Failed adding new cloudmatrixobject to database", "", "", 0, 0, 0);
		}
	}



	// removes cloudmatrixobject from the database
	function remove($cloudmatrixobject_id) {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $CLOUD_MATRIX_OBJECT_TABLE where mo_id=$cloudmatrixobject_id");
	}


	// updates a cloudmatrixobject
	function update($mo_id, $mo_fields) {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		global $event;
		if ($mo_id < 0 || ! is_array($mo_fields)) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", "Unable to update cloudmatrixobject $mo_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($mo_fields["mo_id"]);
		$result = $db->AutoExecute($this->_db_table, $mo_fields, 'UPDATE', "mo_id = $mo_id");
		if (! $result) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", "Failed updating cloudmatrixobject $mo_id", "", "", 0, 0, 0);
		}
	}



	// returns the number of cloudmatrixobjects for an cloudmatrixobject type
	function get_count() {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(mo_id) as num from $CLOUD_MATRIX_OBJECT_TABLE");
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}



	// returns a list of all cloudmatrixobject ids
	function get_all_ids() {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		global $event;
		$cloudmatrixobject_list = array();
		$query = "select mo_id from $CLOUD_MATRIX_OBJECT_TABLE";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudmatrixobject_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudmatrixobject_list;

	}




	// displays the cloudmatrixobject-overview
	function display_overview($offset, $limit, $sort, $order) {
		global $CLOUD_MATRIX_OBJECT_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from $CLOUD_MATRIX_OBJECT_TABLE order by $sort $order", $limit, $offset);
		$cloudmatrixobject_array = array();
		if (!$recordSet) {
			$event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudmatrixobject.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($cloudmatrixobject_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $cloudmatrixobject_array;
	}






// ---------------------------------------------------------------------------------

}


?>