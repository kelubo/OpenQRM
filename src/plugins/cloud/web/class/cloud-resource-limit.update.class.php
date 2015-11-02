<?php
/**
 * Cloud Resource-Limit Update
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_resource_limit_update
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_resource_limit';


	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
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
		require_once $this->webdir."/plugins/cloud/class/cloudusergroup.class.php";
		$this->cloud_user_group = new cloudusergroup();
		require_once $this->webdir."/plugins/cloud/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->webdir."/plugins/cloud/class/cloudhostlimit.class.php";
		$this->cloudhostlimit = new cloudhostlimit();
		$this->appliance = new appliance();
		$this->virtualization = new virtualization();

		// handle response
		$this->response->add('cloud_resource_limit_id', $this->response->html->request()->get('cloud_resource_limit_id'));

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
		$response = $this->update();

		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg));
		}

		$cloud_resource_limit_id = $this->response->html->request()->get('cloud_resource_limit_id');
		$cloud_resource_limit_name = '';
		if (strlen($cloud_resource_limit_id)) {
			$this->appliance->get_instance_by_id($cloud_resource_limit_id);
			$cloud_resource_limit_name = $this->appliance->name;
		}
		$template = $response->html->template($this->tpldir."/cloud-resource-limit-update.tpl.php");
		$template->add(sprintf($this->lang['cloud_resource_limit_update_title'], $cloud_resource_limit_name), 'title');
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Resource-Limit Update
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function update() {
		$response = $this->get_response();
		$form     = $response->form;
		if(!$form->get_errors()	&& $response->submit()) {
			$data = $form->get_request();
			$data['cloud_resource_limit_id'] = $this->response->html->request()->get('cloud_resource_limit_id');
			// update data
			if(!$form->get_errors()) {
				$this->appliance->get_instance_by_id($data['cloud_resource_limit_id']);
				$assigned_to = $data['cloud_resource_limit_assign'];
				if ($this->cloudhostlimit->exists_by_resource_id($this->appliance->resources)) {
					$this->cloudhostlimit->get_instance_by_resource($this->appliance->resources);
					// remove
					if ($assigned_to == -1) {
						// remove from table
						$this->cloudhostlimit->remove($this->cloudhostlimit->id);
					} else {
						// update
						$private_cloud_resource_limit_fields["hl_max_vms"] = $assigned_to;
						$this->cloudhostlimit->update($this->cloudhostlimit->id, $private_cloud_resource_limit_fields);
					}
				} else if ($assigned_to != -1) {
				// new
					$private_cloud_resource_limit_fields["hl_id"]=(int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$private_cloud_resource_limit_fields["hl_resource_id"] = $this->appliance->resources;
					$private_cloud_resource_limit_fields["hl_max_vms"] = $assigned_to;
					$this->cloudhostlimit->add($private_cloud_resource_limit_fields);
				}
			    // success msg
			    $response->msg = sprintf($this->lang['cloud_resource_limit_updated'], $this->appliance->name);
			}
		}
		return $response;
	}


	//--------------------------------------------
	/**
	 * Get response
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {
		$assigned_to = '';
		$assigned_to_default = '';
		$cloud_resource_limit_id = $this->response->html->request()->get('cloud_resource_limit_id');

		if (strlen($cloud_resource_limit_id)) {
			$this->appliance->get_instance_by_id($cloud_resource_limit_id);
			// private image config existing
			if ($this->cloudhostlimit->exists_by_resource_id($this->appliance->resources)) {
				$this->cloudhostlimit->get_instance_by_resource($this->appliance->resources);
				if ($this->cloudhostlimit->max_vms >= 0) {
					$assigned_to_default = $this->cloudhostlimit->max_vms;
				} else if ($this->cloudhostlimit->max_vms < 0) {
					$assigned_to_default = -1;
				}
			} else {
				$assigned_to_default = -1;
			}
		}

		$response = $this->response;
		$form = $response->get_form($this->actions_name, "update");

		$cloud_resource_limit_assign_select = array();
		$cloud_resource_limit_assign_select[] = array( 'value' => '-1', 'label' => $this->lang['cloud_resource_no_limit']);
		$cloud_resource_limit_assign_select[] = array( 'value' => '0', 'label' => $this->lang['cloud_resource_do_not_use']);
		for ($n = 1; $n < 1000; $n++) {
			$cloud_resource_limit_assign_select[] = array( 'value' => $n, 'label' => $n.' '.$this->lang['cloud_resource_vms']);
		}

		$d = array();

		$d['cloud_resource_limit_assign']['label']                          = ' ';
		$d['cloud_resource_limit_assign']['object']['type']                 = 'htmlobject_select';
		$d['cloud_resource_limit_assign']['object']['attrib']['index'] = array('value', 'label');
		$d['cloud_resource_limit_assign']['object']['attrib']['id']         = 'cloud_resource_limit_assign';
		$d['cloud_resource_limit_assign']['object']['attrib']['name']       = 'cloud_resource_limit_assign';
		$d['cloud_resource_limit_assign']['object']['attrib']['options']    = $cloud_resource_limit_assign_select;
		$d['cloud_resource_limit_assign']['object']['attrib']['selected']    = array($assigned_to_default);

		$form->add($d);
		$response->form = $form;
		return $response;
	}
}












?>
