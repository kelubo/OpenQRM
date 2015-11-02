<?php
/**
 *  Hybrid-cloud export select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_export
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
		$this->response = $response;
		$this->file     = $openqrm->file();
		$this->openqrm  = $openqrm;
		$this->user       = $openqrm->user();
		$this->id = $this->response->html->request()->get('hybrid_cloud_id');
		$this->response->add('hybrid_cloud_id', $this->id);
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
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-export.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($data);
		$t->add($this->response->get_array());
		$t->add(sprintf($this->lang['label'], $data['name']), 'label');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
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
		$h['image_icon']['title'] ='&#160;';
		$h['image_icon']['sortable'] = false;
		$h['image_id']['title'] = $this->lang['table_id'];
		$h['image_name']['title'] = $this->lang['table_name'];
		$h['image_version']['title'] = $this->lang['table_version'];
		$h['image_type']['title'] = $this->lang['table_deployment'];
		$h['image_isactive']['title'] = $this->lang['table_isactive'];
		$h['image_comment']['title'] = $this->lang['table_comment'];
		$h['image_comment']['sortable'] = false;
		$h['edit']['title'] = '&#160;';
		$h['edit']['sortable'] = false;

		$image = new image();
		$params  = $this->response->get_array($this->actions_name, 'export');
		$b       = array();

		$table = $this->response->html->tablebuilder('hybridcloud_export', $params);
		$table->offset = 0;
		$table->sort = 'image_id';
		$table->limit = 10;
		$table->order = 'ASC';
		$table->max = $image->get_count();

		$table->init();

		$image_arr = $image->display_overview(0, 10000, $table->sort, $table->order);
		$image_icon = "/openqrm/base/img/image.png";
		foreach ($image_arr as $index => $image_db) {
			// prepare the values for the array
			$image = new image();
			$image->get_instance_by_id($image_db["image_id"]);

			if($image->type === 'lvm-nfs-deployment' || $image->type === 'nfs-deployment') {
				$image_comment = $image_db["image_comment"];
				if (!strlen($image_comment)) {
					$image_comment = "-";
				}
				$image_version = $image_db["image_version"];
				if (!strlen($image_version)) {
					$image_version = "&#160;";
				}
				// edit
				$a = $this->response->html->a();
				$a->label   = $this->lang['action_export'];
				$a->title   = $this->lang['action_export'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'edit';
				$a->href    = $this->response->get_url($this->actions_name, 'extarget').'&image_id='.$image->id;
				$image_edit = $a->get_string();

				// set the active icon
				$isactive_icon = "/openqrm/base/img/enable.png";
				if ($image_db["image_isactive"] == 1) {
					$isactive_icon = "/openqrm/base/img/disable.png";
				}
				$image_isactive_icon = "<img src=".$isactive_icon." width='24' height='24' alt='State'>";

				$b[] = array(
					'image_icon' => "<img width='24' height='24' src='".$image_icon."'>",
					'image_id' => $image_db["image_id"],
					'image_name' => $image_db["image_name"],
					'image_version' => $image_version,
					'image_type' => $image_db["image_type"],
					'image_isactive' => $image_isactive_icon,
					'image_comment' => $image_comment,
					'edit' => $image_edit,
				);
			}
		}

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
		$table->limit_select = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 50, "text" => 50),
			array("value" => 100, "text" => 100),
		);

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);


		$d['name']  = $hc->account_name;
		$d['form']  = $this->response->get_form($this->actions_name, 'export', false)->get_elements();
		$d['table'] = $table;

		return $d;
	}

}
?>
