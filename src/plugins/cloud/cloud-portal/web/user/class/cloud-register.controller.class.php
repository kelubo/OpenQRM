<?php
/**
 * Cloud Users Register Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_register_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'register_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "register_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'register_tab';
/**
* path to templates
* @access public
* @var string
*/
var $tpldir;
/**
* translation
* @access public
* @var array
*/
var $lang = array(
	'home' => array(
		'tab' => 'Login to the cloud',
		'label' => 'Welcome to the cloud',
		'text_1' => 'Welcome to the cloud',
		'text_2' => 'Welcome to the cloud',
	),
	'account' => array(
		'tab' => 'New account',
		'label' => 'New account',
		'user_name' => 'Name',
		'user_forename' => 'Forename',
		'user_lastname' => 'Lastname',
		'user_email' => 'Email',
		'user_address' => 'Adress',
		'user_city' => 'City',
		'user_state' => 'Country',
		'user_country' => 'Country',
		'user_phone' => 'Phone',
		'user_password' => 'Password',
		'user_password_repeat' => 'Password (repeat)',
		'user_group' => 'Group',
		'user_street' => 'Street',
		'user_lang' => 'Language',
		'user_managed_by_ldap' => 'Password is managed by LDAP',
		'msg_register_successful' => 'Successfully registerd Cloud User. An activation mail has been send.',
		'error_name_in_use' => 'Name is in use',
		'error_no_match' => '%s does not match %s',
		'error_password' => 'Password must contain %s only.',
		'error_name' => 'Name must contain %s only.',
		'mailer_subject' => 'openQRM Cloud: Activate your account',
	),
	'activate' => array(
		'tab' => 'Activate your account',
		'label' => 'Enter your activation code',
		'token' => 'Token',
		'msg_activated' => 'You have successfully activated your Cloud User Account',
	),
	'recover' => array(
		'tab' => 'Recover password',
		'label' => 'Recover password',
		'name' => 'Username',
		'email' => 'Email',
		'msg_mail_send' => 'A mail with new password has been send',
		'error_no_such_user' => 'No such User',
		'error_no_such_email' => 'Email does not fit username',
		'mailer_subject' => 'openQRM Cloud: Your password has been restored',
	),
);


	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param file $file
	 * @param htmlobject_response $response
	 */
	//--------------------------------------------
	function __construct($openqrm, $response) {

		$this->identifier_name	= "cloud";
		if ((file_exists("/etc/init.d/openqrm")) && (is_link("/etc/init.d/openqrm"))) {
			$this->basedir = dirname(dirname(dirname(readlink("/etc/init.d/openqrm"))));
		} else {
			$this->basedir = "/usr/share/openqrm";
		}
		$this->rootdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base';
		$this->userdir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/user';
		$this->tpldir  = $this->userdir."/tpl";
		$this->html    = $response->html;

		$this->response = $response;
		$this->openqrm = $openqrm;

		require_once($this->basedir."/plugins/cloud/web/class/cloudconfig.class.php");
		$this->cloudconfig = new cloudconfig();
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @param string $action
	 * @return htmlobject_tabmenu
	 */
	//--------------------------------------------
	function action($action = null) {
		$this->action = '';
		$ar = $this->response->html->request()->get($this->actions_name);
		if($ar !== '') {
			if(is_array($ar)) {
				$this->action = key($ar);
			} else {
				$this->action = $ar;
			}
		}
		else if(isset($action)) {
			$this->action = $action;
		}
		if($this->response->cancel()) {
			$this->action = "login";
		}

		$content = array();
		switch( $this->action ) {
			case '':
			case 'home':
			default:
				$content[] = $this->home(true);
				if (!strcmp($this->cloudconfig->get_value_by_key('public_register_enabled'), "true")) {
					$content[] = $this->account(false);
					$content[] = $this->activate(false);
				}
				$content[] = $this->recover(false);
			break;
			case 'account':
				$content[] = $this->home(false);
				if (!strcmp($this->cloudconfig->get_value_by_key('public_register_enabled'), "true")) {
					$content[] = $this->account(true);
					$content[] = $this->activate(false);
				}
				$content[] = $this->recover(false);
			break;
			case 'activate':
				$content[] = $this->home(false);
				if (!strcmp($this->cloudconfig->get_value_by_key('public_register_enabled'), "true")) {
					$content[] = $this->account(false);
					$content[] = $this->activate(true);
				}
				$content[] = $this->recover(false);
			break;
			case 'recover':
				$content[] = $this->home(false);
				if (!strcmp($this->cloudconfig->get_value_by_key('public_register_enabled'), "true")) {
					$content[] = $this->account(false);
					$content[] = $this->activate(false);
				}
				$content[] = $this->recover(true);
			break;
		}
		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->auto_tab = false;
		$tab->add($content);
		return $tab;
	}

	//--------------------------------------------
	/**
	 * Cloud User Portal Home
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function home( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-register.home.class.php');
			$controller = new cloud_register_home($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param   = $this->message_param;
			$controller->lang            = $this->lang['home'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['home']['tab'];
		$content['value']   = $data;
		$content['target']  = 'user/';
		$content['request'] = '';
		$content['onclick'] = false;
		if($this->action === 'home'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * account registered Cloud User
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function account( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-register.account.class.php');
			$controller = new cloud_register_account($this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param   = $this->message_param;
			$controller->lang            = $this->lang['account'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['account']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'account' );
		$content['onclick'] = false;
		if($this->action === 'account'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Activate registered Cloud User
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function activate( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-register.activate.class.php');
			$controller = new cloud_register_activate($this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param   = $this->message_param;
			$controller->lang            = $this->lang['activate'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['activate']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'activate' );
		$content['onclick'] = false;
		if($this->action === 'activate'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud User Recover Password
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function recover( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-register.recover.class.php');
			$controller = new cloud_register_recover($this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param   = $this->message_param;
			$controller->lang            = $this->lang['recover'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['recover']['tab'];
		$content['value']   = $data;
		$content['css']     = 'xxx';
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'recover' );
		$content['onclick'] = false;
		if($this->action === 'recover'){
			$content['active']  = true;
		}
		return $content;
	}

}
?>
