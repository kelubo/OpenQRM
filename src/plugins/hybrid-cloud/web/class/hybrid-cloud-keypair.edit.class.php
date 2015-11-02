<?php
/**
 *  Hybrid-cloud Instance edit
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_keypair_edit
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_keypair_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_keypair_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_keypair_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_keypair_identifier';
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
		$this->region     = $response->html->request()->get('region');

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);
		$this->hc = $hc;

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
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-vm-edit.tpl.php');
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
		$h['state']['title'] = '&#160;';
		$h['state']['sortable'] = false;
		$h['name']['title'] = $this->lang['table_name'];
		$h['key']['title'] = $this->lang['table_public_key'];
		$h['key']['sortable'] = false;
		$h['remove']['title'] = '&#160;';
		$h['remove']['sortable'] = false;

		$d['add_local_vm'] = '';
		if ($this->hc->account_type == 'lc-openstack') {
			$a = $this->response->html->a();
			$a->label   = $this->lang['action_add_keypair'];
			$a->css     = 'add';
			$a->handler = 'onclick="wait();"';
			$a->href    = $this->response->get_url($this->actions_name, "add");
			$d['add_local_vm'] = $a->get_string();
		}

		$content = array();
		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_keypair.log';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}

		$hc_authentication = '';
		if (($this->hc->account_type == 'aws') || ($this->hc->account_type == 'euca')) {
			$hc_authentication .= ' -O '.$this->hc->access_key;
			$hc_authentication .= ' -W '.$this->hc->secret_key;
			$hc_authentication .= ' -ir '.$this->region;
		}
		if ($this->hc->account_type == 'lc-openstack') {
			$hc_authentication .= ' -u '.$this->hc->username;
			$hc_authentication .= ' -p '.$this->hc->password;
			$hc_authentication .= ' -q '.$this->hc->host;
			$hc_authentication .= ' -x '.$this->hc->port;
			$hc_authentication .= ' -g '.$this->hc->tenant;
			$hc_authentication .= ' -e '.$this->hc->endpoint;
		}

		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-keypair describe';
		$command .= ' -i '.$this->hc->id;
		$command .= ' -n '.$this->hc->account_name;
		$command .= ' -t '.$this->hc->account_type;
		$command .= $hc_authentication;
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
				$tmp		= explode('@', $v);
				$name		= $tmp[1];
				$keypair	= $tmp[2];
				// remove
				$a = $this->response->html->a();
				$a->label   = $this->lang['action_remove_keypair'];
				$a->css     = 'remove';
				$a->handler = 'onclick="wait();"';
				$a->href    = $this->response->get_url($this->actions_name, "remove").'&keypair_name='.$name;
				$remove_keypair = $a->get_string();
				$state_icon="/openqrm/base/img/active.png";
				
				$b[] = array(
					'state' => "<img width=24 height=24 src=".$state_icon.">",
					'name' => $name,
					'key' => $keypair,
					'remove' => $remove_keypair,
				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'edit');
		$table = $this->response->html->tablebuilder('hybridcloud_keypair_edit', $params);
		$table->form_action = $this->response->html->thisfile;
		$table->offset = 0;
		$table->sort = 'name';
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

		$d['name']  = $this->hc->account_name;
		$d['form']  = $this->response->get_form($this->actions_name, 'edit', false)->get_elements();
		$d['table'] = $table;

		return $d;
	}

}
?>
