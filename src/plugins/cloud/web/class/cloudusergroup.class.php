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


// This class represents a cloud user in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";

$CLOUD_USER_GROUPS_TABLE="cloud_usergroups";
global $CLOUD_USER_GROUPS_TABLE;
$event = new event();
global $event;

class cloudusergroup {

	var $id = '';
	var $name = '';
	var $role_id = '';
	var $description = '';

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudusergroup() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		global $CLOUD_USER_GROUPS_TABLE, $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = "cloud_usergroups";
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
	}




// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudusergroup object filled from the db
// ---------------------------------------------------------------------------------

	// returns an appliance from the db selected by id or name
	function get_instance($id, $name) {
		$db=openqrm_get_db_connection();
		if ("$id" != "") {
			$clouduser_array = $db->Execute("select * from ".$this->_db_table." where cg_id=$id");
		} else if ("$name" != "") {
			$clouduser_array = $db->Execute("select * from ".$this->_db_table." where cg_name='$name'");
		} else {
			$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", "Could not create instance of event without data", "", "", 0, 0, 0);
			return;
		}

		foreach ($clouduser_array as $index => $clouduser) {
			$this->id = $clouduser["cg_id"];
			$this->name = $clouduser["cg_name"];
			$this->role_id = $clouduser["cg_role_id"];
			$this->description = $clouduser["cg_description"];
		}
		return $this;
	}

	// returns an appliance from the db selected by id
	function get_instance_by_id($id) {
		$this->get_instance($id, "");
		return $this;
	}

	// returns an appliance from the db selected by iname
	function get_instance_by_name($name) {
		$this->get_instance("", $name);
		return $this;
	}


	// ---------------------------------------------------------------------------------
	// general cloudusergroup methods
	// ---------------------------------------------------------------------------------




	// checks if given cloudusergroup id is free in the db
	function is_id_free($cloudusergroup_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select cg_id from ".$this->_db_table." where cg_id=$cloudusergroup_id");
		if (!$rs)
			$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// checks if given cloudusergroup name is free in the db
	function is_name_free($cloudusergroup_name) {
		$db=openqrm_get_db_connection();

		$rs = $db->Execute("select cg_id from ".$this->_db_table." where cg_name='$cloudusergroup_name'");
		if (!$rs)
			$this->_event->log("is_name_free", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// adds cloudusergroup to the database
	function add($cloudusergroup_fields) {
		if (!is_array($cloudusergroup_fields)) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", "clouduser_fields not well defined", "", "", 0, 0, 0);
			return 1;
		}
		// set stop time and status to now
		$now=$_SERVER['REQUEST_TIME'];
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($this->_db_table, $cloudusergroup_fields, 'INSERT');
		if (! $result) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", "Failed adding new clouduser to database", "", "", 0, 0, 0);
		}
	}


	// updates cloudusergroup in the database
	function update($cloudusergroup_id, $cloudusergroup_fields) {
		if (!is_array($cloudusergroup_fields)) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", "Unable to update clouduser $cloudusergroup_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($cloudusergroup_fields["clouduser_id"]);
		$result = $db->AutoExecute($this->_db_table, $cloudusergroup_fields, 'UPDATE', "cg_id = $cloudusergroup_id");
		if (! $result) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", "Failed updating clouduser $cloudusergroup_id", "", "", 0, 0, 0);
		}
	}


	// removes cloudusergroup from the database
	function remove($cloudusergroup_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from ".$this->_db_table." where cg_id=$cloudusergroup_id");
	}

	// removes cloudusergroup from the database by cloudusergroup_name
	function remove_by_name($cloudusergroup_name) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from ".$this->_db_table." where cg_name='$cloudusergroup_name'");
	}



	// returns cloudusergroup name by clouduser_id
	function get_name($cloudusergroup_id) {
		$db=openqrm_get_db_connection();
		$clouduser_set = $db->Execute("select clouduser_name from ".$this->_db_table." where cg_id=$cloudusergroup_id");
		if (!$clouduser_set) {
			$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			if (!$clouduser_set->EOF) {
				return $clouduser_set->fields["cu_name"];
			} else {
				return "idle";
			}
		}
	}


	// returns the number of cloudusergroup for an clouduser type
	function get_count() {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(cg_id) as num from ".$this->_db_table);
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}



	// returns a list of all cloudusergroup names
	function get_list() {
		$query = "select cg_id, cg_name from ".$this->_db_table;
		$cloudusergroup_name_array = array();
		$cloudusergroup_name_array = openqrm_db_get_result_double ($query);
		return $cloudusergroup_name_array;
	}


	// returns a list of all cloudusergroup ids
	function get_all_ids() {
		$clouduser_list = array();
		$query = "select cg_id from ".$this->_db_table;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$clouduser_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $clouduser_list;

	}



	// displays the clouduser-overview
	function display_user($cloudusergroup_name) {
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from ".$this->_db_table." where cu_name='$cloudusergroup_name'", 1, 0);
		$clouduser_array = array();
		if (!$recordSet) {
			$this->_event->log("display_user", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($clouduser_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $clouduser_array;
	}





	// displays the clouduser-overview
	function display_overview($offset, $limit, $sort, $order) {
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
		$clouduser_array = array();
		if (!$recordSet) {
			$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudusergroup.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
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
