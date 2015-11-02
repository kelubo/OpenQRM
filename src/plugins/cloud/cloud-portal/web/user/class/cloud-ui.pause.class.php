<?php
/**
 * Cloud Users Appliance Pause
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_pause
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud-ui-pause';



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
		if ((file_exists("/etc/init.d/openqrm")) && (is_link("/etc/init.d/openqrm"))) {
			$this->basedir = dirname(dirname(dirname(readlink("/etc/init.d/openqrm"))));
		} else {
			$this->basedir = "/usr/share/openqrm";
		}
		// include classes and prepare ojects
		require_once $this->rootdir."/plugins/cloud/class/cloudappliance.class.php";
		$this->cloudappliance = new cloudappliance();
		require_once $this->rootdir."/plugins/cloud/class/cloudmailer.class.php";
		$this->cloudmailer = new cloudmailer();
		require_once $this->rootdir."/plugins/cloud/class/cloudconfig.class.php";
		$this->cloudconfig = new cloudconfig();
		require_once $this->rootdir."/plugins/cloud/class/cloudrequest.class.php";
		$this->cloudrequest	= new cloudrequest();
	}

	//--------------------------------------------
	/**
	 * Action
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
			if ($this->response->html->request()->get($this->identifier_name) === '') {
				$this->response->redirect($this->response->get_url($this->actions_name, 'appliances'));
			}
			$response = $this->pause();
			if(isset($response->error)) {
				$_REQUEST[$this->message_param] = $response->error;
			}
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'appliances', $this->message_param, $response->msg));
			}
			$template = $this->response->html->template($this->tpldir."/cloud-ui.pause.tpl.php");
			$template->add($response->form->get_elements());
			$template->add($response->html->thisfile, "thisfile");
			$template->add($this->lang['label_pause'], 'label');
			$template->group_elements(array('param_' => 'form'));
			return $template;
		}
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Pause
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function pause() {
		$response = $this->get_response();
		$form = $response->form;

		if($this->response->submit()) {
			$request = $form->get_request($this->identifier_name);

			if(isset($request) && is_array($request)) {
				$errors  = array();
				$message = array();
				$appliance = $this->openqrm->appliance();
				foreach($request as $key => $cloudappliance_id) {
					$this->cloudappliance->get_instance_by_id($cloudappliance_id);
					$this->cloudrequest->get_instance_by_id($this->cloudappliance->cr_id);
					$appliance->get_instance_by_id($this->cloudappliance->appliance_id);
					// check appliance belongs to user
					if ($this->cloudrequest->cu_id != $this->clouduser->id) {
						$message[] = sprintf($this->lang['error_access_denied'], $cloudappliance_id);
						continue;
					}
					// check if no other command is currently running
					if ($this->cloudappliance->cmd != 0) {
						$errors[] = sprintf($this->lang['error_command_running'], $cloudappliance_id);
						continue;
					}
					// check that state is active
					if ($this->cloudappliance->state == 1) {
						$this->cloudappliance->set_cmd($this->cloudappliance->id, "stop");
						$this->cloudappliance->set_state($this->cloudappliance->id, "paused");

						// send mail to cloud-admin
						$cloud_admin_email = $this->cloudconfig->get_value_by_key('cloud_admin_email');
						$external_portal_name = $this->cloudconfig->get_value_by_key('external_portal_url');
						if (!strlen($external_portal_name)) {
							$external_portal_name = 'http://'.$_SERVER['SERVER_NAME'].'/cloud-portal';
						}
						$this->cloudmailer->to = $cloud_admin_email;
						$this->cloudmailer->from = $cloud_admin_email;
						$this->cloudmailer->subject = sprintf($this->lang['mailer_pause_subject'], $cloudappliance_id);
						$this->cloudmailer->template = $this->basedir."/plugins/cloud/etc/mail/paused_cloud_appliance.mail.tmpl";
						$arr = array('@@USER@@' => $this->clouduser->name, '@@CLOUD_APPLIANCE_ID@@' => $cloudappliance_id, '@@CLOUDADMIN@@' => $cloud_admin_email);
						$this->cloudmailer->var_array = $arr;
						$this->cloudmailer->send();
						$message[] = sprintf($this->lang['msg_paused_appliance'],$appliance->name);
					} else {
						$errors[] = sprintf($this->lang['error_pause_failed'],$appliance->name);
						continue;
					}
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

	//--------------------------------------------
	/**
	 * Response
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {
		$topause  = $this->response->html->request()->get($this->identifier_name);
		$response = $this->response;
		$form     = $response->get_form($this->actions_name, 'pause');
		$d        = array();
		if( $topause !== '' ) {
			$i = 0;
			$appliance = $this->openqrm->appliance();
			foreach($topause as $system_id) {
				$this->cloudappliance->get_instance_by_id($system_id);
				if($this->cloudappliance->appliance_id != '') {
					$appliance->get_instance_by_id($this->cloudappliance->appliance_id);
					$d['param_f'.$i]['label']                       = $appliance->name;
					$d['param_f'.$i]['object']['type']              = 'htmlobject_input';
					$d['param_f'.$i]['object']['attrib']['type']    = 'checkbox';
					$d['param_f'.$i]['object']['attrib']['name']    = $this->identifier_name.'['.$i.']';
					$d['param_f'.$i]['object']['attrib']['id']      = $this->identifier_name.'_'.$i;
					$d['param_f'.$i]['object']['attrib']['value']   = $system_id;
					$d['param_f'.$i]['object']['attrib']['checked'] = true;
					$i++;
				}
			}
		}
		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
