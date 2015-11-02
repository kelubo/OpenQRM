<?php
/**
 *  Hybrid-cloud edit AMIs
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_ami_edit
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_ami_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_ami_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_ami_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_ami_tab';
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
		$this->filter     = $this->response->html->request()->get('hybrid_cloud_ami_filter');
		if (!strlen($this->filter)) {
			$this->filter = "a";
		}
		$this->response->add('hybrid_cloud_ami_filter', $this->filter);
		$this->region     = $response->html->request()->get('region');
		$this->ami_type     = $response->html->request()->get('ami_type');
		if (!strlen($this->ami_type)) {
			$this->ami_type = 'public';
		}
		$this->response->add('ami_type', $this->ami_type);

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);
		$this->hc = $hc;
		$deployment = $this->openqrm->deployment();
		$deployment->get_instance_by_type('ami-deployment');
		$this->deployment = $deployment;


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
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-ami-edit.tpl.php');
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

		$form = $this->response->get_form($this->actions_name, 'edit');

		unset($form->__data['submit']);
		$d=array();
		$d['submit'] = '';
		$form->add($d);

		unset($form->__data['cancel']);
		$d=array();
		$d['cancel'] = '';
		$form->add($d);

		$filter = $this->response->html->select();
		$filter->name = 'hybrid_cloud_ami_filter';
		$filter->handler = 'onchange="wait();this.form.submit();return false;"';

		$filter->add(array('private', $this->lang['table_private_ami']) ,array(0,1) );

		foreach($alphabet_arr as $index => $character) {
			$filter->add(array($character),array(0,0));
		}

		if ($this->filter == 'private') {
			$ami_type_switch_cmd_parameter = ' -ap private';
		} else {
			$ami_type_switch_cmd_parameter = ' -ap public';
		}

		$filter->selected = array($this->filter);
		$box = $this->response->html->box();
		$box->label = "Filter";
		$box->add($filter);
		$box->id = "amifilter";
		$box->css = "htmlobject_box";

		$form->add($box);

		$this->response->add('hybrid_cloud_ami_filter', $this->filter);

		$d['filter'] = $form->get_string();

		$h = array();
		$h['ami']['title'] = $this->lang['table_ami'];
		$h['path']['title'] = $this->lang['table_path'];
		$h['perm']['title'] = $this->lang['table_permission'];
		$h['arch']['title'] = $this->lang['table_arch'];
		$h['type']['title'] = $this->lang['table_type'];
		$h['virt']['title'] = $this->lang['table_virt_type'];
		$h['image']['title']      = '&#160;';
		$h['image']['sortable']   = false;

		$content = array();
		$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_images.log';
		if($this->file->exists($file)) {
			$this->file->remove($file);
		}

		$hc_authentication = '';
		if (($this->hc->account_type == 'aws') || ($this->hc->account_type == 'euca')) {
			$hc_authentication .= ' -O '.$this->hc->access_key;
			$hc_authentication .= ' -W '.$this->hc->secret_key;
			$hc_authentication .= ' -f '.$this->filter;
			$hc_authentication .= ' -ar '.$this->region;
			$hc_authentication .= $ami_type_switch_cmd_parameter;
			#$d['ami_type_switch']   = $ami_type_switch;
		}
		if ($this->hc->account_type == 'lc-openstack') {
			$hc_authentication .= ' -u '.$this->hc->username;
			$hc_authentication .= ' -p '.$this->hc->password;
			$hc_authentication .= ' -q '.$this->hc->host;
			$hc_authentication .= ' -x '.$this->hc->port;
			$hc_authentication .= ' -g '.$this->hc->tenant;
			$hc_authentication .= ' -e '.$this->hc->endpoint;
			$hc_authentication .= ' -f '.$this->filter;
			#$d['ami_type_switch']   = '';
		}

		$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-ami describe_images';
		$command .= ' -i '.$this->hc->id;
		$command .= ' -t '.$this->hc->account_type;
		$command .= ' -n '.$this->hc->account_name;
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
				// e.g. IMAGE@ami-25f9c051@021471307000/ami_hapx_ubuntu_10_04@021471307000@available@public@@i386@machine@aki-4deec439@@@ebs@paravirtual@xen
				$tmp   = explode('@', $v);
				$ami   = $tmp[1];
				$path  = $tmp[2];
				if (($this->hc->account_type == 'aws') || ($this->hc->account_type == 'euca')) {
					$state = $tmp[4];
					$perm  = $tmp[5];
					$arch  = $tmp[7];
					$type  = $tmp[13];
					$virt  = $tmp[14];
				}
				if ($this->hc->account_type == 'lc-openstack') {
					$state = '';
					$perm  = '';
					$arch  = '';
					$type  = '';
					$virt  = '';
				}
				if (($arch != "i386") && ($arch != "x86_64")) {
					$arch = '';
				}
				$image_add_remove = '';
				$deployment_type = '';
				$image = new image();
				$image->get_instance_by_name($ami);
				if (strlen($image->id)) {
					if( $image->type != $this->deployment->type ) {
						$deployment_type = $image->type;
						$disabled[] = $ami;
					} else {
						$i = $this->response->html->a();
						$i->title   = $this->lang['action_remove_image'];
						$i->label   = $this->lang['action_remove_image'];
						$i->handler = 'onclick="wait();"';
						$i->css     = 'edit';
						$i->href    = $this->response->get_url($this->actions_name, "image").'&image_id='.$image->id.'&image_command=remove';
						$image_add_remove = $i;
					}
				} else {
					$i = $this->response->html->a();
					$i->title   = $this->lang['action_add_image'];
					$i->label   = $this->lang['action_add_image'];
					$i->handler = 'onclick="wait();"';
					$i->css     = 'edit';
					$i->href    = $this->response->get_url($this->actions_name, "image").'&image_name='.$ami.'&image_rootdevice='.$ami.'&image_comment='.basename($path).'&image_command=add';


					$disabled[] = $ami;
					$image_add_remove = $i;
				}

				$b[] = array(
					'ami' => $ami,
					'path' => basename($path),
					'perm' => $perm,
					'arch' => $arch,
					'type' => $type,
					'virt' => $virt,
					'state' => $arch,
					'image' => $image_add_remove,
				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'edit');
		$table = $this->response->html->tablebuilder('hybridcloud_ami_edit', $params);
		$table->offset = 0;
		$table->sort = 'ami';
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
		$table->limit_select = array(
				array("value" => 10, "text" => 10),
				array("value" => 20, "text" => 20),
				array("value" => 30, "text" => 30),
				array("value" => 50, "text" => 50),
				array("value" => 100, "text" => 100),
				array("value" => 200, "text" => 200),
			);

		$d['name']  = $this->hc->account_name;
		$d['form']  = $this->response->get_form($this->actions_name, 'edit', false)->get_elements();
		$d['table'] = $table;

		return $d;
	}

}
?>
