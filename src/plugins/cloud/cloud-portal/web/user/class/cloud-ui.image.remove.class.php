<?php
/**
 * Remove Cloud Users Private Images
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_image_remove
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud_ui';



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
				$this->response->redirect($this->response->get_url($this->actions_name, 'images'));
			}
			$response = $this->image_remove();
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'images', $this->message_param, $response->msg));
			}

			$template = $this->response->html->template($this->tpldir."/cloud-ui.image-remove.tpl.php");
			$template->add($response->form->get_elements());
			$template->add($response->html->thisfile, "thisfile");
			$template->add($this->lang['label_remove'], 'label');
			$template->group_elements(array('param_' => 'form'));

			return $template;
		}
	}

	//--------------------------------------------
	/**
	 * Remove Cloud Users Private Images
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function image_remove() {
		$this->pi_id = $this->response->html->request()->get($this->identifier_name);
		$this->response->add($this->identifier_name, $this->pi_id);
		$this->cloudprivateimage->get_instance_by_id($this->pi_id);

		$response = $this->get_response();
		$form = $response->form;

		if($this->response->submit()) {
			$cloud_image_remove = $form->get_request('cloud_image_remove');
			if(isset($cloud_image_remove)) {
				$error = false;
				$this->image->get_instance_by_id($this->cloudprivateimage->image_id);
				// check image belongs to user
				if ($this->cloudprivateimage->cu_id != $this->clouduser->id) {
					$response->msg = sprintf($this->lang['error_access_denied'], $this->image->name);
					$error = true;
				}
				// check that image is not active
				if ($this->image->isactive == 1) {
					$response->error = sprintf($this->lang['error_image_active'], $this->image->name);
					$error = true;
				}

				if (!$error) {
					// register a new cloudimage for removal
					$cloud_image_id  = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$cloud_image_arr = array(
							'ci_id' => $cloud_image_id,
							'ci_cr_id' => 0,
							'ci_image_id' => $this->cloudprivateimage->image_id,
							'ci_appliance_id' => 0,
							'ci_resource_id' => 0,
							'ci_disk_size' => 0,
							'ci_state' => 0,
					);
					$this->cloudimage->add($cloud_image_arr);
					// remove logic cloudprivateimage
					$this->cloudprivateimage->remove($this->cloudprivateimage->id);
					$response->msg = sprintf($this->lang['msg_image_removed'], $this->image->name);
				}
			}
		}
		return $response;
	}


	function get_response() {
		$pi_id = $this->response->html->request()->get($this->identifier_name);
		$this->image->get_instance_by_id($this->cloudprivateimage->image_id);
		$cloud_image_name = $this->image->name;

		$response =$this->response;
		$form     = $response->get_form($this->actions_name, 'image_remove');
		
		$d['cloud_image_remove']['label']                       = $this->image->name;
		$d['cloud_image_remove']['object']['type']              = 'htmlobject_input';
		$d['cloud_image_remove']['object']['attrib']['type']    = 'checkbox';
		$d['cloud_image_remove']['object']['attrib']['name']    = 'cloud_image_remove';
		$d['cloud_image_remove']['object']['attrib']['id']      = 'cloud_image_remove';
		$d['cloud_image_remove']['object']['attrib']['checked'] = true;
		
		$form->add($d);
		$response->form = $form;
		return $response;
	}



}

?>







