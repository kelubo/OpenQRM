<?php
/**
 * Cloud Users Appliance Comment
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_appliance_update
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud-ui';



	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $path path to dir
	 * @param htmlobject_response $response
	 */
	//--------------------------------------------
	function __construct($openqrm, $response) {
		$this->openqrm = $openqrm;
		$this->response = $response;
		$this->rootdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
		// include classes and prepare ojects
		require_once $this->rootdir."/plugins/cloud/class/cloudappliance.class.php";
		$this->cloudappliance = new cloudappliance();
		require_once $this->rootdir."/class/appliance.class.php";
		$this->appliance = $this->openqrm->appliance();
		require_once $this->rootdir."/plugins/cloud/class/cloudrequest.class.php";
		$this->cloudrequest	= new cloudrequest();
		require_once $this->rootdir."/plugins/cloud/class/cloudselector.class.php";
		$this->cloudselector = new cloudselector();
		require_once $this->rootdir."/plugins/cloud/class/cloudimage.class.php";
		$this->cloudimage = new cloudimage();
		require_once $this->rootdir."/plugins/cloud/class/cloudconfig.class.php";
		$this->cloudconfig = new cloudconfig();
		require_once $this->rootdir."/plugins/cloud/class/clouduserslimits.class.php";
		$this->clouduserlimits = new clouduserlimits();
		$this->clouduserlimits->get_instance_by_cu_id($this->openqrm->user()->id);

		require_once "cloud.limits.class.php";
		$this->cloud_limits = new cloud_limits($this->openqrm, $this->cloudconfig, $this->clouduserlimits, $this->cloudrequest);

	}

	//--------------------------------------------
	/**
	 * Action remove
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {
		if($this->cloudconfig->get_value_by_key('cloud_enabled') === 'false') {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'appliances', $this->message_param, $this->lang['appliances']['error_cloud_disabled'])
			);
		} else {
			if ($this->response->html->request()->get($this->identifier_name) === '') {
				$this->response->redirect($this->response->get_url($this->actions_name, ''));
			}
			$response = $this->update();
			if(isset($response->error)) {
				$_REQUEST[$this->message_param] = $response->error;
			}
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'appliances', $this->message_param, $response->msg));
			}
			$t = $this->response->html->template($this->tpldir."/cloud-ui.appliance-update.tpl.php");
			$t->add($response->form->get_elements());
			$t->add($response->html->thisfile, "thisfile");
		 	$t->add($this->lang['appliances']['label_update_notice'],  "label_update_notice");
			$t->add($this->lang['appliances']['update_cpu_notice'],  "update_cpu_notice");
			if(isset($response->disk_resize))  {
				$t->add($this->lang['appliances']['update_disk_notice'],  "update_disk_notice");
			}  else  {
				$t->add('',  "update_disk_notice");
			}
			$t->add(sprintf($this->lang['appliances']['label_update'],$response->appliance), 'label');
			$t->group_elements(array('param_' => 'form'));
			return $t;
		}
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Update
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function update() {
		$this->ca_id = $this->response->html->request()->get($this->identifier_name);
		$this->response->add($this->identifier_name, $this->ca_id);
		$this->cloudappliance->get_instance_by_id($this->ca_id);
		$this->appliance->get_instance_by_id($this->cloudappliance->appliance_id);
		$this->cloudrequest->get_instance_by_id($this->cloudappliance->cr_id);

		// check appliance belongs to user
		if ($this->cloudrequest->cu_id != $this->clouduser->id) {
			$response = $this->response;
			$response->msg = sprintf($this->lang['appliances']['error_access_denied'], $this->ca_id);
		} else {
			$response = $this->get_response();
			$form = $response->form;
			if(!$form->get_errors() && $response->submit()) {
				$comment = $form->get_request('comment');
				$cpu     = $form->get_request('cpu');
				$memory  = $form->get_request('memory');
				$disk    = $form->get_request('disk');

				// update appliance comment
				if(isset($comment)) {
					$name = $this->appliance->name;
					$appliance_fields['appliance_comment'] = $comment;
					$this->appliance->update($this->appliance->id, $appliance_fields);
				}
				// update cpu_req in cr
				if(
					isset($cpu) &&
					$cpu !== '' &&
					$this->cloudappliance->state == 0 &&
					$this->cloudrequest->cpu_req != $cpu
				) {
					$cr_cpu_fields['cr_cpu_req'] = $cpu;
					$this->cloudrequest->update($this->cloudrequest->id, $cr_cpu_fields);
				}
				// update ram_req in cr
				if(
					isset($memory) &&
					$memory !== '' &&
					$this->cloudappliance->state == 0 &&
					$this->cloudrequest->ram_req != $memory
				) {
					$cr_mem_fields['cr_ram_req'] = $memory;
					$this->cloudrequest->update($this->cloudrequest->id, $cr_mem_fields);
				}

				// check resize
				if(isset($disk) && $disk !== '') {
					$error = false;
					$image = $this->openqrm->image();
					$image->get_instance_by_id($this->appliance->imageid);
					$this->cloudimage->get_instance_by_image_id($image->id);
					$cloud_image_current_disk_size = $this->cloudimage->disk_size;

					// check disk is bigger
					if ($disk < $cloud_image_current_disk_size) {
						$response->error = $this->lang['appliances']['error_disk_size'];
						$error = true;
					}
					// check if no other command is currently running
					if ($this->cloudappliance->cmd != 0) {
						$response->error = $this->lang['appliances']['error_command_running'];
						$error = true;
					}
					// check that state is active
					if ($this->cloudappliance->state != 1) {
						$response->error = $this->lang['appliances']['error_appliance_not_active'];
						$error = true;
					}
					if (!$error) {
						require_once($this->rootdir.'/plugins/cloud/class/cloudirlc.class.php');
						$cloudirlc = new cloudirlc();
						// put the disk in the cr
						$cr_disk_fields['cr_disk_req'] = $disk;
						$this->cloudrequest->update($this->cloudrequest->id, $cr_disk_fields);
						// put the new size in the cloud_image
						$cloudi_request = array(
							'ci_disk_rsize' => "$disk",
						);
						$this->cloudimage->update($this->cloudimage->id, $cloudi_request);
						// create a new cloud-image resize-life-cycle / using cloudappliance id
						$cirlc_fields['cd_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
						$cirlc_fields['cd_appliance_id'] = $this->cloudappliance->id;
						$cirlc_fields['cd_state'] = '1';
						$cloudirlc->add($cirlc_fields);
					}

				}
				$response->msg = sprintf($this->lang['appliances']['msg_updated_appliance'], $this->appliance->name);
			}
			$response->appliance = $this->appliance->name;
		}
		return $response;
	}


	function get_response() {
		$ca_id = $this->response->html->request()->get($this->identifier_name);
		$this->appliance->get_instance_by_id($this->cloudappliance->appliance_id);
		$name = $this->appliance->name;
		$response =$this->response;
		$form = $response->get_form($this->actions_name, 'appliance_update');
		$this->cloudrequest->get_instance_by_id($this->cloudappliance->cr_id);

		$available_cpunumber = array();
		$available_memtotal = array();
		$this->clouduserlimits->get_instance_by_cu_id($this->cloudrequest->cu_id);
		$cloud_user_memory_limit = $this->clouduserlimits->memory_limit;
		$cloud_user_cpu_limit = $this->clouduserlimits->cpu_limit;

		$image = $this->openqrm->image();
		$image->get_instance_by_id($this->appliance->imageid);
		$this->cloudimage->get_instance_by_image_id($image->id);

		// check if cloud_selector feature is enabled
		$cloud_selector_enabled = $this->cloudconfig->get_value_by_key('cloud_selector'); // cloud_selector
		if (!strcmp($cloud_selector_enabled, "true")) {
			// show what is provided by the cloudselectors
			// cpus
			$product_array = $this->cloudselector->display_overview_per_type("cpu");
			$available_cpunumber = array();
			foreach ($product_array as $index => $cloudproduct) {
				// is product enabled ?
				if ($cloudproduct["state"] == 1) {
					$cs_cpu = $cloudproduct["quantity"];
					if ($cs_cpu <= $this->cloud_limits->free('cpu')) {
						$available_cpunumber[] = array("value" => $cloudproduct["quantity"], "label" => $cloudproduct["name"]);
					}
				}
			}
			// memory sizes
			$product_array = $this->cloudselector->display_overview_per_type("memory");
			$available_memtotal = array();
			foreach ($product_array as $index => $cloudproduct) {
				// is product enabled ?
				if ($cloudproduct["state"] == 1) {
					$cs_memory = $cloudproduct["quantity"];
					if ($cs_memory <= $this->cloud_limits->free('memory')) {
						$available_memtotal[] = array("value" => $cloudproduct["quantity"], "label" => $cloudproduct["name"]);
					}
				}
			}
			// disk size
			$product_array = $this->cloudselector->display_overview_per_type("disk");
			$disk_size_select = array();
			foreach ($product_array as $index => $cloudproduct) {
				// is product enabled ?
				if ($cloudproduct["state"] == 1) {
					$cs_disk = $cloudproduct["quantity"];
					if ($cs_disk <= $this->cloud_limits->free('disk')) {
						if ($cs_disk <= $this->cloudimage->disk_size) {
							continue;
						}
						$disk_size_select[] = array("value" => $cloudproduct["quantity"], "label" => $cloudproduct["name"]);
					}
				}
			}

		} else {
			
			// get list of available resource parameters
			$resource_p = $this->openqrm->resource();
			$resource_p_array = $resource_p->get_list();
			// remove openQRM resource
			array_shift($resource_p_array);
			// gather all available values in arrays
			$available_cpunumber_uniq = array();
			$available_memtotal_uniq = array();
			foreach($resource_p_array as $res) {
				$res_id = $res['resource_id'];
				$tres = $this->openqrm->resource();
				$tres->get_instance_by_id($res_id);
				if ((strlen($tres->cpunumber)) && (!in_array($tres->cpunumber, $available_cpunumber_uniq))) {
					$available_cpunumber[] = array("value" => $tres->cpunumber, "label" => $tres->cpunumber);
					$available_cpunumber_uniq[] .= $tres->cpunumber;
				}
				if ((strlen($tres->memtotal)) && (!in_array($tres->memtotal, $available_memtotal_uniq))) {
					$available_memtotal[] = array("value" => $tres->memtotal, "label" => $tres->memtotal." MB");
					$available_memtotal_uniq[] .= $tres->memtotal;
				}
			}
			// disk size select
			$max_disk_size = $this->cloud_limits->free('disk');
			$disk_size_select = array();
			$sizes = array(1000,2000,3000,4000,5000,10000,20000,50000,100000);
			foreach($sizes as $size) {
				if ($size <= $max_disk_size && $this->cloudimage->disk_size < $size) {
					$disk_size_select[] = array("value" => $size, "label" => ($size/1000).' GB');
				}
			}

		}

		// appliance active or paused, allow updating cpu and memory when paused
		$allow_update = true;
		switch ($this->cloudappliance->state) {
			case 0:
				$allow_update = false;
				break;
			case 1:
				$allow_update = true;
				break;
		}

		$d['comment']['label']                         = $this->lang['appliances']['comment'];
		$d['comment']['object']['type']                = 'htmlobject_textarea';
		$d['comment']['object']['attrib']['name']      = 'comment';
		$d['comment']['object']['attrib']['id']        = 'comment';
		$d['comment']['object']['attrib']['value']     = $this->appliance->comment;
		$d['comment']['object']['attrib']['maxlength'] = 255;

		$d['cpu']['label']                        = $this->lang['create']['cpu'];
		$d['cpu']['object']['type']               = 'htmlobject_select';
		$d['cpu']['object']['attrib']['index']    = array('value', 'label');
		$d['cpu']['object']['attrib']['name']     = 'cpu';
		$d['cpu']['object']['attrib']['id']       = 'cpu';
		$d['cpu']['object']['attrib']['options']  = $available_cpunumber;
		$d['cpu']['object']['attrib']['selected'] = array($this->cloudrequest->cpu_req);
		$d['cpu']['object']['attrib']['disabled'] = $allow_update;

		$d['memory']['label']                        = $this->lang['create']['ram'];
		$d['memory']['object']['type']               = 'htmlobject_select';
		$d['memory']['object']['attrib']['index']    = array('value', 'label');
		$d['memory']['object']['attrib']['name']     = 'memory';
		$d['memory']['object']['attrib']['id']       = 'memory';
		$d['memory']['object']['attrib']['options']  = $available_memtotal;
		$d['memory']['object']['attrib']['selected'] = array($this->cloudrequest->ram_req);
		$d['memory']['object']['attrib']['disabled'] = $allow_update;

		//allow updating disk when active
		$allow_update = true;
		switch ($this->cloudappliance->state) {
			case 0:
				$allow_update = true;
				break;
			case 1:
				$allow_update = false;
				break;
		}
		// image disk size
		$d['disk'] = '';
		if($this->cloudconfig->get_value_by_key('show_disk_resize') === 'true') {

			if(strpos($image->type, 'lvm-') !== false) {
				$d['disk']['label']                        = $this->lang['create']['disk'];
				$d['disk']['object']['type']               = 'htmlobject_select';
				$d['disk']['object']['attrib']['index']    = array('value', 'label');
				$d['disk']['object']['attrib']['name']     = 'disk';
				$d['disk']['object']['attrib']['id']       = 'disk';
				$d['disk']['object']['attrib']['options']  = $disk_size_select;
				$d['disk']['object']['attrib']['disabled'] = $allow_update;
				$response->disk_resize  =  true;
			}
		}

		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
