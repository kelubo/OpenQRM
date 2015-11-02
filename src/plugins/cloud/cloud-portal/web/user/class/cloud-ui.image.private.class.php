<?php
/**
 * Cloud Users Appliance Private Image
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_image_private
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
		$this->basedir = $this->openqrm->get('basedir');
		// include classes and prepare ojects
		$this->appliance = $this->openqrm->appliance();
		$this->image     = $this->openqrm->image();
		require_once($this->basedir."/plugins/cloud/web/class/cloudappliance.class.php");
		$this->cloudappliance = new cloudappliance();
		require_once($this->basedir."/plugins/cloud/web/class/cloudimage.class.php");
		$this->cloudimage = new cloudimage();
		require_once($this->basedir."/plugins/cloud/web/class/cloudiplc.class.php");
		$this->cloudiplc = new cloudiplc();
		require_once($this->basedir."/plugins/cloud/web/class/cloudrequest.class.php");
		$this->cloudrequest = new cloudrequest();

		$this->appliance_id = $this->response->html->request()->get('appliance_id');
		$this->response->add('appliance_id', $this->appliance_id);

	}

	//--------------------------------------------
	/**
	 * Action private
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {
		if($this->cloudconfig->get_value_by_key('cloud_enabled') === 'false') {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'appliances', $this->message_param, $this->lang['error_cloud_disabled'])
			);
		} else {
			if ($this->response->html->request()->get('appliance_id') === '') {
				$this->response->redirect($this->response->get_url($this->actions_name, 'home'));
			}
			$response = $this->appliance_private();
			if(isset($response->error)) {
				$_REQUEST[$this->message_param] = $response->error;
			}
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'appliances', $this->message_param, $response->msg));
			}
			$t = $this->response->html->template($this->tpldir."/cloud-ui.image-private.tpl.php");
			$t->add($response->form->get_elements());
			$t->add($response->html->thisfile, "thisfile");
			$t->add(sprintf($this->lang['label_private'], $this->appliance->name), 'label');
			$t->group_elements(array('param_' => 'form'));
			return $t;
		}
	}

	//--------------------------------------------
	/**
	 * Cloud Users Private Image
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function appliance_private() {

		$response = $this->get_response();
		$form = $response->form;

		if(! $form->get_errors() && $this->response->submit()) {
			//$image_name = $form->get_request('image_name').'-'.$this->clouduser->id;
			$cr_id = $this->cloudrequest->get_cr_for_appliance($this->appliance_id);
			$error = false;
			if(isset($cr_id)) {
				$this->cloudrequest->get_instance_by_id($cr_id);
				$this->appliance->get_instance_by_id($this->appliance_id);
				$cloud_appliance_name = $this->appliance->name;
				$this->image->get_instance_by_id($this->appliance->imageid);
				$this->cloudimage->get_instance_by_image_id($this->image->id);
				$cloud_image_current_disk_size = $this->cloudimage->disk_size;
				$this->cloudappliance->get_instance_by_id($this->cloudappliance->get_id_by_cr($this->cloudrequest->id));

				// generate image name
				$image_name = str_replace("cloud",  "private",  $this->image->name);
				$image_name = substr($image_name,0,11).$_SERVER['REQUEST_TIME'];

				// check image name is unique
				$check = $this->openqrm->image();
				$check->get_instance_by_name($image_name);
				if ($check->id !== '') {
					$response->error = sprintf($this->lang['error_image_name_in_use'], $image_name);
					$error = true;
				}
				// check appliance belongs to user
				if ($this->cloudrequest->cu_id != $this->clouduser->id) {
					$response->msg = sprintf($this->lang['error_access_denied'], $this->image->name);
					$error = true;
				}
				// check if no other command is currently running
				else if ($this->cloudappliance->cmd != 0) {
					$response->error = sprintf($this->lang['error_command_running'], $cloud_appliance_name);
					$error = true;
				}
				// check that state is active
				else if ($this->cloudappliance->state != 1) {
					$response->error = sprintf($this->lang['error_resource_not_active'], $cloud_appliance_name);
					$error = true;
				}

				if (!$error) {
					// put the size + clone name in the cloud_image
					$cloudi_request = array(
						'ci_disk_rsize' => $cloud_image_current_disk_size,
						'ci_clone_name' => $image_name,
					);
					$this->cloudimage->update($this->cloudimage->id, $cloudi_request);
					// create a new cloud-image private-life-cycle / using the cloudappliance id
					$ciplc_fields['cp_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$ciplc_fields['cp_appliance_id'] = $this->cloudappliance->id;
					$ciplc_fields['cp_cu_id'] = $this->clouduser->id;
					$ciplc_fields['cp_state'] = '1';
					$ciplc_fields['cp_start_private'] = $_SERVER['REQUEST_TIME'];
					$this->cloudiplc->add($ciplc_fields);
					$response->msg = sprintf($this->lang['msg_private_image'], $image_name, $cloud_appliance_name);
				}
			}
		}
		else if($form->get_errors()) {
			$response->error = implode('<br>', $form->get_errors());
		}
		return $response;
	}


	function get_response() {
		$ca_id = $this->response->html->request()->get('appliance_id');
		$this->appliance->get_instance_by_id($ca_id);
		$cloud_appliance_name = $this->appliance->name;

		$response = $this->response;
		$form     = $response->get_form($this->actions_name, 'image_private');

		$d['cloud_appliance_private']['label']                       =  $this->appliance->name;
		$d['cloud_appliance_private']['object']['type']              =  'htmlobject_input';
		$d['cloud_appliance_private']['object']['attrib']['type']    =  'checkbox';
		$d['cloud_appliance_private']['object']['attrib']['name']    =  'cloud_appliance_private';
		$d['cloud_appliance_private']['object']['attrib']['id']      =  'cloud_appliance_private';
		$d['cloud_appliance_private']['object']['attrib']['checked'] =  true;

/*		
		$d['cloud_appliance_private']['label']                         = $this->lang['name'];
		$d['cloud_appliance_private']['required']                      = true;
		$d['cloud_appliance_private']['validate']['regex']             = $this->openqrm->get('regex', 'hostname');
		$d['cloud_appliance_private']['validate']['errormsg']          = 'Name must be '.$this->openqrm->get('regex', 'hostname').' only';
		$d['cloud_appliance_private']['object']['type']                = 'htmlobject_input';
		$d['cloud_appliance_private']['object']['attrib']['type']      = 'text';
		$d['cloud_appliance_private']['object']['attrib']['name']      = 'image_name';
		$d['cloud_appliance_private']['object']['attrib']['id']        = 'image_name';
		$d['cloud_appliance_private']['object']['attrib']['maxlength'] = 15;
*/
		
		$form->add($d);
		$form->display_errors = false;
		$response->form = $form;
		return $response;
	}

}
?>
