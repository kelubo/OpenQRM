<?php
/**
 * Cloud UserGroup Insert
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_usergroup_insert
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_usergroup';


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
		$response = $this->insert();
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg));
		}
		$external_portal_name = $this->cloud_config->get_value_by_key('external_portal_url');
		if (!strlen($external_portal_name)) {
			$openqrm_server = new openqrm_server();
			$openqrm_server_ip = $openqrm_server->get_ip_address();
			$external_portal_name = "http://".$openqrm_server_ip."/cloud-portal";
		}
		$template = $response->html->template($this->tpldir."/cloud-usergroup-insert.tpl.php");
		$template->add($this->lang['cloud_usergroup_add_title'], 'title');
		$template->add($this->lang['cloud_usergroup_data'], 'cloud_usergroup_data');
		$template->add($external_portal_name, 'external_portal_name');
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud UserGroup Insert
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function insert() {
		$response = $this->get_response();
		$form     = $response->form;
		if(!$form->get_errors()	&& $response->submit()) {
			$data = $form->get_request();

			// name already in use ?
			$this->cloud_user_group->get_instance_by_name($data['cg_name']);
			if(strlen($this->cloud_user_group->id)) {
				$form->set_error("cg_name", $this->lang['cloud_usergroup_name_already_in_use']);
			}
			if(!$form->get_errors()) {
				$data['cg_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
				$data['cg_role_id'] = 0;
				$dberror = $this->cloud_user_group->add($data);
				// success msg
				$response->msg = $this->lang['cloud_usergroup_insert_successful'];
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
		$response = $this->response;
		$form = $response->get_form($this->actions_name, "insert");

		$d = array();
		$d['cloud_usergroup_name']['label']                     = $this->lang['cloud_usergroup_name'];
		$d['cloud_usergroup_name']['required']                  = true;
		$d['cloud_usergroup_name']['validate']['regex']         = '~^[a-z0-9]+$~i';
		$d['cloud_usergroup_name']['validate']['errormsg']      = 'Name must be [a-z] only';
		$d['cloud_usergroup_name']['object']['type']            = 'htmlobject_input';
		$d['cloud_usergroup_name']['object']['attrib']['type']  = 'text';
		$d['cloud_usergroup_name']['object']['attrib']['id']    = 'cloud_usergroup_name';
		$d['cloud_usergroup_name']['object']['attrib']['name']  = 'cg_name';

// currently no use for role_id, disabled for now
//				$d['cloud_usergroup_role_id']['label']                     = $this->lang['cloud_usergroup_role_id'];
//				$d['cloud_usergroup_role_id']['required']                  = true;
//				$d['cloud_usergroup_role_id']['validate']['regex']         = '~^[a-z0-9]+$~i';
//				$d['cloud_usergroup_role_id']['validate']['errormsg']      = 'Role must be [a-z] only';
//				$d['cloud_usergroup_role_id']['object']['type']            = 'htmlobject_input';
//				$d['cloud_usergroup_role_id']['object']['attrib']['type']  = 'text';
//				$d['cloud_usergroup_role_id']['object']['attrib']['id']    = 'cloud_usergroup_role_id';
//				$d['cloud_usergroup_role_id']['object']['attrib']['name']  = 'cg_role_id';
//
		
		$d['cloud_usergroup_description']['label']                     = $this->lang['cloud_usergroup_description'];
		$d['cloud_usergroup_description']['required']                  = true;
//		$d['cloud_usergroup_description']['validate']['regex']         = '~^[a-z0-9]+$~i';
		$d['cloud_usergroup_description']['validate']['errormsg']      = 'Description must be [a-z] only';
		$d['cloud_usergroup_description']['object']['type']            = 'htmlobject_input';
		$d['cloud_usergroup_description']['object']['attrib']['type']  = 'text';
		$d['cloud_usergroup_description']['object']['attrib']['id']    = 'cloud_usergroup_description';
		$d['cloud_usergroup_description']['object']['attrib']['name']  = 'cg_description';

		$form->add($d);
		$response->form = $form;
		return $response;
	}
}












?>
