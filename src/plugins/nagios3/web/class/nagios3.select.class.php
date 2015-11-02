<?php
/**
 * Nagios3 Appliance select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class nagios3_select
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
	 * @param string $action
	 * @return htmlobject_tabmenu
	 */
	//--------------------------------------------
	function action($action = null) {
		$response            = $this->select();
		$data['label']       = $this->lang['label'];
		$data['table']       = $response->table;
		$data['baseurl']     = $this->openqrm->get('baseurl');
		$data['thisfile']    = $response->html->thisfile;
		$t = $response->html->template($this->tpldir.'/nagios3.select.tpl.php');
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

		$nagios3h = $this->nagios3h;
		$nagios3s = $this->nagios3s;

		$appliance = new appliance();

		$table = $this->response->html->tablebuilder('n3a', $this->response->get_array($this->actions_name, 'select'));
		$table->offset = 0;
		$table->sort = 'appliance_id';
		$table->limit = 10;
		$table->order = 'ASC';
		$table->max   = $appliance->get_count();

		$table->init();

		$h['appliance_id']['title']    = $this->lang['id'];
		$h['appliance_id']['sortable'] = true;
		$h['appliance_id']['hidden']   = true;

		$h['appliance_name']['title']    = $this->lang['name'];
		$h['appliance_name']['sortable'] = true;
		$h['appliance_name']['hidden']   = true;

		$h['appliance_resources']['title']    = $this->lang['resource'];
		$h['appliance_resources']['sortable'] = true;
		$h['appliance_resources']['hidden']   = true;

		$h['appliance']['title']    = $this->lang['appliance'];
		$h['appliance']['sortable'] = false;

		$h['services']['title']    = $this->lang['services'];
		$h['services']['sortable'] = false;

		$h['edit']['title']    = '&#160;';
		$h['edit']['sortable'] = false;
			


		$result = $appliance->display_overview($table->offset, $table->limit, $table->sort, $table->order);
		$b = array();
		foreach($result as $k => $v) {

			$a          = $response->html->a();
			$a->href    = $response->get_url($this->actions_name, 'edit' ).'&appliance_id='.$v['appliance_id'];
			$a->label   = $this->lang['action_edit'];
			$a->title   = $this->lang['action_edit'];
			$a->css     = 'edit';				
			$a->handler = 'onclick="wait();"';

			$appliance = $appliance->get_instance_by_id($v['appliance_id']);
			$resource = new resource();
			$resource = $resource->get_instance_by_id($appliance->resources);

			$host = new nagios3_host();
			$host = $host->get_instance_by_appliance_id($appliance->id);

			$services = array();
			if($host->appliance_services !== '' && $host->appliance_services !== 'false') {
				$s = explode(',', $host->appliance_services);
				foreach($s as $id) {
					$nagios3s = $nagios3s->get_instance_by_id($id);
					$services[] = $nagios3s->name;
				}
			}


			$tmp = array();
			$tmp['appliance_id'] = $appliance->id;
			$tmp['appliance_name'] = $appliance->name;
			$tmp['appliance_resources'] = $appliance->resources;
			$tmp['appliance']  = '<b>'.$this->lang['id'].':</b> '.$appliance->id.'<br>';
			$tmp['appliance'] .= '<b>'.$this->lang['name'].':</b> '.$appliance->name.'<br>';
			$tmp['appliance'] .= '<b>'.$this->lang['resource'].':</b> '.$resource->id.' / '.$resource->ip.'<br>';
			$tmp['services'] = implode(', ', $services);
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
