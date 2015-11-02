<?php
/**
 * Image-shelf select Target
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class image_shelf_target
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
		$this->image = $this->response->html->request()->get('file');
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
		if(is_array($data)) {
			$t = $this->response->html->template($this->tpldir.'/image-shelf-target.tpl.php');
			$t->add($this->response->html->thisfile, "thisfile");
			$t->add($data);
			$t->add($this->response->get_array());
			$t->add(sprintf($this->lang['label'], $this->image), 'label');
			$t->add($this->openqrm->get('baseurl'), 'baseurl');
			$t->group_elements(array('param_' => 'form'));
			return $t;
		} else {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'select', $this->message_param, $data)
			);
		}
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
			$d = array();
		$id = $this->response->html->request()->get('image_id');
		if($id !== '') {
			$image = new image();
			$image->get_instance_by_id($id);
			$storage = new storage();
			$storage->get_instance_by_id($image->storageid);
			$resource = new resource();
			$resource->get_instance_by_id($storage->resource_id);

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

			$distribution = '';
			$lines = explode("\n", $this->file->get_contents($file));
			foreach($lines as $line) {
				$tmp = explode('|', $line);
				if(isset($tmp[0]) && $tmp[0] === $this->image) {
					$distribution = $tmp[1];
					break;
				}
			}

			$command  = $this->openqrm->get('basedir').'/plugins/image-shelf/bin/openqrm-image-shelf get';
			$command .= ' -n '.$imageshelf->name;
			$command .= ' -i '.$imageshelf->uri;
			$command .= ' -f '.$this->image;
			$command .= ' -s '.$resource->ip.':'.$image->rootdevice;
			$command .= ' -d '.$distribution;
			$command .= ' -u '.$imageshelf->user;
			$command .= ' -p '.$imageshelf->password;
			$command .= ' -o '.$this->openqrm->admin()->name.' -q '.$this->openqrm->admin()->password;
			$command .= ' --openqrm-ui-user '.$this->user->name;
			$command .= ' --openqrm-cmd-mode background';

			$openqrm_server->send_command($command);
			$d = sprintf($this->lang['msg'], $image->name , $this->image);

		} else {

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
			$params  = $this->response->get_array($this->actions_name, 'target');
			$b       = array();

			$table = $this->response->html->tablebuilder('imageshelf_target', $params);
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
						$image_version = "-";
					}
					// edit
					$a = $this->response->html->a();
					$a->title   = $this->lang['action_edit'];
					$a->handler = 'onclick="wait();"';
					$a->css     = 'edit';
					$a->href    = $this->response->get_url($this->actions_name, 'target').'&image_id='.$image->id;
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
			$table->form_action = $this->response->html->thisfile;
			$table->limit_select = array(
				array("value" => 10, "text" => 10),
				array("value" => 20, "text" => 20),
				array("value" => 30, "text" => 30),
				array("value" => 50, "text" => 50),
				array("value" => 100, "text" => 100),
			);

			$d['form']   = $this->response->get_form($this->actions_name, 'target', false)->get_elements();
			$d['table']  = $table;

		}

		return $d;
	}

}
?>
