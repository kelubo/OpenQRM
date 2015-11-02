<?php
/**
 * hybrid_cloud Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_select
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_tab';
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
var $lang = array();

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
		$this->response   = $response;
		$this->file       = $openqrm->file();
		$this->openqrm    = $openqrm;
		$this->region     = $response->html->request()->get('region');
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @return htmlobject_template
	 */
	//--------------------------------------------
	function action() {
		$data = $this->select();

		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-select.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($data);
		$t->add($this->lang['label'], 'label');
		$t->group_elements(array('param_' => 'form'));
		return $t;


	}

	//--------------------------------------------
	/**
	 * Select
	 *
	 * @access public
	 * @return array
	 */
	//--------------------------------------------
	function select() {

		$h = array();
		$h['hybrid_cloud_id']['title'] = $this->lang['table_id'];
		$h['hybrid_cloud_id']['hidden'] = true;
		$h['hybrid_cloud_account_name']['title'] = $this->lang['table_name'];
		$h['hybrid_cloud_account_name']['hidden'] = true;
		$h['hybrid_cloud_account_type']['title'] = $this->lang['table_type'];
		$h['hybrid_cloud_account_type']['hidden'] = true;
		$h['data']['title'] = '&#160;';
		$h['data']['sortable'] = false;
		$h['hybrid_cloud_description']['title'] = $this->lang['table_description'];
		$h['hybrid_cloud_description']['sortable'] = false;

		$h['action_buttons']['title'] = '&#160;';
		$h['action_buttons']['sortable'] = false;
		$h['edit']['title'] = '&#160;';
		$h['edit']['sortable'] = false;

		$params  = $this->response->get_array($this->actions_name, 'select');
		$b       = array();

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$resource = new hybrid_cloud();

		$table = $this->response->html->tablebuilder('accounts', $params);
		$table->offset = 0;
		$table->sort = 'hybrid_cloud_id';
		$table->limit = 10;
		$table->order = 'ASC';
		$table->max = $resource->get_count('all');

		$table->init();

		$resources = $resource->display_overview($table->offset, $table->limit, $table->sort, $table->order);

		foreach ($resources as $k => $v) {
			$account_type = $v["hybrid_cloud_account_type"];
			if (($account_type == "aws") || ($account_type == "euca")) {

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_ami'];
				$a->label   = $this->lang['action_ami'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-ami&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$ami = $a->get_string();

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_instance'];
				$a->label   = $this->lang['action_instance'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-vm&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$instance = $a->get_string();

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_snapshots'];
				$a->label   = $this->lang['action_snapshots'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-snapshot&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$snapshots = $a->get_string();

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_volumes'];
				$a->label   = $this->lang['action_volumes'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-volume&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$volumes = $a->get_string();

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_keypair'];
				$a->label   = $this->lang['action_keypair'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-keypair&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$keypair = $a->get_string();

				$group = '';
				if($account_type == "euca") {
					$a = $this->response->html->a();
					$a->title   = $this->lang['action_group'];
					$a->label   = $this->lang['action_group'];
					$a->handler = 'onclick="wait();"';
					$a->css     = 'btn';
					$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-group&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
					$group = $a->get_string();
				}

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_s3'];
				$a->label   = $this->lang['action_s3'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-s3&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$s3 = $a->get_string();

				$i = $this->response->html->a();
				$i->title   = $this->lang['action_import'];
				$i->label   = $this->lang['action_import'];
				$i->handler = 'onclick="wait();"';
				$i->css     = 'btn';
				$i->href    = $this->response->get_url($this->actions_name, "import").'&hybrid_cloud_id='.$v["hybrid_cloud_id"];
				$import = $i->get_string();

				$e = $this->response->html->a();
				$e->title   = $this->lang['action_export'];
				$e->label   = $this->lang['action_export'];
				$e->handler = 'onclick="wait();"';
				$e->css     = 'btn';
				$e->href    = $this->response->get_url($this->actions_name, "export").'&hybrid_cloud_id='.$v["hybrid_cloud_id"];
				$export = $e->get_string();

				$c = $this->response->html->a();
				$c->title   = $this->lang['action_dashboard'];
				$c->label   = $this->lang['action_dashboard'];
				$c->css     = 'btn';
				$c->target     = '_BLANK';
				$c->href    = 'https://console.aws.amazon.com/ec2/';
				$ec2console = $c->get_string();

				$actions = $instance.$keypair.$group."<br>".$ami.$import.$export."<br>".$s3.$volumes.$snapshots."<br>".$ec2console;
			}

			if ($account_type == "lc-openstack") {

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_ami'];
				$a->label   = $this->lang['action_ami'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-ami&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$ami = $a->get_string();

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_instance'];
				$a->label   = $this->lang['action_instance'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-vm&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$instance = $a->get_string();

				$lc_hc = new hybrid_cloud();
				$lc_hc->get_instance_by_id($v["hybrid_cloud_id"]);
				$a = $this->response->html->a();
				$a->title   = $this->lang['action_dashboard'];
				$a->label   = $this->lang['action_dashboard'];
				$a->css     = 'btn';
				$a->target     = '_BLANK';
				$a->href    = 'http://'.$lc_hc->host.'/project/instances/';
				$dashboard = $a->get_string();

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_keypair'];
				$a->label   = $this->lang['action_keypair'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-keypair&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$keypair = $a->get_string();

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_group'];
				$a->label   = $this->lang['action_group'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'btn';
				$a->href    = '/openqrm/base/index.php?plugin=hybrid-cloud&controller=hybrid-cloud-group&hybrid_cloud_id='.$v["hybrid_cloud_id"].'&region='.$this->region;
				$group = $a->get_string();

				$actions = $instance.$keypair.$group."<br>".$ami.$dashboard;
			}

			$a = $this->response->html->a();
			$a->title   = $this->lang['action_edit'];
			$a->label   = $this->lang['action_edit'];
			$a->handler = 'onclick="wait();"';
			$a->css     = 'edit';
			$a->href    = $this->response->get_url($this->actions_name, "edit").'&hybrid_cloud_id='.$v["hybrid_cloud_id"];
			$edit = $a->get_string();

			if(!isset($v["hybrid_cloud_description"])) {
				$v["hybrid_cloud_description"] = '&#160;';
			}

			$data  = '<b>'.$this->lang['table_id'].'</b>: '.$v["hybrid_cloud_id"].'<br>';
			$data .= '<b>'.$this->lang['table_name'].'</b>: '.$v["hybrid_cloud_account_name"].'<br>';
			$data .= '<b>'.$this->lang['table_type'].'</b>: '.$v["hybrid_cloud_account_type"];

			$b[] = array(
				'hybrid_cloud_id' => $v["hybrid_cloud_id"],
				'hybrid_cloud_account_name' => $v["hybrid_cloud_account_name"],
				'hybrid_cloud_account_type' => $v["hybrid_cloud_account_type"],
				'data' => $data,
				'hybrid_cloud_description' => $v["hybrid_cloud_description"],
				'action_buttons' => $actions,
				'edit' => $edit,
			);
		}

		$add = $this->response->html->a();
		$add->title   = $this->lang['action_add'];
		$add->label   = $this->lang['action_add'];
		$add->handler = 'onclick="wait();"';
		$add->css     = 'add';
		$add->href    = $this->response->get_url($this->actions_name, "add");

		$table->form_action = $this->response->html->thisfile;
		$table->id = 'Tabelle';
		$table->css = 'htmlobject_table';
		$table->border = 1;
		$table->cellspacing = 0;
		$table->cellpadding = 3;
		$table->autosort = false;
		$table->sort_link = false;
		$table->max = count($b);
		$table->head = $h;
		$table->body = $b;
		$table->actions_name = $this->actions_name;
		$table->actions = array(array('remove' => $this->lang['action_remove']));
		$table->identifier = 'hybrid_cloud_id';
		$table->identifier_name = $this->identifier_name;

		$table->limit_select = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 50, "text" => 50),
			array("value" => 100, "text" => 100),
		);

		$d['table'] = $table;
		$d['form']  = $this->response->get_form($this->actions_name, 'select', false)->get_elements();
		$d['add']   = $add->get_string();

		return $d;
	}




}
?>
