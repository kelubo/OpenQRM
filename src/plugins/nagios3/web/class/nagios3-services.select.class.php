<?php
/**
 * Nagios3 Services Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class nagios3_services_select
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'nagios3_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "nagios3_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'nagios3_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'nagios3_identifier';
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
		$this->openqrm  = $openqrm;
		$this->user     = $this->openqrm->user();
		$this->rootdir  = $this->openqrm->get('webdir');
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->tpldir   = $this->rootdir.'/plugins/nagios3/tpl';

		require_once($this->openqrm->get('basedir').'/plugins/nagios3/web/class/nagios3_host.class.php');
		require_once($this->openqrm->get('basedir').'/plugins/nagios3/web/class/nagios3_service.class.php');
		$this->nagios3h = new nagios3_host();
		$this->nagios3s = new nagios3_service();
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @return htmlobject_tabmenu
	 */
	//--------------------------------------------
	function action() {
		$response      = $this->select();
		$href          = $response->html->a();
		$href->href    = $response->get_url($this->actions_name, 'add' );
		$href->label   = $this->lang['action_add'];
		$href->css     = 'add';
		$href->handler = 'onclick="wait();"';

		$data['add']         = $href;
		$data['label']       = $this->lang['label'];
		$data['table']       = $response->table;
		$data['baseurl']     = $this->openqrm->get('baseurl');
		$data['thisfile']    = $response->html->thisfile;
		$t = $response->html->template($this->tpldir.'/nagios3-services.select.tpl.php');
		$t->add($data);
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Select
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {
		$response = $this->response;

		$nagios3 = $this->nagios3s;

		$table = $this->response->html->tablebuilder('n3s', $this->response->get_array($this->actions_name, 'select'));
		$table->offset = 0;
		$table->sort = 'nagios3_service_id';
		$table->limit = 10;
		$table->order = 'ASC';
		$table->max   = $nagios3->get_count();

		$table->init();

		$h['nagios3_service_id']['title']    = $this->lang['id'];
		$h['nagios3_service_id']['sortable'] = true;
		$h['nagios3_service_name']['title']    = $this->lang['name'];
		$h['nagios3_service_name']['sortable'] = true;
		$h['nagios3_service_port']['title']    = $this->lang['port'];
		$h['nagios3_service_port']['sortable'] = true;
		$h['nagios3_service_type']['title']    = $this->lang['type'];
		$h['nagios3_service_type']['sortable'] = true;
		$h['nagios3_service_description']['title']    = $this->lang['description'];
		$h['nagios3_service_description']['sortable'] = false;
		$h['edit']['title']    = '&#160;';
		$h['edit']['sortable'] = false;


		$result = $nagios3->display_overview($table->offset, $table->limit, $table->sort, $table->order);
		$b = array();
		foreach($result as $k => $v) {
			$a          = $response->html->a();
			$a->href    = $response->get_url($this->actions_name, 'edit' ).'&service_id='.$v['nagios3_service_id'];
			$a->label   = $this->lang['action_edit'];
			$a->title   = $this->lang['action_edit'];
			$a->css     = 'edit';				
			$a->handler = 'onclick="wait();"';

			$tmp = array();
			$tmp['nagios3_service_id'] = $v['nagios3_service_id'];
			$tmp['nagios3_service_name'] = $v['nagios3_service_name'];				
			$tmp['nagios3_service_port'] = $v['nagios3_service_port'];
			$tmp['nagios3_service_type'] = $v['nagios3_service_type'];
			$tmp['nagios3_service_description'] = $v['nagios3_service_description'];
			$tmp['edit'] = $a->get_string();
			$b[] = $tmp;
		}

		$table->css                 = 'htmlobject_table';
		$table->border              = 0;
		$table->id                  = 'Tabelle';
		$table->form_action	        = $this->response->html->thisfile;
		$table->head                = $h;
		$table->body                = $b;
		$table->sort_params         = $response->get_string( $this->actions_name, 'select' );
		$table->sort_form           = true;
		$table->sort_link           = false;
		$table->autosort            = false;
		$table->identifier          = 'nagios3_service_id';
		$table->identifier_name     = $this->identifier_name;
		$table->actions             = array(array('remove' => $this->lang['action_remove']));
		$table->actions_name        = $this->actions_name;
		$table->limit_select        = array(
				array("value" => 10, "text" => 10),
				array("value" => 20, "text" => 20),
				array("value" => 30, "text" => 30),
				array("value" => 40, "text" => 40),
				array("value" => 50, "text" => 50),
				);

		$response->table = $table;
		return $response;
	}

}
?>
