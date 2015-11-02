<?php
/**
 * Cloud Private Image Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_private_image_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_private_image';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-private-image";
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
	'cloud_private_image_list' => 'Private Images',
	'cloud_private_image_name' => 'Name',
	'cloud_private_image_id' => 'ID',
	'cloud_private_image_description' => 'Description',
	'cloud_private_image_comment' => 'Comment',
	'cloud_private_image_version' => 'Version',
	'cloud_private_image_type' => 'Type',
	'cloud_private_image_root' => 'Root',
	'cloud_private_image_assigned' => 'Assigned',
	'cloud_private_image_user' => 'Cloud User',
	'cloud_private_image_management' => 'Cloud Private Image Management',
	'cloud_private_image_actions' => 'Actions',
	'cloud_private_image_update' => 'Update',
	'cloud_private_image_update_title' => 'Assign Image %s to Cloud User',
	'cloud_private_image_updated' => 'Updated Cloud Private Image %s',
	'cloud_private_image_everybody' => 'Everybody',
	'cloud_private_image_nobody' => 'Nobody',
	'cloud_private_image_not_enabled_label' => 'Cloud Private Images disabled',
	'lang_state' => 'State',
	'lang_inactive' => 'inactive',
	'lang_active' => 'active',
	'lang_private' => 'Private',
	'cloud_private_image_not_enabled' => 'The Cloud Private Image Features is disabled. <br>Please enable it in the Main Cloud Configuration',

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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-private-image.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_private_image_id";

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
		$private_image_enabled = $this->cloud_config->get_value_by_key('show_private_image');
		if (!strcmp($private_image_enabled, "true")) {
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

			$c['label']   = $this->lang['cloud_private_image_not_enabled_label'];
			$c['value']   = $this->lang['cloud_private_image_not_enabled'];
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
	 * Cloud Private Image Select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-private-image.select.class.php');
			$controller = new cloud_private_image_select($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_private_image_list'];
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
	 * Cloud Private Image Update
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-private-image.update.class.php');
			$controller = new cloud_private_image_update($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
//			$this->response->html->help($data);
		}
		$content['label']   = $this->lang['cloud_private_image_update'];
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
