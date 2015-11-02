<?php
/**
 * Cloud User Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_user_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_user';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-user";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'project_tab';
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
	'cloud_user_name' => 'Name',
	'cloud_user_username' => 'Username',
	'cloud_user_user_id' => 'User ID',
	'cloud_user_id' => 'ID',
	'cloud_user' => 'User',
	'cloud_user_management' => 'Cloud User Management',
	'cloud_user_forename' => 'Forename',
	'cloud_user_lastname' => 'Lastname',
	'cloud_user_password' => 'Password',
	'cloud_user_email' => 'Email',
	'cloud_user_address' => 'Adress',
	'cloud_user_city' => 'City',
	'cloud_user_state' => 'Country',
	'cloud_user_country' => 'Country',
	'cloud_user_phone' => 'Phone',
	'cloud_user_ccunits' => 'CCUs',
	'cloud_user_insert_successful' => 'Successful inserted Cloud User',
	'cloud_user_update_successful' => 'Successful updated Cloud User',
	'cloud_user_update' => 'Update',
	'cloud_user_update_title' => 'Update Cloud User',
	'cloud_user_add' => 'New',
	'cloud_user_add_title' => 'Add Cloud User to portal ',
	'cloud_user_update_title' => 'Update Cloud User %s of portal ',
	'cloud_user_status' => 'Status',
	'cloud_user_actions' => 'Actions',
	'cloud_user_informations' => 'Informations',
	'cloud_user_name_in_use' => 'User name already in use!',
	'cloud_user_email_invalid' => 'Invalid Email adress!',
	'cloud_user_short_password' => 'Password must be at least 6 characters long!',
	'cloud_user_short_username' => 'Username must be at least 4 characters long!',
	'cloud_user_group' => 'Group',
	'cloud_user_street' => 'Street',
	'cloud_user_token' => 'Token',
	'cloud_user_data' => 'User Credentials',
	'cloud_user_confirm_delete' => 'Really delete the following Cloud User?',
	'cloud_user_deleted' => 'Deleted Cloud User',
	'cloud_user_delete' => 'Delete',
	'cloud_user_confirm_enable' => 'Enable the following Cloud User?',
	'cloud_user_enabled' => 'Enabled Cloud User',
	'cloud_user_enable' => 'Enable',
	'cloud_user_confirm_disable' => 'Disable the following Cloud User?',
	'cloud_user_disabled' => 'Disabled Cloud User',
	'cloud_user_disable' => 'Disable',
	'cloud_user_lang' => 'Language',
	'cloud_user_actions' => 'Actions',
	'cloud_user_active' => 'Enabled',
	'cloud_user_inactive' => 'Disabled',
	'cloud_user_permissions' => 'Permissions',
	'cloud_user_instances' => 'Instances',
	'cloud_user_new_instance' => 'New Instance',
	'cloud_user_requests' => 'Requests',
	'cloud_user_resource_limit' => 'Resource Limit',
	'cloud_user_memory_limit' => 'Memory Limit (MB)',
	'cloud_user_disk_limit' => 'Disk Limit (MB)',
	'cloud_user_cpu_limit' => 'CPU Limit',
	'cloud_user_network_limit' => 'Network Limit',
	'cloud_user_limit_explain' => '(0 == unlimited)',
	'cloud_user_password_managed_by_ldap' => 'Password is managed by LDAP',
	'cloud_user_managed_by_ldap' => 'Cloud Users are managed by LDAP',
	'lang_password_generate' => 'generate password',
	'lang_password_show' => 'show password',
	'lang_password_hide' => 'hide password',
	'error_NAN' => '%s must be a number',
);

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
		$this->openqrm  = $openqrm;
		$this->user     = $this->openqrm->user();
		$this->rootdir  = $this->openqrm->get('webdir');
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_user_id";
		require_once $this->rootdir."/class/htmlobjects/htmlobject.class.php";
		$this->html = new htmlobject($this->rootdir."/class/htmlobjects/");
		$this->html->lang = $this->user->translate($this->html->lang, $this->rootdir."/plugins/cloud/lang", 'htmlobjects.ini');

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
			$this->action = "select";
		}
		$content = array();
		switch( $this->action ) {
			case '':
			case 'select':
				$content[] = $this->select(true);
				$content[] = $this->insert(false);
			break;
			case 'insert':
				$content[] = $this->select(false);
				$content[] = $this->insert(true);
			break;
			case 'delete':
				$content[] = $this->select(false);
				$content[] = $this->delete(true);
			break;
			case 'enable':
				$content[] = $this->select(false);
				$content[] = $this->enable(true);
			break;
			case 'disable':
				$content[] = $this->select(false);
				$content[] = $this->disable(true);
			break;
			case 'update':
				$content[] = $this->select(false);
				$content[] = $this->update(true);
			break;
			case 'instance':
				$content[] = $this->select(false);
				$content[] = $this->instance(true);
			break;
			case 'instances':
				$content[] = $this->select(false);
				$content[] = $this->instances(true);
			break;
			case 'requests':
				$content[] = $this->select(false);
				$content[] = $this->requests(true);
			break;
		}
		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		return $tab;
	}

	//--------------------------------------------
	/**
	 * Cloud User Select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-user.select.class.php');
			$controller = new cloud_user_select($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_user'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'select' );
		$content['onclick'] = false;
		if($this->action === 'select'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud User Insert
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function insert( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-user.insert.class.php');
			$controller = new cloud_user_insert($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
			$data = $controller->action();
		}
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
		$content['label']   = $this->lang['cloud_user_add'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'insert' );
		$content['onclick'] = false;
		if($this->action === 'insert'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Cloud User Delete
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function delete( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-user.delete.class.php');
			$controller = new cloud_user_delete($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
			$data = $controller->action();
		}
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
		$content['label']   = $this->lang['cloud_user_delete'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'delete' );
		$content['onclick'] = false;
		if($this->action === 'delete'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud User Enable
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function enable( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-user.enable.class.php');
			$controller = new cloud_user_enable($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
			$data = $controller->action();
		}
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
		$content['label']   = $this->lang['cloud_user_enable'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'enable' );
		$content['onclick'] = false;
		if($this->action === 'enable'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud User Disable
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function disable( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-user.disable.class.php');
			$controller = new cloud_user_disable($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
			$data = $controller->action();
		}
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
		$content['label']   = $this->lang['cloud_user_disable'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'disable' );
		$content['onclick'] = false;
		if($this->action === 'disable'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud User Update
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-user.update.class.php');
			$controller = new cloud_user_update($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
			$data = $controller->action();
		}
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-user.ini');
		$content['label']   = $this->lang['cloud_user_update'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'update' );
		$content['onclick'] = false;
		if($this->action === 'update'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud User new instance
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function instance( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloud-user.instance.class.php');

			require_once($this->openqrm->get('basedir').'/plugins/cloud/cloud-portal/web/user/class/cloud-ui.controller.class.php');
			$c = new cloud_ui_controller($this->openqrm, $this->response);

			$controller = new cloud_user_instance($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->openqrm->get('basedir').'/plugins/cloud/cloud-portal/web/user/tpl';
			$controller->message_param = $this->message_param;
			$controller->lang          = $c->lang['create'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_user_new_instance'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'instance' );
		$content['onclick'] = false;
		if($this->action === 'instance'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud User new instance
	 *
	 * @access public

	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function instances( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloud-user.instances.class.php');

			require_once($this->openqrm->get('basedir').'/plugins/cloud/cloud-portal/web/user/class/cloud-ui.controller.class.php');
			$c = new cloud_ui_controller($this->openqrm, $this->response);

			$controller = new cloud_user_instances($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->openqrm->get('basedir').'/plugins/cloud/cloud-portal/web/user/tpl';
			$controller->message_param = $this->message_param;
			$controller->lang          = $c->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_user_instances'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'instances' );
		$content['onclick'] = false;
		if($this->action === 'instances'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud User Update
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function requests( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-user.requests.class.php');
			$controller = new cloud_user_requests($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->message_param = $this->message_param;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_user_requests'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'requests' );
		$content['onclick'] = false;
		if($this->action === 'requests'){
			$content['active']  = true;
		}
		return $content;
	}



}
?>
