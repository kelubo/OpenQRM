<?php
/**
 * Cloud UserGroup Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_usergroup_select
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-usergroupselect';



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
		$template = $this->response->html->template($this->tpldir."/cloud-usergroup-select.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($response->table, 'table');
		$template->add($this->lang['cloud_usergroup_management'], 'title');
		$template->add($response->form);		
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud UserGroup Select
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'select');
		$response->form = $form;

		$head['cg_id']['title'] = $this->lang['cloud_usergroup_id'];
		$head['cg_name']['title'] = $this->lang['cloud_usergroup_name'];
		$head['cg_description']['title'] = $this->lang['cloud_usergroup_description'];
		$head['cg_actions']['title'] = '&#160;';
		$head['cg_actions']['sortable'] = false;

		require_once $this->webdir."/plugins/cloud/class/cloudusergroup.class.php";
		$cloud_user_group = new cloudusergroup();
		
		$table = $response->html->tablebuilder( 'cloud_usergroup_table', $this->response->get_array($this->actions_name, 'select'));
		$table->css             = 'htmlobject_table';
		$table->border          = 0;
		$table->limit           = 10;
		$table->id              = 'cloud_usergroup_table';
		$table->head            = $head;
		$table->sort            = 'cg_id';
		$table->sort_link       = false;
		$table->autosort        = false;
		$table->max		        = $cloud_user_group->get_count();
		$table->identifier      = 'cg_id';
		$table->identifier_name = $this->identifier_name;
		$table->actions         = array('delete');
		$table->actions_name    = $this->actions_name;
		$table->form_action	    = $this->response->html->thisfile;
		$table->limit_select = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 40, "text" => 40),
			array("value" => 50, "text" => 50),
		);
		$table->init();

		$cloud_usergroup_array = $cloud_user_group->display_overview($table->offset, $table->limit, $table->sort, $table->order);
		$ta = '';
		foreach ($cloud_usergroup_array as $index => $cz) {

			// update action
			$a = $this->response->html->a();
			$a->title   = $this->lang['cloud_usergroup_update'];
			$a->label   = $this->lang['cloud_usergroup_update'];
			$a->handler = 'onclick="wait();"';
			$a->css     = 'edit';
			$a->href    = $this->response->get_url($this->actions_name, "update").'&cloud_usergroup_id='.$cz["cg_id"];
	    
			$ta[] = array(
				'cg_id' => $cz["cg_id"],
				'cg_name' => $cz["cg_name"],
				'cg_description' => $cz["cg_description"],
				'cg_actions' => $a->get_string(),
			);
		}
		$table->body = $ta;

		$response->table = $table;
		return $response;
	}




}

?>


