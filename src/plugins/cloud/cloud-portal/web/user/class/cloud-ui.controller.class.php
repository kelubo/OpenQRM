<?php
/**
 * Cloud UI Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_ui';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg_cloud_ui";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'project_tab_ui';
/**
* id for tabs
* @access public
* @var string
*/
var $identifier_name = 'cloudappliance_id';
/**
* path to templates
* @access public
* @var string
*/
var $tpldir;
/**
* user
* @access public
* @var string
*/
var $user;
/**
* usergroup
* @access public
* @var string
*/
var $usergroup;
/**
* cloud-id
* @access public
* @var int
*/
var $cloud_id;
/**
* config
* @access public
* @var object
*/
var $config;

/**
* translation
* @access public
* @var array
*/
var $lang = array(
	'home' => array(
		'tab' => 'Home',
		'label' => 'Home',
		'label_limits' => 'Limits',
		'logged_in_as' => 'Logged in as: %s',
		'limit_resource' => 'Instances',
		'limit_memory' => 'Memory',
		'limit_cpu' => 'CPUs',
		'limit_disk' => 'Disk',
		'limit_network' => 'Nics',
		'active' => 'active',
		'paused' => 'paused',
		'free' => 'free',
		'new' => 'new',
		'logout' => 'Logout',
		'msg_logout' => 'You have successfully logged out',
	),
	'appliances' => array(
		'tab' => 'Instances',
		'label' => 'Your Instances in the cloud',
		'label_update' => 'Update instance %s',
		'label_restart' => 'Restart instance',
		'label_pause' => 'Pause instance',
		'label_unpause' => 'Unpause instance',
		'label_deprovision' => 'Terminate instance',
		'state' => 'State',
		'id' => 'Request ID',
		'comment' => 'Comment',
		'action_pause' => 'Pause instance',
		'action_unpause' => 'Unpause instance',
		'action_update' => 'Update instance',
		'action_restart' => 'Restart instance',
		'action_deprovision' => 'Terminate instance',
		'action_resize' => 'Resize',
		'action_private_image' => 'Create private image',
		'plugin_collectd' => 'Collectd',
		'plugin_novnc' => 'noVNC',
		'plugin_ssh' => 'SSHTerm',
		'plugin_ha' => 'Lcmc',
		'msg_no_appliances_to_manage' => 'No instances found to manage. Please create one.',
		'msg_updated_appliance' => 'Updated instance %s', 
		'msg_paused_appliance' => 'Paused instance %s',
		'msg_unpaused_appliance' => 'Unpaused instance %s',
		'msg_restarted_appliance' => 'Restarted instance %s',
		'msg_deprovisioned_appliance' => 'Terminated instance %s',
		'error_restart_failed' => 'Failed to restart instance %s',
		'error_pause_failed' => 'Failed to pause instance %s',
		'error_unpause_failed' => 'Failed to unpause instance %s',
		'error_access_denied' => 'Access denied for instance %s',
		'error_command_running' => 'There is already a command running for instance %s',
		'error_disk_size' => 'Error: Disk size to small',
		'error_appliance_not_active' => 'Error: Instance must be active',
		'error_cloud_disabled' => 'Cloud is disabled for maintanance. Please contact your Administrator.',
		'info_deny' => 'Instance not approved',
		'info_new' => 'Instance waiting for approval',
		'info_no-res' => 'No resource found for instance',
		'label_update_notice'  =>  'Please  notice!',
		'update_cpu_notice'  =>  '1.  To  update  CPU  and/or  Memory  the  instance  must  be  in  state  <b>paused</b>.',
		'update_disk_notice'  =>  '2.  To  update  Disk  size  the  instance  must  be  in  state  <b>active</b>.',
		'mailer_deprovision_subject' => 'openQRM Cloud: Your request %s is going to be deprovisioned now',
		'mailer_pause_subject' => 'openQRM Cloud: Cloud Instance %s registered for pause',
		'mailer_unpause_subject' => 'openQRM Cloud: Cloud Instance %s registered for unpause',
	),
	'create' => array(
		'tab' => 'New Instance',
		'label' => 'New Instance',
		'label_profiles' => 'Profiles',
		'label_profiles_remove' => 'Remove profile',
		'label_private_images' => 'Private images',
		'table_components' => 'Components',
		'table_ccus' => 'CCUs/h',
		'table_ips' => 'IP-Addresses',
		'name' => 'Name',
		'type' => 'Type',
		'kernel' => 'Kernel',
		'image' => 'Image',
		'cpu' => 'CPU',
		'disk' => 'Disk',
		'ram' => 'Memory',
		'network' => 'Network',
		'ha' => 'Highavailability',
		'capabilities' => 'Extra Params',
		'hostname' => 'Hostname',
		'save_as_profile' => 'Save as Profile',
		'price_hour' => 'Price per hour',
		'price_day' => 'Price per day',
		'price_month' => 'Price per month',
		'ccu_per_hour' => 'CCU(s) per hour',
		'action_enable_profile' => 'Enable Profile',
		'action_remove_profile' => 'Remove Profile',
		'msg_profile_in_use' => 'Profile %s is already in use',
		'msg_created' => 'Created new Instance in the Cloud',
		'msg_saved_profile' => 'Saved profile %s',
		'msg_loading_profile' => 'Loading profile %s',
		'msg_removed_profile' => 'Removed profile %s',
		'error_resource_limit' => 'You are not allowed to create a new instance. The limit of %s instance(s) per user is reached.',
		'error_cloud_disabled' => 'Cloud is disabled for maintanance. Please contact your Administrator.',
		'error_ccus_low' => 'You can not create a new instance because you are too low on CCUs.',
		'error_save_as_profile' => 'Profilename must be %s only',
		'error_image_no_fit' => 'Image does not fit Type',
		'error_limit_exceeded' => 'Limit exceeded',
		'error_profile' => 'Error loading %s from profile %s',
		'error_profile_access_denied' => 'Access denied for profile %s',
		'error_max_profiles' => 'Maximum profiles (%s) exceeded',
		'error_hostname' => 'Hostname %s is already in use',
		'mailer_create_subject' => 'openQRM Cloud: New request from user %s',
	),
	'account' => array(
		'tab' => 'Account',
		'label' => 'Account',
		'details' => 'Details',
		'language' => 'Language',
		'transactions' => 'Transactions',
		'user_name' => 'Name',
		'user_id' => 'ID',
		'user_forename' => 'Forename',
		'user_lastname' => 'Lastname',
		'user_email' => 'Email',
		'user_address' => 'Adress',
		'user_city' => 'City',
		'user_state' => 'Country',
		'user_country' => 'Country',
		'user_phone' => 'Phone',
		'user_ccunits' => 'CCUs',
		'user_update_successful' => 'Successfully updated account data.',
		'user_status' => 'Status',
		'user_password' => 'Password',
		'user_password_repeat' => 'Password (repeat)',
		'user_managed_by_ldap' => 'Password is managed by LDAP',
		'user_group' => 'Project',
		'user_street' => 'Street',
		'user_lang' => 'Language',
		'error_no_match' => '%s does not match %s',
		'error_password' => 'Password must contain %s only.',
	),
	'transactions' => array(
		'label' => 'Transactions',
		'id' => 'ID',
		'date' => 'Date',
		'request_id' => 'Request ID',
		'charge' => 'Charge',
		'balance' => 'Balance',
		'reason' => 'Reason',
		'comment' => 'Comment',
	),
	'images' => array(
		'label' => 'Private images',
		'label_edit' => 'Edit image %s',
		'label_remove' => 'Remove image',
		'label_private' => 'Create private image',
		'id' => 'ID',
		'name' => 'Name',
		'state' => 'State',
		'state_on' => 'true',
		'state_off' => 'false',
		'clone_on_deploy' => 'Clone on deploy',
		'comment' => 'Comment',
		'action_edit' => 'Edit image',
		'action_remove' => 'Remove image',
		'msg_updated' => 'Updated image %s',
		'msg_private_image' => 'Creating private image %s from image %s',
		'msg_image_removed' => 'Image %s removed',
		'error_image_active' => 'Error: Image %s is active',
		'error_command_running' => 'There is already a command running for instance %s',
		'error_resource_not_active' => 'Error: Instance %s must be active',
		'error_access_denied' => 'Access denied for image %s',
		'error_cloud_disabled' => 'Cloud is disabled for maintanance. Please contact your Administrator.',
		'error_image_name_in_use' => 'Image name %s is already in use',
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
		if ((file_exists("/etc/init.d/openqrm")) && (is_link("/etc/init.d/openqrm"))) {
			$this->basedir = dirname(dirname(dirname(readlink("/etc/init.d/openqrm"))));
		} else {
			$this->basedir = "/usr/share/openqrm";
		}
		$this->rootdir   = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base';
		$this->userdir   = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/user';
		$this->tpldir    = $this->userdir."/tpl";
		$this->openqrm   = $openqrm;
		$this->clouduser = $openqrm->user();
		$this->response  = $response;
		$this->html      = $this->response->html;

		// kill session when user is empty
		if($this->clouduser === '') {
			$this->response->redirect('../?'.$this->message_param.'=You are not allowed to login');
			exit(0);
		}

		require_once $this->rootdir."/plugins/cloud/class/cloudconfig.class.php";
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
			if($this->action === 'profile_remove') {
				$this->action = "create";
			}
			else if($this->action === 'image_edit') {
				$this->action = "images";
			}
			else if($this->action === 'image_private') {
				$this->action = "appliances";
			}
			else if($this->action === 'image_remove') {
				$this->action = "images";
			} else {
				$this->action = "appliances";
			}
		}

		$content = array();
		switch( $this->action ) {
			case '':
			case 'home':
				$content[] = $this->home(true);
				$content[] = $this->appliances(false);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;
			case 'appliances':
				$content[] = $this->home(false);
				$content[] = $this->appliances(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;
			case 'create':
				$content[] = $this->home(false);
				$content[] = $this->appliances(false);
				$content[] = $this->account(false);
				$content[] = $this->create(true);
			break;
			case 'deprovision':
				$content[] = $this->home(false);
				$content[] = $this->deprovision(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;
			case 'appliance_update':
				$content[] = $this->home(false);
				$content[] = $this->appliance_update(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;
/*
			case 'appliance_resize':
				$content[] = $this->create(false);
				$content[] = $this->appliance_resize(true);
				$content[] = $this->account(false);
				if ($private_image_enabled) {
					$content[] = $this->images(false);
				}
				$content[] = $this->transaction(false);
			break;
*/
			case 'restart':
				$content[] = $this->home(false);
				$content[] = $this->restart(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;
			case 'pause':
				$content[] = $this->home(false);
				$content[] = $this->pause(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;
			case 'unpause':
				$content[] = $this->home(false);
				$content[] = $this->unpause(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;

			case 'statistics':
				$content[] = $this->home(false);
				$content[] = $this->__statistics(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;
			case 'novnc':
				$content[] = $this->home(false);
				$content[] = $this->__novnc(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;


			case 'profiles':
				$content[] = $this->home(false);
				$content[] = $this->appliances(false);
				$content[] = $this->account(false);
				$content[] = $this->profiles(true);
			break;
			case 'profile_remove':
				$content[] = $this->home(false);
				$content[] = $this->appliances(false);
				$content[] = $this->account(false);
				$content[] = $this->profile_remove(true);
			break;
			case 'images':
				$content[] = $this->home(false);
				$content[] = $this->appliances(false);
				$content[] = $this->account(false);
				$content[] = $this->images(true);
			break;
			case 'image_remove':
				$content[] = $this->home(false);
				$content[] = $this->appliances(false);
				$content[] = $this->account(false);
				$content[] = $this->image_remove(true);
			break;
			case 'image_edit':
				$content[] = $this->home(false);
				$content[] = $this->appliances(false);
				$content[] = $this->account(false);
				$content[] = $this->image_edit(true);
			break;
			case 'image_private':
				$content[] = $this->home(false);
				$content[] = $this->image_private(true);
				$content[] = $this->account(false);
				$content[] = $this->create(false);
			break;

			case 'account':
				$content[] = $this->home(false);
				$content[] = $this->appliances(false);
				$content[] = $this->account(true);
				$content[] = $this->create(false);
			break;
			case 'transaction':
				$content[] = $this->home(false);
				$content[] = $this->appliances(false);
				$content[] = $this->transaction(true);
				$content[] = $this->create(false);
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
	 * Home
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function home( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.home.class.php');
			$controller = new cloud_ui_home($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->identifier_name = $this->identifier_name;
			$controller->lang            = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['home']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'home' );
		$content['onclick'] = false;
		if($this->action === 'home'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Create Cloud User Request
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function create( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.create.class.php');
			$controller = new cloud_ui_create($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->identifier_name = $this->identifier_name;
			$controller->lang            = $this->lang['create'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['create']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'create' );
		$content['onclick'] = false;
		if($this->action === 'create'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Deprovision Cloud Users Request
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function deprovision( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.deprovision.class.php');
			$controller = new cloud_ui_deprovision($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['appliances'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'deprovision'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Actions
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function appliances( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.appliances.class.php');
			$controller = new cloud_ui_appliances($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'appliances'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Comment
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function appliance_update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.appliance.update.class.php');
			$controller = new cloud_ui_appliance_update($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'appliance_update'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Resize
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function appliance_resize( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.appliance.resize.class.php');
			$controller = new cloud_ui_appliance_resize($this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->clouduser->translate($this->lang, $this->userdir."/lang", 'cloud-ui.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliance_resize' );
		$content['onclick'] = false;
		if($this->action === 'appliance_resize'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Restart
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function restart( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.restart.class.php');
			$controller = new cloud_ui_restart($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['appliances'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'restart'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Pause
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function pause( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.pause.class.php');
			$controller = new cloud_ui_pause($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['appliances'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'pause'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Unpause Cloud Users Appliance
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function unpause( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.unpause.class.php');
			$controller = new cloud_ui_unpause($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['appliances'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'unpause'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Collectd statistics
	 *
	 * @access protected
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function __statistics( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->openqrm->get('basedir').'/plugins/collectd/web/class/collectd.controller.class.php');
			$controller = new collectd_controller($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->openqrm->get('basedir').'/plugins/collectd/web/tpl/';
			$controller->message_param = $this->message_param;
			$controller->image_path    = 'api.php?action=collectd&'.$controller->actions_name.'=image';
			$controller->image_width   = 600;
			$controller->image_height  = 150;
			$controller->action = 'statistics';
			$data = $controller->statistics();
			$data = $data['value'];
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'statistics'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Account
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function account( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.account.class.php');
			$controller = new cloud_ui_account($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
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
	 * Cloud Users Profiles
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function profiles( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.profiles.class.php');
			$controller = new cloud_ui_profiles($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['create'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['create']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'create' );
		$content['onclick'] = false;
		if($this->action === 'profiles'){
			$content['active']  = true;
		}
		return $content;
	}
	
	//--------------------------------------------
	/**
	 * Cloud Users Profile Remove
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function profile_remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.profile.remove.class.php');
			$controller = new cloud_ui_profile_remove($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['create'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['create']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'create' );
		$content['onclick'] = false;
		if($this->action === 'profile_remove'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Private Images
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function images( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.images.class.php');
			$controller = new cloud_ui_images($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['images'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['create']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'create' );
		$content['onclick'] = false;
		if($this->action === 'images'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Remove Cloud Users Private Images
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function image_remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.image.remove.class.php');
			$controller = new cloud_ui_image_remove($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['images'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'image_remove'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Edit Cloud Users Private Image
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function image_edit( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.image.edit.class.php');
			$controller = new cloud_ui_image_edit($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['images'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['create']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'create' );
		$content['onclick'] = false;
		if($this->action === 'image_edit'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Private Image
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function image_private( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.image.private.class.php');
			$controller = new cloud_ui_image_private($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['images'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['appliances']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'appliances' );
		$content['onclick'] = false;
		if($this->action === 'image_private'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Transactions
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function transaction( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->userdir.'/class/cloud-ui.transaction.class.php');
			$controller = new cloud_ui_transaction($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->clouduser       = $this->clouduser;
			$controller->cloudconfig     = $this->cloudconfig;
			$controller->message_param   = $this->message_param;
			$controller->basedir         = $this->basedir;
			$controller->rootdir         = $this->rootdir;
			$controller->userdir         = $this->userdir;
			$controller->lang            = $this->lang['transactions'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['account']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'account' );
		$content['onclick'] = false;
		if($this->action === 'transaction'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Api
	 *
	 * @access public
	 * @return htmlobject_template
	 */
	//--------------------------------------------
	function api() {
		require_once($this->userdir.'/class/cloud-ui.api.class.php');
		$controller = new cloud_api($this->openqrm, $this->response, $this);
		$controller->cloudconfig     = $this->cloudconfig;
		$controller->basedir         = $this->basedir;
		$controller->rootdir         = $this->rootdir;
		$controller->userdir         = $this->userdir;
		$controller->clouduser       = $this->clouduser;
		$controller->lang            = $this->clouduser->translate($this->lang, $this->userdir."/lang", 'cloud-ui.ini');
		$controller->action();
	}

}
?>
