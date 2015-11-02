<?php
/**
 *  Hybrid-cloud edit Volumes
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_volume_edit
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_volume_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_volume_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_volume_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_volume_tab';
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
		$this->user       = $openqrm->user();
		$this->id         = $this->response->html->request()->get('hybrid_cloud_id');
		$this->response->add('hybrid_cloud_id', $this->id);
		$this->filter     = $this->response->html->request()->get('hybrid_cloud_volume_filter');
		$this->response->add('hybrid_cloud_volume_filter', $this->filter);
		$this->region     = $response->html->request()->get('region');
		$this->volume_type     = $response->html->request()->get('volume_type');
		if (!strlen($this->volume_type)) {
			$this->volume_type = 'public';
		}
		$this->response->add('volume_type', $this->volume_type);
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
		$data = $this->edit();
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-volume-edit.tpl.php');
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
	function edit() {

		$h = array();
		$h['volume']['title'] = $this->lang['table_volume'];
		$h['id']['title'] = $this->lang['table_id'];
		$h['snapshot']['title'] = $this->lang['table_snapshot'];
		$h['zone']['title'] = $this->lang['table_zone'];
		$h['state']['title'] = $this->lang['table_state'];
		$h['date']['title']  = $this->lang['table_date'];
		$h['type']['title']  = $this->lang['table_type'];
		$h['snap']['title']      = '&#160;';
		$h['snap']['sortable']   = false;
		$h['attach']['title']      = '&#160;';
		$h['attach']['sortable']   = false;
		$h['detach']['title']      = '&#160;';
		$h['detach']['sortable']   = false;
		$h['remove']['title']      = '&#160;';
		$h['remove']['sortable']   = false;

		$content = array();
		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_volumes.log';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}

		$a = $this->response->html->a();
		$a->label   = $this->lang['action_add_volume'];
		$a->css     = 'add';
		$a->handler = 'onclick="wait();"';
		$a->href    = $this->response->get_url($this->actions_name, "add");
		$d['add_volume']   = $a->get_string();

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);

		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-ebs describe_volumes';
		$command .= ' -i '.$hc->id;
		$command .= ' -n '.$hc->account_name;
		$command .= ' -O '.$hc->access_key;
		$command .= ' -W '.$hc->secret_key;
		$command .= ' -t '.$hc->account_type;
		$command .= ' -ar '.$this->region;
		$command .= ' --openqrm-ui-user '.$this->user->name;
		$command .= ' --openqrm-cmd-mode background';

		$server = new openqrm_server();
		$server->send_command($command, NULL, true);

		while (!$this->file->exists($file))
		{
		  usleep(10000); // sleep 10ms to unload the CPU
		  clearstatcache();
		}

		$content = $this->file->get_contents($file);
		$content = explode("\n", $content);

		$b = array();
		foreach ($content as $k => $v) {
			if($v !== '') {
				// e.g. VOLUME	vol-1a2b3c4d	30	snap-1a2b3c4d	us-west-2a	available	YYYY-MM-DDTHH:MM:SS+0000	standard
				$tmp   = explode('@', $v);
				$volume   = $tmp[1];
				$id  = $tmp[2];
				$snapshot = $tmp[3];
				$zone  = $tmp[4];
				$state  = $tmp[5];
				$date  = $tmp[6];
				$type  = $tmp[7];

				$a = $this->response->html->a();
				$a->label   = $this->lang['action_snap_volume'];
				$a->css     = 'edit';
				$a->handler = 'onclick="wait();"';
				$a->href    = $this->response->get_url($this->actions_name, "snap", "volume_name", $volume);
				$a->css = 'edit';
				$snap = $a->get_string();

				if ($state == 'available') {
					$a = $this->response->html->a();
					$a->label   = $this->lang['action_attach_volume'];
					$a->css     = 'remove';
					$a->handler = 'onclick="wait();"';
					$a->href    = $this->response->get_url($this->actions_name, "attach", "volume_name", $volume);
					$a->css = 'edit';
					$attach = $a->get_string();

					$a = $this->response->html->a();
					$a->label   = $this->lang['action_remove_volume'];
					$a->css     = 'remove';
					$a->handler = 'onclick="wait();"';
					$a->href    = $this->response->get_url($this->actions_name, "remove", "volume_name", $volume);
					$a->css = 'edit';
					$remove = $a->get_string();
					
				} else {

					$a = $this->response->html->a();
					$a->label   = $this->lang['action_detach_volume'];
					$a->css     = 'remove';
					$a->handler = 'onclick="wait();"';
					$a->href    = $this->response->get_url($this->actions_name, "detach", "volume_name", $volume);
					$a->css = 'edit';
					$attach = $a->get_string();
					$remove = '';
				}

				$b[] = array(
					'volume' => $volume,
					'id' => $id,
					'snapshot' => $snapshot,
					'zone' => $zone,
					'state' => $state,
					'date' => $date,
					'type' => $type,
					'snap' => $snap,
					'attach' => $attach,
					'remove' => $remove,
				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'edit');
		$table = $this->response->html->tablebuilder('hybridcloud_import_edit', $params);
		$table->offset = 0;
		$table->sort = 'volume';
		$table->limit = 200;
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

		// handle account name
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);

		$d['name']  = $hc->account_name;
		$d['form']  = $this->response->get_form($this->actions_name, 'edit', false)->get_elements();
		$d['table'] = $table;

		return $d;
	}

}
?>
