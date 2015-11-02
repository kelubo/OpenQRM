<?php
/**
 *  Hybrid-cloud edit Groups
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_group_edit
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_group_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_group_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_group_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_group_tab';
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
		$this->filter     = $this->response->html->request()->get('hybrid_cloud_group_filter');
		$this->response->add('hybrid_cloud_group_filter', $this->filter);
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
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-group-edit.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($data);
		$t->add($this->response->get_array());
		$t->add(sprintf($this->lang['label'], $data['name']), 'label');
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

		// group management is not yet implemented in the libcloud API
		if (($this->hc->account_type == 'aws') || ($this->hc->account_type == 'euca')) {

			// filter
			$alphabet = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";
			$alphabet_arr = explode(",", $alphabet);
			$filter_str = '';
			$a = $this->response->html->a();
			$a->label   = 'none';
			$a->css     = 'edit';
			$a->handler = 'onclick="wait();"';
			$a->href    = $this->response->get_url($this->actions_name, "edit", 'hybrid_cloud_group_filter', '');
			if(!strlen($this->filter)) {
				$a->css = 'edit current';
			}
			$filter_str .= $a->get_string();
			foreach($alphabet_arr as $index => $character) {
				$a = $this->response->html->a();
				$a->label   = $character;
				$a->css     = 'edit';
				$a->handler = 'onclick="wait();"';
				$a->href    = $this->response->get_url($this->actions_name, "edit", 'hybrid_cloud_group_filter', $character);
				if($this->filter === $character) {
					$a->css = 'edit current';
				}
				$filter_str .= $a->get_string();
			}
			$d['filter']   = $filter_str;

			$a = $this->response->html->a();
			$a->label   = $this->lang['action_add_group'];
			$a->css     = 'add';
			$a->handler = 'onclick="wait();"';
			$a->href    = $this->response->get_url($this->actions_name, "add");
			$d['add_group']   = $a->get_string();
		}
		if ($this->hc->account_type == 'lc-openstack') {
			$d['add_group']   = '';
			$d['filter']   = '';
		}


		$h = array();
		$h['id']['title'] = $this->lang['table_id'];
		$h['group']['title'] = $this->lang['table_group'];
		$h['description']['title'] = $this->lang['table_description'];
		$h['protocol']['title'] = $this->lang['table_protocol'];
		$h['port']['title'] = $this->lang['table_port'];
		$h['add']['title']      = '&#160;';
		$h['add']['sortable']   = false;
		$h['remove']['title']      = '&#160;';
		$h['remove']['sortable']   = false;

		$content = array();
		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_groups.log';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}

		$filter_paramter = '';
		if (!strlen($this->filter)) {
			$filter_paramter = '';
		} else {
			$filter_paramter = ' -f '.$this->filter;
		}

		$hc_authentication = '';
		if (($this->hc->account_type == 'aws') || ($this->hc->account_type == 'euca')) {
			$hc_authentication .= ' -O '.$this->hc->access_key;
			$hc_authentication .= ' -W '.$this->hc->secret_key;
			$hc_authentication .= ' -f '.$this->filter;
			$hc_authentication .= ' -ar '.$this->region;
			$hc_authentication .= $filter_paramter;
		}
		if ($this->hc->account_type == 'lc-openstack') {
			$hc_authentication .= ' -u '.$this->hc->username;
			$hc_authentication .= ' -p '.$this->hc->password;
			$hc_authentication .= ' -q '.$this->hc->host;
			$hc_authentication .= ' -x '.$this->hc->port;
			$hc_authentication .= ' -g '.$this->hc->tenant;
			$hc_authentication .= ' -e '.$this->hc->endpoint;
			$hc_authentication .= $filter_paramter;
		}

		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-group describe_groups';
		$command .= ' -i '.$this->hc->id;
		$command .= ' -n '.$this->hc->account_name;
		$command .= ' -t '.$this->hc->account_type;
		$command .= $hc_authentication;
		$command .= $filter_paramter;
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
				// e.g. IMAGE@group-25f9c051@021471307000/group_hapx_ubuntu_10_04@021471307000@available@public@@i386@machine@aki-4deec439@@@ebs@paravirtual@xen
				$tmp   = explode('@', $v);
				$id = $tmp[1];
				$group = $tmp[2];
				$description = trim($tmp[3]);
				$protocol  = $tmp[4];
				$port  = $tmp[5];

				// group management is not yet implemented in the libcloud API
				if (($this->hc->account_type == 'aws') || ($this->hc->account_type == 'euca')) {

					if (strlen($id)) {
						$i = $this->response->html->a();
						$i->title   = $this->lang['action_remove_group'];
						$i->label   = $this->lang['action_remove_group'];
						$i->handler = 'onclick="wait();"';
						$i->css     = 'edit';
						$i->href    = $this->response->get_url($this->actions_name, "remove").'&group_name='.$group;
						$remove = $i;

						$c = $this->response->html->a();
						$c->title   = $this->lang['action_add_permission'];
						$c->label   = $this->lang['action_add_permission'];
						$c->handler = 'onclick="wait();"';
						$c->css     = 'edit';
						$c->href    = $this->response->get_url($this->actions_name, "add_perm").'&group_name='.$group;
						$add = $c;

					} else {

						$i = $this->response->html->a();
						$i->title   = $this->lang['action_remove_permission'];
						$i->label   = $this->lang['action_remove_permission'];
						$i->handler = 'onclick="wait();"';
						$i->css     = 'edit';
						$i->href    = $this->response->get_url($this->actions_name, "remove_perm").'&group_name='.$group.'&port_number='.$port.'&protocol='.$protocol;
						$remove = $i;
						$add = '';

					}
					// not remove the vpc security group
					$pos = strpos($description, "default VPC security group");
					if ($pos !== false) {
						continue;
					}
					$pos = strpos($description, "quick-start-1");
					if ($pos !== false) {
						continue;
					}
					$pos = strpos($group, "quick-start-1");
					if ($pos !== false) {
						continue;
					}
				}
				if ($this->hc->account_type == 'lc-openstack') {
					$remove = '';
					$add = '';
				}

				$b[] = array(
					'id' => $id,
					'group' => $group,
					'description' => $description,
					'protocol' => $protocol,
					'port' => $port,
					'add' => $add,
					'remove' => $remove,
				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'edit');
		$table = $this->response->html->tablebuilder('hybridcloud_group_edit', $params);
		$table->offset = 0;
		$table->sort = 'id';
		$table->limit = 200;
		$table->order = 'DESC';
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
