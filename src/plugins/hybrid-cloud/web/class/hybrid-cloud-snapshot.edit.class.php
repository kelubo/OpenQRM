<?php
/**
 *  Hybrid-cloud edit Snapshots
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_snapshot_edit
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_snapshot_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_snapshot_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_snapshot_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_snapshot_tab';
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
		$this->filter     = $this->response->html->request()->get('hybrid_cloud_snapshot_filter');
		$this->response->add('hybrid_cloud_snapshot_filter', $this->filter);
		$this->region     = $response->html->request()->get('region');
		$this->snapshot_type     = $response->html->request()->get('snapshot_type');
		if (!strlen($this->snapshot_type)) {
			$this->snapshot_type = 'public';
		}
		$this->response->add('snapshot_type', $this->snapshot_type);

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
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-snapshot-edit.tpl.php');
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
		// filter
		$alphabet = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";
		$alphabet_arr = explode(",", $alphabet);
		$filter_str = '';
		foreach($alphabet_arr as $index => $character) {
			$a = $this->response->html->a();
			$a->label   = $character;
			$a->css     = 'edit';
			$a->handler = 'onclick="wait();"';
			$a->href    = $this->response->get_url($this->actions_name, "edit", 'hybrid_cloud_snapshot_filter', $character);
			if($this->filter === $character) {
				$a->css = 'edit current';
			}
			$filter_str .= $a->get_string();
		}
		$d['filter']   = $filter_str;

		// public or own AMIs ?
		$snapshot_type_switch = '';
		$snapshot_type_switch_cmd_parameter = '';
		if ($this->snapshot_type == 'public') {
			$a = $this->response->html->a();
			$a->label   = $this->lang['table_private_snapshot'];
			$a->css     = 'edit';
			$a->handler = 'onclick="wait();"';
			$a->href    = $this->response->get_url($this->actions_name, "edit", "snapshot_type", "private");
			$a->css = 'edit';
			$snapshot_type_switch = $a->get_string();
			$snapshot_type_switch_cmd_parameter = ' -ap public';
		} else if ($this->snapshot_type == 'private') {
			$a = $this->response->html->a();
			$a->label   = $this->lang['table_public_snapshot'];
			$a->css     = 'edit';
			$a->handler = 'onclick="wait();"';
			$a->href    = $this->response->get_url($this->actions_name, "edit", "snapshot_type", "public");
			$a->css = 'edit';
			$snapshot_type_switch = $a->get_string();
			$snapshot_type_switch_cmd_parameter = ' -ap private';
			$d['filter']   = '';
		}
		$d['snapshot_type_switch']   = $snapshot_type_switch;

		$h = array();
		$h['snapshot']['title'] = $this->lang['table_snapshot'];
		$h['volume']['title'] = $this->lang['table_path'];
		$h['state']['title'] = $this->lang['table_state'];
		$h['from']['title'] = $this->lang['table_date'];
		$h['description']['title'] = $this->lang['table_comment'];
		$h['remove']['title']      = '&#160;';
		$h['remove']['sortable']   = false;

		$content = array();
		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_snapshots.log';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);
		if (!strlen($this->filter)) {
			$this->filter = "a";
		}

		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-ebs describe_snapshots';
		$command .= ' -i '.$hc->id;
		$command .= ' -n '.$hc->account_name;
		$command .= ' -O '.$hc->access_key;
		$command .= ' -W '.$hc->secret_key;
		$command .= ' -t '.$hc->account_type;
		$command .= ' -f '.$this->filter;
		$command .= ' -ar '.$this->region;
		$command .= $snapshot_type_switch_cmd_parameter;
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
				// e.g. SNAPSHOT@snap-fb1aecd3@vol-02747728@completed@2013-01-18T19:22:01+0000@100%@647496772601@1@pvlinux-centos-5.5-x86_1.3.0.156_130118_135827
				$tmp   = explode('@', $v);
				$snapshot   = $tmp[1];
				$volume  = $tmp[2];
				$state = $tmp[3];
				$from  = $tmp[4];
				$description  = $tmp[8];

				// remove only for own snapshots
				if ($this->snapshot_type == 'public') {
					$remove = '';
				} else if ($this->snapshot_type == 'private') {
					$a = $this->response->html->a();
					$a->label   = $this->lang['action_remove_snapshot'];
					$a->css     = 'remove';
					$a->handler = 'onclick="wait();"';
					$a->href    = $this->response->get_url($this->actions_name, "remove", "snapshot_name", $snapshot);
					$a->css = 'edit';
					$remove = $a->get_string();
				}

				$b[] = array(
					'snapshot' => $snapshot,
					'volume' => $volume,
					'state' => $state,
					'from' => $from,
					'description' => $description,
					'remove' => $remove,

				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'edit');
		$table = $this->response->html->tablebuilder('hybridcloud_snap_edit', $params);
		$table->offset = 0;
		$table->sort = 'snapshot';
		$table->limit = 100;
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
