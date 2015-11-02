<?php
/**
 * @package openQRM
 */
/*
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


	$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
	require_once "$RootDir/include/openqrm-server-config.php";
	require_once "$RootDir/include/openqrm-database-functions.php";
	require_once "$RootDir/class/event.class.php";

/**
 * This class represents an nagios3 service object
 *
 * @package openQRM
 * @author Matt Rechenburg <mattr_sf@users.sourceforge.net>
 * @version 1.0
 */


class nagios3_service
{

/**
* nagios3 id
* @access protected
* @var int
*/
var $id = '';
/**
* nagios3 service name
* @access protected
* @var string
*/
var $name = '';
/**
* nagios3 service port
* @access protected
* @var string
*/
var $port = '';
/**
* nagios3 service type
* @access protected
* @var string
*/
var $type = '';
/**
* nagios3 account description
* @access protected
* @var string
*/
var $description = '';


/**
* name of database table
* @access protected
* @var string
*/
var $_db_table;
/**
* path to openqrm basedir
* @access protected
* @var string
*/
var $_base_dir;
/**
* event object
* @access protected
* @var object
*/
var $_event;

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function nagios3_service() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		global $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = "nagios3_services";
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
	}

	//--------------------------------------------------
	/**
	* get an instance of an nagios3 object from db
	* @access public
	* @param int $id
	* @param string $name
	* @return object
	*/
	//--------------------------------------------------
	function get_instance($id, $name, $port) {
		$nagios3_service_array = array();
		$db=openqrm_get_db_connection();
		if ("$id" != "") {
			$nagios3_service_array = $db->Execute("select * from $this->_db_table where nagios3_service_id=$id");
		} else if ("$name" != "") {
			$nagios3_service_array = $db->Execute("select * from $this->_db_table where nagios3_service_name='$name'");
		} else if ("$port" != "") {
			$nagios3_service_array = $db->Execute("select * from $this->_db_table where nagios3_service_port='$port'");
		}
		foreach ($nagios3_service_array as $index => $nagios3_service) {
			$this->id = $nagios3_service["nagios3_service_id"];
			$this->name = $nagios3_service["nagios3_service_name"];
			$this->type = $nagios3_service["nagios3_service_type"];
			$this->port = $nagios3_service["nagios3_service_port"];
			$this->description = $nagios3_service["nagios3_service_description"];
		}
		return $this;
	}

	//--------------------------------------------------
	/**
	* get an instance of an nagios3 by id
	* @access public
	* @param int $id
	* @return object
	*/
	//--------------------------------------------------
	function get_instance_by_id($id) {
		$this->get_instance($id, "", "");
		return $this;
	}

	//--------------------------------------------------
	/**
	* get an instance of an nagios3 by name
	* @access public
	* @param int $name
	* @return object
	*/
	//--------------------------------------------------
	function get_instance_by_name($name) {
		$this->get_instance("", $name, "");
		return $this;
	}


	//--------------------------------------------------
	/**
	* get an instance of an nagios3 by port
	* @access public
	* @param int $port
	* @return object
	*/
	//--------------------------------------------------
	function get_instance_by_port($port) {
		$this->get_instance("", "", $port);
		return $this;
	}

	//--------------------------------------------------
	/**
	* add a new nagios3
	* @access public
	* @param array $nagios3_service_fields
	*/
	//--------------------------------------------------
	function add($nagios3_service_fields) {
		if (!is_array($nagios3_service_fields)) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "nagios3.class.php", "Fields not well defined", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($this->_db_table, $nagios3_service_fields, 'INSERT');
		if (! $result) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "nagios3.class.php", "Failed adding new nagios3 to database", "", "", 0, 0, 0);
		}
	}

	//--------------------------------------------------
	/**
	* update an nagios3
	* <code>
	* $fields = array();
	* $fields['nagios3_service_name'] = 'somename';
	* $fields['nagios3_service_uri'] = 'some-uri';
	* $nagios3 = new nagios3();
	* $nagios3->update(1, $fields);
	* </code>
	* @access public
	* @param int $nagios3_service_id
	* @param array $nagios3_service_fields
	* @return bool
	*/
	//--------------------------------------------------
	function update($nagios3_service_id, $nagios3_service_fields) {
		if ($nagios3_service_id < 0 || ! is_array($nagios3_service_fields)) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "nagios3.class.php", "Unable to update nagios3 $nagios3_service_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($nagios3_service_fields["nagios3_service_id"]);
		$result = $db->AutoExecute($this->_db_table, $nagios3_service_fields, 'UPDATE', "nagios3_service_id = $nagios3_service_id");
		if (! $result) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "nagios3.class.php", "Failed updating nagios3 $nagios3_service_id", "", "", 0, 0, 0);
		}
	}

	//--------------------------------------------------
	/**
	* remove an nagios3 by id
	* @access public
	* @param int $nagios3_service_id
	*/
	//--------------------------------------------------
	function remove($nagios3_service_id) {
		// remove from db
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $this->_db_table where nagios3_service_id=$nagios3_service_id");
	}

	//--------------------------------------------------
	/**
	* remove an nagios3 by name
	* @access public
	* @param string $nagios3_service_name
	*/
	//--------------------------------------------------
	function remove_by_name($nagios3_service_name) {
		// remove from db
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $this->_db_table where nagios3_service_name='$nagios3_service_name'");

	}


	//--------------------------------------------------
	/**
	* remove an nagios3 by port
	* @access public
	* @param string $nagios3_service_port
	*/
	//--------------------------------------------------
	function remove_by_port($nagios3_service_port) {
		// remove from db
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $this->_db_table where nagios3_service_port='$nagios3_service_port'");

	}

	//--------------------------------------------------
	/**
	* get nagios3 name by id
	* @access public
	* @param int $nagios3_service_id
	* @return string
	*/
	//--------------------------------------------------
	function get_name($nagios3_service_id) {
		$db=openqrm_get_db_connection();
		$nagios3_service_set = $db->Execute("select nagios3_service_name from $this->_db_table where nagios3_service_id=$nagios3_service_id");
		if (!$nagios3_service_set) {
			$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "nagios3.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			if (!$nagios3_service_set->EOF) {
				return $nagios3_service_set->fields["nagios3_service_name"];
			} else {
				return "not found";
			}
		}
	}



	//--------------------------------------------------
	/**
	* get an array of all nagios3 names
	* <code>
	* $nagios3 = new nagios3();
	* $arr = $nagios3->get_list();
	* // $arr[0]['value']
	* // $arr[0]['label']
	* </code>
	* @access public
	* @return array
	*/
	//--------------------------------------------------
	function get_list() {
		$query = "select nagios3_service_id, nagios3_service_name from $this->_db_table order by nagios3_service_id ASC";
		$nagios3_service_name_array = array();
		$nagios3_service_name_array = openqrm_db_get_result_double ($query);
		return $nagios3_service_name_array;
	}


	//--------------------------------------------------
	/**
	* get an array of all nagios3 ids
	* <code>
	* $nagios3 = new nagios3();
	* $arr = $nagios3->get_ids();
	* // $arr['value']
	* </code>
	* @access public
	* @return array
	*/
	//--------------------------------------------------
	function get_ids() {
		$nagios3_service_array = array();
		$query = "select nagios3_service_id from $this->_db_table";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "nagios3.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$nagios3_service_array[] = $rs->fields;
			$rs->MoveNext();
		}
		return $nagios3_service_array;
	}

	//--------------------------------------------------
	/**
	* get number of nagios3 accounts
	* @access public
	* @return int
	*/
	//--------------------------------------------------
	function get_count() {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(nagios3_service_id) as num from $this->_db_table");
		if (!$rs) {
			$this->_event->log("get_count", $_SERVER['REQUEST_TIME'], 2, "nagios3.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}


	//--------------------------------------------------
	/**
	* get an array of nagios3s
	* @access public
	* @param int $offset
	* @param int $limit
	* @param string $sort
	* @param enum $order [ASC/DESC]
	* @return array
	*/
	//--------------------------------------------------
	function display_overview($offset, $limit, $sort, $order) {
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from $this->_db_table order by $sort $order", $limit, $offset);
		$nagios3_service_array = array();
		if (!$recordSet) {
			$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "nagios3.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($nagios3_service_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $nagios3_service_array;
	}


}
?>
