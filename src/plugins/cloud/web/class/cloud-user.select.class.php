<?php
/**
 * Cloud User Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_user_select
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-userselect';



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
		$this->response = $response;
		$this->openqrm = $openqrm;
		$this->file = $this->openqrm->file();
		$this->rootdir  = $this->openqrm->get('rootdir');
		$this->webdir  = $this->openqrm->get('webdir');
		// central user management ?
		$central_user_management = false;
		if (file_exists($this->webdir."/plugins/ldap/.running")) {
			$central_user_management = true;
		}
		$this->central_user_management = $central_user_management;
	}

	//--------------------------------------------
	/**
	 * Action New
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {
		$response = $this->select();
		$template = $this->response->html->template($this->tpldir."/cloud-user-select.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($response->table, 'table');
		$template->add($this->lang['cloud_user_management'], 'title');
		$template->add($response->form);		
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud User Select
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {


		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'select');
		$response->form = $form;

		// to get the user group name
		require_once $this->webdir."/plugins/cloud/class/clouduser.class.php";
		require_once $this->webdir."/plugins/cloud/class/cloudusergroup.class.php";
		$cloud_group = new cloudusergroup();
		$cloud_user = new clouduser();

		$head['cu_status']['title'] = '&#160;';
		$head['cu_status']['sortable'] = false;
		$head['cu_name']['title'] = $this->lang['cloud_user_username'];
		$head['cu_name']['hidden'] = true;
		$head['cu_id']['title'] = $this->lang['cloud_user_id'];
		$head['cu_id']['hidden'] = true;
		$head['cu_cg_id']['title'] = $this->lang['cloud_user_group'];
		$head['cu_cg_id']['hidden'] = true;
		$head['info']['title'] =  '&#160;';
		$head['info']['sortable'] =  false;
		$head['cu_ccunits']['title'] = $this->lang['cloud_user_ccunits'];
		$head['cu_actions']['title'] =  '&#160;';
		$head['cu_actions']['sortable'] =  false;

		$table = $response->html->tablebuilder( 'cloud_user_table', $response->get_array($this->actions_name, 'select'));
		$table->css             = 'htmlobject_table';
		$table->border          = 0;
		$table->limit           = 10;
		$table->id              = 'cloud_users';
		$table->head            = $head;
		$table->sort            = 'cu_id';
		$table->sort_link       = false;
		$table->autosort        = false;
		$table->max		        = $cloud_user->get_count();
		$table->identifier      = 'cu_id';
		$table->identifier_name = $this->identifier_name;
		if ($this->central_user_management) {
			$table->actions = array('enable', 'disable');
		} else {
			$table->actions = array(
				array('enable' => $this->lang['cloud_user_enable']),
				array('disable' => $this->lang['cloud_user_disable']),
				array('delete' => $this->lang['cloud_user_delete'])
			);
		}
		$table->actions_name    = $this->actions_name;
		$table->limit_select = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 40, "text" => 40),
			array("value" => 50, "text" => 50),
		);
		$table->init();

		$cloud_user_array = $cloud_user->display_overview($table->offset, $table->limit, $table->sort, $table->order);
		$ta = '';
		foreach ($cloud_user_array as $index => $cz) {
			$cloud_user->get_instance_by_id($cz['cu_id']);
			// user group
			$cloud_group->get_instance_by_id($cz['cu_cg_id']);
			// lang
			if (!strlen($cz['cu_lang'])) {
				$cz['cu_lang'] = "-";
			}
			// status
			$user_state = '';
			if ($cz['cu_status'] == 1) {
				$user_state = '<span class="pill active">'.$this->lang['cloud_user_active'].'</span>';
			} else {
				$user_state = '<span class="pill inactive">'.$this->lang['cloud_user_inactive'].'</span>';
			}
			// update action
			$a = $this->response->html->a();
			$a->title   = $this->lang['cloud_user_update'];
			$a->label   = $this->lang['cloud_user_update'];
			$a->css     = 'edit';
			$a->href    = $this->response->get_url($this->actions_name, "update").'&cloud_user_id='.$cz["cu_id"];
			$str_action = $a->get_string();

			// requests action
			$a = $this->response->html->a();
			$a->title   = $this->lang['cloud_user_requests'];
			$a->label   = $this->lang['cloud_user_requests'];
			$a->css     = 'edit';
			$a->href    = $this->response->get_url($this->actions_name, "requests").'&username='.$cloud_user->name;
			$str_action .= $a->get_string();

			// new instance action
			$a = $this->response->html->a();
			$a->title   = $this->lang['cloud_user_new_instance'];
			$a->label   = $this->lang['cloud_user_new_instance'];
			$a->css     = 'edit';
			$a->href    = $this->response->get_url($this->actions_name, "instance").'&username='.$cloud_user->name;
			$str_action .= $a->get_string();

			// instances action
			$a = $this->response->html->a();
			$a->title   = $this->lang['cloud_user_instances'];
			$a->label   = $this->lang['cloud_user_instances'];
			$a->css     = 'edit';
			$a->href    = $this->response->get_url($this->actions_name, "instances").'&username='.$cloud_user->name;
			$str_action .= $a->get_string();

			$info  = '<b>'.$this->lang['cloud_user_username'].'</b>: '.$cz["cu_name"].'<br>';
			$info .= '<b>'.$this->lang['cloud_user_id'].'</b>: '.$cz["cu_id"].'<br>';
			$info .= '<b>'.$this->lang['cloud_user_name'].'</b>: '.$cloud_user->forename.' '.$cloud_user->lastname.'<br>';
			$info .= '<b>'.$this->lang['cloud_user_email'].'</b>: <a href="mailto:'.$cloud_user->email.'">'.$cloud_user->email.'</a><br>';
			$info .= '<b>'.$this->lang['cloud_user_phone'].'</b>: '.$cloud_user->phone.'<br>';
			$info .= '<b>'.$this->lang['cloud_user_group'].'</b>: '.$cloud_group->name.'<br>';

			$ta[] = array(
				'cu_status' => $user_state,
				'cu_id' => $cz["cu_id"],
				'cu_name' => $cz['cu_name'],
				'cu_cg_id' => $cloud_group->name,
				'info' => $info,
				'cu_ccunits' => $cz['cu_ccunits'],
				'cu_actions' => $str_action,
			);
		}

		$table->body = $ta;

		$response->table = $table;
		return $response;
	}




}

?>


