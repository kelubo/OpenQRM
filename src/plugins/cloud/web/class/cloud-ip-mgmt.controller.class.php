<?php
/**
 * Cloud IP-Mgmt Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ip_mgmt_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_ip_mgmt';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-ip-mgmt";
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
	'cloud_ip_mgmt_list' => 'IP Management',
	'cloud_ip_mgmt_name' => 'Name',
	'cloud_ip_mgmt_id' => 'ID',
	'cloud_ip_mgmt_description' => 'Description',
	'cloud_ip_mgmt_comment' => 'Comment',
	'cloud_ip_mgmt_type' => 'Type',
	'cloud_ip_mgmt_assigned' => 'Assigned to',
	'cloud_ip_mgmt_user' => 'Cloud User',
	'cloud_ip_mgmt_management' => 'Cloud IP Management',
	'cloud_ip_mgmt_actions' => 'Actions',
	'cloud_ip_mgmt_update' => 'Update',
	'cloud_ip_mgmt_update_title' => 'Assign IP Network %s to Cloud Usergroup',
	'cloud_ip_mgmt_updated' => 'Updated IP Management %s',
	'cloud_ip_mgmt_everybody' => 'Everybody',
	'cloud_ip_mgmt_nobody' => 'Nobody',
	'cloud_ip_mgmt_not_enabled_label' => 'Cloud IP Management disabled',
	'cloud_ip_mgmt_not_enabled' => 'The Cloud IP Management Features is disabled. <br>Please enable it in the Main Cloud Configuration',
	'cloud_ip_mgmt_not_available_label' => 'Cloud IP Management Features not available',
	'cloud_ip_mgmt_not_available' => 'The Cloud IP Management Features is not available on this openQRM Cloud. <br>Please contact <a href="http://www.openqrm-enterprise.com" target="_BLANK">openQRM Enterprise</a> to get this commercial Enterprise Plugin.',
	'cloud_ip_mgmt_not_started_label' => 'IP Management Plugin not enabled/started',
	'cloud_ip_mgmt_not_started' => 'The IP Management Plugin is not enabled/started. <br>Please enable and start it via the Plugin-Manager.',
	'cloud_ip_mgmt_not_assigned' => 'Not assigned',

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
		$this->basedir  = $this->openqrm->get('basedir');
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-ip-mgmt.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_ip_mgmt_name";

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
		$ip_mgmt_enabled = $this->cloud_config->get_value_by_key('ip-management');
		if (!strcmp($ip_mgmt_enabled, "true")) {
			if (file_exists($this->rootdir."/plugins/ip-mgmt/.running")) {
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

			} else if (file_exists($this->basedir."/plugins/ip-mgmt/web/menu.txt")) {

				$c['label']   = $this->lang['cloud_ip_mgmt_not_started_label'];
				$c['value']   = $this->lang['cloud_ip_mgmt_not_started'];
				$c['onclick'] = false;
				$c['active']  = true;
				$c['target']  = $this->response->html->thisfile;
				$c['request'] = '';
				$content[] = $c;


			} else {
				$c['label']   = $this->lang['cloud_ip_mgmt_not_available_label'];
				$c['value']   = $this->lang['cloud_ip_mgmt_not_available'];
				$c['onclick'] = false;
				$c['active']  = true;
				$c['target']  = $this->response->html->thisfile;
				$c['request'] = '';
				$content[] = $c;
			}
		} else {
			$c['label']   = $this->lang['cloud_ip_mgmt_not_enabled_label'];
			$c['value']   = $this->lang['cloud_ip_mgmt_not_enabled'];
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
	 * Cloud IP-Mgmt Select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-ip-mgmt.select.class.php');
			$controller = new cloud_ip_mgmt_select($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_ip_mgmt_list'];
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
	 * Cloud IP-Mgmt Update
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-ip-mgmt.update.class.php');
			$controller = new cloud_ip_mgmt_update($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_ip_mgmt_update'];
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
