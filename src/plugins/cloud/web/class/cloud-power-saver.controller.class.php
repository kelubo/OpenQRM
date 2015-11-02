<?php
/**
 * Cloud Power-Saver Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_power_saver_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_power_saver';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-power-saver";
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
	'cloud_power_saver_list' => 'Power Saver',
	'cloud_power_saver_name' => 'Name',
	'cloud_power_saver_id' => 'ID',
	'cloud_power_saver_description' => 'Description',
	'cloud_power_saver_comment' => 'Comment',
	'cloud_power_saver_type' => 'Type',
	'cloud_power_saver_assigned' => 'Mode',
	'cloud_power_saver_user' => 'Cloud User',
	'cloud_power_saver_management' => 'Cloud Power Saver Management',
	'cloud_power_saver_actions' => 'Actions',
	'cloud_power_saver_update' => 'Update',
	'cloud_power_saver_update_title' => 'Assign Power Saver %s',
	'cloud_power_saver_updated' => 'Updated Cloud Power Saver %s',
	'cloud_power_saver_explain' => 'To save power consumption please select which Virtualization Host Appliance to power down when not in use',
	'cloud_resource_enabled' => 'Enabled',
	'cloud_resource_disabled' => 'Disabled',
	'cloud_resource_vms' => 'Virtual Machines',

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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-power-saver.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_power_saver_id";

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

		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		return $tab;
	}

	//--------------------------------------------
	/**
	 * Cloud Power-Saver Select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-power-saver.select.class.php');
			$controller = new cloud_power_saver_select($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_power_saver_list'];
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
	 * Cloud Power-Saver Update
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-power-saver.update.class.php');
			$controller = new cloud_power_saver_update($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
//			$this->response->html->help($data);
		}
		$content['label']   = $this->lang['cloud_power_saver_update'];
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
