<?php
/**
 * Cloud Request Pause
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_request_pause
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud_request';



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
		require_once $this->rootdir."/plugins/cloud/web/class/clouduser.class.php";
		$this->cloud_user = new clouduser();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudrequest.class.php";
		$this->cloud_request = new cloudrequest();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudmailer.class.php";
		$this->cloud_mailer = new cloudmailer();
		if(class_exists('cloudappliance') === false) {
			require_once $this->rootdir."/plugins/cloud/web/class/cloudappliance.class.php";
		}
		$this->cloudappliance = new cloudappliance();

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
		$response = $this->pause();
//		if(isset($response->error)) {
//			$_REQUEST[$this->message_param] = $response->error;
//		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg));
		}

		$template = $this->response->html->template($this->tpldir."/cloud-request-pause.tpl.php");
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->add($this->lang['cloud_request_confirm_pause'], 'confirm_pause');
		$template->group_elements(array('param_' => 'form'));

		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Request Pause
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function pause() {
		$response = $this->get_response();
		$form = $response->form;
		$cc_admin_email = $this->cloud_config->get_value_by_key('cloud_admin_email');

		if(!$form->get_errors() && $this->response->submit()) {
			$cr_id = $form->get_request($this->identifier_name);

			if(isset($cr_id)) {
				$errors  = array();
				$message = array();
				$this->cloud_request->get_instance_by_id($cr_id);
				$this->cloud_user->get_instance_by_id($this->cloud_request->cu_id);
				$pause_cr=false;
				$cr_status="unknown";
				switch ($this->cloud_request->status) {
					case 3:
						$cr_status="active";
						$pause_cr=true;
						break;
					case 1:
						$cr_status="new";
						break;
					case 2:
						$cr_status="approve";
						break;
					case 4:
						// deny
						$cr_status="deny";
						break;
					case 6:
						// done
						$cr_status="done";
						break;
					case 7:
						// no-res
						$cr_status="no-res";
						break;
				}

				// get the cloudappliance ojbect
				if ($pause_cr) {
					if (($this->cloud_request->appliance_id != '') && ($this->cloud_request->appliance_id != 0)) {
						$this->cloudappliance->get_instance_by_appliance_id($this->cloud_request->appliance_id);
						if (($this->cloudappliance->state == 1) && ($this->cloudappliance->cmd == 0)) {
							$pause_cr=true;
						} else {
							$pause_cr=false;
						}
					}
				}
				// do we pause ?
				if ($pause_cr) {
					// mail user before pause
					$this->cloud_mailer->to = $this->cloud_user->email;
					$this->cloud_mailer->from = $cc_admin_email;
					$this->cloud_mailer->subject = "openQRM Cloud: Your request ".$cr_id." has been paused";
					$this->cloud_mailer->template = $this->rootdir."/plugins/cloud/etc/mail/paused_cloud_appliance.mail.tmpl";
					$arr = array('@@USER@@' => $this->clouduser->name, '@@CLOUD_APPLIANCE_ID@@' => $this->cloud_request->appliance_hostname, '@@CLOUDADMIN@@' => $cc_admin_email);
					$this->cloud_mailer->var_array = $arr;
					$this->cloud_mailer->send();
					// set pause action
					$this->cloudappliance->set_cmd($this->cloudappliance->id, "stop");
					$this->cloudappliance->set_state($this->cloudappliance->id, "paused");
					
					$message[] = $this->lang['cloud_request_paused']." - ".$this->cloud_request->appliance_id;
				} else {
					$message[] = $this->lang['cloud_request_not_pausing']." - ".$cr_id." in status ".$cr_status;
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
		$cr_id = $this->response->html->request()->get($this->identifier_name);
		$response =$this->response;
		$form     = $response->get_form($this->actions_name, 'pause');
		$d        = array();
		if( $cr_id !== '' ) {
			$this->cloud_request->get_instance_by_id($cr_id);
			$d['param_f0']['label']                       = $this->lang['cloud_request']." ".$cr_id."<br>Server ID ".$this->cloud_request->appliance_id;
			$d['param_f0']['object']['type']              = 'htmlobject_input';
			$d['param_f0']['object']['attrib']['type']    = 'checkbox';
			$d['param_f0']['object']['attrib']['name']    = $this->identifier_name;
			$d['param_f0']['object']['attrib']['id']      = $this->identifier_name;
			$d['param_f0']['object']['attrib']['value']   = $cr_id;
			$d['param_f0']['object']['attrib']['checked'] = true;
		}
		$form->add($d);
		$response->form = $form;
		return $response;
	}



}

?>


