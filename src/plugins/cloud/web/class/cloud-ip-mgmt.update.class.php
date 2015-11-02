<?php
/**
 * Cloud IP-Mgmt Update
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_ip_mgmt_update
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_ip_mgmt';


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
		require_once $this->rootdir."/plugins/cloud/web/class/cloudusergroup.class.php";
		$this->cloud_user_group = new cloudusergroup();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->rootdir."/plugins/ip-mgmt/web/class/ip-mgmt.class.php";
		$this->ip_mgmt = new ip_mgmt();

		// handle response
		$this->response->add('cloud_ip_mgmt_name', $this->response->html->request()->get('cloud_ip_mgmt_name'));
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

		$cloud_ip_mgmt = $this->response->html->request()->get('cloud_ip_mgmt_name');

		$template = $response->html->template($this->tpldir."/cloud-ip-mgmt-update.tpl.php");
		$template->add(sprintf($this->lang['cloud_ip_mgmt_update_title'], $cloud_ip_mgmt), 'title');
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud IP-Mgmt Update
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
			$data['cloud_ip_mgmt_name'] = $this->response->html->request()->get('cloud_ip_mgmt_name');
			// update data
			if(!$form->get_errors()) {
				$private_cloud_ip_mgmt_fields["ip_mgmt_user_id"] = $data['cloud_ip_mgmt_assign'];
				$this->ip_mgmt->update($data['cloud_ip_mgmt_name'], $private_cloud_ip_mgmt_fields);
			    // success msg
			    $response->msg = sprintf($this->lang['cloud_ip_mgmt_updated'], $this->appliance->name);

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

		$cloud_ip_mgmt = $this->response->html->request()->get('cloud_ip_mgmt_name');
		$assigned_to_default = '';
		$cloud_ip_mgmt_name = '';
		if (strlen($cloud_ip_mgmt)) {
			$cloud_ip_mgmt_name = $cloud_ip_mgmt;

			$ip_mgmt_lib_by_name = $this->ip_mgmt->get_list($cloud_ip_mgmt_name);
			$assigned_to_default = $ip_mgmt_lib_by_name[$cloud_ip_mgmt_name]['first']['ip_mgmt_user_id'];
			if (!strlen($assigned_to_default)) {
				$assigned_to_default=-1;
			}
		}

		$response = $this->response;
		$form = $response->get_form($this->actions_name, "update");

		$cloud_ip_mgmt_assign_default_arr = array();
		$cloud_ip_mgmt_assign_default_arr[] = array( 'value' => '-1', 'label' => $this->lang['cloud_ip_mgmt_not_assigned']);
		$cloud_ip_mgmt_assign_arr = $this->cloud_user_group->get_list();
		$cloud_ip_mgmt_assign_select = array_merge($cloud_ip_mgmt_assign_arr, $cloud_ip_mgmt_assign_default_arr);

		$d = array();

		$d['cloud_ip_mgmt_assign']['label']                          = ' ';
		$d['cloud_ip_mgmt_assign']['object']['type']                 = 'htmlobject_select';
		$d['cloud_ip_mgmt_assign']['object']['attrib']['index'] = array('value', 'label');
		$d['cloud_ip_mgmt_assign']['object']['attrib']['id']         = 'cloud_ip_mgmt_assign';
		$d['cloud_ip_mgmt_assign']['object']['attrib']['name']       = 'cloud_ip_mgmt_assign';
		$d['cloud_ip_mgmt_assign']['object']['attrib']['options']    = $cloud_ip_mgmt_assign_select;
		$d['cloud_ip_mgmt_assign']['object']['attrib']['selected']    = array($assigned_to_default);

		$form->add($d);
		$response->form = $form;
		return $response;
	}


}
?>
