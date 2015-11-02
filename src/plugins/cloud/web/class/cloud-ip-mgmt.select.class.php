<?php
/**
 * Cloud IP-Mgmt Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ip_mgmt_select
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-ip-mgmtselect';



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
		$this->rootdir  = $this->openqrm->get('basedir');
		$this->webdir  = $this->openqrm->get('webdir');
		require_once $this->rootdir."/plugins/cloud/web/class/cloudusergroup.class.php";
		$this->cloud_user_group = new cloudusergroup();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->rootdir."/plugins/ip-mgmt/web/class/ip-mgmt.class.php";
		$this->ip_mgmt = new ip_mgmt();
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
		$template = $this->response->html->template($this->tpldir."/cloud-ip-mgmt-select.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($response->table, 'table');
		$template->add($this->lang['cloud_ip_mgmt_management'], 'title');
		$template->add($response->form);		
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud IP-Mgmt Select
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {
		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'select');
		$response->form = $form;

		$head['ip_mgmt_icon']['title'] = ' ';
		$head['ip_mgmt_icon']['sortable'] = false;
		$head['ip_mgmt_name']['title'] = $this->lang['cloud_ip_mgmt_name'];
		$head['ip_mgmt_assigned']['title'] = $this->lang['cloud_ip_mgmt_assigned'];
		$head['ip_mgmt_assigned']['sortable'] = false;
		$head['ip_mgmt_actions']['title'] = ' ';
		$head['ip_mgmt_actions']['sortable'] = false;

		$table = $response->html->tablebuilder( 'cloud_ip_mgmt_table', $this->response->get_array($this->actions_name, 'select'));
		$table->css         = 'htmlobject_table';
		$table->border      = 0;
		$table->id          = 'cloud_ip_mgmt_table';
		$table->head        = $head;
		$table->sort        = 'ip_mgmt_name';
		$table->autosort    = true;
		$table->sort_link   = false;
		$table->limit_select = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 40, "text" => 40),
			array("value" => 50, "text" => 50),
		);
		$table->form_action	= $this->response->html->thisfile;

		$ip_mgmt_icon = "<img width='24' height='24' src='/openqrm/base/plugins/cloud/img/cloudipgroups.png'>";

		$cloud_ip_mgmt_array = $this->ip_mgmt->get_names();
		$ta = '';
		foreach ($cloud_ip_mgmt_array as $ip_mgmt_name) {

			$ip_mgmt_lib_by_name = $this->ip_mgmt->get_list($ip_mgmt_name);
			$pi_selected = $ip_mgmt_lib_by_name[$ip_mgmt_name]['first']['ip_mgmt_user_id'];
			if (!strlen($pi_selected)) {
				$pi_selected=-1;
			}
			$assigned_to = '';
			switch ($pi_selected) {
				case '-1':
					$assigned_to = $this->lang['cloud_ip_mgmt_not_assigned'];
					break;

				default:
					$this->cloud_user_group->get_instance_by_id($pi_selected);
					$assigned_to = $this->cloud_user_group->name;
					break;
			}

			// update action
			$a = $this->response->html->a();
			$a->title   = $this->lang['cloud_ip_mgmt_update'];
			$a->label   = $this->lang['cloud_ip_mgmt_update'];
			$a->css     = 'edit';
			$a->href    = $this->response->get_url($this->actions_name, "update").'&cloud_ip_mgmt_name='.$ip_mgmt_name;


			$ta[] = array(
				'ip_mgmt_icon' => $ip_mgmt_icon,
				'ip_mgmt_name' => $ip_mgmt_name,
				'ip_mgmt_assigned' => $assigned_to,
				'ip_mgmt_actions' => $a->get_string(),
			);
		}
		$table->max = count($ta);
		$table->body = $ta;

		$response->table = $table;
		return $response;
	}


}
?>
