<?php
/**
 *  Hybrid-cloud import select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_import
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_tab';
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
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-import.tpl.php');
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
	function select() {

		$h = array();
		$h['state']['title'] = $this->lang['table_state'];
		$h['name']['title'] = $this->lang['table_name'];
		$h['region']['title'] = $this->lang['table_region'];
		$h['ami']['title'] = $this->lang['table_ami'];
		$h['type']['title'] = $this->lang['table_type'];
		$h['public']['title'] = $this->lang['table_public_ip'];
		$h['private']['title'] = $this->lang['table_private_ip'];
		$h['import']['title'] = '&#160;';
		$h['import']['sortable'] = false;

		$content = array();
		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_instances.log';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);

		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm describe';
		$command .= ' -i '.$hc->id;
		$command .= ' -n '.$hc->account_name;
		$command .= ' -O '.$hc->access_key;
		$command .= ' -W '.$hc->secret_key;
		$command .= ' -t '.$hc->account_type;
		$command .= ' -ir '.$this->region;
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
				$public_ip	= $tmp[16];
				$private_ip	= $tmp[17];
				$unknown7	= $tmp[18];
				$unknown8	= $tmp[19];
				$store		= $tmp[20];
				$unknown9	= $tmp[21];
				$unknown10	= $tmp[22];
				$unknown11	= $tmp[23];
				$unknown12	= $tmp[24];
				$hvm		= $tmp[25];
				$virt_type	= $tmp[26];
				$mac	= '';

				$select_for_import = '';
				if ($state == 'idle') {
					$state_icon="/openqrm/base/img/idle.png";
				} else if ($state == 'running') {
					$state_icon="/openqrm/base/img/active.png";
					// select for import
					$a = $this->response->html->a();
					$a->label   = $this->lang['action_import'];
					$a->title   = $this->lang['action_import'];
					$a->handler = 'onclick="wait();"';
					$a->css     = 'edit';
					$a->href    = $this->response->get_url($this->actions_name, 'imtarget').'&instance_name='.$name.'&instance_public_ip='.$public_ip.'&instance_public_hostname='.$public_hostname.'&instance_keypair='.$keypair;
					$select_for_import = $a->get_string();
				} else {
					$state_icon="/openqrm/base/img/error.png";
				}



				$b[] = array(
					'state' => "<img width=24 height=24 src=".$state_icon.">",
					'name' => $name,
					'region' => $region,
					'ami' => $ami,
					'type' => $type,
					'public' => $public_hostname,
					'private' => $private_ip,
					'import' => $select_for_import,
				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'import');
		$table = $this->response->html->tablebuilder('hybridcloud_import', $params);
		$table->offset = 0;
		$table->sort = 'id';
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

		// handle account name
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);

		$d['name']  = $hc->account_name;
		$d['form']  = $this->response->get_form($this->actions_name, 'import', false)->get_elements();
		$d['table'] = $table;

		return $d;
	}

}
?>
