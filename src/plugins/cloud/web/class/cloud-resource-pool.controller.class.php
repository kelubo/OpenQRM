<?php
/**
 * Cloud Resource-Pool Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_resource_pool_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_resource_pool';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-resource-pool";
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
	'cloud_resource_pool_list' => 'Host Pools',
	'cloud_resource_pool_name' => 'Name',
	'cloud_resource_pool_id' => 'ID',
	'cloud_resource_pool_description' => 'Description',
	'cloud_resource_pool_comment' => 'Comment',
	'cloud_resource_pool_type' => 'Type',
	'cloud_resource_pool_assigned' => 'Assigned to',
	'cloud_resource_pool_user' => 'Cloud User',
	'cloud_resource_pool_management' => 'Cloud Host Pool Management',
	'cloud_resource_pool_actions' => 'Actions',
	'cloud_resource_pool_update' => 'Update',
	'cloud_resource_pool_update_title' => 'Assign Host Pool %s to Cloud project',
	'cloud_resource_pool_updated' => 'Updated Cloud Resource Pool %s',
	'cloud_resource_pool_everybody' => 'Everybody',
	'cloud_resource_pool_nobody' => 'Nobody',
	'cloud_resource_pool_not_enabled_label' => 'Cloud Host Pool disabled',
	'cloud_resource_pool_not_enabled' => 'The Cloud Host Pool Features is disabled. <br>Please enable it in the Main Cloud Configuration',

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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-resource-pool.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_resource_pool_id";

		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudconfig.class.php');
		$this->cloud_config = new cloudconfig();

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
		// enabled in main config ?
		$resource_pool_enabled = $this->cloud_config->get_value_by_key('resource_pooling');
		if (!strcmp($resource_pool_enabled, "true")) {
			switch( $this->action ) {
				case '':
				case 'select':
					$content[] = $this->select(true);
				break;
				case 'update':
					$content[] = $this->select(false);
					$content[] = $this->update(true);
				break;
			}
		} else {

			$c['label']   = $this->lang['cloud_resource_pool_not_enabled_label'];
			$c['value']   = $this->lang['cloud_resource_pool_not_enabled'];
			$c['onclick'] = false;
			$c['active']  = true;
			$c['target']  = $this->response->html->thisfile;
			$c['request'] = '';
			$content[] = $c;

		}


		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		return $tab;
	}

	//--------------------------------------------
	/**
	 * Cloud Resource-Pool Select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-resource-pool.select.class.php');
			$controller = new cloud_resource_pool_select($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_resource_pool_list'];
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
	 * Cloud Resource-Pool Update
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-resource-pool.update.class.php');
			$controller = new cloud_resource_pool_update($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
//			$this->response->html->help($data);
		}
		$content['label']   = $this->lang['cloud_resource_pool_update'];
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
