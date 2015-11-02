<?php
/**
 * hybrid_cloud Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_identifier';
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
		'label' => 'Select account',
		'table_id' => 'ID',
		'table_name' => 'Name',
		'table_type' => 'Type',
		'table_access_key' => 'AWS Access Key ID',
		'table_secret_key' => 'AWS Secret Access Key',
		'table_description' => 'Description',
		'action_add' => 'Add new account',
		'action_remove' => 'remove',
		'action_import' => 'import',
		'action_export' => 'export',
		'action_ami' => 'AMIs',
		'action_s3' => 'S3',
		'action_ec2console' => 'EC2 Console',
		'action_dashboard' => 'Dashboard',
		'action_volumes' => 'Volumes',
		'action_snapshots' => 'Snapshots',
		'action_instance' => 'Instances',
		'action_keypair' => 'Keypairs',
		'action_group' => 'Groups',
		'action_edit' => 'edit',
		'please_wait' => 'Loading. Please wait ..'
	),
	'add' => array(
		'tab' => 'Add new account',
		'label' => 'Add new account',
		'form_name' => 'Account name',
		'form_type' => 'Account type',
		'form_access_key' => 'AWS Access Key ID',
		'form_secret_key' => 'AWS Secret Access Key',
		'form_username' => 'Username',
		'form_password' => 'Password',
		'form_tenant' => 'Tenant',
		'form_host' => 'Host IP',
		'form_port' => 'Auth port',
		'form_endpoint' => 'API endpoint',
		'form_description' => 'Description',
		'error_name' => 'Name must be %s only',
		'msg_added' => 'added account %s',
		'msg_add_fail' => 'Could not add Cloud Account %s! The check for the given configuration failed!',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'edit' => array(
		'tab' => 'Update account',
		'label_edit' => 'Update account %s',
		'label_help' => 'Help',
		'form_type' => 'Account type',
		'form_access_key' => 'AWS Access Key ID',
		'form_secret_key' => 'AWS Secret Access Key',
		'form_username' => 'Username',
		'form_password' => 'Password',
		'form_tenant' => 'Tenant',
		'form_host' => 'Host IP',
		'form_port' => 'Auth port',
		'form_endpoint' => 'API endpoint',
		'form_description' => 'Description',
		'lang_browse' => 'browse',
		'lang_browser' => 'Filepicker',
		'lang_help' => '',
		'lang_help_link' => '',
		'msg_updated' => 'updated account %s',
		'msg_update_fail' => 'Could not update Cloud Account %s! The check for the given configuration failed!',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'remove' => array(
		'tab' => 'Remove account(s)',
		'label_remove' => 'Remove account(s)',
		'msg_removed' => 'removed account %s',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'export' => array(
		'tab' => 'Hybrid-Cloud Export',
		'label' => 'Select an image to export to %s',
		'table_name' => 'Name',
		'table_id' => 'ID',
		'table_version' => 'Version',
		'table_deployment' => 'Deployment',
		'table_isactive' => 'Active',
		'table_comment' => 'Comment',
		'table_name' => 'Name',
		'table_type' => 'Type',
		'table_image' => 'Image',
		'action_export' => 'export',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'extarget' => array(
		'tab' =>  'Export image',
		'label_target' => 'Export image %s to %s',
		'form_name' => 'Name',
		'form_size' => 'Size',
		'form_architecture' => 'Architecture',
		'form_public_key_file' => 'RSA public key certificate file',
		'form_private_key_file' => 'RSA private key certificate file',
		'form_user_id' => 'EC2 User ID/AWS account number',
		'action_export' => 'export',
		'error_name' => 'Name may contain %s only',
		'lang_browse' => 'browse',
		'lang_browser' => 'Filepicker',
		'msg_exported' => 'exporting image %s to account %s',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..'
	),
	'import' => array(
		'tab' => 'Hybrid-Cloud Import',
		'label' => 'Select an instance to import from %s',
		'table_host' => 'Host',
		'table_id' => 'ID',
		'table_ami' => 'AMI',
		'table_type' => 'Type',
		'table_state' => 'State',
		'table_name' => 'Name',
		'table_region' => 'Region',
		'table_public_ip' => 'Public IP',
		'table_private_ip' => 'Private IP',
		'table_virt_type' => 'Virtualization',
		'table_version' => 'Version',
		'table_deployment' => 'Deployment',
		'table_isactive' => 'Active',
		'table_comment' => 'Comment',
		'table_image' => 'Image',
		'action_import' => 'import',
		'error_name' => 'Name may contain %s only',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'imtarget' => array(
		'tab' => 'Import image',
		'table_id' => 'ID',
		'table_name' => 'Name',
		'table_version' => 'Version',
		'table_isactive' => 'Active',
		'table_comment' => 'Comment',
		'table_deployment' => 'Deployment',
		'label_target' => 'Select an image to import instance %s from %s',
		'action_import' => 'import',
		'error_name' => 'Name may contain %s only',
		'msg_imported' => 'Importing instance %s from account %s to image %s',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'imparams' => array(
		'tab' => 'Import Parameter',
		'action_import' => 'import',
		'label_target' => 'Additional Paramaters to import instance %s from %s',
		'lang_browse' => 'browse',
		'lang_browser' => 'Filepicker',
		'form_ssh_key_file' => 'Private Key file for Keypair %s',
		'msg_imported' => 'Importing instance %s from account %s to image %s',
		'please_wait' => 'Loading. Please wait ..',
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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/hybrid-cloud/lang", 'hybrid-cloud.ini');
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
			$this->action = 'select';
		}
		// handle response
		if($this->action !== 'select' && $this->action !== 'add') {
			$this->response->add('hybrid_cloud_id', $this->response->html->request()->get('hybrid_cloud_id'));
		}

		// make sure region is set before any action
		$region_select = $this->__region_select().'<div class="floatbreaker">&#160;</div>';

		$content = array();
		switch( $this->action ) {
			case '':
			default:
			case 'select':
				$content[] = $this->select(true);
			break;
			case 'add':
				$content[] = $this->select(false);
				$content[] = $this->add(true);
			break;
			case 'edit':
				$content[] = $this->select(false);
				$content[] = $this->edit(true);
			break;
			case 'remove':
				$content[] = $this->select(false);
				$content[] = $this->remove(true);
			break;
			case 'export':
				$content[] = $this->select(false);
				$content[] = $this->export(true);
			break;
			case 'extarget':
				$content[] = $this->select(false);
				$content[] = $this->export(false);
				$content[] = $this->extarget(true);
			break;
			case 'import':
				$content[] = $this->select(false);
				$content[] = $this->import(true);
			break;
			case 'imtarget':
				$content[] = $this->select(false);
				$content[] = $this->imtarget(true);
			break;
			case 'imparams':
				$content[] = $this->select(false);
				$content[] = $this->imparams(true);
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
	 * Select Account
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.select.class.php');
			$controller = new hybrid_cloud_select($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['select'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['select']['tab'];
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
	 * Add Account
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function add( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.add.class.php');
			$controller = new hybrid_cloud_add($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['add'];
			$data = $controller->action();
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
	 * Edit Account
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function edit( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.edit.class.php');
			$controller = new hybrid_cloud_edit($this->openqrm, $this->response);
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
	 * Remove Account
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.remove.class.php');
			$controller = new hybrid_cloud_remove($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['remove'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['remove']['tab'];
		$content['value']   = $data;
		$content['hidden']  = true;
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
	 * Select Export
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function export( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.export.class.php');
			$controller = new hybrid_cloud_export($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang['export'];
			$controller->prefix_tab    = $this->prefix_tab;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['export']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'export' );
		$content['onclick'] = false;
		if($this->action === 'export'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Export image as AMI
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function extarget( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.extarget.class.php');
			$controller = new hybrid_cloud_extarget($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang['extarget'];
			$controller->prefix_tab    = $this->prefix_tab;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['extarget']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'extarget' );
		$content['onclick'] = false;
		if($this->action === 'extarget'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Select Import
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function import( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.import.class.php');
			$controller = new hybrid_cloud_import($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->message_param = $this->message_param;
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
	 * Import instance AMI
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function imtarget( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.imtarget.class.php');
			$controller = new hybrid_cloud_imtarget($this->openqrm, $this->response, $this);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang['imtarget'];
			$controller->prefix_tab    = $this->prefix_tab;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['imtarget']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'imtarget' );
		$content['onclick'] = false;
		if($this->action === 'imtarget'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Import Parameter
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function imparams( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.imparams.class.php');
			$controller = new hybrid_cloud_imparams($this->openqrm, $this->response, $this);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang['imparams'];
			$controller->prefix_tab    = $this->prefix_tab;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['imparams']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'imparams' );
		$content['onclick'] = false;
		if($this->action === 'imparams'){
			$content['active']  = true;
		}
		return $content;
	}


	
	//--------------------------------------------
	/**
	 * Region select tab
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
