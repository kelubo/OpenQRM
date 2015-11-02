<?php
/**
 * Deprovision Cloud Users Request
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_deprovision
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
		require_once $this->rootdir."/plugins/cloud/class/cloudappliance.class.php";
		$this->cloudappliance = new cloudappliance();
		require_once $this->rootdir."/plugins/cloud/class/cloudrequest.class.php";
		$this->cloudrequest	= new cloudrequest();
		require_once $this->rootdir."/plugins/cloud/class/cloudmailer.class.php";
		$this->cloudmailer	= new cloudmailer();

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
		if($this->cloudconfig->get_value_by_key('cloud_enabled') === 'false') {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'appliances', $this->message_param, $this->lang['error_cloud_disabled'])
			);
		} else {
			if ($this->response->html->request()->get($this->identifier_name) === '') {
				$this->response->redirect($this->response->get_url($this->actions_name, ''));
			}
			$response = $this->form();
			if(isset($response->error)) {
				$_REQUEST[$this->message_param] = $response->error;
			}
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'appliances', $this->message_param, $response->msg));
			}

			$template = $this->response->html->template($this->tpldir."/cloud-ui.deprovision.tpl.php");
			$template->add($response->form->get_elements());
			$template->add($response->html->thisfile, "thisfile");
			$template->add($this->lang['label_deprovision'], 'label');
			$template->group_elements(array('param_' => 'form'));

			return $template;
		}
	}

	//--------------------------------------------
	/**
	 * Deprovision Cloud Users Request
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function form() {
		$response = $this->get_response();
		$form = $response->form;

		if($this->response->submit()) {
			$request = $form->get_request($this->identifier_name);

			if(isset($request) && is_array($request)) {
				$errors  = array();
				$message = array();
				foreach($request as $key => $cz_id) {
					$this->cloudappliance->get_instance_by_id($cz_id);
					$this->cloudrequest->get_instance_by_id($this->cloudappliance->cr_id);
					// check appliance belongs to user
					if ($this->cloudrequest->cu_id != $this->clouduser->id) {
						$message[] = sprintf($this->lang['error_access_denied'], $cz_id);
						continue;
					}
					// only allow to deprovision if cr is in state active or no-res
					if (($this->cloudrequest->status != 3) && ($this->cloudrequest->status != 7)) {
						$errors[] = sprintf($this->lang['error_command_running'],$this->cloudrequest->appliance_hostname);
					}
					if(count($errors) === 0) {
						// mail user before deprovisioning
						$start = date("d-m-Y H-i", $this->cloudrequest->start);
						$now = date("d-m-Y H-i", $_SERVER['REQUEST_TIME']);
						// get admin email
						$cc_admin_email = $this->cloudconfig->get_value_by_key('cloud_admin_email');
						// send mail to user
						$this->cloudmailer->to = $this->clouduser->email;
						$this->cloudmailer->from = $cc_admin_email;
						$this->cloudmailer->subject = sprintf($this->lang['mailer_deprovision_subject'], $cz_id);
						$this->cloudmailer->template = $this->basedir."/plugins/cloud/etc/mail/deprovision_cloud_request.mail.tmpl";
						$arr = array('@@ID@@' => $cz_id, '@@FORENAME@@' => $this->clouduser->forename, '@@LASTNAME@@' => $this->clouduser->lastname, '@@START@@' => $start, '@@STOP@@' => $now, '@@CLOUDADMIN@@' => $cc_admin_email);
						$this->cloudmailer->var_array = $arr;
						$this->cloudmailer->send();
						// send mail to cloud-admin
						$this->cloudmailer->to = $cc_admin_email;
						$this->cloudmailer->from = $cc_admin_email;
						$this->cloudmailer->subject = "openQRM Cloud: Your request ".$cz_id." is going to be deprovisioned now !";
						$this->cloudmailer->template = $this->basedir."/plugins/cloud/etc/mail/deprovision_cloud_request.mail.tmpl";
						$aarr = array('@@ID@@' => $cz_id, '@@FORENAME@@' => "", '@@LASTNAME@@' => "CloudAdmin", '@@START@@' => $start, '@@STOP@@' => $now, '@@CLOUDADMIN@@' => $cc_admin_email);
						$this->cloudmailer->var_array = $aarr;
						$this->cloudmailer->send();
						// set cr status
						$this->cloudrequest->setstatus($this->cloudappliance->cr_id, 'deprovision');
						$message[] = sprintf($this->lang['msg_deprovisioned_appliance'],$this->cloudrequest->appliance_hostname);
					}
				}

				if(count($errors) === 0) {
					$response->msg = join('<br>', $message);
				} else {
					$response->error  = join('<br>', $message);
					$response->error .= join('<br>', $errors);
				}
			}
		}
		return $response;
	}


	function get_response() {
		$this->response->add($this->identifier_name.'[]','');
		$todelete = $this->response->html->request()->get($this->identifier_name);
		$response =$this->response;
		$form     = $response->get_form($this->actions_name, 'deprovision');
		$d        = array();
		if( $todelete !== '' ) {
			$i = 0;
			$appliance = $this->openqrm->appliance();
			foreach($todelete as $id) {
				$this->cloudappliance->get_instance_by_id($id);
				if($this->cloudappliance->appliance_id != '') {
					$this->cloudrequest->get_instance_by_id($this->cloudappliance->cr_id);
					$d['param_f'.$i]['label']                       = $this->cloudrequest->appliance_hostname;
					$d['param_f'.$i]['object']['type']              = 'htmlobject_input';
					$d['param_f'.$i]['object']['attrib']['type']    = 'checkbox';
					$d['param_f'.$i]['object']['attrib']['name']    = $this->identifier_name.'['.$i.']';
					$d['param_f'.$i]['object']['attrib']['id']      = $this->identifier_name.'_'.$i;
					$d['param_f'.$i]['object']['attrib']['value']   = $id;
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
