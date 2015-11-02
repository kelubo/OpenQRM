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


// This class represents a cloud request in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/plugins/cloud/class/cloudappliance.class.php";

$CLOUD_REQUEST_TABLE="cloud_requests";
global $CLOUD_REQUEST_TABLE;
$event = new event();
global $event;


// request status
// 1 = new
// 2 = approved
// 3 = active (provisioned)
// 4 = denied
// 5 = deprovisioned
// 6 = done
// 7 = no resource available
// 8 = starting

class cloudrequest {

	var $id = '';
	var $cu_id = '';
	var $status = '';
	var $request_time = '';
	var $start = '';
	var $stop = '';
	var $kernel_id = '';
	var $image_id = '';
	var $ram_req = '';
	var $cpu_req = '';
	var $disk_req = '';
	var $network_req = '';
	var $resource_quantity = '';
	var $resource_type_req = '';
	var $deployment_type_req = '';
	var $ha_req = '';
	var $shared_req = '';
	var $puppet_groups = '';
	var $ip_mgmt = '';
	var $appliance_id = '';
	var $appliance_hostname = '';
	var $lastbill = '';
	var $image_password = '';
	var $appliance_capabilities = '';

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function __construct() {
		global $CLOUD_REQUEST_TABLE, $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = "cloud_requests";
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
	}


	// ---------------------------------------------------------------------------------
	// methods to create an instance of a cloudrequest object filled from the db
	// ---------------------------------------------------------------------------------

	// returns an appliance from the db selected by id
	function get_instance($id) {
		if ("$id" != "") {
			$db=openqrm_get_db_connection();
			$cloudrequest_array = $db->Execute("select * from ".$this->_db_table." where cr_id=$id");
		} else {
			$error = '';
			foreach(debug_backtrace() as $key => $msg) {
				if($key === 1) {
					$error .= '( '.basename($msg['file']).' '.$msg['line'].' )';
				}
				syslog(LOG_ERR, $msg['function'].'() '.basename($msg['file']).':'.$msg['line']);
			}
			$this->_event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", "Could not create instance of cloudrequest without data ".$error, "", "", 0, 0, 0);
			return;
		}

		foreach ($cloudrequest_array as $index => $cloudrequest) {
			$this->id = $cloudrequest["cr_id"];
			$this->cu_id = $cloudrequest["cr_cu_id"];
			$this->status = $cloudrequest["cr_status"];
			$this->request_time = $cloudrequest["cr_request_time"];
			$this->start = $cloudrequest["cr_start"];
			$this->stop = $cloudrequest["cr_stop"];
			$this->kernel_id = $cloudrequest["cr_kernel_id"];
			$this->image_id = $cloudrequest["cr_image_id"];
			$this->ram_req = $cloudrequest["cr_ram_req"];
			$this->cpu_req = $cloudrequest["cr_cpu_req"];
			$this->disk_req = $cloudrequest["cr_disk_req"];
			$this->network_req = $cloudrequest["cr_network_req"];
			$this->resource_quantity = $cloudrequest["cr_resource_quantity"];
			$this->resource_type_req = $cloudrequest["cr_resource_type_req"];
			$this->deployment_type_req = $cloudrequest["cr_deployment_type_req"];
			$this->ha_req = $cloudrequest["cr_ha_req"];
			$this->shared_req = $cloudrequest["cr_shared_req"];
			$this->puppet_groups = $cloudrequest["cr_puppet_groups"];
			$this->ip_mgmt = $cloudrequest["cr_ip_mgmt"];
			$this->appliance_id = $cloudrequest["cr_appliance_id"];
			$this->appliance_hostname = $cloudrequest["cr_appliance_hostname"];
			$this->lastbill = $cloudrequest["cr_lastbill"];
			$this->image_password = $cloudrequest["cr_image_password"];
			$this->appliance_capabilities = $cloudrequest["cr_appliance_capabilities"];
		}
		return $this;
	}

	// returns an appliance from the db selected by id
	function get_instance_by_id($id) {
		$this->get_instance($id);
		return $this;
	}



	// ---------------------------------------------------------------------------------
	// general cloudrequest methods
	// ---------------------------------------------------------------------------------




	// checks if given cloudrequest id is free in the db
	function is_id_free($cloudrequest_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select cloudrequest_id from ".$this->_db_table." where cr_id=$cloudrequest_id");
		if (!$rs)
			$this->_event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// adds cloudrequest to the database
	function add($cloudrequest_fields) {
		if (!is_array($cloudrequest_fields)) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", "coulduser_field not well defined", "", "", 0, 0, 0);
			return 1;
		}
		// set request time to now
		$now=$_SERVER['REQUEST_TIME'];
		$cloudrequest_fields['cr_request_time'] = $now;
		// set status to 1 = new
		$cloudrequest_fields['cr_status'] = 1;
		// set the appliance_id to 0
		$cloudrequest_fields['cr_appliance_id'] = 0;
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($this->_db_table, $cloudrequest_fields, 'INSERT');
		if (! $result) {
			$this->_event->log("add", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", "Failed adding new cloudrequest to database", "", "", 0, 0, 0);
		}
	}



	// removes cloudrequest from the database
	function remove($cloudrequest_id) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from ".$this->_db_table." where cr_id=$cloudrequest_id");
	}


	// updates a cloudrequest
	function update($cloudrequest_id, $cr_fields) {
		if ($cloudrequest_id < 0 || ! is_array($cr_fields)) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", "Unable to update cloudrequest $cloudrequest_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($cr_fields["cr_id"]);
		$result = $db->AutoExecute($this->_db_table, $cr_fields, 'UPDATE', "cr_id = $cloudrequest_id");
		if (! $result) {
			$this->_event->log("update", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", "Failed updating cloudrequest $cloudrequest_id", "", "", 0, 0, 0);
		}
	}


	// returns the number of cloudrequests for an cloudrequest type
	function get_count() {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(cr_id) as num from ".$this->_db_table);
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}


	// returns the number of cloudrequests for an cloudrequest per user
	function get_count_per_user($cu_id) {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(cr_id) as num from ".$this->_db_table." where cr_cu_id=$cu_id");
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}


	// returns the number of waiting (new + approved) cloudrequests per user
	function get_count_waiting_per_user($cu_id) {
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(cr_id) as num from ".$this->_db_table." where cr_cu_id=".$cu_id." and cr_status>0 and cr_status<3");
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}





	// returns a list of all cloudrequest ids + user ids
	function get_list() {
		$query = "select cr_id, cr_cu_id from ".$this->_db_table;
		$cloudrequest_name_array = array();
		$cloudrequest_name_array = openqrm_db_get_result_double ($query);
		return $cloudrequest_name_array;
	}


	// returns a list of all cloudrequest ids
	function get_all_ids() {
		$cloudrequest_list = array();
		$query = "select cr_id from ".$this->_db_table." order by cr_id DESC";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_all_ids", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudrequest_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudrequest_list;

	}



	// returns a list of all cloudrequest ids which are new or approvded
	function get_all_new_and_approved_ids() {
		$cloudrequest_list = array();
		$query = "select cr_id from ".$this->_db_table." where cr_status=1 or cr_status=2 order by cr_id DESC";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_all_new_and_approved_ids", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudrequest_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudrequest_list;

	}


	// returns a list of all cloudrequest ids which are starting
	function get_all_starting_ids() {
		$cloudrequest_list = array();
		$query = "select cr_id from ".$this->_db_table." where cr_status=8 order by cr_id DESC";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_all_starting_ids", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudrequest_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudrequest_list;

	}


	// returns a list of all cloudrequest ids which are active
	function get_all_active_ids($user = null) {
		$cloudrequest_list = array();
		if(isset($user)) {
			$query = "SELECT cr_id FROM ".$this->_db_table." WHERE cr_status=3 AND cr_cu_id=".$user." ORDER BY cr_id DESC";
		} else {
			$query = "select cr_id from ".$this->_db_table." where cr_status=3 order by cr_id DESC";
		}
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_all_active_ids", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudrequest_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudrequest_list;

	}


	// returns a list of all cloudrequest ids which are deprovisioned
	function get_all_deprovisioned_ids() {
		$cloudrequest_list = array();
		$query = "select cr_id from ".$this->_db_table." where cr_status=5 order by cr_id DESC";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_all_deprovisioned_ids", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudrequest_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudrequest_list;

	}



	// returns a list of all cloudrequest ids per clouduser
	function get_all_ids_per_user($cu_id) {
		$cloudrequest_list = array();
		$query = "select cr_id from ".$this->_db_table." where cr_cu_id=".$cu_id." order by cr_id DESC";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_all_ids_per_user", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudrequest_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudrequest_list;

	}



	// returns a list of all active cloudrequest ids per clouduser
	function get_all_active_ids_per_user($cu_id) {
		$cloudrequest_list = array();
		$query = "select cr_id from ".$this->_db_table." where (cr_cu_id=".$cu_id." and cr_status>0 and cr_status<4) or (cr_cu_id=".$cu_id." and cr_status=8) order by cr_id DESC";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$this->_event->log("get_all_ids_per_user", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$cloudrequest_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $cloudrequest_list;

	}



	// returns the cost of a request (in cc_units)
	function get_cost() {
		$this->_event->log("get_costs", $_SERVER['REQUEST_TIME'], 5, "cloudrequest.class.php", "Calulating bill for cr $this->id", "", "", 0, 0, 0);
		$cr_appliance_id = $this->appliance_id;
		$app_id_arr = explode(",", $cr_appliance_id);
		$cr_costs_final = 0;
		foreach ($app_id_arr as $app_id) {
			$cloud_app = new cloudappliance();
			$cloud_app->get_instance_by_appliance_id($app_id);
			// check state, only bill if active
			if ($cloud_app->state == 1) {
				// basic cost
				$cr_costs = 0;
				// + per cpu
				$cr_costs = $cr_costs + $this->cpu_req;
				// + per nic
				$cr_costs = $cr_costs + $this->network_req;
				// ha cost double
				if (!strcmp($this->ha_req, '1')) {
					$cr_costs = $cr_costs * 2;
				}
				// TODO : disk costs
				// TODO : network-traffic costs

				// sum
				$cr_costs_final = $cr_costs_final + $cr_costs;
				$this->_event->log("get_costs", $_SERVER['REQUEST_TIME'], 5, "cloudrequest.class.php", "-> Billing active appliance $app_id (cr $this->id) = $cr_costs CC-units", "", "", 0, 0, 0);
			} else {
				$this->_event->log("get_costs", $_SERVER['REQUEST_TIME'], 5, "cloudrequest.class.php", "-> Not billing paused appliance $app_id (cr $this->id)", "", "", 0, 0, 0);
			}
		}
		$this->_event->log("get_costs", $_SERVER['REQUEST_TIME'], 5, "cloudrequest.class.php", "-> Final bill for cr $this->id = $cr_costs_final CC-units", "", "", 0, 0, 0);
		return $cr_costs_final;
	}



	// set requests lastbill
	function set_requests_lastbill($cr_id, $timestamp) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update ".$this->_db_table." set cr_lastbill=$timestamp where cr_id=$cr_id");
	}





	// function to set the status of a request
	function setstatus($cloudrequest_id, $cloud_status) {
		switch ($cloud_status) {
			case 'new':
				$cr_status=1;
				break;
			case 'approve':
				$cr_status=2;
				break;
			case 'active':
				$cr_status=3;
				break;
			case 'deny':
				$cr_status=4;
				break;
			case 'deprovision':
				$cr_status=5;
				break;
			case 'done':
				$cr_status=6;
				break;
			case 'no-res':
				$cr_status=7;
				break;
			case 'starting':
				$cr_status=8;
				break;
			default:
				exit(1);
				break;
		}
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update ".$this->_db_table." set cr_status=$cr_status where cr_id=$cloudrequest_id");

	}

	// Get list of possible states
	function getstates() {
		$r[] = array(1, 'new');
		$r[] = array(2, 'approve');
		$r[] = array(3, 'active');
		$r[] = array(4, 'deny');
		$r[] = array(5, 'deprovision');
		$r[] = array(6, 'done');
		$r[] = array(7, 'no-res');
		$r[] = array(8, 'starting');
		return $r;
	}

	// function to get the status of a request
	function getstatus($cloudrequest_id) {
		$this->get_instance($cloudrequest_id);
		switch ($this->status) {
			case 1:
				$cr_status='new';
				break;
			case 2:
				$cr_status='approve';
				break;
			case 3:
				$cr_status='active';
				break;
			case 4:
				$cr_status='deny';
				break;
			case 5:
				$cr_status='deprovision';
				break;
			case 6:
				$cr_status='done';
				break;
			case 7:
				$cr_status='no-res';
				break;
			case 8:
				$cr_status='starting';
				break;
			default:
				$cr_status='';
				break;
		}
		return $cr_status;
	}




	// function to set the appliance_id of a request
	function setappliance($cmd, $appliance_id) {
		$updated_appliance_ids = '';
		$current_appliance_ids = $this->appliance_id;
		switch ($cmd) {
			case 'add':
				if ($current_appliance_ids == 0) {
					$updated_appliance_ids = "$appliance_id";
				} else {
					$updated_appliance_ids = "$current_appliance_ids,$appliance_id";
				}
				break;
			case 'remove':
				$app_id_arr = explode(",", $current_appliance_ids);
				$loop=1;
				foreach ($app_id_arr as $app_id) {
					if (strcmp($app_id, $appliance_id)) {
						if ($loop == 1) {
							$updated_appliance_ids = $app_id;
						} else {
							$updated_appliance_ids = $updated_appliance_ids.",".$app_id;
						}
					}
				}
				break;
			default:
				exit(1);
				break;
		}
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update ".$this->_db_table." set cr_appliance_id='$updated_appliance_ids' where cr_id=$this->id");

	}




	// find a cr according to its appliance id
	function get_cr_for_appliance($appliance_id) {
		$db=openqrm_get_db_connection();
		$cloudrequest_array = $db->Execute("select cr_id from ".$this->_db_table." where cr_appliance_id=$appliance_id");
		foreach ($cloudrequest_array as $index => $cloudrequest) {
			return $cloudrequest["cr_id"];
		}
	}





	// function to re-set stop-time of a request
	function extend_stop_time($cloudrequest_id, $stop_time) {
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update ".$this->_db_table." set cr_stop=$stop_time where cr_id=$cloudrequest_id");

	}



	// displays the cloudrequest-overview per user
	function display_overview_per_user($cu_id, $offset, $limit, $sort, $order) {
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from ".$this->_db_table." where cr_cu_id=$cu_id order by $sort $order", $limit, $offset);
		$cloudrequest_array = array();
		if (!$recordSet) {
			$this->_event->log("display_overview_per_user", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($cloudrequest_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $cloudrequest_array;
	}



	// displays the cloudrequest-overview
	function display_overview($offset, $limit, $sort, $order) {
		$db=openqrm_get_db_connection();
		$recordSet = $db->SelectLimit("select * from ".$this->_db_table." order by $sort $order", $limit, $offset);
		$cloudrequest_array = array();
		if (!$recordSet) {
			$this->_event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "cloudrequest.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($cloudrequest_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $cloudrequest_array;
	}







// ---------------------------------------------------------------------------------

}

?>
