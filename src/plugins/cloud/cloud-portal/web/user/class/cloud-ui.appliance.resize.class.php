<?php
/**
 * Cloud Users Appliance Resize
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_appliance_resize
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
	function __construct($response) {
		$this->response = $response;
		$this->rootdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
		// include classes and prepare ojects
		require_once $this->rootdir."/plugins/cloud/class/cloudappliance.class.php";
		$this->cloudappliance	= new cloudappliance();
		require_once $this->rootdir."/plugins/cloud/class/cloudimage.class.php";
		$this->cloudimage	= new cloudimage();
	    require_once $this->rootdir."/class/appliance.class.php";
	    $this->appliance	= new appliance();
		$this->image		= new image();
		require_once $this->rootdir."/plugins/cloud/class/clouduserslimits.class.php";
		$this->clouduserlimits	= new clouduserlimits();
		require_once $this->rootdir."/plugins/cloud/class/cloudirlc.class.php";
		$this->cloudirlc	= new cloudirlc();
		require_once $this->rootdir."/plugins/cloud/class/cloudrequest.class.php";
		$this->cloudrequest	= new cloudrequest();

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
			$this->response->redirect($this->response->get_url($this->actions_name, ''));
		}
		$response = $this->appliance_resize();
//		if(isset($response->error)) {
//			$_REQUEST[$this->message_param] = $response->error;
//		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'appliances', $this->message_param, $response->msg));
		}

		// get current disk size + limits
		$this->appliance->get_instance_by_id($this->cloudappliance->appliance_id);
		$this->image->get_instance_by_id($this->appliance->imageid);
		$this->cloudimage->get_instance_by_image_id($this->image->id);
		$cloud_image_disk_size = $this->cloudimage->disk_size;
		// global limit
		$cloud_max_disk_size = $this->cloudconfig->get_value_by_key('max_disk_size');
		// user limit
		$this->clouduserlimits->get_instance_by_cu_id($this->clouduser->id);
		$cloud_user_disk_size = $this->clouduserlimits->disk_limit;
		// calculate the max size for this disk
		$cloud_uses_max_disk_size = $cloud_image_disk_size;
		if ($cloud_user_disk_size == 0) {
			$cloud_uses_max_disk_size = $cloud_max_disk_size;
		} else {
			if ($cloud_user_disk_size >= $cloud_max_disk_size) {
				$cloud_uses_max_disk_size = $cloud_user_disk_size;
			} else {
				$cloud_uses_max_disk_size = $cloud_user_disk_size;
			}
		}

		$template = $this->response->html->template($this->tpldir."/cloud-ui.appliance-resize.tpl.php");
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->add($cloud_image_disk_size, "cloud_image_disk_size");
		$template->add($cloud_uses_max_disk_size, "cloud_uses_max_disk_size");
		$template->add($this->lang['cloud_ui_appliance_resize'], 'cloud_ui_appliance_resize');
		$template->group_elements(array('param_' => 'form'));

		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Resize
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function appliance_resize() {
		$this->ca_id = $this->response->html->request()->get($this->identifier_name);
		$this->response->add($this->identifier_name, $this->ca_id);
		$this->cloudappliance->get_instance_by_id($this->ca_id);

		$response = $this->get_response();
		$form = $response->form;

		if($this->response->submit()) {
			$cloud_appliance_resize = $form->get_request('cloud_appliance_resize');
			if(isset($cloud_appliance_resize)) {
				$this->appliance->get_instance_by_id($this->cloudappliance->appliance_id);
				$cloud_appliance_name = $this->appliance->name;
				$this->cloudrequest->get_instance_by_id($this->cloudappliance->cr_id);
				if ($this->cloudrequest->cu_id != $this->clouduser->id) {
					// not request of the authuser
					exit(1);
				}
				// check resize
				$error = false;
				$this->image->get_instance_by_id($this->appliance->imageid);
				$this->cloudimage->get_instance_by_image_id($this->image->id);
				$cloud_image_current_disk_size = $this->cloudimage->disk_size;
				if ($cloud_image_current_disk_size == $cloud_appliance_resize) {
					$response->error = sprintf($this->lang['cloud_ui_appliance_resize_size_equal'], $cloud_appliance_name);
					$error = true;
				}
				if ($cloud_image_current_disk_size > $cloud_appliance_resize) {
					$response->error = sprintf($this->lang['cloud_ui_appliance_resize_size_smaller'], $cloud_appliance_name);
					$error = true;
				}
				// check if no other command is currently running
				if ($this->cloudappliance->cmd != 0) {
					$response->error = sprintf($this->lang['cloud_ui_appliance_command_running'], $cloud_appliance_name);
					$error = true;
				}
				// check that state is active
				if ($this->cloudappliance->state != 1) {
					$response->error = $this->lang['cloud_ui_appliance_command_needs_active'];
					$error = true;
				}

				if (!$error) {
					$additional_disk_space = $cloud_appliance_resize - $cloud_image_current_disk_size;
					// put the new size in the cloud_image
					$cloudi_request = array(
						'ci_disk_rsize' => "$cloud_appliance_resize",
					);
					$this->cloudimage->update($this->cloudimage->id, $cloudi_request);
					// create a new cloud-image resize-life-cycle / using cloudappliance id
					$cirlc_fields['cd_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$cirlc_fields['cd_appliance_id'] = $this->cloudappliance->id;
					$cirlc_fields['cd_state'] = '1';
					$this->cloudirlc->add($cirlc_fields);
					$response->msg = sprintf($this->lang['cloud_ui_appliance_resized'], $cloud_appliance_name);
				}

			}
		}
		return $response;
	}


	function get_response() {
		$ca_id = $this->response->html->request()->get($this->identifier_name);
		$this->appliance->get_instance_by_id($this->cloudappliance->appliance_id);
		$cloud_appliance_name = $this->appliance->name;
		$this->appliance->get_instance_by_id($this->cloudappliance->appliance_id);
		$this->image->get_instance_by_id($this->appliance->imageid);
		$this->cloudimage->get_instance_by_image_id($this->image->id);

		$response =$this->response;
		$form     = $response->get_form($this->actions_name, 'appliance_resize');
		
		$d['cloud_appliance_resize']['label']                       = $this->appliance->name;
		$d['cloud_appliance_resize']['object']['type']              = 'htmlobject_input';
		$d['cloud_appliance_resize']['object']['attrib']['type']    = 'text';
		$d['cloud_appliance_resize']['object']['attrib']['name']    = 'cloud_appliance_resize';
		$d['cloud_appliance_resize']['object']['attrib']['id']      = 'cloud_appliance_resize';
		$d['cloud_appliance_resize']['object']['attrib']['value']   = $this->cloudimage->disk_size;

		$form->add($d);
		$response->form = $form;
		return $response;
	}



}

?>







