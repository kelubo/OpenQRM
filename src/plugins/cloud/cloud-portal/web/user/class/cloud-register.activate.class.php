<?php
/**
 * Activate registered Cloud User
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_register_activate
{
var $tpldir;
var $lang;
var $actions_name = 'cloud-register-activate';

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $path path to dir
	 * @param htmlobject_response $response
	 * @param file $file
	 */
	//--------------------------------------------
	function __construct($response) {
		$this->response = $response;
		$this->rootdir			= $_SERVER["DOCUMENT_ROOT"].'/openqrm/base';
		require_once $this->rootdir."/plugins/cloud/class/clouduser.class.php";
		$this->clouduser	= new clouduser();
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
		if($this->response->html->request()->get('nooutput') === 'true') {
			$div = $this->response->html->div();
			$div->add('&#160;');
			return $div;
		} else {
			$response = $this->activate();
			if(isset($response->error)) {
				$_REQUEST[$this->message_param] = $response->error;
			}
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'home', $this->message_param, $response->msg).'&nooutput=true');
			}
			$template = $response->html->template($this->tpldir."/cloud-register-activate.tpl.php");
			$template->add($response->form->get_elements());
			$template->add($response->html->thisfile, "thisfile");
			$template->add($this->lang['label'], 'label');
			$template->group_elements(array('param_' => 'form'));
			return $template;
		}
	}

	//--------------------------------------------
	/**
	 * Activate registered Cloud User
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function activate() {
		$response = $this->get_response("activate");
		$form     = $response->form;

		if(!$form->get_errors()	&& $response->submit()) {
			$data = $form->get_request();
			// token exists ?
			$this->clouduser->get_instance_by_token($data['cu_token']);
			if (!$this->clouduser->id > 0) {
				$form->set_error("cu_token", "No such token!");
			}
			if(!$form->get_errors()) {
				// status enabled
				$clouduser_fields['cu_status'] = 1;
				$this->clouduser->update($this->clouduser->id, $clouduser_fields);
				// success msg
				$response->msg = $this->lang['msg_activated'];
				// allow to have a hook when user gets activated
				if (file_exists($this->rootdir."/plugins/cloud/openqrm-cloud-user-hook.php")) {
					require_once $this->rootdir."/plugins/cloud/openqrm-cloud-user-hook.php";
					openqrm_cloud_user($this->clouduser->id, 'activate');
				}
			} else {
				$response->error = implode('<br>', $form->get_errors());
			}
		} else {
			if($form->get_errors()) {
				$response->error = implode('<br>', $form->get_errors());
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
		$form = $response->get_form($this->actions_name, "activate");

		$d = array();
		$d['cu_token']['label']                     = $this->lang['token'];
		$d['cu_token']['required']                  = true;
		$d['cu_token']['validate']['regex']         = '~^[a-z0-9]+$~i';
		$d['cu_token']['validate']['errormsg']      = 'Token must be [a-z] only';
		$d['cu_token']['object']['type']            = 'htmlobject_input';
		$d['cu_token']['object']['attrib']['type']  = 'text';
		$d['cu_token']['object']['attrib']['id']    = 'cu_token';
		$d['cu_token']['object']['attrib']['name']  = 'cu_token';

		$form->add($d);
		$form->display_errors = false;
		$response->form = $form;
		return $response;
	}

}
?>
