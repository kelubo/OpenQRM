<?php
/**
 * Cloud UserGroup Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_usergroup_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_usergroup';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-usergroup";
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
	'cloud_usergroup_list' => 'Groups',
	'cloud_usergroup_name' => 'Name',
	'cloud_usergroup_id' => 'ID',
	'cloud_usergroup_role_id' => 'Role',
	'cloud_usergroup_description' => 'Description',
	'cloud_usergroup_management' => 'Cloud User Group Management',
	'cloud_usergroup_insert_successful' => 'Successful inserted Cloud User Group.',
	'cloud_usergroup_update_successful' => 'Successful updated Cloud User Group.',
	'cloud_usergroup_data' => 'Cloud User Group Details',
	'cloud_usergroup_add' => 'New',
	'cloud_usergroup_add_title' => 'Add Cloud User Group',
	'cloud_usergroup_actions' => 'Actions',
	'cloud_namegroup_in_use' => 'User Group name already in use!',
	'cloud_usergroup_group' => 'Group',
	'cloud_usergroup_confirm_delete' => 'Really delete the following Cloud User Groups?',
	'cloud_usergroup_deleted' => 'Deleted Cloud User Group ',
	'cloud_usergroup_delete' => 'Delete',
	'cloud_usergroup_not_removing_default' => 'Not removing the Default User Group',
	'cloud_usergroup_permissions' => 'Permissions',
	'cloud_usergroup_update' => 'Update',
	'cloud_usergroup_update_title' => 'Update Cloud User Group %s ',
	'cloud_usergroup_name_already_in_use' => 'Cloud User Group name already in use!',
	'cloud_usergroup_still_contains_user' => 'Cloud User Group still contains user! Not removing ',
	'cloud_usergroup_still_contains_userlimits' => 'Cloud User Group still contains userlimits! Not removing ',

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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-usergroup.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_usergroup_id";
		require_once $this->rootdir."/class/htmlobjects/htmlobject.class.php";
		$this->html = new htmlobject($this->rootdir."/class/htmlobjects/");
		$this->html->lang = $this->user->translate($this->html->lang, $this->rootdir."/plugins/cloud/lang", 'htmlobjects.ini');

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
				$content[] = $this->insert(false);
			break;
			case 'insert':
				$content[] = $this->select(false);
				$content[] = $this->insert(true);
			break;
			case 'delete':
				$content[] = $this->select(false);
				$content[] = $this->delete(true);
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
	 * Cloud UserGroup Select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-usergroup.select.class.php');
			$controller = new cloud_usergroup_select($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_usergroup_list'];
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
	 * Cloud UserGroup Insert
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function insert( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-usergroup.insert.class.php');
			$controller = new cloud_usergroup_insert($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
//			$this->response->html->help($data);
		}
		$content['label']   = $this->lang['cloud_usergroup_add'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'insert' );
		$content['onclick'] = false;
		if($this->action === 'insert'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Cloud UserGroup Delete
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function delete( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-usergroup.delete.class.php');
			$controller = new cloud_usergroup_delete($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
//			$this->response->html->help($data);
		}
		$content['label']   = $this->lang['cloud_usergroup_delete'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'delete' );
		$content['onclick'] = false;
		if($this->action === 'delete'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud UserGroup Update
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function update( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-usergroup.update.class.php');
			$controller = new cloud_usergroup_update($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->rootdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_usergroup_update'];
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
