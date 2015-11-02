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


// This class represents a cloudtransactionfailed object in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once $RootDir."/include/openqrm-database-functions.php";
require_once $RootDir."/include/user.inc.php";
require_once $RootDir."/class/resource.class.php";
require_once $RootDir."/class/virtualization.class.php";
require_once $RootDir."/class/image.class.php";
require_once $RootDir."/class/kernel.class.php";
require_once $RootDir."/class/plugin.class.php";
require_once $RootDir."/class/event.class.php";
require_once $RootDir."/class/openqrm_server.class.php";
// cloud user class for updating ccus from the cloud zones master
// cloud config for getting the cloud zones config
require_once $RootDir."/plugins/cloud/class/clouduser.class.php";
require_once $RootDir."/plugins/cloud/class/cloudusergroup.class.php";
require_once $RootDir."/plugins/cloud/class/cloudconfig.class.php";

$CLOUD_TRANSACTION_FAILED_TABLE="cloud_transaction_failed";
global $CLOUD_TRANSACTION_FAILED_TABLE;
global $OPENQRM_SERVER_BASE_DIR;
$event = new event();
global $event;


class cloudtransactionfailed {

	var $id = '';
	var $ct_id = '';


	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function cloudtransactionfailed() {
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
		$this->_db_table = 'cloud_transaction_failed';
	}

// ---------------------------------------------------------------------------------
// methods to create an instance of a cloudtransactionfailed object filled from the db
// ---------------------------------------------------------------------------------

	// returns an transaction from the db selected by id or name
	function get_instance($id, $cr_id) {
		$db=openqrm_get_db_connection();
		if ("$id" != "") {
			$cloudtransactionfailed_array = $db->Execute("select * from ".$this->_db_table." where tf_id=$id");
		} else {
			$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudtransactionfailed.class.php", "Could not create instance of event without data", "", "", 0, 0, 0);
			return;
		}

		foreach ($cloudtransactionfailed_array as $index => $cloudtransactionfailed) {
			$this->id = $cloudtransactionfailed["tf_id"];
			$this->ct_id = $cloudtransactionfailed["tf_ct_id"];
		}
		return $this;
	}

	// returns an cloudtransactionfailed from the db selected by id
	function get_instance_by_id($id) {
		$this->get_instance($id, "", "");
		return $this;
	}

	// ---------------------------------------------------------------------------------
	// general cloudtransactionfailed methods
	// ---------------------------------------------------------------------------------




	// checks if given cloudtransactionfailed id is free in the db
	function is_id_free($cloudtransactionfailed_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select tf_id from ".$this->_db_table." where tf_id=$cloudtransactionfailed_id");
		if (!$rs)
			$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudtransactionfailed.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// adds cloudtransactionfailed to the database
	function add($cloudtransactionfailed_fields) {
		if (!is_array($cloudtransactionfailed_fields)) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudtransactionfailed.class.php", "cloudtransactionfailed_field not well defined", "", "", 0, 0, 0);
			return 1;
		}
		// set stop time and status to now
		$now=$_SERVER['REQUEST_TIME'];
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($this->_db_table, $cloudtransactionfailed_fields, 'INSERT');
		if (! $result) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudtransactionfailed.class.php", "Failed adding new cloudtransactionfailed to database", "", "", 0, 0, 0);
		}
	}



	// removes cloudtransactionfailed from the database
	function remove($cloudtransactionfailed_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from ".$this->_db_table." where tf_id=$cloudtransactionfailed_id");
	}



	// function to push a new transaction to the stack
	function push($ct_id) {
		$transaction_fields['tf_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
		$transaction_fields['tf_ct_id'] = $ct_id;
		$this->add($transaction_fields);
	}



	// returns the number of cloudtransactionfaileds for an cloudtransactionfailed type
	function get_count() {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(tf_id) as num from ".$this->_db_table);
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}


	// returns a list of all cloudtransactionfailed names
	function get_list() {
		$query = "select tf_id, tf_cr_id from ".$this->_db_table;
		$cloudtransactionfailed_name_array = array();
		$cloudtransactionfailed_name_array = openqrm_db_get_result_double ($query);
		return $cloudtransactionfailed_name_array;
	}


	// returns a list of all cloudtransactionfailed ids
	function get_all_ids() {
		$cloudtransactionfailed_list = array();
		$query = "select tf_id from ".$this->_db_table;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "cloudtransactionfailed.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudtransactionfailed_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudtransactionfailed_list;

	}




	// displays the cloudtransactionfailed-overview
	function display_overview($offset, $limit, $sort, $order) {
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
		$cloudtransactionfailed_array = array();
		if (!$recordSet) {
			$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudtransactionfailed.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($cloudtransactionfailed_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $cloudtransactionfailed_array;
	}







// ---------------------------------------------------------------------------------

}

?>