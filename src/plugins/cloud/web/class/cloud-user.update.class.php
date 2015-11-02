<?php
/**
 * Cloud User Update
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_user_update
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_user';

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
		$this->clouddir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/';
		require_once $this->webdir."/plugins/cloud/class/clouduser.class.php";
		$this->cloud_user = new clouduser();
		require_once $this->webdir."/plugins/cloud/class/cloudusergroup.class.php";
		$this->cloud_user_group = new cloudusergroup();
		require_once $this->webdir."/plugins/cloud/class/clouduserslimits.class.php";
		$this->cloud_user_limits = new clouduserlimits();
		require_once $this->webdir."/plugins/cloud/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->webdir."/plugins/cloud/class/cloudmailer.class.php";
		$this->cloud_mailer = new cloudmailer();
		// central user management ?
		$central_user_management = false;
		if (file_exists($this->webdir."/plugins/ldap/.running")) {
			$central_user_management = true;
		}
		$this->central_user_management = $central_user_management;

		// handle response
		$this->response->add('cloud_user_id', $this->response->html->request()->get('cloud_user_id'));
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

		$external_portal_name = $this->cloud_config->get_value_by_key('external_portal_url');
		if (!strlen($external_portal_name)) {
			$openqrm_server = new openqrm_server();
			$openqrm_server_ip = $openqrm_server->get_ip_address();
			$external_portal_name = "http://$openqrm_server_ip/cloud-portal";
		}
		$this->cloud_user_id = $this->response->html->request()->get('cloud_user_id');
		if (strlen($this->cloud_user_id)) {
			$this->cloud_user->get_instance_by_id($this->cloud_user_id);
		}

		$template = $response->html->template($this->tpldir."/cloud-user-update.tpl.php");
		$template->add(sprintf($this->lang['cloud_user_update_title'], $this->cloud_user->name), 'title');
		$template->add($this->cloud_user->name, 'cloud_user_name');
		
		$template->add($this->lang['cloud_user_address'], 'cloud_user_address');
		$template->add($this->lang['cloud_user_data'], 'cloud_user_data');
		$template->add($this->lang['cloud_user_permissions'], 'cloud_user_permissions');
		$template->add($this->lang['cloud_user_limit_explain'], 'cloud_user_limit_explain');
		$template->add($external_portal_name, 'external_portal_name');
		$template->add($this->lang['lang_password_show'], 'lang_password_show');
		$template->add($this->lang['lang_password_hide'], 'lang_password_hide');
		$template->add($this->lang['lang_password_generate'], 'lang_password_generate');
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud User Update
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function update() {
		$response = $this->get_response();
		$form     = $response->form;

		$cloud_user_resource_limit = $form->get_request('cloud_user_resource_limit');
		$cloud_user_disk_limit = $form->get_request('cloud_user_disk_limit');
		$cloud_user_memory_limit = $form->get_request('cloud_user_memory_limit');
		$cloud_user_cpu_limit = $form->get_request('cloud_user_cpu_limit');
		$cloud_user_network_limit = $form->get_request('cloud_user_network_limit');

		if(!$form->get_errors()	&& $response->submit()) {

			$db   = $this->cloud_user;
			$data = $form->get_request();
			$data['cu_id'] = $this->response->html->request()->get('cloud_user_id');

			// name already in use ?
			$this->cloud_user->get_instance_by_id($data['cu_id']);
			// email valid ?
			if (!strcmp($data['cu_email'], "@localhost")) {
				if (!$this->cloud_user->checkEmail($data['cu_email'])) {
					$form->set_error("cu_email", $this->lang['cloud_user_email_invalid']);
				}
			}

			if(!$form->get_errors()) {

				$password = '';
				if (isset($data['cu_password'])) {
					$password = $data['cu_password'];
					// password min 6 characters
					if (strlen($data['cu_password'])<6) {
						$form->set_error("cu_password", $this->lang['cloud_user_short_password']);
					}
				}

				unset($data['cloud_user_resource_limit']);
				unset($data['cloud_user_disk_limit']);
				unset($data['cloud_user_memory_limit']);
				unset($data['cloud_user_cpu_limit']);
				unset($data['cloud_user_network_limit']);

				// add user to htpasswd
				if (strlen($password)) {
					$cloud_htpasswd = $this->clouddir."/user/.htpasswd";
					if (file_exists($cloud_htpasswd)) {
						$openqrm_server_command="htpasswd -b ".$this->clouddir."/user/.htpasswd ".$username." ".$password;
					} else {
						$openqrm_server_command="htpasswd -c -b ".$this->clouddir."/user/.htpasswd ".$username." ".$password;
					}
					$output = shell_exec($openqrm_server_command);

					// send mail to user just in case the password is set
					$cc_admin_email = $this->cloud_config->get_value(1);  // 1 is admin_email
					// get external name
					$external_portal_name = $this->cloud_config->get_value(3);  // 3 is the external name
					if (!strlen($external_portal_name)) {
						$openqrm_server = new openqrm_server();
						$openqrm_server_ip = $openqrm_server->get_ip_address();
						$external_portal_name = "http://$openqrm_server_ip/cloud-portal";
					}
					$email = $data['cu_email'];
					$forename = $data['cu_forename'];
					$lastname = $data['cu_lastname'];
					$this->cloud_mailer->to = $email;
					$this->cloud_mailer->from = $cc_admin_email;
					$this->cloud_mailer->subject = "openQRM Cloud: Your account has been updated";
					$this->cloud_mailer->template = $this->rootdir."/plugins/cloud/etc/mail/welcome_new_cloud_user.mail.tmpl";
					$arr = array('@@USER@@' => $username, '@@PASSWORD@@' => $password, '@@EXTERNALPORTALNAME@@' => $external_portal_name, '@@FORENAME@@' => $forename, '@@LASTNAME@@' => $lastname, '@@CLOUDADMIN@@' => $cc_admin_email);
					$this->cloud_mailer->var_array = $arr;
					$this->cloud_mailer->send();

				}
				// set user permissions and limits, set to 0 (infinite) by default
				$cloud_user_limits_fields['cl_resource_limit'] = $cloud_user_resource_limit;
				$cloud_user_limits_fields['cl_memory_limit'] = $cloud_user_memory_limit;
				$cloud_user_limits_fields['cl_disk_limit'] = $cloud_user_disk_limit;
				$cloud_user_limits_fields['cl_cpu_limit'] = $cloud_user_cpu_limit;
				$cloud_user_limits_fields['cl_network_limit'] = $cloud_user_network_limit;
				$this->cloud_user_limits->get_instance_by_cu_id($data['cu_id']);
				$this->cloud_user_limits->update($this->cloud_user_limits->id, $cloud_user_limits_fields);

				$dberror = $this->cloud_user->update($data['cu_id'], $data);
				// success msg
				$response->msg = $this->lang['cloud_user_update_successful'];
			}
		} else if($form->get_errors()) {
			$response->error = implode('<br>', $form->get_errors());
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

		$cloud_user_id = $this->response->html->request()->get('cloud_user_id');
		if (strlen($cloud_user_id)) {
			$this->cloud_user->get_instance_by_id($cloud_user_id);
			// handle response

		}

		$response = $this->response;
		$form = $response->get_form($this->actions_name, "update");
		$cloud_usergroup_select = $this->cloud_user_group->get_list();

		$d = array();
		$d['cloud_usergroup_id']['label']                     = $this->lang['cloud_user_group'];
		$d['cloud_usergroup_id']['required']                  = true;
		$d['cloud_usergroup_id']['object']['type']            = 'htmlobject_select';
		$d['cloud_usergroup_id']['object']['attrib']['index'] = array('value', 'label');
		$d['cloud_usergroup_id']['object']['attrib']['id']    = "cloud_usergroup_id";
		$d['cloud_usergroup_id']['object']['attrib']['name']  = "cu_cg_id";
		$d['cloud_usergroup_id']['object']['attrib']['options']    = $cloud_usergroup_select;
		$d['cloud_usergroup_id']['object']['attrib']['selected']    = array($this->cloud_user->cg_id);

		if ($this->central_user_management) {
			$d['cloud_user_password']                              = $this->lang['cloud_user_password_managed_by_ldap'];
		} else {
			$d['cloud_user_password']['label']                     = $this->lang['cloud_user_password'];
			$d['cloud_user_password']['validate']['regex']         = '~^[a-z0-9]+$~i';
			$d['cloud_user_password']['validate']['errormsg']      = 'Password must be [a-z0-9] only';
			$d['cloud_user_password']['object']['type']            = 'htmlobject_input';
			$d['cloud_user_password']['object']['attrib']['type']  = 'password';
			$d['cloud_user_password']['object']['attrib']['id']    = 'cloud_user_password';
			$d['cloud_user_password']['object']['attrib']['name']  = 'cu_password';
		}

		$d['cloud_user_email']['label']                     = $this->lang['cloud_user_email'];
		$d['cloud_user_email']['required']                  = true;
		$d['cloud_user_email']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_email']['object']['attrib']['type']  = 'text';
		$d['cloud_user_email']['object']['attrib']['id']    = 'cloud_user_email';
		$d['cloud_user_email']['object']['attrib']['name']  = 'cu_email';
		$d['cloud_user_email']['object']['attrib']['value'] = $this->cloud_user->email;

		$d['cloud_user_forename']['label']                     = $this->lang['cloud_user_forename'];
		$d['cloud_user_forename']['required']                  = true;
		$d['cloud_user_forename']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_forename']['object']['attrib']['type']  = 'text';
		$d['cloud_user_forename']['object']['attrib']['id']    = 'cloud_user_forename';
		$d['cloud_user_forename']['object']['attrib']['name']  = 'cu_forename';
		$d['cloud_user_forename']['object']['attrib']['value'] = $this->cloud_user->forename;

		$d['cloud_user_lastname']['label']                     = $this->lang['cloud_user_lastname'];
		$d['cloud_user_lastname']['required']                  = true;
		$d['cloud_user_lastname']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_lastname']['object']['attrib']['type']  = 'text';
		$d['cloud_user_lastname']['object']['attrib']['id']    = 'cloud_user_lastname';
		$d['cloud_user_lastname']['object']['attrib']['name']  = 'cu_lastname';
		$d['cloud_user_lastname']['object']['attrib']['value']  = $this->cloud_user->lastname;

		$d['cloud_user_street']['label']                     = $this->lang['cloud_user_street'];
		$d['cloud_user_street']['required']                  = true;
		$d['cloud_user_street']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_street']['object']['attrib']['type']  = 'text';
		$d['cloud_user_street']['object']['attrib']['id']    = 'cloud_user_street';
		$d['cloud_user_street']['object']['attrib']['name']  = 'cu_street';
		$d['cloud_user_street']['object']['attrib']['value'] = $this->cloud_user->street;

		$d['cloud_user_city']['label']                     = $this->lang['cloud_user_city'];
		$d['cloud_user_city']['required']                  = true;
		$d['cloud_user_city']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_city']['object']['attrib']['type']  = 'text';
		$d['cloud_user_city']['object']['attrib']['id']    = 'cloud_user_city';
		$d['cloud_user_city']['object']['attrib']['name']  = 'cu_city';
		$d['cloud_user_city']['object']['attrib']['value']  = $this->cloud_user->city;

		$d['cloud_user_country']['label']                     = $this->lang['cloud_user_country'];
		$d['cloud_user_country']['required']                  = true;
		$d['cloud_user_country']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_country']['object']['attrib']['type']  = 'text';
		$d['cloud_user_country']['object']['attrib']['id']    = 'cloud_user_country';
		$d['cloud_user_country']['object']['attrib']['name']  = 'cu_country';
		$d['cloud_user_country']['object']['attrib']['value'] = $this->cloud_user->country;

		$d['cloud_user_phone']['label']                     = $this->lang['cloud_user_phone'];
		$d['cloud_user_phone']['required']                  = true;
		$d['cloud_user_phone']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_phone']['object']['attrib']['type']  = 'text';
		$d['cloud_user_phone']['object']['attrib']['id']    = 'cloud_user_phone';
		$d['cloud_user_phone']['object']['attrib']['name']  = 'cu_phone';
		$d['cloud_user_phone']['object']['attrib']['value'] = $this->cloud_user->phone;

		$d['cloud_user_ccunits']['label']                     = $this->lang['cloud_user_ccunits'];
		$d['cloud_user_ccunits']['required']                  = true;
		$d['cloud_user_ccunits']['validate']['regex']         = '~^[0-9]+$~i';
		$d['cloud_user_ccunits']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], $this->lang['cloud_user_ccunits']);
		$d['cloud_user_ccunits']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_ccunits']['object']['attrib']['type']  = 'text';
		$d['cloud_user_ccunits']['object']['attrib']['id']    = 'cloud_user_ccunits';
		$d['cloud_user_ccunits']['object']['attrib']['name']  = 'cu_ccunits';
		$d['cloud_user_ccunits']['object']['attrib']['value'] = $this->cloud_user->ccunits;

		// language
		$files = $this->file->get_files($this->openqrm->get('basedir').'/plugins/cloud/cloud-portal/web/user/lang/', '', '*.htmlobjects.ini');
		$lang = array();
		foreach($files as $v) {
			$tmp = explode('.', $v['name']);
			$lang[] = array($tmp[0]);
		}
		$d['cloud_user_lang']['label']                        = $this->lang['cloud_user_lang'];
		$d['cloud_user_lang']['object']['type']               = 'htmlobject_select';
		$d['cloud_user_lang']['object']['attrib']['index']    = array(0,0);
		$d['cloud_user_lang']['object']['attrib']['id']       = 'cloud_user_lang';
		$d['cloud_user_lang']['object']['attrib']['name']     = 'cu_lang';
		$d['cloud_user_lang']['object']['attrib']['options']  = $lang;
		$d['cloud_user_lang']['object']['attrib']['selected'] = array($this->cloud_user->lang);

		// user limits
		if (strlen($this->cloud_user->id)) {
			$this->cloud_user_limits->get_instance_by_cu_id($this->cloud_user->id);
		}
	
		$d['cloud_user_resource_limit']['label']                     = $this->lang['cloud_user_resource_limit'];
		$d['cloud_user_resource_limit']['required']                  = true;
		$d['cloud_user_resource_limit']['validate']['regex']         = '~^[0-9]+$~i';
		$d['cloud_user_resource_limit']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], $this->lang['cloud_user_resource_limit']);
		$d['cloud_user_resource_limit']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_resource_limit']['object']['attrib']['type']  = 'text';
		$d['cloud_user_resource_limit']['object']['attrib']['id']    = 'cloud_user_resource_limit';
		$d['cloud_user_resource_limit']['object']['attrib']['name']  = 'cloud_user_resource_limit';
		$d['cloud_user_resource_limit']['object']['attrib']['value'] = $this->cloud_user_limits->resource_limit;

		$d['cloud_user_memory_limit']['label']                     = $this->lang['cloud_user_memory_limit'];
		$d['cloud_user_memory_limit']['required']                  = true;
		$d['cloud_user_memory_limit']['validate']['regex']         = '~^[0-9]+$~i';
		$d['cloud_user_memory_limit']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], $this->lang['cloud_user_memory_limit']);
		$d['cloud_user_memory_limit']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_memory_limit']['object']['attrib']['type']  = 'text';
		$d['cloud_user_memory_limit']['object']['attrib']['id']    = 'cloud_user_memory_limit';
		$d['cloud_user_memory_limit']['object']['attrib']['name']  = 'cloud_user_memory_limit';
		$d['cloud_user_memory_limit']['object']['attrib']['value'] = $this->cloud_user_limits->memory_limit;

		$d['cloud_user_disk_limit']['label']                     = $this->lang['cloud_user_disk_limit'];
		$d['cloud_user_disk_limit']['required']                  = true;
		$d['cloud_user_disk_limit']['validate']['regex']         = '~^[0-9]+$~i';
		$d['cloud_user_disk_limit']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], $this->lang['cloud_user_disk_limit']);
		$d['cloud_user_disk_limit']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_disk_limit']['object']['attrib']['type']  = 'text';
		$d['cloud_user_disk_limit']['object']['attrib']['id']    = 'cloud_user_disk_limit';
		$d['cloud_user_disk_limit']['object']['attrib']['name']  = 'cloud_user_disk_limit';
		$d['cloud_user_disk_limit']['object']['attrib']['value'] = $this->cloud_user_limits->disk_limit;

		$d['cloud_user_cpu_limit']['label']                     = $this->lang['cloud_user_cpu_limit'];
		$d['cloud_user_cpu_limit']['required']                  = true;
		$d['cloud_user_cpu_limit']['validate']['regex']         = '~^[0-9]+$~i';
		$d['cloud_user_cpu_limit']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], $this->lang['cloud_user_cpu_limit']);
		$d['cloud_user_cpu_limit']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_cpu_limit']['object']['attrib']['type']  = 'text';
		$d['cloud_user_cpu_limit']['object']['attrib']['id']    = 'cloud_user_cpu_limit';
		$d['cloud_user_cpu_limit']['object']['attrib']['name']  = 'cloud_user_cpu_limit';
		$d['cloud_user_cpu_limit']['object']['attrib']['value'] = $this->cloud_user_limits->cpu_limit;

		$d['cloud_user_network_limit']['label']                     = $this->lang['cloud_user_network_limit'];
		$d['cloud_user_network_limit']['required']                  = true;
		$d['cloud_user_network_limit']['validate']['regex']         = '~^[0-9]+$~i';
		$d['cloud_user_network_limit']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], $this->lang['cloud_user_network_limit']);
		$d['cloud_user_network_limit']['object']['type']            = 'htmlobject_input';
		$d['cloud_user_network_limit']['object']['attrib']['type']  = 'text';
		$d['cloud_user_network_limit']['object']['attrib']['id']    = 'cloud_user_network_limit';
		$d['cloud_user_network_limit']['object']['attrib']['name']  = 'cloud_user_network_limit';
		$d['cloud_user_network_limit']['object']['attrib']['value'] = $this->cloud_user_limits->network_limit;

		$form->add($d);
		$form->display_errors = false;
		$response->form = $form;
		return $response;
	}

}
?>
