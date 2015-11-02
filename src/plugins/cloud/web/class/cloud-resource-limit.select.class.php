<?php
/**
 * Cloud Resource-Limit Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_resource_limit_select
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-resource-limitselect';



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
		require_once $this->webdir."/plugins/cloud/class/cloudusergroup.class.php";
		$this->cloud_user_group = new cloudusergroup();
		require_once $this->webdir."/plugins/cloud/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->webdir."/plugins/cloud/class/cloudhostlimit.class.php";
		$this->cloudhostlimit = new cloudhostlimit();
		$this->appliance = new appliance();
		$this->virtualization = new virtualization();
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
		$template = $this->response->html->template($this->tpldir."/cloud-resource-limit-select.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($response->table, 'table');
		$template->add($this->lang['cloud_resource_limit_management'], 'title');
		$template->add($response->form);		
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Resource-Limit Select
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {
		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'select');
		$response->form = $form;

		$head['appliance_icon']['title'] = ' ';
		$head['appliance_icon']['sortable'] = false;
		$head['appliance_id']['title'] = $this->lang['cloud_resource_limit_id'];
		$head['appliance_name']['title'] = $this->lang['cloud_resource_limit_name'];
		$head['appliance_virtualization']['title'] = $this->lang['cloud_resource_limit_type'];
		$head['appliance_comment']['title'] = $this->lang['cloud_resource_limit_comment'];
		$head['appliance_assigned']['title'] = $this->lang['cloud_resource_limit_assigned'];
		$head['appliance_assigned']['sortable'] = false;
		$head['appliance_actions']['title'] = ' ';
		$head['appliance_actions']['sortable'] = false;

		$table = $response->html->tablebuilder( 'cloud_resource_limit_table', $this->response->get_array($this->actions_name, 'select'));
		$table->css             = 'htmlobject_table';
		$table->max             = $this->appliance->get_count();
		$table->border          = 0;
		$table->id              = 'cloud_resource_limit_table';
		$table->head            = $head;
		$table->sort            = 'appliance_id';
		$table->offset          = 0;
		$table->limit           = 10;
		$table->order           = 'ASC';
		$table->autosort        = false;
		$table->form_action	    = $this->response->html->thisfile;
		$table->sort_link       = false;
		$table->limit_select    = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 40, "text" => 40),
			array("value" => 50, "text" => 50),
		);
		$table->init();

		$appliance_icon = "<img width='24' height='24' src='/openqrm/base/img/appliance.png'>";
		$cloud_resource_limit_array = $this->appliance->display_overview(0, 10000, $table->sort, $table->order);
		$ta = '';
		$non_virtualization_hosts = 0;
		foreach ($cloud_resource_limit_array as $index => $cz) {

			$this->appliance->get_instance_by_id($cz["appliance_id"]);
			$this->virtualization->get_instance_by_id($this->appliance->virtualization);
			if (!strstr($this->virtualization->type, "-vm")) {
				// update action
				$a = $this->response->html->a();
				$a->title   = $this->lang['cloud_resource_limit_update'];
				$a->label   = $this->lang['cloud_resource_limit_update'];
				$a->css     = 'edit';
				$a->href    = $this->response->get_url($this->actions_name, "update").'&cloud_resource_limit_id='.$cz["appliance_id"];

				if (!strlen($cz["appliance_comment"])) {
					$cz["appliance_comment"] = '-';
				}

				// private image config existing
				$assigned_to = '';
				if ($this->cloudhostlimit->exists_by_resource_id($this->appliance->resources)) {
					$this->cloudhostlimit->get_instance_by_resource($this->appliance->resources);
					if ($assigned_to = $this->cloudhostlimit->max_vms < 0) {
						$assigned_to = $this->lang['cloud_resource_no_limit'];
					} else if ($assigned_to = $this->cloudhostlimit->max_vms >= 0) {
						$assigned_to = $this->cloudhostlimit->max_vms." ".$this->lang['cloud_resource_vms'];
					}
				} else {
					$assigned_to = $this->lang['cloud_resource_no_limit'];
				}

				$ta[] = array(
					'appliance_icon' => $appliance_icon,
					'appliance_id' => $cz["appliance_id"],
					'appliance_name' => $cz["appliance_name"],
					'appliance_virtualization' => $this->virtualization->type,
					'appliance_comment' => $cz["appliance_comment"],
					'appliance_assigned' => $assigned_to,
					'appliance_actions' => $a->get_string(),
				);
			}
		}
		$table->max  = count($ta);
		$table->body = $ta;

		$response->table = $table;
		return $response;
	}




}

?>


