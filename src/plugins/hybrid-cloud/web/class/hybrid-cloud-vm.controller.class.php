<?php
/**
 * hybrid_cloud Instance Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_vm_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_vm_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_vm_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_vm_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_vm_identifier';
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
	'select' => array(
		'tab' => 'Hybrid-Cloud',
	),
	'edit' => array(
		'tab' => 'Instances List',
		'label' => 'Instances for account  %s',
		'table_host' => 'Host',
		'table_id' => 'ID',
		'table_ami' => 'AMI',
		'table_type' => 'Type',
		'table_state' => 'State',
		'table_name' => 'Name',
		'table_region' => 'Region',
		'table_public_ip' => 'Public Ip',
		'table_private_ip' => 'Private IP',
		'table_virt_type' => 'Virtualization',
		'table_version' => 'Version',
		'table_deployment' => 'Deployment',
		'table_isactive' => 'Active',
		'table_comment' => 'Comment',
		'table_image' => 'Image',
		'action_add_local_vm' => 'Add Instance',
		'action_remove_vm' => 'Remove Instance',
		'action_export_instance' => 'Export',
		'action_import_instance' => 'Import',
		'action_import_instance_title' => 'Import innstance configuration to openQRM',
		'error_name' => 'Name may contain %s only',
		'msg_select_account' => 'Please select a Cloud Account!',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'add' => array (
		'tab' => 'Add Instance',
		'label' => 'Add new Instance',
		'lang_basic' => 'Basic',
		'lang_hardware' => 'Hardware',
		'lang_name_generate' => 'generate name',
		'lang_notice' => '<h4>Please notice:</h4>Type depends on the choosen AMI Image. Not all types are supported.<br>Make sure that Availability Zone and Subnets Availability Zone match. The new Instance will use the Subnets default Security Group.',
		'form_name' => 'Name',
		'form_instance_type' => 'Type',
		'form_availability_zone' => 'Availability Zone',
		'form_keypair' => 'Keypair',
		'form_security_group' => 'Security Group',
		'form_ami' => 'AMI Image',
		'form_subnet' => 'Subnet',
		'form_add_volume' => 'Add new AMI Image',
		'form_add_networks' => 'Add new Instance Networks/Bridges',
		'form_custom_script' => 'Configuration script',
		'form_custom_script_title' => 'URL to custom configuration script',
		'msg_added' => 'Added Instance %s',
		'error_exists' => 'Instance %s already exists',
		'error_name' => 'Name must be %s',
		'error_memory' => 'Memory must be %s',
		'error_mac' => 'Mac is not valid',
		'error_bridge' => 'Bridge is not valid',
		'error_nic' => 'Nic is not valid',
		'error_boot' => 'Please select an AMI Image for the Instance',
		'error_iso_path' => 'Path must not be empty',
		'error_vnc_password' => 'Password (repeat) does not match Password',
		'error_vnc_password_count' => 'Password must have at least 6 chars',
		'please_wait' => 'Adding Instance. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'remove' => array (
		'tab' => 'Remove Instance',
		'label' => 'Remove Instance',
		'msg_removed' => 'Removed Instance %s',
		'msg_vm_resource_still_in_use' => 'Instance %s resource ID %s is still in use by Server %s',
		'please_wait' => 'Removing Instance. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'import' => array (
		'tab' => 'Import Instance',
		'label' => 'Import Instance',
		'msg_imported' => 'Imported Instance %s',
		'please_wait' => 'Importing Instance. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),

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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/hybrid-cloud/lang", 'hybrid-cloud-vm.ini');
		$this->tpldir   = $this->rootdir.'/plugins/hybrid-cloud/tpl';
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
			$this->action = 'edit';
		}
		if($this->action === '') {
			$this->action = 'edit';
		}
		// handle response
		if($this->response->html->request()->get('hybrid_cloud_id') == '') {
			$this->response->redirect('/openqrm/base/index.php?plugin=hybrid-cloud&hybrid_cloud_msg='.$this->lang['edit']['msg_select_account']);
		}
		$this->response->add('hybrid_cloud_id', $this->response->html->request()->get('hybrid_cloud_id'));
		// make sure region is set before any action
		$region_select = $this->__region_select().'<div class="floatbreaker">&#160;</div>';


		$content = array();
		// handle backtab
		$r = $this->response->get_array('hybrid_cloud_action', 'select' );
		$r['controller'] = 'hybrid-cloud';
		$content[0]['label']   = $this->lang['select']['tab'];
		$content[0]['value']   = '';
		$content[0]['target']  = $this->response->html->thisfile;
		$content[0]['request'] = $r;
		$content[0]['onclick'] = false;

		switch( $this->action ) {
			case '':
			default:
			case 'edit':
				$content[] = $this->edit(true);
			break;
			case 'add':
				$content[] = $this->edit(false);
				$content[] = $this->add(true);
			break;
			case 'remove':
				$content[] = $this->edit(false);
				$content[] = $this->remove(true);
			break;
			case 'import':
				$content[] = $this->edit(false);
				$content[] = $this->import(true);
			break;


		}
		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		$tab->custom_tab = $region_select;
		return $tab;
	}


	//--------------------------------------------
	/**
	 * API
	 *
	 * @access public
	 */
	//--------------------------------------------
	function api() {
		require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.api.class.php');
		$controller = new hybrid_cloud_api($this);
		$controller->action();
	}

	//--------------------------------------------
	/**
	 * List Instances
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function edit( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-vm.edit.class.php');
			$controller = new hybrid_cloud_vm_edit($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['edit'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['edit']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'edit' );
		$content['onclick'] = false;
		if($this->action === 'edit'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Add Instance
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function add( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			if($this->__reload_configuration()) {
				require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-vm.add.class.php');
				$controller = new hybrid_cloud_vm_add($this->openqrm, $this->response);
				$controller->actions_name  = $this->actions_name;
				$controller->tpldir        = $this->tpldir;
				$controller->prefix_tab    = $this->prefix_tab;
				$controller->lang          = $this->lang['add'];
				$data = $controller->action();
			}
		}
		$content['label']   = $this->lang['add']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'add' );
		$content['onclick'] = false;
		if($this->action === 'add'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Remove Instance
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-vm.remove.class.php');
			$controller = new hybrid_cloud_vm_remove($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['remove'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['remove']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'remove' );
		$content['onclick'] = false;
		if($this->action === 'remove'){
			$content['active']  = true;
		}
		return $content;
	}

	

	//--------------------------------------------
	/**
	 * Import Instance
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function import( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-vm.import.class.php');
			$controller = new hybrid_cloud_vm_import($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['import'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['import']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'import' );
		$content['onclick'] = false;
		if($this->action === 'import'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Reload Configuration
	 *
	 * @access public
	 */
	//--------------------------------------------
	function __reload_configuration() {
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->response->html->request()->get('hybrid_cloud_id'));

		$hc_authentication = '';
		if (($hc->account_type == 'aws') || ($hc->account_type == 'euca')) {
			$hc_authentication .= ' -O '.$hc->access_key;
			$hc_authentication .= ' -W '.$hc->secret_key;
			$hc_authentication .= ' -ir '.$this->response->html->request()->get('region');
		}
		if ($hc->account_type == 'lc-openstack') {
			$hc_authentication .= ' -u '.$hc->username;
			$hc_authentication .= ' -p '.$hc->password;
			$hc_authentication .= ' -q '.$hc->host;
			$hc_authentication .= ' -x '.$hc->port;
			$hc_authentication .= ' -g '.$hc->tenant;
			$hc_authentication .= ' -e '.$hc->endpoint;
		}


		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm configuration';
		$command .= ' -i '.$hc->id;
		$command .= ' -n '.$hc->account_name;
		$command .= ' -t '.$hc->account_type;
		$command .= $hc_authentication;
		$command .= ' --openqrm-ui-user '.$this->user->name;
		$command .= ' --openqrm-cmd-mode background';

		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$hc->id.'.describe_configuration.log';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}
		$openqrm = new openqrm_server();
		$openqrm->send_command($command, NULL, true);
		while (!$this->file->exists($file)) // check if the data file has been modified
		{
		  usleep(10000); // sleep 10ms to unload the CPU
		  clearstatcache();
		}
		return true;
	}

	//--------------------------------------------
	/**
	 * Region select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function __region_select() {
		$response = $this->response;
		$form = $response->get_form($this->actions_name, $this->action);

		$hybrid_cloud_conf = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/etc/openqrm-plugin-hybrid-cloud.conf';
		$hybrid_cloud_conf_arr = openqrm_parse_conf($hybrid_cloud_conf);
		$region_arr = explode(",", $hybrid_cloud_conf_arr['OPENQRM_PLUGIN_HYBRID_CLOUD_REGIONS']);
		$regions = array();
		foreach ($region_arr as $region) {
			$region = trim($region);
			$regions[] = array($region);
		}

		$region = $response->html->request()->get('region');
		if($region === '' && count($regions) > 0) {
			$region = $regions[0][0];
			$_REQUEST['region'] = $region;
		}
		$this->response->add('region', $region);	

		$d['region']['label']                        = '';
		$d['region']['object']['type']               = 'htmlobject_select';
		$d['region']['object']['attrib']['id']       = 'region';
		$d['region']['object']['attrib']['name']     = 'region';
		$d['region']['object']['attrib']['css']      = 'region';
		$d['region']['object']['attrib']['handler']  = 'onchange="form.submit(); return false;"';
		$d['region']['object']['attrib']['index']    = array(0,0);
		$d['region']['object']['attrib']['options']  = $regions;
		$d['region']['object']['attrib']['selected'] = array($region);

		$form->add($d);
		
		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->style = 'display:none;';
		$form->add($submit, 'cancel');
		
		return $form->get_string();
	}
	
}
?>
