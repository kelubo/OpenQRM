<?php
/**
 * Cloud Request Approve
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_request_approve
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
		$response = $this->approve();
//		if(isset($response->error)) {
//			$_REQUEST[$this->message_param] = $response->error;
//		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg));
		}

		$template = $this->response->html->template($this->tpldir."/cloud-request-approve.tpl.php");
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->add($this->lang['cloud_request_confirm_approve'], 'confirm_approve');
		$template->group_elements(array('param_' => 'form'));

		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Request Approve
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function approve() {
		$response = $this->get_response();
		$form = $response->form;
		$cc_admin_email = $this->cloud_config->get_value_by_key('cloud_admin_email');

		if(!$form->get_errors() && $this->response->submit()) {
			$request = $form->get_request($this->identifier_name);

			if(isset($request) && is_array($request)) {
				$errors  = array();
				$message = array();
				foreach($request as $key => $cr_id) {
					$this->cloud_request->get_instance_by_id($cr_id);
					$this->cloud_user->get_instance_by_id($this->cloud_request->cu_id);
					//delete here;
					$approve_cr=false;
					$cr_status="unknown";
					switch ($this->cloud_request->status) {
						case 1:
							$cr_status="new";
							$approve_cr=true;
							break;
						case 2:
							$cr_status="approve";
							break;
						case 3:
							$cr_status="active";
							break;
						case 4:
							// deny
							$cr_status="deny";
							$approve_cr=false;
							break;
						case 6:
							// done
							$cr_status="done";
							$approve_cr=false;
							break;
						case 7:
							// no-res
							$cr_status="no-res";
							$approve_cr=false;
							break;
					}
					// do we remove ?
					if ($approve_cr) {
						// mail user before removing
						$this->cloud_mailer->to = $this->cloud_user->email;
						$this->cloud_mailer->from = $cc_admin_email;
						$this->cloud_mailer->subject = "openQRM Cloud: Your request ".$cr_id." has been approved";
						$this->cloud_mailer->template = $this->rootdir."/plugins/cloud/etc/mail/approve_cloud_request.mail.tmpl";
						$arr = array('@@ID@@' => $cr_id, '@@FORENAME@@' => $this->cloud_user->forename, '@@LASTNAME@@' => $this->cloud_user->lastname, '@@START@@' => $this->cloud_request->start, '@@STOP@@' => $this->cloud_request->stop, '@@CLOUDADMIN@@' => $cc_admin_email);
						$this->cloud_mailer->var_array = $arr;
						$this->cloud_mailer->send();
						// approve
						$this->cloud_request->setstatus($cr_id, 'approve');
						$message[] = $this->lang['cloud_request_approved']." - ".$cr_id;
					} else {
						$message[] = $this->lang['cloud_request_not_approving']." - ".$cr_id." in status ".$cr_status;
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


	function get_response() {
		$todelete = $this->response->html->request()->get($this->identifier_name);
		$response =$this->response;
		$form     = $response->get_form($this->actions_name, 'approve');
		$d        = array();
		if( $todelete !== '' ) {
			$i = 0;
			foreach($todelete as $cz_ug_id) {
				$this->cloud_request->get_instance_by_id($cz_ug_id);
				$d['param_f'.$i]['label']                       = $cz_ug_id;
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


