<?php
/**
 * Cloud Request Details
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_request_details
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud-request-details';



	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param openqrm $openqrm
	 * @param htmlobject_response $response
	 */
	//--------------------------------------------
	function __construct($openqrm, $response) {
		$this->response = $response;
		$this->openqrm = $openqrm;
		$this->file = $this->openqrm->file();
		$this->webdir  = $this->openqrm->get('webdir');
		$this->rootdir  = $this->openqrm->get('basedir');
		require_once $this->rootdir."/plugins/cloud/web/class/clouduser.class.php";
		$this->cloud_user = new clouduser();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudrequest.class.php";
		$this->cloud_request = new cloudrequest();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudmailer.class.php";
		$this->cloud_mailer = new cloudmailer();
	}

	//--------------------------------------------
	/**
	 * Action New
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {
		
		$cr_id = $this->response->html->request()->get($this->identifier_name);
		$this->cloud_request->get_instance($cr_id);
		$this->cloud_user->get_instance_by_id($this->cloud_request->cu_id);
		$data = $this->details();
		$template = $this->response->html->template($this->tpldir."/cloud-request-details.tpl.php");

		$template->add($cr_id, 'cloud_request_id');
		$template->add($this->cloud_user->name, 'username');

		
		$template->add($this->lang['cloud_request'], 'cloud_requests');
		$template->add($this->lang['cloud_request_user'], 'cloud_request_user');
		$template->add($data, 'table');
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Request Details
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function details() {
		$this->response->add($this->identifier_name, $this->response->html->request()->get($this->identifier_name));

		$kernel = new kernel();
		$kernel->get_instance_by_id($this->cloud_request->kernel_id);
		$image = new image();
		$image->get_instance_by_id($this->cloud_request->image_id);
		$virtualization = new virtualization();
		$virtualization->get_instance_by_id($this->cloud_request->resource_type_req);
		$cr_status = $this->cloud_request->getstatus($this->cloud_request->id);
		$ha_req = '';
		if ((isset($this->cloud_request->ha_req)) && ($this->cloud_request->ha_req == 1)) {
			$ha_req = $this->lang['cloud_request_enabled'];
		} else {
			$ha_req = $this->lang['cloud_request_disabled'];
		}
		$appliance_hostname = '';
		if (strlen($this->cloud_request->appliance_hostname)) {
			$appliance_hostname = $this->cloud_request->appliance_hostname;
		} else {
			$appliance_hostname = '-';
		}
		
		$appliance_applications = '';
		if (strlen($this->cloud_request->puppet_groups)) {
			$appliance_applications = $this->cloud_request->puppet_groups;
		} else {
			$appliance_applications = '-';
		}
		
		$appliance_ip_config = '';
		if (strlen($this->cloud_request->ip_mgmt)) {
			$ip_config_arr = explode(',', $this->cloud_request->ip_mgmt);
			foreach ($ip_config_arr as $ip) {
				$single_ip_config_arr = explode(':', $ip);
				$nic_no = $single_ip_config_arr[0];
				switch($single_ip_config_arr[1]) {
					case '-2':
						$appliance_ip_config .= $this->lang['cloud_request_network_req'].' '.$nic_no.':auto<br>';
						break;
					default:
						$appliance_ip_config .= $this->lang['cloud_request_network_req'].' '.$nic_no.':custom<br>';
						break;
				}
			}
		} else {
			$appliance_ip_config = '-';
		}
		
		$table = $this->response->html->table();
		$table->css             = 'htmlobject_table';
		$table->border          = 0;
		$table->id              = 'cloud_request_details';

		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_id'],
			'cr_value' => $this->cloud_request->id,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_status'],
			'cr_value' => $cr_status,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_time'],
			'cr_value' => date("Y-m-d H:i:s", $this->cloud_request->request_time),
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_start_time'],
			'cr_value' => date("Y-m-d H:i:s", $this->cloud_request->start),
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_stop_time'],
			'cr_value' =>date("Y-m-d H:i:s", $this->cloud_request->stop),
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_kernel'],
			'cr_value' => $kernel->name,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_image'],
			'cr_value' => $image->name,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_cpu_req'],
			'cr_value' => $this->cloud_request->cpu_req,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_ram_req'],
			'cr_value' => $this->cloud_request->ram_req." MB",
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_disk_req'],
			'cr_value' => $this->cloud_request->disk_req." MB",
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_network_req'],
			'cr_value' => $this->cloud_request->network_req,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_app_id'],
			'cr_value' => $this->cloud_request->appliance_id,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_resource_req'],
			'cr_value' => $virtualization->name,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_ha_req'],
			'cr_value' => $ha_req,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_name'],
			'cr_value' => $appliance_hostname,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_applications'],
			'cr_value' => $appliance_applications,
		);
		$ta[] = array(
			'cr_key' => $this->lang['cloud_request_ipconfig'],
			'cr_value' => $appliance_ip_config,
		);
		
		$table->add($ta);
		return $table;
		
	}


}

?>


