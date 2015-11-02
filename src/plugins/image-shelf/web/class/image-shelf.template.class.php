<?php
/**
 * image_shelf Select template
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class image_shelf_template
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
		$this->user     = $openqrm->user();

		$this->id = $this->response->html->request()->get('imageshelf_id');
		$this->response->add('imageshelf_id', $this->id);
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
		$t = $this->response->html->template($this->tpldir.'/image-shelf-template.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($data);
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
		$h['file']['title'] = $this->lang['table_file'];
		$h['distribution']['title'] = $this->lang['table_distribution'];
		$h['application']['title'] = $this->lang['table_application'];
		$h['size']['title'] = $this->lang['table_size'];
		$h['password']['title'] = $this->lang['table_password'];
		$h['maintainer']['title'] = $this->lang['table_maintainer'];
		$h['edit']['title'] = '&#160;';
		$h['edit']['sortable'] = false;

		$b = array();

		require_once($this->openqrm->get('basedir').'/plugins/image-shelf/web/class/imageshelf.class.php');
		$imageshelf = new imageshelf();
		$imageshelf->get_instance_by_id($this->id);
		$file = $this->openqrm->get('basedir').'/plugins/image-shelf/web/image-lists/'.$imageshelf->name.'/image-shelf.conf';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}
		$command  = $this->openqrm->get('basedir').'/plugins/image-shelf/bin/openqrm-image-shelf list';
		$command .= ' -n '.$imageshelf->name;
		$command .= ' -i '.$imageshelf->uri;
		$command .= ' -u '.$imageshelf->user;
		$command .= ' -p '.$imageshelf->password;
		$command .= ' --openqrm-ui-user '.$this->user->name;
		$command .= ' --openqrm-cmd-mode background';

		$openqrm_server = new openqrm_server();
		$openqrm_server->send_command($command);
		while (!$this->file->exists($file))
		{
		  usleep(10000); // sleep 10ms to unload the CPU
		  clearstatcache();
		}
		sleep(1);
		$lines = explode("\n", $this->file->get_contents($file));
		foreach ($lines as $line) {
			if($line !== '') {
				$tmp = explode('|', $line);

				$a = $this->response->html->a();
				$a->title   = $this->lang['action_edit'];
				$a->label   = $this->lang['action_edit'];
				$a->handler = 'onclick="wait();"';
				$a->css     = 'edit';
				$a->href    = $this->response->get_url($this->actions_name, "target").'&file='.$tmp[0];

				$b[] = array(
					'file' => $tmp[0],
					'distribution' => $tmp[1],
					'application' => $tmp[3],
					'size' => $tmp[4],
					'password' => $tmp[5],
					'maintainer' => $tmp[6],
					'edit' => $a->get_string(),
				);
			}
		}

		$table = $this->response->html->tablebuilder('imageshelf_template', $this->response->get_array($this->actions_name, 'template'));
		$table->offset = 0;
		$table->sort = 'file';
		$table->limit = 10;
		$table->order = 'ASC';
		$table->id = 'Tabelle';
		$table->css = 'htmlobject_table';
		$table->border = 1;
		$table->cellspacing = 0;
		$table->cellpadding = 3;
		$table->autosort = true;
		$table->sort_link = false;
		$table->max = count($b);
		$table->head = $h;
		$table->body = $b;

		$d['table'] = $table;
		$d['name']  = $imageshelf->name;
		$d['form']  = $this->response->get_form($this->actions_name, 'template', false)->get_elements();

		return $d;
	}

}
?>
