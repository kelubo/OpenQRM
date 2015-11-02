<?php
/**
 * Cloud UserGroup Delete
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_usergroup_delete
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud_usergroup';



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
		require_once $this->webdir."/plugins/cloud/class/clouduser.class.php";
		$this->cloud_user = new clouduser();
		require_once $this->webdir."/plugins/cloud/class/cloudusergroup.class.php";
		$this->cloud_user_group = new cloudusergroup();

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
		if ($this->response->html->request()->get($this->identifier_name) === '') {
			$this->response->redirect($this->response->get_url($this->actions_name, ''));
		}
		$response = $this->delete();
//		if(isset($response->error)) {
//			$_REQUEST[$this->message_param] = $response->error;
//		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg));
		}

		$template = $this->response->html->template($this->tpldir."/cloud-usergroup-delete.tpl.php");
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->add($this->lang['cloud_usergroup_confirm_delete'], 'confirm_delete');
		$template->group_elements(array('param_' => 'form'));

		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud UserGroup Delete
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function delete() {
		$response = $this->get_response();
		$form = $response->form;

		if(!$form->get_errors() && $this->response->submit()) {
			$request = $form->get_request($this->identifier_name);

			if(isset($request) && is_array($request)) {
				$errors  = array();
				$message = array();
				foreach($request as $key => $cz_ug_id) {
					// get name before delete
					$this->cloud_user_group->get_instance_by_id($cz_ug_id);
					$cloud_usergroup_name = $this->cloud_user_group->name;
					if ($cz_ug_id == 0) {
						$message[] = $this->lang['cloud_usergroup_not_removing_default'];
						continue;
					}
					// check if this group still contains user
					$cloud_usergroup_still_in_use = false;
					$cloud_user_id_arr = $this->cloud_user->get_all_ids();
					foreach($cloud_user_id_arr as $cz_user_id_arr) {
						$this->cloud_user->get_instance_by_id($cz_user_id_arr['cu_id']);
						if ($this->cloud_user->cg_id == $cz_ug_id) {
							$cloud_usergroup_still_in_use = true;
						}
					}
					if ($cloud_usergroup_still_in_use) {
						$message[] = $this->lang['cloud_usergroup_still_contains_user']." - ".$cloud_usergroup_name;
						continue;
					}

					//delete here;
					$error = $this->cloud_user_group->remove($cz_ug_id);
					$message[] = $this->lang['cloud_usergroup_deleted']." - ".$cloud_usergroup_name;
				}
				if(count($errors) === 0) {
					$response->msg = join('<br>', $message);
				} else {
					$msg = array_merge($errors, $message);
					$response->error = join('<br>', $msg);
				}
			}
		}
		return $response;
	}


	function get_response() {
		$todelete = $this->response->html->request()->get($this->identifier_name);
		$response =$this->response;
		$form     = $response->get_form($this->actions_name, 'delete');
		$d        = array();
		if( $todelete !== '' ) {
			$i = 0;
			foreach($todelete as $cz_ug_id) {
				$this->cloud_user_group->get_instance_by_id($cz_ug_id);
				$d['param_f'.$i]['label']                       = $cz_ug_id." - ".$this->cloud_user_group->name;
				$d['param_f'.$i]['object']['type']              = 'htmlobject_input';
				$d['param_f'.$i]['object']['attrib']['type']    = 'checkbox';
				$d['param_f'.$i]['object']['attrib']['name']    = $this->identifier_name.'[]';
				$d['param_f'.$i]['object']['attrib']['id']      = $this->identifier_name.'_'.$i;
				$d['param_f'.$i]['object']['attrib']['value']   = $cz_ug_id;
				$d['param_f'.$i]['object']['attrib']['checked'] = true;
				$i++;
			}
		}
		$form->add($d);
		$response->form = $form;
		return $response;
	}



}

?>


