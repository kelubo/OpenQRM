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

class hybrid_cloud_vm_edit
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_vm_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_vm_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_vm_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_vm_tab';
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
		$h['state']['title'] = $this->lang['table_state'];
		$h['state']['sortable'] = false;

		$h['name']['title'] = $this->lang['table_name'];
		$h['name']['hidden'] = true;
		$h['region']['title'] = $this->lang['table_region'];
		$h['region']['hidden'] = true;
		$h['ami']['title'] = $this->lang['table_ami'];
		$h['ami']['hidden'] = true;
		$h['type']['title'] = $this->lang['table_type'];
		$h['type']['hidden'] = true;
		$h['data']['title'] = '&#160;';
		$h['data']['sortable'] = false;

		$h['public']['title'] = $this->lang['table_public_ip'];
		$h['private']['title'] = $this->lang['table_private_ip'];

		$h['remove']['title'] = '&#160;';
		$h['remove']['sortable'] = false;

		$a = $this->response->html->a();
		$a->label   = $this->lang['action_add_local_vm'];
		$a->css     = 'add';
		$a->handler = 'onclick="wait();"';
		$a->href    = $this->response->get_url($this->actions_name, "add");
		$d['add_local_vm']   = $a->get_string();

		$content = array();
		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_instances.log';
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

		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm describe';
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
				// e.g. INSTANCE@i-ec3667a1@ami-02f4fe76@ec2-54-216-10-23.eu-west-1.compute.amazonaws.com@ip-10-234-109-57.eu-west-1.compute.internal@running@home@0@@m1.small@2013-06-25T16:58:30+0000@eu-west-1a@aki-71665e05@@@monitoring-disabled@54.216.10.23@10.234.109.57@@@instance-store@@@@@paravirtual@xen@@sg-9d664de9@default@false@
				$tmp		= explode('@', $v);
				$name		= $tmp[1];
				$ami		= $tmp[2];
				$public_hostname	= $tmp[3];
				$private_hostname	= $tmp[4];
				$state		= $tmp[5];
				$keypair	= $tmp[6];
				$unknown1	= $tmp[7];
				$unknown2	= $tmp[8];
				$type		= $tmp[9];
				$date		= $tmp[10];
				$region		= $tmp[11];
				$unknown4	= $tmp[12];
				$unknown5	= $tmp[13];
				$unknown6	= $tmp[14];
				$monitoring	= $tmp[15];
				$public_ip	= $this->hc->format_ip_address($tmp[16]);
				$private_ip	= $this->hc->format_ip_address($tmp[17]);
				$vpc		= $tmp[18];
				$subnet		= $tmp[19];
				$store		= $tmp[20];
				$unknown9	= $tmp[21];
				$unknown10	= $tmp[22];
				$unknown11	= $tmp[23];
				$unknown12	= $tmp[24];
				$hvm		= $tmp[25];
				$virt_type	= $tmp[26];
				$mac	= '';
			
				// remove
				$remove_instance = '';
				$import_str = '';
				if ($state == 'idle') {
					$mac	= $tmp[30];
					$a = $this->response->html->a();
					$a->label   = $this->lang['action_remove_vm'];
					$a->css     = 'remove';
					$a->handler = 'onclick="wait();"';
					$a->href    = $this->response->get_url($this->actions_name, "remove").'&instance_name='.$name.'&instance_mac='.$mac;
					$remove_instance = $a->get_string();
					$state_icon = '<span class="pill idle">idle</span>';
				} else if ($state == 'running') {
					$state_icon= '<span class="pill active">active</span>';
					// import
					$import_resource = new resource();
					$import_resource->get_instance_id_by_hostname($name);
					if ($import_resource->id == '') {
						// import add
						if (!strlen($mac)) {
							$import_resource->generate_mac();
							$mac = $import_resource->mac;
						}
						$link  = $this->response->get_url($this->actions_name, "import");
						$link .= '&instance_command=add&instance_name='.$name;
						$link .= '&instance_mac='.$mac;
						$link .= '&instance_public_ip='.$public_ip;
						$link .= '&instance_type='.$type;
						$link .= '&instance_keypair='.$keypair;
						$link .= '&instance_region='.$region;
						$link .= '&instance_ami='.$ami;
						$link .= '&instance_subnet='.$subnet;

						$a = $this->response->html->a();
						$a->label   = $this->lang['action_import_instance'];
						$a->title   = $this->lang['action_import_instance_title'];
						$a->css     = 'add';
						$a->handler = 'onclick="wait();"';
						$a->href    = $link;
						$import_str = $a->get_string();
					}
					
				} else {
					$state_icon = '<span class="pill '.$state.'">'.$state.'</span>';
				}
				$remove = $remove_instance.$import_str;

				$data  = '<b>'.$this->lang['table_name'].'</b>: '.$name.'<br>';
				$data .= '<b>'.$this->lang['table_region'].'</b>: '.$region.'<br>';
				$data .= '<b>'.$this->lang['table_ami'].'</b>: '.$ami.'<br>';
				$data .= '<b>'.$this->lang['table_type'].'</b>: '.$type;

				$b[] = array(
					'state' => $state_icon,
					'name' => $name,
					'region' => $region,
					'ami' => $ami,
					'type' => $type,
					'data' => $data,
					'public' => $public_ip,
					'private' => $private_ip,
					'remove' => $remove,
				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'edit');
		$table = $this->response->html->tablebuilder('hybridcloud_vm_edit', $params);
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
		$d['form']  = $this->response->get_form($this->actions_name, 'import', false)->get_elements();
		$d['table'] = $table;

		return $d;
	}

}
?>
