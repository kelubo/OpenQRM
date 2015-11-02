<?php
/**
 * hybrid_cloud Keypair Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_keypair_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_keypair_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_keypair_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_keypair_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_keypair_identifier';
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
		'tab' => 'Keypairs List',
		'label' => 'Keypairs for account  %s',
		'table_name' => 'Name',
		'table_public_key' => 'Public Key',
		'action_add_keypair' => 'Add Keypair',
		'action_remove_keypair' => 'Remove Keypair',
		'error_name' => 'Name may contain %s only',
		'msg_select_account' => 'Please select a Cloud Account!',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'add' => array (
		'tab' => 'Add Keypair',
		'label' => 'Add new Keypair',
		'form_name' => 'Name',
		'form_keypair' => 'Keypair content',
		'msg_added' => 'Added Keypair %s',
		'error_exists' => 'Keypair %s already exists',
		'error_name' => 'Name must be %s',
		'error_keypair_content' => 'Keypair content must be %s',
		'please_wait' => 'Adding Keypair. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'remove' => array (
		'tab' => 'Remove Keypair',
		'label' => 'Remove Keypair',
		'msg_removed' => 'Removed Keypair %s',
		'please_wait' => 'Removing Keypair. Please wait ..',
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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/hybrid-cloud/lang", 'hybrid-cloud-keypair.ini');
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
	 * List Keypairs
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function edit( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-keypair.edit.class.php');
			$controller = new hybrid_cloud_keypair_edit($this->openqrm, $this->response);
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
	 * Add Keypair
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function add( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-keypair.add.class.php');
			$controller = new hybrid_cloud_keypair_add($this->openqrm, $this->response);
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
	 * Remove Keypair
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-keypair.remove.class.php');
			$controller = new hybrid_cloud_keypair_remove($this->openqrm, $this->response);
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
	 * Reload Configuration
	 *
	 * @access public
	 */
	//--------------------------------------------
	function __reload() {
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->response->html->request()->get('hybrid_cloud_id'));

		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-keypair describe';
		$command .= ' -i '.$hc->id;
		$command .= ' -n '.$hc->account_name;
		$command .= ' -c '.$hc->rc_config;
		$command .= ' -t '.$hc->account_type;
		$command .= ' -ir '.$this->response->html->request()->get('region');
		$command .= ' --openqrm-ui-user '.$this->user->name;
		$command .= ' --openqrm-cmd-mode background';
	
		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$hc->id.'.describe_keypair.log';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}
		$openqrm = new openqrm_server();
		$openqrm->send_command($command);
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
