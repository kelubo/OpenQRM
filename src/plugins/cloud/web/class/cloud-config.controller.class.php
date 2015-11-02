<?php
/**
 * Cloud Config Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_config_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_config';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-config";
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
	'tab' => 'Cloud Config',
	'update_successfull' => 'Successfully updated cloud configuration.',

	'cloud_admin_email' => 'Email address to send status messages and events to.',
	'auto_provision' => 'Automatically provision systems (true) or wait for approval (false).',
	'external_portal_url' => 'External DNS/Domain name for the Cloud portal.',
	'request_physical_systems' => 'Allow automatic provisioning of physical servers (true).',
	'default_clone_on_deploy' => 'By default a clone of the requested image is provisioned (not the origin)',
	'max_resources_per_cr' => 'Global Cloud limit. Until openQRM 5.0 this is statically set to 1.',
	'auto_create_vms' => 'Automatically create VMs (true) or use a static pool of pre-created VMs (false).',
	'max_disk_size' => 'Maximum overall disk space (in MB) used by a Cloud User.',
	'max_memory' => 'Maximum overall memory (in MB) used by a Cloud User.',
	'max_cpu' => 'Maximum overall cpus used by a Cloud User.',
	'max_network' => 'Maximum overall networkcards used by a Cloud User.',
	'max_network_interfaces' => 'Maximum network-interfaces per VM.',
	'show_ha_checkbox' => 'Show highavailability option. Highavailability plugin must be enabled and started.',
	'show_puppet_groups' => 'Show deployment option. Puppet plugin must be enabled and started.',
	'auto_give_ccus' => 'Automatically provides some Cloud Computing Units (CCUs) to new registered Users.',
	'max_apps_per_user' => 'Maximum overall number of active servers per user.',
	'public_register_enabled' => 'Allow users to register themselves via the public portal.',
	'cloud_enabled' => 'Use this option to set the Cloud in a maintenance mode. If set to false, running systems will stay as they are but Cloud Users will not be able to submit new requests.',
	'cloud_billing_enabled' => 'Enable/disable the internal billing mechanism.',
	'show_sshterm_login' => 'Enable/disable Web-SSH login. SSHterm plugin must be enabled and started.',
	'cloud_nat' => 'Translate the (private) openQRM managed network to a public network. Requires to set pre/post-routing on the gateway/router to the external (public) network.',
	'show_collectd_graphs' => 'Enable/disable System statistics. Collectd plugin must be enabled and started.',
	'show_disk_resize' => 'Allow disk resize.',
	'show_private_image' => 'Allow to map images to specific users.',
	'cloud_selector' => 'Enable/disable the cloud product manager.',
	'cloud_currency' => 'The real currency virtual cloud currency (CCU) is mapped to.',
	'cloud_1000_ccus' => 'Value of 1000 CCUs (virtual Cloud currency) in real currency.',
	'resource_pooling' => 'Allow mapping of servers to specific usergroups.',
	'ip-management' => 'Enable/disable the automatic IP-address configuration for the external (public) network interfaces of the requested Cloud Systems. Ip-mgmt plugin must be enabled and started.',
	'max_parallel_phase_one_actions' => 'How many actions should run in phase 1.',
	'max_parallel_phase_two_actions' => 'How many actions should run in phase 2.',
	'max_parallel_phase_three_actions' => 'How many actions should run in phase 3.',
	'max_parallel_phase_four_actions' => 'How many actions should run in phase 4.',
	'max_parallel_phase_five_actions' => 'How many actions should run in phase 5.',
	'max_parallel_phase_six_actions' => 'How many actions should run in phase 6.',
	'max_parallel_phase_seven_actions' => 'How many actions should run in phase 7.',
	'appliance_hostname' => 'Allow users to provide own hostname.',
	'cloud_zones_client' => 'Enable/disable this Cloud as an openQRM Enterprise Cloud Zones client.',
	'cloud_zones_master_ip' => 'Defines the openQRM Enterprise Cloud Zones IP-address.',
	'cloud_external_ip' => 'Defines the public IP-address of this Cloud.',
	'deprovision_warning' => 'Send a deprovision warning to the user when configured CCU amount is reached.',
	'deprovision_pause' => 'Pause server requests when configured CCU amount is reached.',
	'vm_provision_delay' => 'Delayed Provisioning of VMs for N seconds.',
	'vm_loadbalance_algorithm' => 'Loadbalancing-Algorithm for VMs. 0=Load, 1=Memory, 2=Random, 3=First available Host until Hosts VM-Limit is reached.',
	'allow_vnc_access' => '',
	'error_NAN' => '%s must be a number',
	'error_empty' => '%s must not be empty',
	'error_to_low' => '%s must not be lower than %s',
	'error_cloud_selector_disabled' => 'If cloud_billing_enabled is enabled cloud_selector must be enabled too.',
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
		$this->webdir   = $this->openqrm->get('webdir');
		$this->rootdir  = $this->openqrm->get('rootdir');
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-config.ini');
		$this->tpldir   = $this->webdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_config_id";

		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudconfig.class.php');
		$this->config = new cloudconfig();
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
			case 'update':
			default:
				$content[] = $this->update(true);
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
	 * Update Cloud Configuration
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-config.update.class.php');
			$controller = new cloud_config_update($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param   = $this->message_param;
			$controller->lang            = $this->lang;
			$controller->config          = $this->config;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'update' );
		$content['onclick'] = false;
		if($this->action === 'update'){
			$content['active']  = true;
		}
		return $content;
	}



}
?>
