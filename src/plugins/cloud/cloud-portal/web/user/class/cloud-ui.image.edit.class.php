<?php
/**
 * Edit Cloud Users Private Image
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_image_edit
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
		require_once $this->rootdir."/plugins/cloud/class/cloudimage.class.php";
		$this->cloudimage = new cloudimage();
		require_once $this->rootdir."/plugins/cloud/class/cloudprivateimage.class.php";
		$this->cloudprivateimage = new cloudprivateimage();
		$this->appliance = $this->openqrm->appliance();
		$this->image     = $this->openqrm->image();
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
				$this->response->get_url($this->actions_name, 'images', $this->message_param, $this->lang['error_cloud_disabled'])
			);
		} else {
			if ($this->response->html->request()->get($this->identifier_name) === '') {
				$this->response->redirect($this->response->get_url($this->actions_name, ''));
			}
			$response = $this->image_edit();
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'images', $this->message_param, $response->msg));
			}

			$this->ca_id = $this->response->html->request()->get($this->identifier_name);
			$this->cloudprivateimage->get_instance_by_id($this->ca_id);
			$this->image->get_instance_by_id($this->cloudprivateimage->image_id);

			$template = $this->response->html->template($this->tpldir."/cloud-ui.image-edit.tpl.php");
			$template->add($response->form->get_elements());
			$template->add($response->html->thisfile, "thisfile");
			$template->add(sprintf($this->lang['label_edit'], $this->image->name), 'label');
			$template->group_elements(array('param_' => 'form'));

			return $template;
		}
	}

	//--------------------------------------------
	/**
	 * Edit Cloud Users Private Image
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function image_edit() {
		$this->ca_id = $this->response->html->request()->get($this->identifier_name);
		$this->response->add($this->identifier_name, $this->ca_id);
		$this->cloudprivateimage->get_instance_by_id($this->ca_id);
		$this->image->get_instance_by_id($this->cloudprivateimage->image_id);

		$response = $this->get_response();
		$form = $response->form;

		if($this->response->submit()) {
			// check image belongs to user
			if ($this->cloudprivateimage->cu_id != $this->clouduser->id) {
				$response->msg = sprintf($this->lang['error_access_denied'], $this->image->name);
			} else {
				$cloud_image_comment = $form->get_request('cloud_image_comment');
				$cloud_image_clone_on_deploy = $form->get_request('cloud_image_clone_on_deploy');
				$update = false;
				if(isset($cloud_image_comment)) {
					if (strlen($cloud_image_comment)) {
						$image_fields['co_comment'] = $cloud_image_comment;
						$update = true;
					}
				}
				if (isset($cloud_image_clone_on_deploy)) {
					if (strlen($cloud_image_clone_on_deploy)) {
						$image_fields['co_clone_on_deploy'] = 1;
					} else {
						$image_fields['co_clone_on_deploy'] = 0;
					}
					$update = true;
				} else {
					$image_fields['co_clone_on_deploy'] = 0;
					$update = true;
				}
				if ($update) {
					$this->cloudprivateimage->update($this->cloudprivateimage->id, $image_fields);
				}
				$response->msg = sprintf($this->lang['msg_updated'], $this->image->name);
			}
		}
		return $response;
	}


	function get_response() {
		$ca_id = $this->response->html->request()->get($this->identifier_name);
		$this->cloudprivateimage->get_instance_by_id($ca_id);

		$response = $this->response;
		$form     = $response->get_form($this->actions_name, 'image_edit');

		$d['cloud_image_comment']['label']                         = $this->lang['comment'];
		$d['cloud_image_comment']['object']['type']                = 'htmlobject_textarea';
		$d['cloud_image_comment']['object']['attrib']['name']      = 'cloud_image_comment';
		$d['cloud_image_comment']['object']['attrib']['id']        = 'cloud_image_comment';
		$d['cloud_image_comment']['object']['attrib']['value']     = $this->cloudprivateimage->comment;
		$d['cloud_image_comment']['object']['attrib']['maxlength'] = 255;

		$d['cloud_image_clone_on_deploy']['label']                    = $this->lang['clone_on_deploy'];
		$d['cloud_image_clone_on_deploy']['object']['type']           = 'htmlobject_input';
		$d['cloud_image_clone_on_deploy']['object']['attrib']['type'] = 'checkbox';
		$d['cloud_image_clone_on_deploy']['object']['attrib']['name'] = 'cloud_image_clone_on_deploy';
		$d['cloud_image_clone_on_deploy']['object']['attrib']['id']   = 'cloud_image_clone_on_deploy';
		if ($this->cloudprivateimage->clone_on_deploy == 1) {
			$d['cloud_image_clone_on_deploy']['object']['attrib']['checked'] = true;
		} else {
			$d['cloud_image_clone_on_deploy']['object']['attrib']['checked'] = false;
		}

		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
