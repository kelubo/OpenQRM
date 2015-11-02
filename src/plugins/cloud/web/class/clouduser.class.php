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
require_once $RootDir.'/include/openqrm-database-functions.php';
require_once $RootDir.'/class/resource.class.php';
require_once $RootDir.'/class/virtualization.class.php';
require_once $RootDir.'/class/image.class.php';
require_once $RootDir.'/class/kernel.class.php';
require_once $RootDir.'/class/plugin.class.php';
require_once $RootDir.'/class/event.class.php';
require_once $RootDir.'/class/file.handler.class.php';


$CLOUD_USER_TABLE="cloud_users";
global $CLOUD_USER_TABLE;
$event = new event();
global $event;

class clouduser {

	var $id = '';
	var $cg_id = '';
	var $name = '';
	var $password = '';
	var $lastname = '';
	var $forename = '';
	var $email = '';
	var $street = '';
	var $city = '';
	var $country = '';
	var $phone = '';
	var $status = '';
	var $ccunits = '';
	var $token = '';
	/**
	* Lang (language)
	* @access public
	* @var string
	*/
	var $lang = '';


	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function __construct($username = null) {
		global $CLOUD_USER_TABLE, $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = "cloud_users";
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
		if (strlen($username)) {
			$this->get_instance_by_name($username);
			$this->file = new file_handler();
		}

	}



// ---------------------------------------------------------------------------------
// methods to create an instance of a clouduser object filled from the db
// ---------------------------------------------------------------------------------

	// returns an appliance from the db selected by id or name
	function get_instance($id, $name, $token) {
		$db=openqrm_get_db_connection();
		if ($id != "") {
			$clouduser_array = $db->Execute("select * from ".$this->_db_table." where cu_id=$id");
		} else if ($name != "") {
			$clouduser_array = $db->Execute("select * from ".$this->_db_table." where cu_name='$name'");
		} else if ($token != "") {
			$clouduser_array = $db->Execute("select * from ".$this->_db_table." where cu_token='$token'");
		} else {
			$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "coulduser.class.php", "Could not create instance of event without data", "", "", 0, 0, 0);
			return;
		}

		foreach ($clouduser_array as $index => $clouduser) {
			$this->id = $clouduser["cu_id"];
			$this->cg_id = $clouduser["cu_cg_id"];
			$this->name = $clouduser["cu_name"];
			$this->password = $clouduser["cu_password"];
			$this->forename = $clouduser["cu_forename"];
			$this->lastname = $clouduser["cu_lastname"];
			$this->email = $clouduser["cu_email"];
			$this->street = $clouduser["cu_street"];
			$this->city = $clouduser["cu_city"];
			$this->country = $clouduser["cu_country"];
			$this->phone = $clouduser["cu_phone"];
			$this->status = $clouduser["cu_status"];
			$this->token = $clouduser["cu_token"];
			$this->ccunits = $clouduser["cu_ccunits"];
			$this->lang = $clouduser["cu_lang"];
		}
		return $this;
	}

	// returns an appliance from the db selected by id
	function get_instance_by_id($id) {
		$this->get_instance($id, "", "");
		return $this;
	}

	// returns an appliance from the db selected by name
	function get_instance_by_name($name) {
		$this->get_instance("", $name, "");
		return $this;
	}

	// returns an appliance from the db selected by token
	function get_instance_by_token($token) {
		$this->get_instance("", "", $token);
		return $this;
	}

	// ---------------------------------------------------------------------------------
	// general clouduser methods
	// ---------------------------------------------------------------------------------




	// checks if given clouduser id is free in the db
	function is_id_free($clouduser_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select cu_id from ".$this->_db_table." where cu_id=$clouduser_id");
		if (!$rs)
			$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// checks if given clouduser name is free in the db
	function is_name_free($clouduser_name) {
		$db=openqrm_get_db_connection();

		$rs = $db->Execute("select cu_id from ".$this->_db_table." where cu_name='$clouduser_name'");
		if (!$rs)
			$this->_event->log("is_name_free", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// adds clouduser to the database
	function add($clouduser_fields) {
		if (!is_array($clouduser_fields)) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", "clouduser_fields not well defined", "", "", 0, 0, 0);
			return 1;
		}
		// set stop time and status to now
		$now=$_SERVER['REQUEST_TIME'];
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($this->_db_table, $clouduser_fields, 'INSERT');
		if (! $result) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", "Failed adding new clouduser to database", "", "", 0, 0, 0);
		} else {
			// add user to htpasswd
			$username = $clouduser_fields['cu_name'];
			$password = $clouduser_fields['cu_password'];
			$cloud_htpasswd = $this->_base_dir."/openqrm/plugins/cloud/cloud-portal/web/user/.htpasswd";
			if (strlen($password)) {
				if (file_exists($cloud_htpasswd)) {
					$openqrm_server_command="htpasswd -b ".$cloud_htpasswd." ".$username." ".$password;
				} else {
					$openqrm_server_command="htpasswd -c -b ".$cloud_htpasswd." ".$username." ".$password;
				}
				$output = shell_exec($openqrm_server_command);
			}
		}
	}


	// updates clouduser in the database
	function update($clouduser_id, $clouduser_fields) {
		if (!is_array($clouduser_fields)) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", "Unable to update clouduser $clouduser_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($clouduser_fields["clouduser_id"]);
		$result = $db->AutoExecute($this->_db_table, $clouduser_fields, 'UPDATE', "cu_id = $clouduser_id");
		if (! $result) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", "Failed updating clouduser $clouduser_id", "", "", 0, 0, 0);
		} else {
			if ((isset($clouduser_fields['cu_password'])) && (strlen($clouduser_fields['cu_password']))) {
				// add user to htpasswd
				$this->get_instance_by_id($clouduser_id);
				$username = $this->name;
				$password = $clouduser_fields['cu_password'];
				$cloud_htpasswd = $this->_base_dir."/openqrm/plugins/cloud/cloud-portal/web/user/.htpasswd";
				if (file_exists($cloud_htpasswd)) {
					$openqrm_server_command="htpasswd -b ".$cloud_htpasswd." ".$username." ".$password;
				} else {
					$openqrm_server_command="htpasswd -c -b ".$cloud_htpasswd." ".$username." ".$password;
				}
				$output = shell_exec($openqrm_server_command);
				// also update the eventual htpasswd for the personal user stats dir
				$cloud_htpasswd1 = $this->_base_dir."/openqrm/plugins/cloud/cloud-portal/web/user/users/".$username."/.htpasswd";
				if (file_exists($cloud_htpasswd1)) {
					$openqrm_server_command="htpasswd -b ".$cloud_htpasswd1." ".$username." ".$password;
				} else {
					$openqrm_server_command="htpasswd -c -b ".$cloud_htpasswd1." ".$username." ".$password;
				}
				$output = shell_exec($openqrm_server_command);
			}
		}
	}


	// removes clouduser from the database
	function remove($clouduser_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from ".$this->_db_table." where cu_id=$clouduser_id");
	}

	// removes clouduser from the database by clouduser_name
	function remove_by_name($clouduser_name) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from ".$this->_db_table." where cu_name='$clouduser_name'");
	}


	// enables user
	function activate_user_status($cu_id, $stat) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update ".$this->_db_table." set cu_status=$stat where cu_id=$cu_id");
	}


	// set users ccunits
	function set_users_ccunits($cu_id, $ccunits) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update ".$this->_db_table." set cu_ccunits=$ccunits where cu_id=$cu_id");
	}


	// set users password
	function set_users_password($cu_id, $password) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update ".$this->_db_table." set cu_password='$password' where cu_id=$cu_id");
	}

	// set users lang
	function set_users_language($cu_id, $lang) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update ".$this->_db_table." set cu_lang='$lang' where cu_id=".$cu_id);
	}

	// returns clouduser name by clouduser_id
	function get_name($clouduser_id) {
		$db=openqrm_get_db_connection();
		$clouduser_set = $db->Execute("select clouduser_name from ".$this->_db_table." where cu_id=$clouduser_id");
		if (!$clouduser_set) {
			$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			if (!$clouduser_set->EOF) {
				return $clouduser_set->fields["cu_name"];
			} else {
				return "idle";
			}
		}
	}


	// returns the number of cloudusers for an clouduser type
	function get_count() {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(cu_id) as num from ".$this->_db_table);
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}



	// returns a list of all clouduser names
	function get_list() {
		$query = "select cu_id, cu_name from ".$this->_db_table;
		$clouduser_name_array = array();
		$clouduser_name_array = openqrm_db_get_result_double ($query);
		return $clouduser_name_array;
	}


	// returns a list of all clouduser ids
	function get_all_ids() {
		$clouduser_list = array();
		$query = "select cu_id from ".$this->_db_table;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$clouduser_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $clouduser_list;

	}


	function checkEmail($email) {
		if (!preg_match("/^[[:alnum:]][a-z0-9_.-]*@[a-z0-9.-]+\.[a-z]{2,4}$/i", $email)) {
			return false;
		} else {
			return true;
		}
	}


	// displays the clouduser-overview
	function display_user($clouduser_name) {
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from ".$this->_db_table." where cu_name='$clouduser_name'", 1, 0);
		$clouduser_array = array();
		if (!$recordSet) {
			$this->_event->log("display_user", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
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
			$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($clouduser_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $clouduser_array;
	}




	//--------------------------------------------
	/**
	 * Translate
	 *
	 * @access public
	 * @param array $text_array array to translate
	 * @param string $dir dir of translation files
	 * @param string $file translation file
	 * @return array
	 */
	//--------------------------------------------
	function translate( $text_array, $dir, $file ) {
		$user_language = $this->lang;
		$path = $dir.'/'.$user_language.'.'.$file;
		if(file_exists($path)) {
			$tmp = $this->file->get_ini( $path );
			foreach($tmp as $k => $v) {
				if(is_array($v)) {
					foreach($v as $k2 => $v2) {
						$text_array[$k][$k2] = $v2;
					}
				} else {
					$text_array[$k] = $v;
				}
			}
		}
		return $text_array;
	}

	//--------------------------------------------
	/**
	 * fake openqrm user function to
	 * bypass role check for cloud users
	 *
	 * @access public
	 * @return true
	 */
	//--------------------------------------------
	function isAdmin() {
		return true;
	}

}
?>
