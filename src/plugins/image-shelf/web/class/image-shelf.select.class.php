<?php
/**
 * image_shelf Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class image_shelf_select
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'image_shelf_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'image_shelf_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "image_shelf_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'image_shelf_tab';
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
		$t = $this->response->html->template($this->tpldir.'/image-shelf-select.tpl.php');
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
		$h['imageshelf_id']['title'] = $this->lang['table_id'];
		$h['imageshelf_name']['title'] = $this->lang['table_name'];
		$h['imageshelf_protocol']['title'] = $this->lang['table_protocol'];
		$h['imageshelf_uri']['title'] = $this->lang['table_uri'];
		$h['edit']['title'] = '&#160;';
		$h['edit']['sortable'] = false;

		$params  = $this->response->get_array($this->actions_name, 'select');
		$b       = array();

		require_once($this->openqrm->get('basedir').'/plugins/image-shelf/web/class/imageshelf.class.php');
		$resource = new imageshelf();

		$table = $this->response->html->tablebuilder('ishelf', $params);
		$table->offset = 0;
		$table->sort = 'imageshelf_id';
		$table->limit = 10;
		$table->order = 'ASC';
		$table->max = $resource->get_count('all');

		$table->init();

		$resources = $resource->display_overview($table->offset, $table->limit, $table->sort, $table->order);

		foreach ($resources as $k => $v) {

			$a = $this->response->html->a();
			$a->title   = $this->lang['action_edit'];
			$a->label   = $this->lang['action_edit'];
			$a->handler = 'onclick="wait();"';
			$a->css     = 'edit';
			$a->href    = $this->response->get_url($this->actions_name, "template").'&imageshelf_id='.$v["imageshelf_id"];

			$b[] = array(
				'imageshelf_id' => $v["imageshelf_id"],
				'imageshelf_name' => $v["imageshelf_name"],
				'imageshelf_protocol' => $v["imageshelf_protocol"],
				'imageshelf_uri' => $v["imageshelf_uri"],
				'edit' => $a->get_string(),
			);
		}

		$add = $this->response->html->a();
		$add->title   = $this->lang['action_add'];
		$add->label   = $this->lang['action_add'];
		$add->handler = 'onclick="wait();"';
		$add->css     = 'add';
		$add->href    = $this->response->get_url($this->actions_name, "add");

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
		$table->identifier = 'imageshelf_id';
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
