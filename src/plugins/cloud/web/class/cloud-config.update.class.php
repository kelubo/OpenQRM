<?php
/**
 * Update Cloud Configuration
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_config_update
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud_config';



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
		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudconfig.class.php');
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
		$response = $this->update();

		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'update', $this->message_param, $response->msg));
		}
		$template = $this->response->html->template($this->tpldir."/cloud-config-update.tpl.php");
		$template->add($this->lang['cloud_config_management'], 'title');
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Update Cloud Configuration
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function update() {
		// $this->response->html->debug();
		$response = $this->get_response();
		$form     = $response->form;
		if($form->get_request('cloud_billing_enabled') === 'true') {
			if($form->get_request('cloud_currency') === '') {
				$form->set_error('cloud_currency', sprintf($this->lang['error_empty'], 'cloud_currency'));
			}
			if($form->get_request('cloud_1000_ccus') === '') {
				$form->set_error('cloud_1000_ccus', sprintf($this->lang['error_empty'], 'cloud_1000_ccus'));
			}
			if($form->get_request('auto_give_ccus') === '') {
				$form->set_error('auto_give_ccus', sprintf($this->lang['error_empty'], 'auto_give_ccus'));
			}
			$dw = $form->get_request('deprovision_warning');
			$dp = $form->get_request('deprovision_pause');
			if($dw === '') {
				$form->set_error('deprovision_warning', sprintf($this->lang['error_empty'], 'deprovision_warning'));
			}
			if($dp === '') {
				$form->set_error('deprovision_pause', sprintf($this->lang['error_empty'], 'deprovision_pause'));
			}
			if( $dw !== '' && $dp !== '' && !$form->get_errors()) {
				if($dw < $dp) {
					$form->set_error('deprovision_warning', sprintf($this->lang['error_to_low'], 'deprovision_warning', 'deprovision_pause'));
				}
			}
			$products = $form->get_request('cloud_selector');
			if( $products === '' || $products === 'false') {
				$form->set_error('cloud_selector', $this->lang['error_cloud_selector_disabled']);
			}
		}
		if(!$form->get_errors() && $response->submit()) {
			$data = $form->get_request(null, true);
			foreach($data as $key => $value) {
				$this->cloud_config->set_value_by_key($key,$value);
			}

			/*
			$this->cloud_config->set_value_by_key('cloud_admin_email', $data['cloud_admin_email']);
			$this->cloud_config->set_value_by_key('auto_provision', $data['auto_provision']);
			$this->cloud_config->set_value_by_key('external_portal_url', $data['external_portal_url']);
			$this->cloud_config->set_value_by_key('request_physical_systems', $data['request_physical_systems']);
			$this->cloud_config->set_value_by_key('default_clone_on_deploy', $data['default_clone_on_deploy']);
			$this->cloud_config->set_value_by_key('max_resources_per_cr', 1);
			$this->cloud_config->set_value_by_key('auto_create_vms', $data['auto_create_vms']);
			$this->cloud_config->set_value_by_key('max_disk_size', $data['max_disk_size']);
			$this->cloud_config->set_value_by_key('max_network_interfaces', $data['max_network_interfaces']);
			$this->cloud_config->set_value_by_key('show_ha_checkbox', $data['show_ha_checkbox']);
			$this->cloud_config->set_value_by_key('show_puppet_groups', $data['show_puppet_groups']);
			$this->cloud_config->set_value_by_key('auto_give_ccus', $data['auto_give_ccus']);
			$this->cloud_config->set_value_by_key('max_apps_per_user', $data['max_apps_per_user']);
			$this->cloud_config->set_value_by_key('public_register_enabled', $data['public_register_enabled']);
			$this->cloud_config->set_value_by_key('cloud_enabled', $data['cloud_enabled']);
			$this->cloud_config->set_value_by_key('cloud_billing_enabled', $data['cloud_billing_enabled']);
			$this->cloud_config->set_value_by_key('show_sshterm_login', $data['show_sshterm_login']);
			$this->cloud_config->set_value_by_key('cloud_nat', $data['cloud_nat']);
			$this->cloud_config->set_value_by_key('show_collectd_graphs', $data['show_collectd_graphs']);
			$this->cloud_config->set_value_by_key('show_disk_resize', $data['show_disk_resize']);
			$this->cloud_config->set_value_by_key('show_private_image', $data['show_private_image']);
			$this->cloud_config->set_value_by_key('cloud_selector', $data['cloud_selector']);
			$this->cloud_config->set_value_by_key('cloud_currency', $data['cloud_currency']);
			$this->cloud_config->set_value_by_key('cloud_1000_ccus', $data['cloud_1000_ccus']);
			$this->cloud_config->set_value_by_key('resource_pooling', $data['resource_pooling']);
			$this->cloud_config->set_value_by_key('ip-management', $data['ip-management']);
			$this->cloud_config->set_value_by_key('max-parallel-phase-one-actions', $data['max-parallel-phase-one-actions']);
			$this->cloud_config->set_value_by_key('max-parallel-phase-two-actions', $data['max-parallel-phase-two-actions']);
			$this->cloud_config->set_value_by_key('max-parallel-phase-three-actions', $data['max-parallel-phase-three-actions']);
			$this->cloud_config->set_value_by_key('max-parallel-phase-four-actions', $data['max-parallel-phase-four-actions']);
			$this->cloud_config->set_value_by_key('max-parallel-phase-five-actions', $data['max-parallel-phase-five-actions']);
			$this->cloud_config->set_value_by_key('max-parallel-phase-six-actions', $data['max-parallel-phase-six-actions']);
			$this->cloud_config->set_value_by_key('max-parallel-phase-seven-actions', $data['max-parallel-phase-seven-actions']);
			$this->cloud_config->set_value_by_key('appliance_hostname', $data['appliance_hostname']);
			$this->cloud_config->set_value_by_key('cloud_zones_client', $data['cloud_zones_client']);
			$this->cloud_config->set_value_by_key('cloud_zones_master_ip', $data['cloud_zones_master_ip']);
			$this->cloud_config->set_value_by_key('cloud_external_ip', $data['cloud_external_ip']);
			$this->cloud_config->set_value_by_key('deprovision_warning', $data['deprovision_warning']);
			$this->cloud_config->set_value_by_key('deprovision_pause', $data['deprovision_pause']);
			$this->cloud_config->set_value_by_key('vm_provision_delay', $data['vm_provision_delay']);
			$this->cloud_config->set_value_by_key('vm_loadbalance_algorithm', $data['vm_loadbalance_algorithm']);
			$this->cloud_config->set_value_by_key('allow_vnc_access', $data['allow_vnc_access']);
			// success msg
			*/
			$response->msg = $this->lang['update_successfull'];
		} 
		else if($form->get_errors()) {
			$response->error = implode('<br>',$form->get_errors());
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
		$form = $response->get_form($this->actions_name, "update");

		$true_false_select[] = array("true");
		$true_false_select[] = array("false");

		$d = array();
		$d['cloud_admin_email']['label']                     = 'cloud_admin_email';
		$d['cloud_admin_email']['required']                  = true;
		$d['cloud_admin_email']['object']['type']            = 'htmlobject_input';
		$d['cloud_admin_email']['object']['attrib']['type']  = 'text';
		$d['cloud_admin_email']['object']['attrib']['id']    = 'cloud_admin_email';
		$d['cloud_admin_email']['object']['attrib']['name']  = 'cloud_admin_email';
		$d['cloud_admin_email']['object']['attrib']['title'] = $this->lang['cloud_admin_email'];
		$d['cloud_admin_email']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('cloud_admin_email');

		$d['auto_provision']['label']                          = 'auto_provision';
		$d['auto_provision']['object']['type']                 = 'htmlobject_select';
		$d['auto_provision']['object']['attrib']['index']      = array(0,0);
		$d['auto_provision']['object']['attrib']['id']         = 'auto_provision';
		$d['auto_provision']['object']['attrib']['name']       = 'auto_provision';
		$d['auto_provision']['object']['attrib']['title']      = $this->lang['auto_provision'];
		$d['auto_provision']['object']['attrib']['options']    = $true_false_select;
		$d['auto_provision']['object']['attrib']['selected']   = array($this->cloud_config->get_value_by_key('auto_provision'));

		$d['external_portal_url']['label']                     = 'external_portal_url';
		$d['external_portal_url']['required']                  = false;
		$d['external_portal_url']['validate']['regex']         = '';
		$d['external_portal_url']['validate']['errormsg']      = 'Url must be [a-z] only';
		$d['external_portal_url']['object']['type']            = 'htmlobject_input';
		$d['external_portal_url']['object']['attrib']['type']  = 'text';
		$d['external_portal_url']['object']['attrib']['id']    = 'external_portal_url';
		$d['external_portal_url']['object']['attrib']['name']  = 'external_portal_url';
		$d['external_portal_url']['object']['attrib']['title'] = $this->lang['external_portal_url'];
		$d['external_portal_url']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('external_portal_url');

		$d['request_physical_systems']['label']                        = 'request_physical_systems';
		$d['request_physical_systems']['object']['type']               = 'htmlobject_select';
		$d['request_physical_systems']['object']['attrib']['index']    = array(0,0);
		$d['request_physical_systems']['object']['attrib']['id']       = 'request_physical_systems';
		$d['request_physical_systems']['object']['attrib']['name']     = 'request_physical_systems';
		$d['request_physical_systems']['object']['attrib']['options']  = $true_false_select;
		$d['request_physical_systems']['object']['attrib']['title']    = $this->lang['request_physical_systems'];
		$d['request_physical_systems']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('request_physical_systems'));

		$d['default_clone_on_deploy']['label']                          = 'default_clone_on_deploy';
		$d['default_clone_on_deploy']['object']['type']                 = 'htmlobject_select';
		$d['default_clone_on_deploy']['object']['attrib']['index']      = array(0,0);
		$d['default_clone_on_deploy']['object']['attrib']['id']         = 'default_clone_on_deploy';
		$d['default_clone_on_deploy']['object']['attrib']['name']       = 'default_clone_on_deploy';
		$d['default_clone_on_deploy']['object']['attrib']['title']      = $this->lang['default_clone_on_deploy'];
		$d['default_clone_on_deploy']['object']['attrib']['options']    = $true_false_select;
		$d['default_clone_on_deploy']['object']['attrib']['selected']   = array($this->cloud_config->get_value_by_key('default_clone_on_deploy'));

		$d['max_resources_per_cr']['label']                        = 'max_resources_per_cr';
		$d['max_resources_per_cr']['static']                       = true;
		$d['max_resources_per_cr']['object']['type']               = 'htmlobject_input';
		$d['max_resources_per_cr']['object']['attrib']['type']     = 'text';
		$d['max_resources_per_cr']['object']['attrib']['id']       = 'max_resources_per_cr';
		$d['max_resources_per_cr']['object']['attrib']['name']     = 'max_resources_per_cr';
		$d['max_resources_per_cr']['object']['attrib']['title']    = $this->lang['max_resources_per_cr'];
		$d['max_resources_per_cr']['object']['attrib']['value']    = 1;
		$d['max_resources_per_cr']['object']['attrib']['disabled'] = true;

		$d['auto_create_vms']['label']                        = 'auto_create_vms';
		$d['auto_create_vms']['object']['type']               = 'htmlobject_select';
		$d['auto_create_vms']['object']['attrib']['index']    = array(0,0);
		$d['auto_create_vms']['object']['attrib']['id']       = 'auto_create_vms';
		$d['auto_create_vms']['object']['attrib']['name']     = 'auto_create_vms';
		$d['auto_create_vms']['object']['attrib']['title']    = $this->lang['auto_create_vms'];
		$d['auto_create_vms']['object']['attrib']['options']  = $true_false_select;
		$d['auto_create_vms']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('auto_create_vms'));

		$d['max_disk_size']['label']                     = 'max_disk_size';
		$d['max_disk_size']['required']                  = true;
		$d['max_disk_size']['validate']['regex']         = '/^[0-9]+$/i';
		$d['max_disk_size']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'max_disk_size');
		$d['max_disk_size']['object']['type']            = 'htmlobject_input';
		$d['max_disk_size']['object']['attrib']['id']    = 'max_disk_size';
		$d['max_disk_size']['object']['attrib']['name']  = 'max_disk_size';
		$d['max_disk_size']['object']['attrib']['title'] = $this->lang['max_disk_size'];
		$d['max_disk_size']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('max_disk_size');

		$d['max_memory']['label']                     = 'max_memory';
		$d['max_memory']['required']                  = true;
		$d['max_memory']['validate']['regex']         = '/^[0-9]+$/i';
		$d['max_memory']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'max_memory');
		$d['max_memory']['object']['type']            = 'htmlobject_input';
		$d['max_memory']['object']['attrib']['id']    = 'max_memory';
		$d['max_memory']['object']['attrib']['name']  = 'max_memory';
		$d['max_memory']['object']['attrib']['title'] = $this->lang['max_memory'];
		$d['max_memory']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('max_memory');

		$d['max_cpu']['label']                     = 'max_cpu';
		$d['max_cpu']['required']                  = true;
		$d['max_cpu']['validate']['regex']         = '/^[0-9]+$/i';
		$d['max_cpu']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'max_cpu');
		$d['max_cpu']['object']['type']            = 'htmlobject_input';
		$d['max_cpu']['object']['attrib']['id']    = 'max_cpu';
		$d['max_cpu']['object']['attrib']['name']  = 'max_cpu';
		$d['max_cpu']['object']['attrib']['title'] = $this->lang['max_cpu'];
		$d['max_cpu']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('max_cpu');

		$d['max_network']['label']                     = 'max_network';
		$d['max_network']['required']                  = true;
		$d['max_network']['validate']['regex']         = '/^[0-9]+$/i';
		$d['max_network']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'max_network');
		$d['max_network']['object']['type']            = 'htmlobject_input';
		$d['max_network']['object']['attrib']['id']    = 'max_network';
		$d['max_network']['object']['attrib']['name']  = 'max_network';
		$d['max_network']['object']['attrib']['title'] = $this->lang['max_network'];
		$d['max_network']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('max_network');

		$cloud_network_interfaces_select_arr[] = array("1");
		$cloud_network_interfaces_select_arr[] = array("2");
		$cloud_network_interfaces_select_arr[] = array("3");
		$cloud_network_interfaces_select_arr[] = array("4");

		$d['max_network_interfaces']['label']                        = 'max_network_interfaces';
		$d['max_network_interfaces']['object']['type']               = 'htmlobject_select';
		$d['max_network_interfaces']['object']['attrib']['index']    = array(0,0);
		$d['max_network_interfaces']['object']['attrib']['id']       = 'max_network_interfaces';
		$d['max_network_interfaces']['object']['attrib']['name']     = 'max_network_interfaces';
		$d['max_network_interfaces']['object']['attrib']['title']    = $this->lang['max_network_interfaces'];
		$d['max_network_interfaces']['object']['attrib']['options']  = $cloud_network_interfaces_select_arr;
		$d['max_network_interfaces']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('max_network_interfaces'));

		$d['show_ha_checkbox']['label']                        = 'show_ha_checkbox';
		$d['show_ha_checkbox']['object']['type']               = 'htmlobject_select';
		$d['show_ha_checkbox']['object']['attrib']['index']    = array(0,0);
		$d['show_ha_checkbox']['object']['attrib']['id']       = 'show_ha_checkbox';
		$d['show_ha_checkbox']['object']['attrib']['name']     = 'show_ha_checkbox';
		$d['show_ha_checkbox']['object']['attrib']['title']    = $this->lang['show_ha_checkbox'];
		$d['show_ha_checkbox']['object']['attrib']['options']  = $true_false_select;
		$d['show_ha_checkbox']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('show_ha_checkbox'));

		$d['show_puppet_groups']['label']                        = 'show_puppet_groups';
		$d['show_puppet_groups']['object']['type']               = 'htmlobject_select';
		$d['show_puppet_groups']['object']['attrib']['index']    = array(0,0);
		$d['show_puppet_groups']['object']['attrib']['id']       = 'show_puppet_groups';
		$d['show_puppet_groups']['object']['attrib']['name']     = 'show_puppet_groups';
		$d['show_puppet_groups']['object']['attrib']['title']    = $this->lang['show_puppet_groups'];
		$d['show_puppet_groups']['object']['attrib']['options']  = $true_false_select;
		$d['show_puppet_groups']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('show_puppet_groups'));

		$d['auto_give_ccus']['label']                     = 'auto_give_ccus';
		$d['auto_give_ccus']['validate']['regex']         = '/^[0-9]+$/i';
		$d['auto_give_ccus']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'auto_give_ccus');
		$d['auto_give_ccus']['object']['type']            = 'htmlobject_input';
		$d['auto_give_ccus']['object']['attrib']['id']    = 'auto_give_ccus';
		$d['auto_give_ccus']['object']['attrib']['name']  = 'auto_give_ccus';
		$d['auto_give_ccus']['object']['attrib']['title'] = $this->lang['auto_give_ccus'];
		$d['auto_give_ccus']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('auto_give_ccus');

		$d['max_apps_per_user']['label']                     = 'max_apps_per_user';
		$d['max_apps_per_user']['required']                  = true;
		$d['max_apps_per_user']['validate']['regex']         = '/^[0-9]+$/i';
		$d['max_apps_per_user']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'max_apps_per_user');
		$d['max_apps_per_user']['object']['type']            = 'htmlobject_input';
		$d['max_apps_per_user']['object']['attrib']['id']    = 'max_apps_per_user';
		$d['max_apps_per_user']['object']['attrib']['name']  = 'max_apps_per_user';
		$d['max_apps_per_user']['object']['attrib']['title'] = $this->lang['max_apps_per_user'];
		$d['max_apps_per_user']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('max_apps_per_user');

		$d['public_register_enabled']['label']                        = 'public_register_enabled';
		$d['public_register_enabled']['object']['type']               = 'htmlobject_select';
		$d['public_register_enabled']['object']['attrib']['index']    = array(0,0);
		$d['public_register_enabled']['object']['attrib']['id']       = 'public_register_enabled';
		$d['public_register_enabled']['object']['attrib']['name']     = 'public_register_enabled';
		$d['public_register_enabled']['object']['attrib']['title']    = $this->lang['public_register_enabled'];
		$d['public_register_enabled']['object']['attrib']['options']  = $true_false_select;
		$d['public_register_enabled']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('public_register_enabled'));

		$d['cloud_enabled']['label']                        = 'cloud_enabled';
		$d['cloud_enabled']['object']['type']               = 'htmlobject_select';
		$d['cloud_enabled']['object']['attrib']['index']    = array(0,0);
		$d['cloud_enabled']['object']['attrib']['id']       = 'cloud_enabled';
		$d['cloud_enabled']['object']['attrib']['name']     = 'cloud_enabled';
		$d['cloud_enabled']['object']['attrib']['title']    = $this->lang['cloud_enabled'];
		$d['cloud_enabled']['object']['attrib']['options']  = $true_false_select;
		$d['cloud_enabled']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('cloud_enabled'));

		$d['cloud_billing_enabled']['label']                        = 'cloud_billing_enabled';
		$d['cloud_billing_enabled']['object']['type']               = 'htmlobject_select';
		$d['cloud_billing_enabled']['object']['attrib']['index']    = array(0,0);
		$d['cloud_billing_enabled']['object']['attrib']['id']       = 'cloud_billing_enabled';
		$d['cloud_billing_enabled']['object']['attrib']['name']     = 'cloud_billing_enabled';
		$d['cloud_billing_enabled']['object']['attrib']['title']    = $this->lang['cloud_billing_enabled'];
		$d['cloud_billing_enabled']['object']['attrib']['options']  = $true_false_select;
		$d['cloud_billing_enabled']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('cloud_billing_enabled'));

		$d['show_sshterm_login']['label']                        = 'show_sshterm_login';
		$d['show_sshterm_login']['object']['type']               = 'htmlobject_select';
		$d['show_sshterm_login']['object']['attrib']['index']    = array(0,0);
		$d['show_sshterm_login']['object']['attrib']['id']       = 'show_sshterm_login';
		$d['show_sshterm_login']['object']['attrib']['name']     = 'show_sshterm_login';
		$d['show_sshterm_login']['object']['attrib']['title']    = $this->lang['show_sshterm_login'];
		$d['show_sshterm_login']['object']['attrib']['options']  = $true_false_select;
		$d['show_sshterm_login']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('show_sshterm_login'));

		$d['cloud_nat']['label']                        = 'cloud_nat';
		$d['cloud_nat']['object']['type']               = 'htmlobject_select';
		$d['cloud_nat']['object']['attrib']['index']    = array(0,0);
		$d['cloud_nat']['object']['attrib']['id']       = 'cloud_nat';
		$d['cloud_nat']['object']['attrib']['name']     = 'cloud_nat';
		$d['cloud_nat']['object']['attrib']['title']    = $this->lang['cloud_nat'];
		$d['cloud_nat']['object']['attrib']['options']  = $true_false_select;
		$d['cloud_nat']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('cloud_nat'));

		$d['show_collectd_graphs']['label']                        = 'show_collectd_graphs';
		$d['show_collectd_graphs']['object']['type']               = 'htmlobject_select';
		$d['show_collectd_graphs']['object']['attrib']['index']    = array(0,0);
		$d['show_collectd_graphs']['object']['attrib']['id']       = 'show_collectd_graphs';
		$d['show_collectd_graphs']['object']['attrib']['name']     = 'show_collectd_graphs';
		$d['show_collectd_graphs']['object']['attrib']['title']    = $this->lang['show_collectd_graphs'];
		$d['show_collectd_graphs']['object']['attrib']['options']  = $true_false_select;
		$d['show_collectd_graphs']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('show_collectd_graphs'));

		$d['show_disk_resize']['label']                        = 'show_disk_resize';
		$d['show_disk_resize']['object']['type']               = 'htmlobject_select';
		$d['show_disk_resize']['object']['attrib']['index']    = array(0,0);
		$d['show_disk_resize']['object']['attrib']['id']       = 'show_disk_resize';
		$d['show_disk_resize']['object']['attrib']['name']     = 'show_disk_resize';
		$d['show_disk_resize']['object']['attrib']['title']    = $this->lang['show_disk_resize'];
		$d['show_disk_resize']['object']['attrib']['options']  = $true_false_select;
		$d['show_disk_resize']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('show_disk_resize'));

		$d['show_private_image']['label']                        = 'show_private_image';
		$d['show_private_image']['object']['type']               = 'htmlobject_select';
		$d['show_private_image']['object']['attrib']['index']    = array(0,0);
		$d['show_private_image']['object']['attrib']['id']       = 'show_private_image';
		$d['show_private_image']['object']['attrib']['name']     = 'show_private_image';
		$d['show_private_image']['object']['attrib']['title']    = $this->lang['show_private_image'];
		$d['show_private_image']['object']['attrib']['options']  = $true_false_select;
		$d['show_private_image']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('show_private_image'));

		$d['cloud_selector']['label']                        = 'cloud_selector';
		$d['cloud_selector']['object']['type']               = 'htmlobject_select';
		$d['cloud_selector']['object']['attrib']['index']    = array(0,0);
		$d['cloud_selector']['object']['attrib']['id']       = 'cloud_selector';
		$d['cloud_selector']['object']['attrib']['name']     = 'cloud_selector';
		$d['cloud_selector']['object']['attrib']['title']    = $this->lang['cloud_selector'];
		$d['cloud_selector']['object']['attrib']['options']  = $true_false_select;
		$d['cloud_selector']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('cloud_selector'));

		$d['cloud_currency']['label']                         = 'cloud_currency';
		$d['cloud_currency']['object']['type']                = 'htmlobject_input';
		$d['cloud_currency']['object']['attrib']['id']        = 'cloud_currency';
		$d['cloud_currency']['object']['attrib']['name']      = 'cloud_currency';
		$d['cloud_currency']['object']['attrib']['title']     = $this->lang['cloud_currency'];
		$d['cloud_currency']['object']['attrib']['value']     = $this->cloud_config->get_value_by_key('cloud_currency');
		$d['cloud_currency']['object']['attrib']['maxlength'] = 5;

		$d['cloud_1000_ccus']['label']                     = 'cloud_1000_ccus';
		$d['cloud_1000_ccus']['validate']['regex']         = '/^[0-9]+$/i';
		$d['cloud_1000_ccus']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'cloud_1000_ccus');
		$d['cloud_1000_ccus']['object']['type']            = 'htmlobject_input';
		$d['cloud_1000_ccus']['object']['attrib']['id']    = 'cloud_1000_ccus';
		$d['cloud_1000_ccus']['object']['attrib']['name']  = 'cloud_1000_ccus';
		$d['cloud_1000_ccus']['object']['attrib']['title'] = $this->lang['cloud_1000_ccus'];
		$d['cloud_1000_ccus']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('cloud_1000_ccus');

		$d['resource_pooling']['label']                        = 'resource_pooling';
		$d['resource_pooling']['object']['type']               = 'htmlobject_select';
		$d['resource_pooling']['object']['attrib']['index']    = array(0,0);
		$d['resource_pooling']['object']['attrib']['id']       = 'resource_pooling';
		$d['resource_pooling']['object']['attrib']['name']     = 'resource_pooling';
		$d['resource_pooling']['object']['attrib']['title']    = $this->lang['resource_pooling'];
		$d['resource_pooling']['object']['attrib']['options']  = $true_false_select;
		$d['resource_pooling']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('resource_pooling'));

		$d['ip-management']['label']                        = 'ip-management';
		$d['ip-management']['object']['type']               = 'htmlobject_select';
		$d['ip-management']['object']['attrib']['index']    = array(0,0);
		$d['ip-management']['object']['attrib']['id']       = 'ip-management';
		$d['ip-management']['object']['attrib']['name']     = 'ip-management';
		$d['ip-management']['object']['attrib']['title']    = $this->lang['ip-management'];
		$d['ip-management']['object']['attrib']['options']  = $true_false_select;
		$d['ip-management']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('ip-management'));

		$cloud_actions_per_phase_arr[] = array("0");
		$cloud_actions_per_phase_arr[] = array("1");
		$cloud_actions_per_phase_arr[] = array("2");
		$cloud_actions_per_phase_arr[] = array("3");
		$cloud_actions_per_phase_arr[] = array("4");
		$cloud_actions_per_phase_arr[] = array("5");
		$cloud_actions_per_phase_arr[] = array("6");
		$cloud_actions_per_phase_arr[] = array("7");
		$cloud_actions_per_phase_arr[] = array("8");
		$cloud_actions_per_phase_arr[] = array("9");
		$cloud_actions_per_phase_arr[] = array("10");

		$d['max-parallel-phase-one-actions']['label']                        = 'max-parallel-phase-one-actions';
		$d['max-parallel-phase-one-actions']['object']['type']               = 'htmlobject_select';
		$d['max-parallel-phase-one-actions']['object']['attrib']['index']    = array(0,0);
		$d['max-parallel-phase-one-actions']['object']['attrib']['id']       = 'max-parallel-phase-one-actions';
		$d['max-parallel-phase-one-actions']['object']['attrib']['name']     = 'max-parallel-phase-one-actions';
		$d['max-parallel-phase-one-actions']['object']['attrib']['title']    = $this->lang['max_parallel_phase_one_actions'];
		$d['max-parallel-phase-one-actions']['object']['attrib']['options']  = $cloud_actions_per_phase_arr;
		$d['max-parallel-phase-one-actions']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('max-parallel-phase-one-actions'));

		$d['max-parallel-phase-two-actions']['label']                        = 'max-parallel-phase-two-actions';
		$d['max-parallel-phase-two-actions']['object']['type']               = 'htmlobject_select';
		$d['max-parallel-phase-two-actions']['object']['attrib']['index']    = array(0,0);
		$d['max-parallel-phase-two-actions']['object']['attrib']['id']       = 'max-parallel-phase-two-actions';
		$d['max-parallel-phase-two-actions']['object']['attrib']['name']     = 'max-parallel-phase-two-actions';
		$d['max-parallel-phase-two-actions']['object']['attrib']['title']    = $this->lang['max_parallel_phase_two_actions'];
		$d['max-parallel-phase-two-actions']['object']['attrib']['options']  = $cloud_actions_per_phase_arr;
		$d['max-parallel-phase-two-actions']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('max-parallel-phase-two-actions'));

		$d['max-parallel-phase-three-actions']['label']                        = 'max-parallel-phase-three-actions';
		$d['max-parallel-phase-three-actions']['object']['type']               = 'htmlobject_select';
		$d['max-parallel-phase-three-actions']['object']['attrib']['index']    = array(0,0);
		$d['max-parallel-phase-three-actions']['object']['attrib']['id']       = 'max-parallel-phase-three-actions';
		$d['max-parallel-phase-three-actions']['object']['attrib']['name']     = 'max-parallel-phase-three-actions';
		$d['max-parallel-phase-three-actions']['object']['attrib']['title']    = $this->lang['max_parallel_phase_three_actions'];
		$d['max-parallel-phase-three-actions']['object']['attrib']['options']  = $cloud_actions_per_phase_arr;
		$d['max-parallel-phase-three-actions']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('max-parallel-phase-three-actions'));

		$d['max-parallel-phase-four-actions']['label']                        = 'max-parallel-phase-four-actions';
		$d['max-parallel-phase-four-actions']['object']['type']               = 'htmlobject_select';
		$d['max-parallel-phase-four-actions']['object']['attrib']['index']    = array(0,0);
		$d['max-parallel-phase-four-actions']['object']['attrib']['id']       = 'max-parallel-phase-four-actions';
		$d['max-parallel-phase-four-actions']['object']['attrib']['name']     = 'max-parallel-phase-four-actions';
		$d['max-parallel-phase-four-actions']['object']['attrib']['title']    = $this->lang['max_parallel_phase_four_actions'];
		$d['max-parallel-phase-four-actions']['object']['attrib']['options']  = $cloud_actions_per_phase_arr;
		$d['max-parallel-phase-four-actions']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('max-parallel-phase-four-actions'));

		$d['max-parallel-phase-five-actions']['label']                        = 'max-parallel-phase-five-actions';
		$d['max-parallel-phase-five-actions']['object']['type']               = 'htmlobject_select';
		$d['max-parallel-phase-five-actions']['object']['attrib']['index']    = array(0,0);
		$d['max-parallel-phase-five-actions']['object']['attrib']['id']       = 'max-parallel-phase-five-actions';
		$d['max-parallel-phase-five-actions']['object']['attrib']['name']     = 'max-parallel-phase-five-actions';
		$d['max-parallel-phase-five-actions']['object']['attrib']['title']    = $this->lang['max_parallel_phase_five_actions'];
		$d['max-parallel-phase-five-actions']['object']['attrib']['options']  = $cloud_actions_per_phase_arr;
		$d['max-parallel-phase-five-actions']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('max-parallel-phase-five-actions'));

		$d['max-parallel-phase-six-actions']['label']                        = 'max-parallel-phase-six-actions';
		$d['max-parallel-phase-six-actions']['object']['type']               = 'htmlobject_select';
		$d['max-parallel-phase-six-actions']['object']['attrib']['index']    = array(0,0);
		$d['max-parallel-phase-six-actions']['object']['attrib']['id']       = 'max-parallel-phase-six-actions';
		$d['max-parallel-phase-six-actions']['object']['attrib']['name']     = 'max-parallel-phase-six-actions';
		$d['max-parallel-phase-six-actions']['object']['attrib']['title']    = $this->lang['max_parallel_phase_six_actions'];
		$d['max-parallel-phase-six-actions']['object']['attrib']['options']  = $cloud_actions_per_phase_arr;
		$d['max-parallel-phase-six-actions']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('max-parallel-phase-six-actions'));

		$d['max-parallel-phase-seven-actions']['label']                        = 'max-parallel-phase-seven-actions';
		$d['max-parallel-phase-seven-actions']['object']['type']               = 'htmlobject_select';
		$d['max-parallel-phase-seven-actions']['object']['attrib']['index']    = array(0,0);
		$d['max-parallel-phase-seven-actions']['object']['attrib']['id']       = 'max-parallel-phase-seven-actions';
		$d['max-parallel-phase-seven-actions']['object']['attrib']['name']     = 'max-parallel-phase-seven-actions';
		$d['max-parallel-phase-seven-actions']['object']['attrib']['title']    = $this->lang['max_parallel_phase_seven_actions'];
		$d['max-parallel-phase-seven-actions']['object']['attrib']['options']  = $cloud_actions_per_phase_arr;
		$d['max-parallel-phase-seven-actions']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('max-parallel-phase-seven-actions'));

		$d['appliance_hostname']['label']                        = 'appliance_hostname';
		$d['appliance_hostname']['object']['type']               = 'htmlobject_select';
		$d['appliance_hostname']['object']['attrib']['index']    = array(0,0);
		$d['appliance_hostname']['object']['attrib']['id']       = 'appliance_hostname';
		$d['appliance_hostname']['object']['attrib']['name']     = 'appliance_hostname';
		$d['appliance_hostname']['object']['attrib']['title']    = $this->lang['appliance_hostname'];
		$d['appliance_hostname']['object']['attrib']['options']  = $true_false_select;
		$d['appliance_hostname']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('appliance_hostname'));

		$d['cloud_zones_client']['label']                        = 'cloud_zones_client';
		$d['cloud_zones_client']['object']['type']               = 'htmlobject_select';
		$d['cloud_zones_client']['object']['attrib']['index']    = array(0,0);
		$d['cloud_zones_client']['object']['attrib']['id']       = 'cloud_zones_client';
		$d['cloud_zones_client']['object']['attrib']['name']     = 'cloud_zones_client';
		$d['cloud_zones_client']['object']['attrib']['title']    = $this->lang['cloud_zones_client'];
		$d['cloud_zones_client']['object']['attrib']['options']  = $true_false_select;
		$d['cloud_zones_client']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('cloud_zones_client'));

		$d['cloud_zones_master_ip']['label']                     = 'cloud_zones_master_ip';
		$d['cloud_zones_master_ip']['object']['type']            = 'htmlobject_input';
		$d['cloud_zones_master_ip']['object']['attrib']['id']    = 'cloud_zones_master_ip';
		$d['cloud_zones_master_ip']['object']['attrib']['name']  = 'cloud_zones_master_ip';
		$d['cloud_zones_master_ip']['object']['attrib']['title'] = $this->lang['cloud_zones_master_ip'];
		$d['cloud_zones_master_ip']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('cloud_zones_master_ip');

		$d['cloud_external_ip']['label']                     = 'cloud_external_ip';
		$d['cloud_external_ip']['object']['type']            = 'htmlobject_input';
		$d['cloud_external_ip']['object']['attrib']['type']  = 'text';
		$d['cloud_external_ip']['object']['attrib']['id']    = 'cloud_external_ip';
		$d['cloud_external_ip']['object']['attrib']['name']  = 'cloud_external_ip';
		$d['cloud_external_ip']['object']['attrib']['title'] = $this->lang['cloud_external_ip'];
		$d['cloud_external_ip']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('cloud_external_ip');

		#$deprovision_action_ccu_arr[] = array("value" => "0", "label" => "0");
		#$deprovision_action_ccu_arr[] = array("value" => "50", "label" => "50");
		#$deprovision_action_ccu_arr[] = array("value" => "100", "label" => "100");
		#$deprovision_action_ccu_arr[] = array("value" => "200", "label" => "200");
		#$deprovision_action_ccu_arr[] = array("value" => "500", "label" => "500");
		#$deprovision_action_ccu_arr[] = array("value" => "1000", "label" => "1000");

		$d['deprovision_warning']['label']                     = 'deprovision_warning';
		$d['deprovision_warning']['validate']['regex']         = '/^[0-9]+$/i';
		$d['deprovision_warning']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'deprovision_warning');
		$d['deprovision_warning']['object']['type']            = 'htmlobject_input';
		$d['deprovision_warning']['object']['attrib']['id']    = 'deprovision_warning';
		$d['deprovision_warning']['object']['attrib']['name']  = 'deprovision_warning';
		$d['deprovision_warning']['object']['attrib']['title'] = $this->lang['deprovision_warning'];
		$d['deprovision_warning']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('deprovision_warning');

		$d['deprovision_pause']['label']                     = 'deprovision_pause';
		$d['deprovision_pause']['validate']['regex']         = '/^[0-9]+$/i';
		$d['deprovision_pause']['validate']['errormsg']      = sprintf($this->lang['error_NAN'], 'deprovision_pause');
		$d['deprovision_pause']['object']['type']            = 'htmlobject_input';
		$d['deprovision_pause']['object']['attrib']['id']    = 'deprovision_pause';
		$d['deprovision_pause']['object']['attrib']['name']  = 'deprovision_pause';
		$d['deprovision_pause']['object']['attrib']['title'] = $this->lang['deprovision_pause'];
		$d['deprovision_pause']['object']['attrib']['value'] = $this->cloud_config->get_value_by_key('deprovision_pause');

		$vm_provision_delay_arr[] = array("value" => "0", "label" => "0");
		$vm_provision_delay_arr[] = array("value" => "5", "label" => "5");
		$vm_provision_delay_arr[] = array("value" => "10", "label" => "10");
		$vm_provision_delay_arr[] = array("value" => "20", "label" => "20");
		$vm_provision_delay_arr[] = array("value" => "30", "label" => "30");
		$vm_provision_delay_arr[] = array("value" => "60", "label" => "60");

		$d['vm_provision_delay']['label']                        = 'vm_provision_delay';
		$d['vm_provision_delay']['object']['type']               = 'htmlobject_select';
		$d['vm_provision_delay']['object']['attrib']['index']    = array('value', 'label');
		$d['vm_provision_delay']['object']['attrib']['id']       = 'vm_provision_delay';
		$d['vm_provision_delay']['object']['attrib']['name']     = 'vm_provision_delay';
		$d['vm_provision_delay']['object']['attrib']['title']    = $this->lang['vm_provision_delay'];
		$d['vm_provision_delay']['object']['attrib']['options']  = $vm_provision_delay_arr;
		$d['vm_provision_delay']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('vm_provision_delay'));

		$vm_loadbalance_algorithm_arr[] = array("value" => "0", "label" => "0");
		$vm_loadbalance_algorithm_arr[] = array("value" => "1", "label" => "1");
		$vm_loadbalance_algorithm_arr[] = array("value" => "2", "label" => "2");
		$vm_loadbalance_algorithm_arr[] = array("value" => "3", "label" => "3");

		$d['vm_loadbalance_algorithm']['label']                        = 'vm_loadbalance_algorithm';
		$d['vm_loadbalance_algorithm']['object']['type']               = 'htmlobject_select';
		$d['vm_loadbalance_algorithm']['object']['attrib']['index']    = array('value', 'label');
		$d['vm_loadbalance_algorithm']['object']['attrib']['id']       = 'vm_loadbalance_algorithm';
		$d['vm_loadbalance_algorithm']['object']['attrib']['name']     = 'vm_loadbalance_algorithm';
		$d['vm_loadbalance_algorithm']['object']['attrib']['title']    = $this->lang['vm_loadbalance_algorithm'];
		$d['vm_loadbalance_algorithm']['object']['attrib']['options']  = $vm_loadbalance_algorithm_arr;
		$d['vm_loadbalance_algorithm']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('vm_loadbalance_algorithm'));

		$d['allow_vnc_access']['label']                        = 'allow_vnc_access';
		$d['allow_vnc_access']['object']['type']               = 'htmlobject_select';
		$d['allow_vnc_access']['object']['attrib']['index']    = array(0,0);
		$d['allow_vnc_access']['object']['attrib']['id']       = 'allow_vnc_access';
		$d['allow_vnc_access']['object']['attrib']['name']     = 'allow_vnc_access';
		$d['allow_vnc_access']['object']['attrib']['title']    = $this->lang['allow_vnc_access'];
		$d['allow_vnc_access']['object']['attrib']['options']  = $true_false_select;
		$d['allow_vnc_access']['object']['attrib']['selected'] = array($this->cloud_config->get_value_by_key('allow_vnc_access'));

		$form->add($d);
		$response->form = $form;
		$response->form->display_errors = false;
		return $response;
	}

}
?>
