<?php
/**
 * Cloud Send Mail
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_mail_send
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_mail';


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
		$response = $this->send();
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'send', $this->message_param, $response->msg));
		}
		$external_portal_name = $this->cloud_config->get_value(3);  // 3 is the external name;
		if (!strlen($external_portal_name)) {
			$openqrm_server = new openqrm_server();
			$openqrm_server_ip = $openqrm_server->get_ip_address();
			$external_portal_name = "http://".$openqrm_server_ip."/cloud-portal";
		}
		
		$template = $response->html->template($this->tpldir."/cloud-mail-send.tpl.php");
		$template->add($this->lang['cloud_mail_title'], 'title');
		$template->add($this->lang['cloud_mail_data'], 'cloud_mail_data');
		$template->add($external_portal_name, 'external_portal_name');
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Send Mail
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function send() {
		$response = $this->get_response();
		$form     = $response->form;
		if(!$form->get_errors()	&& $response->submit()) {
			$data = $form->get_request();
			if(!$form->get_errors()) {
			    $mailtype = "text";
			    $cc_admin_email = $this->cloud_config->get_value_by_key('cloud_admin_email');
			    $from_header = "From: ".$cc_admin_email."\r\n";
				$headers   = array();
				$headers[] = "MIME-Version: 1.0";
				$headers[] = "Content-type: text/plain; charset=utf-8";
				$headers[] = "Content-Transfer-Encoding: 8bit";
				$headers[] = "From: ".$cc_admin_email;
				$headers[] = "Reply-To: ".$cc_admin_email;
				$headers[] = "Subject: ".$data['cloud_mail_subject'];
				$headers[] = "X-Mailer: PHP/".phpversion();
			    
			    if ($data['cloud_mail_to'] == 0) {
				$cloud_user_id_arr = $this->cloud_user->get_all_ids();
				foreach ($cloud_user_id_arr as $cu) {
				    $this->cloud_user->get_instance_by_id($cu['cu_id']);
				    $full_body = "Dear ".$this->cloud_user->forename." ".$this->cloud_user->lastname.",\n\n".$data['cloud_mail_body']."\n";
				    $full_body = wordwrap($full_body, 70);
				    $res = mail($this->cloud_user->email, $data['cloud_mail_subject'], $full_body, implode("\r\n", $headers));
				    if ($res) {
					$response->msg .= sprintf($this->lang['cloud_mail_send_successful'], $this->cloud_user->forename." ".$this->cloud_user->lastname);
				    } else {
					$response->msg .= sprintf($this->lang['cloud_mail_send_error'], $this->cloud_user->forename." ".$this->cloud_user->lastname);
				    }
				    $response->msg .= "<br>";
				}
				
			    } else {
				$this->cloud_user->get_instance_by_id($data['cloud_mail_to']);
				$full_body = "Dear ".$this->cloud_user->forename." ".$this->cloud_user->lastname.",\n\n".$data['cloud_mail_body']."\n";
				$full_body = wordwrap($full_body, 70);
				$res = mail($this->cloud_user->email, $data['cloud_mail_subject'], $full_body, implode("\r\n", $headers));
				if ($res) {
				    $response->msg = sprintf($this->lang['cloud_mail_send_successful'], $this->cloud_user->forename." ".$this->cloud_user->lastname);
				} else {
				    $response->msg = sprintf($this->lang['cloud_mail_send_error'], $this->cloud_user->forename." ".$this->cloud_user->lastname);
				}
			    }
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
		$response = $this->response;
		$form = $response->get_form($this->actions_name, "send");

		$cloud_user_select = $this->cloud_user->get_list();
		$cloud_user_select = array_reverse($cloud_user_select);
		$cloud_user_select[] = array('value' => 0, 'label' => $this->lang['cloud_mail_all_users']);
		$cloud_user_select = array_reverse($cloud_user_select);
		
		$d = array();

		$d['cloud_mail_to']['label']                     = $this->lang['cloud_mail_to'];
		$d['cloud_mail_to']['required']                  = true;
		$d['cloud_mail_to']['object']['type']            = 'htmlobject_select';
		$d['cloud_mail_to']['object']['attrib']['type']  = 'text';
		$d['cloud_mail_to']['object']['attrib']['index'] = array('value', 'label');
		$d['cloud_mail_to']['object']['attrib']['id']    = "cloud_mail_to";
		$d['cloud_mail_to']['object']['attrib']['name']  = "cloud_mail_to";
		$d['cloud_mail_to']['object']['attrib']['options']    = $cloud_user_select;
		
		$d['cloud_mail_subject']['label']                         = $this->lang['cloud_mail_subject'];
		$d['cloud_mail_subject']['required']                      = true;
		$d['cloud_mail_subject']['object']['type']                = 'htmlobject_input';
		$d['cloud_mail_subject']['object']['attrib']['type']      = 'text';
		$d['cloud_mail_subject']['object']['attrib']['id']        = 'cloud_mail_subject';
		$d['cloud_mail_subject']['object']['attrib']['name']      = 'cloud_mail_subject';
		$d['cloud_mail_subject']['object']['attrib']['maxlength'] = 50;

		$d['cloud_mail_body']['label']                     = $this->lang['cloud_mail_body'];
		$d['cloud_mail_body']['required']                  = true;
		$d['cloud_mail_body']['object']['type']            = 'htmlobject_textarea';
		$d['cloud_mail_body']['object']['attrib']['type']  = 'text';
		$d['cloud_mail_body']['object']['attrib']['id']    = 'cloud_mail_body';
		$d['cloud_mail_body']['object']['attrib']['name']  = 'cloud_mail_body';

		$form->add($d);
		$response->form = $form;
		return $response;
	}
}












?>
