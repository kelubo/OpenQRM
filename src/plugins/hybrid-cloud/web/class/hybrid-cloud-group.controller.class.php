<?php
/**
 * hybrid_cloud Group Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_group_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_group_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_group_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_group_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_group_identifier';
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
		'tab' => 'Group List',
		'label' => 'Security Groups for Account %s',
		'table_id' => 'ID',
		'table_group' => 'Name',
		'table_description' => 'Description',
		'table_port' => 'Port',
		'table_protocol' => 'Protocol',
		'action_add_group' => 'Add Group',
		'action_remove_permission' => 'Remove Permission',
		'action_add_permission' => 'Add Permission',
		'action_remove_group' => 'Remove Group',
		'error_name' => 'Name may contain %s only',
		'msg_select_account' => 'Please select a Cloud Account!',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'add' => array (
		'label' => 'Add Security Group',
		'tab' => 'Add Group',
		'form_name' => 'Group Name',
		'form_description' => 'Description',
		'form_vpc' => 'VPC Security Group',
		'error_exists' => 'Group %s allready exists',
		'error_name' => 'Name may contain %s only',
		'error_description' => 'Description may contain %s only',
		'msg_added_group' => 'Added Group %s',
		'msg_removed_image' => 'Removed Group %s',
		'please_wait' => 'Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'remove' => array (
		'tab' => 'Remove Security Group',
		'label' => 'Remove Security Group',
		'msg_removed' => 'Removed Security Group %s',
		'please_wait' => 'Removing Security Group. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'add_perm' => array (
		'label' => 'Add Permission to Security Group %s',
		'tab' => 'Add Permission',
		'form_name' => 'Permission Name',
		'form_protocol' => 'Protocol',
		'form_port' => 'Port Number',
		'msg_added_permission' => 'Added Permission %s to Security Port Group %s',
		'error_portnumber' => 'Port number may contain %s only',
		'please_wait' => 'Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'remove_perm' => array (
		'tab' => 'Remove Permission',
		'label' => 'Remove Permission from Security Group %s',
		'msg_removed' => 'Removed Permission %s from Security Group %s',
		'please_wait' => 'Removing Permission. Please wait ..',
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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/hybrid-cloud/lang", 'hybrid-cloud-group.ini');
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
			case 'add_perm':
				$content[] = $this->edit(false);
				$content[] = $this->add_perm(true);
			break;
			case 'remove_perm':
				$content[] = $this->edit(false);
				$content[] = $this->remove_perm(true);
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
	 * List Groups
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function edit( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-group.edit.class.php');
			$controller = new hybrid_cloud_group_edit($this->openqrm, $this->response);
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
	 * Add Group
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function add( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-group.add.class.php');
			$controller = new hybrid_cloud_group_add($this->openqrm, $this->response);
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
	 * Remove Group
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-group.remove.class.php');
			$controller = new hybrid_cloud_group_remove($this->openqrm, $this->response);
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
	 * Add Permission to Group
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function add_perm( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-group.add_perm.class.php');
			$controller = new hybrid_cloud_group_add_perm($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['add_perm'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['add_perm']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'add_perm' );
		$content['onclick'] = false;
		if($this->action === 'add_perm'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Remove Permission from Group
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function remove_perm( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-group.remove_perm.class.php');
			$controller = new hybrid_cloud_group_remove_perm($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['remove_perm'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['remove_perm']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'remove_perm' );
		$content['onclick'] = false;
		if($this->action === 'remove_perm'){
			$content['active']  = true;
		}
		return $content;
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
