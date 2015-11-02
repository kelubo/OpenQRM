<?php
/**
 * Cloud Power-Saver Update
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_power_saver_update
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_power_saver';


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
		require_once $this->rootdir."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		$this->appliance = $this->openqrm->appliance();
		$this->virtualization = $this->openqrm->virtualization();
		$this->resource = $this->openqrm->resource();

		// handle response
		$this->response->add('cloud_power_saver_id', $this->response->html->request()->get('cloud_power_saver_id'));
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

		$cloud_power_saver_id = $this->response->html->request()->get('cloud_power_saver_id');
		$cloud_power_saver_name = '';
		if (strlen($cloud_power_saver_id)) {
			$this->appliance->get_instance_by_id($cloud_power_saver_id);
			$cloud_power_saver_name = $this->appliance->name;
		}
		$template = $response->html->template($this->tpldir."/cloud-power-saver-update.tpl.php");
		$template->add(sprintf($this->lang['cloud_power_saver_update_title'], $cloud_power_saver_name), 'title');
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Power-Saver Update
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
			$data['cloud_power_saver_id'] = $this->response->html->request()->get('cloud_power_saver_id');
			// update data
			if(!$form->get_errors()) {
				$this->appliance->get_instance_by_id($data['cloud_power_saver_id']);
				$assigned_to = $data['cloud_power_saver_assign'];
				$this->resource->get_instance_by_id($this->appliance->resources);
				if ($assigned_to == 0) {
					$this->resource->set_resource_capabilities('CPS', 0);
				} else if ($assigned_to == 1) {
					$this->resource->set_resource_capabilities('CPS', 1);
				}
			    // success msg
			    $response->msg = sprintf($this->lang['cloud_power_saver_updated'], $this->appliance->name);
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
		$assigned_to_default = 0;
		$cloud_power_saver_id = $this->response->html->request()->get('cloud_power_saver_id');
		if (strlen($cloud_power_saver_id)) {
			$this->appliance->get_instance_by_id($cloud_power_saver_id);
			$this->resource->get_instance_by_id($this->appliance->resources);
			$assigned_to_default = $this->resource->get_resource_capabilities('CPS');
		}

		$response = $this->response;
		$form = $response->get_form($this->actions_name, "update");

		$cloud_power_saver_assign_select = array();
		$cloud_power_saver_assign_select[] = array( 'value' => '1', 'label' => $this->lang['cloud_resource_enabled']);
		$cloud_power_saver_assign_select[] = array( 'value' => '0', 'label' => $this->lang['cloud_resource_disabled']);

		$d = array();

		$d['cloud_power_saver_assign']['label']                          = ' ';
		$d['cloud_power_saver_assign']['object']['type']                 = 'htmlobject_select';
		$d['cloud_power_saver_assign']['object']['attrib']['index'] = array('value', 'label');
		$d['cloud_power_saver_assign']['object']['attrib']['id']         = 'cloud_power_saver_assign';
		$d['cloud_power_saver_assign']['object']['attrib']['name']       = 'cloud_power_saver_assign';
		$d['cloud_power_saver_assign']['object']['attrib']['options']    = $cloud_power_saver_assign_select;
		$d['cloud_power_saver_assign']['object']['attrib']['selected']    = array($assigned_to_default);

		$form->add($d);
		$response->form = $form;
		return $response;
	}
}
?>
