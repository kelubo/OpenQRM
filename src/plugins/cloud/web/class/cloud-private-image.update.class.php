<?php
/**
 * Cloud Private Image Update
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_private_image_update
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_private_image';


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
		require_once $this->rootdir."/plugins/cloud/web/class/clouduser.class.php";
		$this->cloud_user = new clouduser();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudprivateimage.class.php";
		$this->cloudprivateimage = new cloudprivateimage();
		$this->image = $this->openqrm->image();

		// handle response
		$this->response->add('cloud_private_image_id', $this->response->html->request()->get('cloud_private_image_id'));
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

		$cloud_private_image_id = $this->response->html->request()->get('cloud_private_image_id');
		$cloud_private_image_name = '';
		if (strlen($cloud_private_image_id)) {
			$this->image->get_instance_by_id($cloud_private_image_id);
			$cloud_private_image_name = $this->image->name;
		}
		$template = $response->html->template($this->tpldir."/cloud-private-image-update.tpl.php");
		$template->add(sprintf($this->lang['cloud_private_image_update_title'], $cloud_private_image_name), 'title');
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Private Image Update
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
			$data['cloud_private_image_id'] = $this->response->html->request()->get('cloud_private_image_id');
			// update data
			if(!$form->get_errors()) {

				$this->image->get_instance_by_id($data['cloud_private_image_id']);
				$assigned_to = $data['cloud_private_image_assign'];

				if ($this->cloudprivateimage->exists_by_image_id($data['cloud_private_image_id'])) {
					// remove
					if ($assigned_to === '-1') {
						// remove from table
						$this->cloudprivateimage->get_instance_by_image_id($data['cloud_private_image_id']);
						$this->cloudprivateimage->remove($this->cloudprivateimage->id);
					} else {
						// update
						$private_cloud_image_fields["co_cu_id"] = $assigned_to;
						$this->cloudprivateimage->update($this->cloudprivateimage->id, $private_cloud_image_fields);
					}
				} else {
					// new
					$private_cloud_image_fields["co_id"] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$private_cloud_image_fields["co_image_id"] = $data['cloud_private_image_id'];
					$private_cloud_image_fields["co_cu_id"] = $assigned_to;
					$private_cloud_image_fields["co_state"] = 1;
					$this->cloudprivateimage->add($private_cloud_image_fields);
				}
			    // success msg
			    $response->msg = sprintf($this->lang['cloud_private_image_updated'], $this->image->name);

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
		$cloud_private_image_id = $this->response->html->request()->get('cloud_private_image_id');
		if (strlen($cloud_private_image_id)) {
			$this->image->get_instance_by_id($cloud_private_image_id);
			// private image config existing
			if ($this->cloudprivateimage->exists_by_image_id($cloud_private_image_id)) {
				$this->cloudprivateimage->get_instance_by_image_id($cloud_private_image_id);
				if ($this->cloudprivateimage->cu_id > 0) {
					$this->cloud_user->get_instance_by_id($this->cloudprivateimage->cu_id);
					$assigned_to = $this->cloud_user->name;
					$assigned_to_default = $this->cloud_user->id;
				} else if ($this->cloudprivateimage->cu_id == 0) {
					// 0 == all
					$assigned_to_default = 0;
				} else if ($this->cloudprivateimage->cu_id < 0) {
					$assigned_to_default = -1;
				}
			} else {
				$assigned_to_default = -1;
			}
		}

		$response = $this->response;
		$form = $response->get_form($this->actions_name, "update");

		$cloud_private_image_assign_default_arr = array();
		$cloud_private_image_assign_arr = $this->cloud_user->get_list();

		foreach ($cloud_private_image_assign_arr as $key => $row) {
			$label[]  = strtolower($row['label']);
		}
		array_multisort($label, SORT_ASC, SORT_STRING, $cloud_private_image_assign_arr);
		$cloud_private_image_assign_arr[] = array( 'value' => '-1', 'label' => $this->lang['cloud_private_image_nobody']);
		$cloud_private_image_assign_arr[] = array( 'value' => '0', 'label' => $this->lang['cloud_private_image_everybody']);

		$d = array();
		$d['cloud_private_image_assign']['label']                        = ' ';
		$d['cloud_private_image_assign']['object']['type']               = 'htmlobject_select';
		$d['cloud_private_image_assign']['object']['attrib']['index']    = array('value', 'label');
		$d['cloud_private_image_assign']['object']['attrib']['id']       = 'cloud_private_image_assign';
		$d['cloud_private_image_assign']['object']['attrib']['name']     = 'cloud_private_image_assign';
		$d['cloud_private_image_assign']['object']['attrib']['options']  = $cloud_private_image_assign_arr;
		$d['cloud_private_image_assign']['object']['attrib']['selected'] = array($assigned_to_default);

		$form->add($d);
		$response->form = $form;
		return $response;
	}
}
?>
