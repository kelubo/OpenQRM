<?php
/**
 * Cloud Users Account
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_account
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-ui';

/**
* user
* @access public
* @var string
*/
var $user;
/**
* cloud-id
* @access public
* @var int
*/
var $cloud_id;


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
		$this->response = $response;
		$this->openqrm = $openqrm;
		$this->rootdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
		$this->clouduser = $openqrm->user();
		require_once $this->rootdir."/plugins/cloud/class/cloudusergroup.class.php";
		$this->cloudusergroup = new cloudusergroup();

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
		$response = $this->form();
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'account', $this->message_param, $response->msg));
		}

		$t = $this->response->html->template("./tpl/cloud-ui.account.tpl.php");
		$t->add($response->form->get_elements());
		$t->add($response->html->thisfile, "thisfile");
		$t->group_elements(array('param_' => 'form'));
		$t->add($this->lang['label'], "label");
		$t->add($this->lang['details'], "details");
		// name
		$t->add($this->lang['user_name'], 'user_name');
		$t->add($this->clouduser->name, 'user_name_value');
		// group
		$t->add($this->lang['user_group'], 'user_group');
		$t->add($this->cloudusergroup->name, 'user_group_value');
		// ccus
		$t->add($this->lang['user_ccunits'], "cloud_user_ccus");
		$t->add($this->clouduser->ccunits, "cloud_user_ccus_value");
		// lang
		$t->add($this->lang['language'], "cloud_user_lang");
		$cloud_user_lang_value = $this->clouduser->lang;
		$t->add($cloud_user_lang_value, "cloud_user_lang_value");

		$a = '';
		if (!strcmp($this->cloudconfig->get_value_by_key('cloud_billing_enabled'), "true")) {
			$a = $this->response->html->a();
			$a->label = $this->lang['transactions'];
			$a->css   = 'last';
			$a->href  = $this->response->get_url($this->actions_name, 'transaction');
			$a = '<div id="transaction_link"><ul><li>'.$a->get_string().'</li></ul></div>';
		}
		$t->add($a, "transactions");
		return $t;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Account
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function form() {
		$response = $this->get_response("update");
		$form     = $response->form;
		
		if(!$form->get_errors() && $response->submit()) {
			$data = $form->get_request();
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
			if(!$form->get_errors()) {
				unset($data['cu_id']);
				unset($data['cu_cg_id']);
				unset($data['cu_name']);
				unset($data['cu_ccunits']);
				unset($data['cu_status']);
				unset($data['cu_token']);
				unset($data['cu_password_repeat']);
				$dberror = $this->clouduser->update($this->clouduser->id, $data);
				// success msg
				$response->msg = $this->lang['user_update_successful'];
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
		
		$this->cloudusergroup->get_instance_by_id($this->clouduser->cg_id);

		$d = array();

		if (file_exists($this->rootdir."/plugins/ldap/.running")) {
			// central user management, do now show to update the users password
			$d['cu_password'] = $this->lang['cloud_ui_user_managed_by_ldap'];
			$d['cu_password_repeat'] = '';
		} else {
			// regular user management
			$d['cu_password']['label']                         = $this->lang['user_password'];
			//$d['cu_password']['validate']['regex']            = '~^[a-z0-9]+$~i';
			//$d['cu_password']['validate']['errormsg']         = sprintf($this->lang['error_password'], '[a-z0-9]');
			$d['cu_password']['object']['type']                = 'htmlobject_input';
			$d['cu_password']['object']['attrib']['type']      = 'password';
			$d['cu_password']['object']['attrib']['id']        = 'cu_password';
			$d['cu_password']['object']['attrib']['name']      = 'cu_password';
			$d['cu_password']['object']['attrib']['value']     = '';
			$d['cu_password']['object']['attrib']['maxlength'] = 50;

			$d['cu_password_repeat']['label']                     = $this->lang['user_password_repeat'];
			$d['cu_password_repeat']['object']['type']            = 'htmlobject_input';
			$d['cu_password_repeat']['object']['attrib']['type']  = 'password';
			$d['cu_password_repeat']['object']['attrib']['id']    = 'cu_password_repeat';
			$d['cu_password_repeat']['object']['attrib']['name']  = 'cu_password_repeat';
			$d['cu_password_repeat']['object']['attrib']['value'] = '';
		}

		$d['cu_email']['label']                         = $this->lang['user_email'];
		$d['cu_email']['required']                      = true;
		$d['cu_email']['object']['type']                = 'htmlobject_input';
		$d['cu_email']['object']['attrib']['type']      = 'text';
		$d['cu_email']['object']['attrib']['id']        = 'cu_email';
		$d['cu_email']['object']['attrib']['name']      = 'cu_email';
		$d['cu_email']['object']['attrib']['value']     = $this->clouduser->email;
		$d['cu_email']['object']['attrib']['maxlength'] = 50;

		$d['cu_forename']['label']                         = $this->lang['user_forename'];
		$d['cu_forename']['required']                      = true;
		$d['cu_forename']['object']['type']                = 'htmlobject_input';
		$d['cu_forename']['object']['attrib']['type']      = 'text';
		$d['cu_forename']['object']['attrib']['id']        = 'cu_forename';
		$d['cu_forename']['object']['attrib']['name']      = 'cu_forename';
		$d['cu_forename']['object']['attrib']['value']     = $this->clouduser->forename;
		$d['cu_forename']['object']['attrib']['maxlength'] = 50;

		$d['cu_lastname']['label']                         = $this->lang['user_lastname'];
		$d['cu_lastname']['required']                      = true;
		$d['cu_lastname']['object']['type']                = 'htmlobject_input';
		$d['cu_lastname']['object']['attrib']['type']      = 'text';
		$d['cu_lastname']['object']['attrib']['id']        = 'cu_lastname';
		$d['cu_lastname']['object']['attrib']['name']      = 'cu_lastname';
		$d['cu_lastname']['object']['attrib']['value']     = $this->clouduser->lastname;
		$d['cu_lastname']['object']['attrib']['maxlength'] = 50;

		$d['cu_street']['label']                         = $this->lang['user_street'];
		$d['cu_street']['required']                      = true;
		$d['cu_street']['object']['type']                = 'htmlobject_input';
		$d['cu_street']['object']['attrib']['type']      = 'text';
		$d['cu_street']['object']['attrib']['id']        = 'cu_street';
		$d['cu_street']['object']['attrib']['name']      = 'cu_street';
		$d['cu_street']['object']['attrib']['value']     = $this->clouduser->street;
		$d['cu_street']['object']['attrib']['maxlength'] = 100;

		$d['cu_city']['label']                         = $this->lang['user_city'];
		$d['cu_city']['required']                      = true;
		$d['cu_city']['object']['type']                = 'htmlobject_input';
		$d['cu_city']['object']['attrib']['type']      = 'text';
		$d['cu_city']['object']['attrib']['id']        = 'cu_city';
		$d['cu_city']['object']['attrib']['name']      = 'cu_city';
		$d['cu_city']['object']['attrib']['value']     = $this->clouduser->city;
		$d['cu_city']['object']['attrib']['maxlength'] = 100;

		$d['cu_country']['label']                         = $this->lang['user_country'];
		$d['cu_country']['required']                      = true;
		$d['cu_country']['object']['type']                = 'htmlobject_input';
		$d['cu_country']['object']['attrib']['type']      = 'text';
		$d['cu_country']['object']['attrib']['id']        = 'cu_country';
		$d['cu_country']['object']['attrib']['name']      = 'cu_country';
		$d['cu_country']['object']['attrib']['value']     = $this->clouduser->country;
		$d['cu_country']['object']['attrib']['maxlength'] = 100;

		$d['cu_phone']['label']                         = $this->lang['user_phone'];
		$d['cu_phone']['required']                      = true;
		$d['cu_phone']['object']['type']                = 'htmlobject_input';
		$d['cu_phone']['object']['attrib']['type']      = 'text';
		$d['cu_phone']['object']['attrib']['id']        = 'cu_phone';
		$d['cu_phone']['object']['attrib']['name']      = 'cu_phone';
		$d['cu_phone']['object']['attrib']['value']     = $this->clouduser->phone;
		$d['cu_phone']['object']['attrib']['maxlength'] = 100;

		$form->add($d);
		$form->display_errors = false;
		$response->form = $form;
		return $response;
	}

}
?>
