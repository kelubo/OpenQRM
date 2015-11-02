<?php
/**
 * account registered Cloud User
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_register_account
{
var $tpldir;
var $lang;
var $actions_name = 'cloud-register-account';

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $path path to dir
	 * @param htmlobject_response $response
	 * @param file $file
	 */
	//--------------------------------------------
	function __construct($response) {
		$this->response = $response;
		if ((file_exists("/etc/init.d/openqrm")) && (is_link("/etc/init.d/openqrm"))) {
			$this->basedir = dirname(dirname(dirname(readlink("/etc/init.d/openqrm"))));
		} else {
			$this->basedir = "/usr/share/openqrm";
		}
		$this->rootdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base';
		require_once $this->rootdir."/plugins/cloud/class/clouduser.class.php";
		$this->clouduser = new clouduser();
		require_once $this->rootdir."/plugins/cloud/class/cloudusergroup.class.php";
		$this->cloudusergroup = new cloudusergroup();
		require_once $this->rootdir."/plugins/cloud/class/cloudmailer.class.php";
		$this->cloudmailer = new cloudmailer();
		require_once $this->rootdir."/plugins/cloud/class/cloudconfig.class.php";
		$this->cloudconfig = new cloudconfig();
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
		if($this->response->html->request()->get('nooutput') === 'true') {
			$div = $this->response->html->div();
			$div->add('&#160;');
			return $div;
		} else {
			$response = $this->account();
			if(isset($response->error)) {
				$_REQUEST[$this->message_param] = $response->error;
			}
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'home', $this->message_param, $response->msg).'&nooutput=true');
			}
			$template = $response->html->template($this->tpldir."/cloud-register-account.tpl.php");
			$template->add($response->form->get_elements());
			$template->add($response->html->thisfile, "thisfile");
			$template->add($this->lang['label'], "label");
			$template->group_elements(array('param_' => 'form'));
			return $template;
		}
	}

	//--------------------------------------------
	/**
	 * account registered Cloud User
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function account() {
		$response = $this->get_response("account");
		$form     = $response->form;

		if(!$form->get_errors()	&& $response->submit()) {
			$data = $form->get_request();
			// name already in use ?
			if(
				(isset($data['cu_password']) && !isset($data['cu_password_repeat'])) ||
				(isset($data['cu_password']) && $data['cu_password'] !== $data['cu_password_repeat'])
			) {
				$form->set_error('cu_password_repeat',
					sprintf($this->lang['error_no_match'],
					$this->lang['user_password_repeat'],
					$this->lang['user_password'])
				);
			}
			$this->clouduser->get_instance_by_name($data['cu_name']);
			if($this->clouduser->id > 0) {
				$form->set_error("cu_name", $this->lang['error_name_in_use']);
			}
			if($this->cloudconfig->get_value_by_key('public_register_enabled') != 'true') {
					$form->set_error("cu_name", "Public registration is disabled!");
			}
			if(!$form->get_errors()) {
				// create token
				$data['cu_token'] = md5(uniqid(rand(), true));
				// disabed for now
				$data['cu_status'] = 0;
				$data['cu_lang'] = 'en';
				// default user group
				$this->cloudusergroup->get_instance_by_name("Default");
				$data['cu_cg_id'] = $this->cloudusergroup->id;
				$data['cu_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
				// check how many ccunits to give for a new user
				$data['cu_ccunits'] = $this->cloudconfig->get_value_by_key('auto_give_ccus');
				$this->clouduser->add($data);
				$this->clouduser->get_instance_by_id($data['cu_id']);
				// send mail to user
				$cloud_admin_email = $this->cloudconfig->get_value_by_key('cloud_admin_email');
				$external_portal_name = $this->cloudconfig->get_value_by_key('external_portal_url');
				if (!strlen($external_portal_name)) {
					$external_portal_name = 'http://'.$_SERVER['SERVER_NAME'].'/cloud-portal';
				}
				$this->cloudmailer->to = $this->clouduser->email;
				$this->cloudmailer->from = $cloud_admin_email;
				$this->cloudmailer->subject = $this->lang['mailer_subject'];
				$this->cloudmailer->template = $this->basedir."/plugins/cloud/etc/mail/activate_new_cloud_user.mail.tmpl";
				$arr = array('@@USER@@' => $this->clouduser->name, '@@ID@@' => $this->clouduser->id, '@@TOKEN@@' => $this->clouduser->token, '@@EXTERNALPORTALNAME@@' => $external_portal_name, '@@FORENAME@@' => $this->clouduser->forename, '@@LASTNAME@@' => $this->clouduser->lastname, '@@CLOUDADMIN@@' => $cloud_admin_email);
				$this->cloudmailer->var_array = $arr;
				$this->cloudmailer->send();
				// success msg
				$response->msg = $this->lang['msg_register_successful'];
			} else {
				$response->error = implode('<br>', $form->get_errors());
			}
		} else {
			if($form->get_errors()) {
				$response->error = implode('<br>', $form->get_errors());
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
		$form = $response->get_form($this->actions_name, "account");

		$d = array();

		$d['cu_name']['label']                         = $this->lang['user_name'];
		$d['cu_name']['required']                      = true;
		$d['cu_name']['validate']['regex']             = '~^[a-z0-9]+$~i';
		$d['cu_name']['validate']['errormsg']          = sprintf($this->lang['error_name'], '[a-z0-9]');
		$d['cu_name']['object']['type']                = 'htmlobject_input';
		$d['cu_name']['object']['attrib']['type']      = 'text';
		$d['cu_name']['object']['attrib']['id']        = 'cu_name';
		$d['cu_name']['object']['attrib']['name']      = 'cu_name';
		$d['cu_name']['object']['attrib']['maxlength'] = 50;

		$d['cu_email']['label']                         = $this->lang['user_email'];
		$d['cu_email']['required']                      = true;
		$d['cu_email']['object']['type']                = 'htmlobject_input';
		$d['cu_email']['object']['attrib']['type']      = 'text';
		$d['cu_email']['object']['attrib']['id']        = 'cu_email';
		$d['cu_email']['object']['attrib']['name']      = 'cu_email';
		$d['cu_email']['object']['attrib']['maxlength'] = 50;

		$d['cu_forename']['label']                         = $this->lang['user_forename'];
		$d['cu_forename']['required']                      = true;
		$d['cu_forename']['object']['type']                = 'htmlobject_input';
		$d['cu_forename']['object']['attrib']['type']      = 'text';
		$d['cu_forename']['object']['attrib']['id']        = 'cu_forename';
		$d['cu_forename']['object']['attrib']['name']      = 'cu_forename';
		$d['cu_forename']['object']['attrib']['maxlength'] = 50;

		$d['cu_lastname']['label']                         = $this->lang['user_lastname'];
		$d['cu_lastname']['required']                      = true;
		$d['cu_lastname']['object']['type']                = 'htmlobject_input';
		$d['cu_lastname']['object']['attrib']['type']      = 'text';
		$d['cu_lastname']['object']['attrib']['id']        = 'cu_lastname';
		$d['cu_lastname']['object']['attrib']['name']      = 'cu_lastname';
		$d['cu_lastname']['object']['attrib']['maxlength'] = 50;

		$d['cu_street']['label']                         = $this->lang['user_street'];
		$d['cu_street']['required']                      = true;
		$d['cu_street']['object']['type']                = 'htmlobject_input';
		$d['cu_street']['object']['attrib']['type']      = 'text';
		$d['cu_street']['object']['attrib']['id']        = 'cu_street';
		$d['cu_street']['object']['attrib']['name']      = 'cu_street';
		$d['cu_street']['object']['attrib']['maxlength'] = 100;

		$d['cu_city']['label']                         = $this->lang['user_city'];
		$d['cu_city']['required']                      = true;
		$d['cu_city']['object']['type']                = 'htmlobject_input';
		$d['cu_city']['object']['attrib']['type']      = 'text';
		$d['cu_city']['object']['attrib']['id']        = 'cu_city';
		$d['cu_city']['object']['attrib']['name']      = 'cu_city';
		$d['cu_city']['object']['attrib']['maxlength'] = 100;

		$d['cu_country']['label']                         = $this->lang['user_country'];
		$d['cu_country']['required']                      = true;
		$d['cu_country']['object']['type']                = 'htmlobject_input';
		$d['cu_country']['object']['attrib']['type']      = 'text';
		$d['cu_country']['object']['attrib']['id']        = 'cu_country';
		$d['cu_country']['object']['attrib']['name']      = 'cu_country';
		$d['cu_country']['object']['attrib']['maxlength'] = 100;

		$d['cu_phone']['label']                         = $this->lang['user_phone'];
		$d['cu_phone']['required']                      = true;
		$d['cu_phone']['object']['type']                = 'htmlobject_input';
		$d['cu_phone']['object']['attrib']['type']      = 'text';
		$d['cu_phone']['object']['attrib']['id']        = 'cu_phone';
		$d['cu_phone']['object']['attrib']['name']      = 'cu_phone';
		$d['cu_phone']['object']['attrib']['maxlength'] = 100;

		if (file_exists($this->rootdir."/plugins/ldap/.running")) {
			// central user management, do now show to update the users password
			$d['cu_password'] = $this->lang['cloud_ui_user_managed_by_ldap'];
			$d['cu_password_repeat'] = '';
		} else {
			// regular user management
			$d['cu_password']['label']                     = $this->lang['user_password'];
			$d['cu_password']['required']                  = true;
			//$d['cu_password']['validate']['regex']         = '~^[a-z0-9]+$~i';
			//$d['cu_password']['validate']['errormsg']      = sprintf($this->lang['error_password'], '[a-z0-9]');
			$d['cu_password']['object']['type']            = 'htmlobject_input';
			$d['cu_password']['object']['attrib']['type']  = 'password';
			$d['cu_password']['object']['attrib']['id']    = 'cu_password';
			$d['cu_password']['object']['attrib']['name']  = 'cu_password';

			$d['cu_password_repeat']['label']                     = $this->lang['user_password_repeat'];
			$d['cu_password_repeat']['required']                  = true;
			$d['cu_password_repeat']['object']['type']            = 'htmlobject_input';
			$d['cu_password_repeat']['object']['attrib']['type']  = 'password';
			$d['cu_password_repeat']['object']['attrib']['id']    = 'cu_password_repeat';
			$d['cu_password_repeat']['object']['attrib']['name']  = 'cu_password_repeat';
			$d['cu_password_repeat']['object']['attrib']['value'] = '';
		}

		$form->add($d);
		$form->display_errors = false;
		$response->form = $form;
		return $response;
	}

}
?>
