<?php
/**
 * Cloud Users Profile Remove
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_profile_remove
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
		require_once $this->rootdir."/plugins/cloud/class/cloudprofile.class.php";
		$this->cloudprofile = new cloudprofile();
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
		if ($this->response->html->request()->get($this->identifier_name) === '') {
			$this->response->redirect($this->response->get_url($this->actions_name, 'create'));
		}
		$response = $this->profile_remove();
//		if(isset($response->error)) {
//			$_REQUEST[$this->message_param] = $response->error;
//		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'create', $this->message_param, $response->msg));
		}
		$t = $this->response->html->template($this->tpldir."/cloud-ui.profile-remove.tpl.php");
		$t->add($response->form->get_elements());
		$t->add($response->html->thisfile, "thisfile");
		$t->add($this->lang['label_profiles_remove'], 'label');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Profile Remove
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function profile_remove() {
		
		$this->pr_id = $this->response->html->request()->get($this->identifier_name);
		$this->response->add($this->identifier_name, $this->pr_id);
		$this->cloudprofile->get_instance_by_id($this->pr_id);
		$response = $this->get_response();
		$form = $response->form;

		if($this->response->submit()) {
			$cloud_profile_id = $form->get_request($this->identifier_name);
			if(isset($cloud_profile_id) && $cloud_profile_id !== '') {
				// check profile belongs to user
				if ($this->cloudprofile->cu_id != $this->clouduser->id) {
					$response->msg = sprintf($this->lang['error_profile_access_denied'], $cloud_profile_id);
				} else {
					$cloud_profile_name = $this->cloudprofile->name;
					$this->cloudprofile->remove($this->cloudprofile->id);
					$response->msg = sprintf($this->lang['msg_removed_profile'], $cloud_profile_name);
				}
			} else {
				$response->msg = '';
			}
		}
		return $response;
	}


	function get_response() {
		$profile_id = $this->response->html->request()->get($this->identifier_name);
		$response   = $this->response;
		$form       = $response->get_form($this->actions_name, 'profile_remove');
		$d          = array();
		$i          = 0;
		if( $profile_id !== '' && $this->cloudprofile->id !== '' ) {
			$d['param_f'.$i]['label']                       = $this->cloudprofile->name;
			$d['param_f'.$i]['object']['type']              = 'htmlobject_input';
			$d['param_f'.$i]['object']['attrib']['type']    = 'checkbox';
			$d['param_f'.$i]['object']['attrib']['name']    = $this->identifier_name;
			$d['param_f'.$i]['object']['attrib']['id']      = $this->identifier_name;
			$d['param_f'.$i]['object']['attrib']['value']   = $profile_id;
			$d['param_f'.$i]['object']['attrib']['checked'] = true;
			$i++;
		}
		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
