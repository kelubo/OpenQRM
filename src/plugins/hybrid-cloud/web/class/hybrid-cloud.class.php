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
	require_once "$RootDir/class/openqrm_server.class.php";
	require_once "$RootDir/class/event.class.php";

/**
 * This class represents an hybrid-cloud object
 *
 * @package openQRM
 * @author Matt Rechenburg <mattr_sf@users.sourceforge.net>
 * @version 1.0
 */


class hybrid_cloud
{

/**
* hybrid-cloud id
* @access protected
* @var int
*/
var $id = '';
/**
* hybrid-cloud account_name
* @access protected
* @var string
*/
var $account_name = '';
/**
* hybrid-cloud account_type
* @access protected
* @var string
*/
var $account_type = '';
/**
* hybrid-cloud access key
* @access protected
* @var string
*/
var $access_key = '';
/**
* hybrid-cloud secret key
* @access protected
* @var string
*/
var $secret_key = '';
/**
* hybrid-cloud username
* @access protected
* @var string
*/
var $username = '';
/**
* hybrid-cloud password
* @access protected
* @var string
*/
var $password = '';
/**
* hybrid-cloud host ip
* @access protected
* @var string
*/
var $host = '';
/**
* hybrid-cloud port
* @access protected
* @var string
*/
var $port = '';
/**
* hybrid-cloud tenant
* @access protected
* @var string
*/
var $tenant = '';
/**
* hybrid-cloud endpoint
* @access protected
* @var string
*/
var $endpoint = '';
/**
* hybrid-cloud account description
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
	function hybrid_cloud() {
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
		$this->_db_table = "hybrid_cloud_accounts";
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
		$this->_web_dir = $RootDir = $_SERVER["DOCUMENT_ROOT"];
	}

	//--------------------------------------------------
	/**
	* get an instance of an hybrid-cloud object from db
	* @access public
	* @param int $id
	* @param string $name
	* @return object
	*/
	//--------------------------------------------------
	function get_instance($id, $name) {
		$db=openqrm_get_db_connection();
		if ("$id" != "") {
			$hybrid_cloud_array = $db->Execute("select * from $this->_db_table where hybrid_cloud_id=$id");
		} else if ("$name" != "") {
			$hybrid_cloud_array = $db->Execute("select * from $this->_db_table where hybrid_cloud_account_name='$name'");
		} else {
			$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", "Could not create instance of hybrid-cloud without data", "", "", 0, 0, 0);
			return;
		}
		foreach ($hybrid_cloud_array as $index => $hybrid_cloud) {
			$this->id = $hybrid_cloud["hybrid_cloud_id"];
			$this->account_name = $hybrid_cloud["hybrid_cloud_account_name"];
			$this->account_type = $hybrid_cloud["hybrid_cloud_account_type"];
			$this->access_key = $hybrid_cloud["hybrid_cloud_access_key"];
			$this->secret_key = $hybrid_cloud["hybrid_cloud_secret_key"];
			$this->username = $hybrid_cloud["hybrid_cloud_username"];
			$this->password = $hybrid_cloud["hybrid_cloud_password"];
			$this->host = $hybrid_cloud["hybrid_cloud_host"];
			$this->port = $hybrid_cloud["hybrid_cloud_port"];
			$this->tenant = $hybrid_cloud["hybrid_cloud_tenant"];
			$this->endpoint = $hybrid_cloud["hybrid_cloud_endpoint"];
			$this->description = $hybrid_cloud["hybrid_cloud_description"];
		}
		return $this;
	}

	//--------------------------------------------------
	/**
	* get an instance of an hybrid-cloud by id
	* @access public
	* @param int $id
	* @return object
	*/
	//--------------------------------------------------
	function get_instance_by_id($id) {
		$this->get_instance($id, "");
		return $this;
	}

	//--------------------------------------------------
	/**
	* get an instance of an hybrid-cloud by name
	* @access public
	* @param int $id
	* @return object
	*/
	//--------------------------------------------------
	function get_instance_by_name($name) {
		$this->get_instance("", $name);
		return $this;
	}

	//--------------------------------------------------
	/**
	* add a new hybrid-cloud
	* @access public
	* @param array $hybrid_cloud_fields
	*/
	//--------------------------------------------------
	function add($hybrid_cloud_fields) {
		if (!is_array($hybrid_cloud_fields)) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", "Fields not well defined", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($this->_db_table, $hybrid_cloud_fields, 'INSERT');
		if (! $result) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", "Failed adding new hybrid-cloud to database", "", "", 0, 0, 0);
		}
	}

	//--------------------------------------------------
	/**
	* update an hybrid-cloud
	* <code>
	* $fields = array();
	* $fields['hybrid_cloud_name'] = 'somename';
	* $fields['hybrid_cloud_uri'] = 'some-uri';
	* $hybrid-cloud = new hybrid-cloud();
	* $hybrid-cloud->update(1, $fields);
	* </code>
	* @access public
	* @param int $hybrid_cloud_id
	* @param array $hybrid_cloud_fields
	* @return bool
	*/
	//--------------------------------------------------
	function update($hybrid_cloud_id, $hybrid_cloud_fields) {
		if ($hybrid_cloud_id < 0 || ! is_array($hybrid_cloud_fields)) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", "Unable to update hybrid-cloud $hybrid_cloud_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($hybrid_cloud_fields["hybrid_cloud_id"]);
		$result = $db->AutoExecute($this->_db_table, $hybrid_cloud_fields, 'UPDATE', "hybrid_cloud_id = $hybrid_cloud_id");
		if (! $result) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", "Failed updating hybrid-cloud $hybrid_cloud_id", "", "", 0, 0, 0);
		}
	}

	//--------------------------------------------------
	/**
	* remove an hybrid-cloud by id
	* @access public
	* @param int $hybrid_cloud_id
	*/
	//--------------------------------------------------
	function remove($hybrid_cloud_id) {
		// remove from db
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $this->_db_table where hybrid_cloud_id=$hybrid_cloud_id");
	}

	//--------------------------------------------------
	/**
	* remove an hybrid-cloud by name
	* @access public
	* @param string $hybrid_cloud_name
	*/
	//--------------------------------------------------
	function remove_by_name($hybrid_cloud_name) {
		// remove from db
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $this->_db_table where hybrid_cloud_account_name='$hybrid_cloud_name'");

	}




	//--------------------------------------------------
	/**
	* returns the authencation command parameters
	* @access public
	* @param
	*/
	//--------------------------------------------------
	function get_authentication() {
		$hc_authentication = '';
		if (($this->account_type == 'aws') || ($this->account_type == 'euca')) {
			$hc_authentication .= ' -O '.$this->access_key;
			$hc_authentication .= ' -W '.$this->secret_key;
			$hc_authentication .= ' -ir '.$this->region;
			$hc_authentication .= ' -iz '.$form->get_request('availability_zone');
		}
		if ($this->account_type == 'lc-openstack') {
			$hc_authentication .= ' -u '.$this->username;
			$hc_authentication .= ' -p '.$this->password;
			$hc_authentication .= ' -q '.$this->host;
			$hc_authentication .= ' -x '.$this->port;
			$hc_authentication .= ' -g '.$this->tenant;
			$hc_authentication .= ' -e '.$this->endpoint;
		}
		return $hc_authentication;
	}




	//--------------------------------------------------
	/**
	* returns an array of available flovours
	* @access public
	* @param none
	*/
	//--------------------------------------------------
	function get_sizes() {
		$hc_authentication = $this->get_authentication();
		$file = $this->_web_dir.'/openqrm/base/plugins/hybrid-cloud/hybrid-cloud-stat/'.$this->id.'.describe_sizes.log';
		if(file_exists($file)) {
			unlink($file);
		}
		$openqrm = new openqrm_server();
		$command=$this->_base_dir."/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-sizes describe_sizes ";
		$command .= ' -i '.$this->id;
		$command .= ' -n '.$this->account_name;
		$command .= ' -t '.$this->account_type;
		$command .= $hc_authentication;
		$command .= ' --openqrm-cmd-mode background';
		$openqrm = new openqrm_server();
		$openqrm->send_command($command, NULL, true);
		while (!file_exists($file)) {
		  usleep(10000);
		  clearstatcache();
		}
		$hc_sizes = array();
		$content = file_get_contents($file);
		$content = explode("\n", $content);
		foreach ($content as $k => $v) {
			if($v !== '') {
				$tmp		= explode('@', $v);
				$id		= $tmp[1];
				$name		= $tmp[2];
				$memory		= $tmp[3];
				$cpu		= $tmp[4];
				// echo "id :".$id." - name: ".$name." - mem: ".$memory." - cpus: ".$cpu."<br>";
				$hc_sizes[] = array('id' => $id, 'name' => $name, 'memory' => $memory, 'cpu' => $cpu);

			}
		}
		return $hc_sizes;
	}






	//--------------------------------------------------
	/**
	* translates resource components according to the cloud type instance type
	* @access public
	* @param string $hybrid_cloud_name
	*/
	//--------------------------------------------------
	function translate_resource_components($component, $instance_type) {

		switch($component) {
			case 't1.micro':
				switch($component) {
					case 'cpu':
						return "1";
					break;
					case 'mem':
						return "615";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm1.small':
				switch($component) {
					case 'cpu':
						return "1";
					break;
					case 'mem':
						return "1700";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm1.medium':
				switch($component) {
					case 'cpu':
						return "1";
					break;
					case 'mem':
						return "3750";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm1.large':
				switch($component) {
					case 'cpu':
						return "2";
					break;
					case 'mem':
						return "7500";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm1.xlarge':
				switch($component) {
					case 'cpu':
						return "4";
					break;
					case 'mem':
						return "15000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm3.xlarge':
				switch($component) {
					case 'cpu':
						return "4";
					break;
					case 'mem':
						return "15000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm3.2xlarge':
				switch($component) {
					case 'cpu':
						return "8";
					break;
					case 'mem':
						return "30000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'c1.medium':
				switch($component) {
					case 'cpu':
						return "2";
					break;
					case 'mem':
						return "1700";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'c1.xlarge':
				switch($component) {
					case 'cpu':
						return "8";
					break;
					case 'mem':
						return "7000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm2.xlarge':
				switch($component) {
					case 'cpu':
						return "2";
					break;
					case 'mem':
						return "20000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm2.2xlarge':
				switch($component) {
					case 'cpu':
						return "4";
					break;
					case 'mem':
						return "34000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'm2.4xlarge':
				switch($component) {
					case 'cpu':
						return "8";
					break;
					case 'mem':
						return "68000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'cr1.8xlarge':
				switch($component) {
					case 'cpu':
						return "32";
					break;
					case 'mem':
						return "244000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'hi1.4xlarge':
				switch($component) {
					case 'cpu':
						return "16";
					break;
					case 'mem':
						return "60000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'hs1.8xlarge':
				switch($component) {
					case 'cpu':
						return "16";
					break;
					case 'mem':
						return "117000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'cc1.4xlarge':
				switch($component) {
					case 'cpu':
						return "16";
					break;
					case 'mem':
						return "60000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'cc2.8xlarge':
				switch($component) {
					case 'cpu':
						return "32";
					break;
					case 'mem':
						return "60000";
					break;
					case 'net':
						return "1";
					break;
				}
			break;
			case 'cg1.4xlarge':
				switch($component) {
					case 'cpu':
						return "16";
					break;
					case 'mem':
						return "60000";
					break;
					case 'net':
						return "1";
					break;
				}

			default:
				switch($component) {
					case 'cpu':
						return "1";
					break;
					case 'mem':
						return "1000";
					break;
					case 'net':
						return "1";
					break;
				}


			break;


		}

	}




	

	//--------------------------------------------------
	/**
	* translates resource components to cloud type instance types
	* @access public
	* @param string $hybrid_cloud_name
	*/
	//--------------------------------------------------
	function translate_resource_components_to_instance_type($cpu, $memory) {

		if ($this->account_type == 'lc-openstack') {
			$available_sizes = $this->get_sizes();
			foreach ($available_sizes as $k => $size) {
				if (($size['cpu'] == $cpu) && ($size['memory'] == $memory)) {
					return $size['name'];
				}
			}
			// we did not found a correct fitting size, try to get a close as possible
			$mem_difference = 999999999999999999999999;
			$best_size = 0;
			foreach ($available_sizes as $k => $size) {
				if ($size['cpu'] == $cpu) {
					if ($size['memory'] >= $memory) {
						if (($size['memory'] - $memory) < $mem_difference) {
							$mem_difference = $size['memory'] - $memory;
							$best_size = $k;
						}
					}
				}
			}
			return $available_sizes[$best_size]['name'];

		}
		if (($this->account_type == 'aws') || ($this->account_type == 'euca')) {

			$instance_type = 0;
			$instance_types[0] = 'm1.small';
			$instance_types[1] = 'm1.small';
			$instance_types[2] = 'm1.medium';
			$instance_types[3] = 'm1.large';
			$instance_types[4] = 'm1.xlarge';
			$instance_types[5] = 'm3.xlarge';
			$instance_types[6] = 'm3.2xlarge';
			$instance_types[7] = 'm2.xlarge';
			$instance_types[8] = 'm2.2xlarge';
			$instance_types[9] = 'm2.4xlarge';

			switch($cpu) {
				case '1':
					$instance_type = 0;
				break;
				case '2':
					if ($instance_type < 3) {
						$instance_type = 3;
					}
				break;
				case '4':
					if ($instance_type < 4) {
						$instance_type = 4;
					}
				break;
				case '8':
					if ($instance_type < 6) {
						$instance_type = 6;
					}
				break;
				case '16':
					if ($instance_type < 6) {
						$instance_type = 6;
					}
				break;
				case '32':
					if ($instance_type < 6) {
						$instance_type = 6;
					}
				break;
				case '64':
					if ($instance_type < 6) {
						$instance_type = 6;
					}
				break;
				case '128':
					if ($instance_type < 6) {
						$instance_type = 6;
					}
				break;
			}

			if ($memory > 615) {
				if ($instance_type < 1) {
					$instance_type = 1;
				}
			}
			if ($memory > 1700) {
				if ($instance_type < 2) {
					$instance_type = 2;
				}
			}
			if ($memory > 3750) {
				if ($instance_type < 3) {
					$instance_type = 3;
				}
			}
			if ($memory > 7500) {
				if ($instance_type < 4) {
					$instance_type = 4;
				}
			}
			if ($memory > 15000) {
				if ($instance_type < 5) {
					$instance_type = 5;
				}
			}
			if ($memory > 30000) {
				if ($instance_type < 6) {
					$instance_type = 6;
				}
			}
			return $instance_types[$instance_type];
		}
	}




	//--------------------------------------------------
	/**
	* formats ip address array
	* @access public
	* @param ip address array $ip_arr
	* @return string
	*/
	//--------------------------------------------------
	function format_ip_address($ip_arr_str) {
		$ip_str = '';
		$ip_str = str_replace('[','', $ip_arr_str);
		$ip_str = str_replace(']','', $ip_str);
		$ip_str = str_replace("'",'', $ip_str);
		$ip_str = str_replace("u",'', $ip_str);
		$ip_arr	= explode(',', $ip_str);
		$ip_str = $ip_arr[0];
		return $ip_str;
	}



	//--------------------------------------------------
	/**
	* get hybrid-cloud name by id
	* @access public
	* @param int $hybrid_cloud_id
	* @return string
	*/
	//--------------------------------------------------
	function get_name($hybrid_cloud_id) {
		$db=openqrm_get_db_connection();
		$hybrid_cloud_set = $db->Execute("select hybrid_cloud_account_name from $this->_db_table where hybrid_cloud_id=$hybrid_cloud_id");
		if (!$hybrid_cloud_set) {
			$this->_event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			if (!$hybrid_cloud_set->EOF) {
				return $hybrid_cloud_set->fields["hybrid_cloud_account_name"];
			} else {
				return "not found";
			}
		}
	}



	//--------------------------------------------------
	/**
	* get an array of all hybrid-cloud names
	* <code>
	* $hybrid-cloud = new hybrid-cloud();
	* $arr = $hybrid-cloud->get_list();
	* // $arr[0]['value']
	* // $arr[0]['label']
	* </code>
	* @access public
	* @return array
	*/
	//--------------------------------------------------
	function get_list() {
		$query = "select hybrid_cloud_id, hybrid_cloud_account_name from $this->_db_table order by hybrid_cloud_id ASC";
		$hybrid_cloud_name_array = array();
		$hybrid_cloud_name_array = openqrm_db_get_result_double ($query);
		return $hybrid_cloud_name_array;
	}


	//--------------------------------------------------
	/**
	* get an array of all hybrid-cloud ids
	* <code>
	* $hybrid-cloud = new hybrid-cloud();
	* $arr = $hybrid-cloud->get_ids();
	* // $arr['value']
	* </code>
	* @access public
	* @return array
	*/
	//--------------------------------------------------
	function get_ids() {
		$hybrid_cloud_array = array();
		$query = "select hybrid_cloud_id from $this->_db_table";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$hybrid_cloud_array[] = $rs->fields;
			$rs->MoveNext();
		}
		return $hybrid_cloud_array;
	}

	//--------------------------------------------------
	/**
	* get number of hybrid-cloud accounts
	* @access public
	* @return int
	*/
	//--------------------------------------------------
	function get_count() {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(hybrid_cloud_id) as num from $this->_db_table");
		if (!$rs) {
			$this->_event->log("get_count", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}


	//--------------------------------------------------
	/**
	* get an array of hybrid-clouds
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
		$hybrid_cloud_array = array();
		if (!$recordSet) {
			$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($hybrid_cloud_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $hybrid_cloud_array;
	}


}
?>
