<?php
/**
 * Cloud NAT Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_nat_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_nat';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-nat";
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
	'cloud_nat_list' => 'Network Address Translation',
	'cloud_nat_update_title' => 'Assign Cloud Network Address Translation',
	'cloud_nat_updated' => 'Updated Cloud Network Address Translation',
	'cloud_nat_explain' => 'Cloud Network Address Translation maps the openQRM Management network (internal) against a public network (external, class C network with public ip-addresses).
		The actual mapping needs to be setup and configured on the gateway/router to the public network (e.g. via "iptables" post/pre-routing).',
	'cloud_resource_enabled' => 'Enabled',
	'cloud_resource_disabled' => 'Disabled',
	'cloud_nat_internal_net' => 'Internal Management Network (Class C)',
	'cloud_nat_external_net' => 'External Public Network (Class C)',
	'cloud_nat_not_enabled_label' => 'Cloud Network Address Translation disabled',
	'cloud_nat_not_enabled' => 'The Cloud Network Address Translation Features (cloud_nat) is disabled. <br>Please enable it in the Main Cloud Configuration',

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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-nat.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_nat_id";

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
		$content = array();


		// enabled in main config ?
		$cloud_nat_enabled = $this->cloud_config->get_value_by_key('cloud_nat');
		if (!strcmp($cloud_nat_enabled, "true")) {
			switch( $this->action ) {
				case '':
				case 'update':
					$content[] = $this->update(true);
				break;
			}
		} else {

			$c['label']   = $this->lang['cloud_nat_not_enabled_label'];
			$c['value']   = $this->lang['cloud_nat_not_enabled'];
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
	 * Cloud NAT Update
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-nat.update.class.php');
			$controller = new cloud_nat_update($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_nat_list'];
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
