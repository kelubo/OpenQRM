<?php
/**
 * Hybrid-cloud Volume add
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_volume_add
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
		$this->region     = $this->response->html->request()->get('region');
		$this->statfile = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_volume_configuration.log';
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
		$response = $this->add();
		if(isset($response->msg)) {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'edit', $this->message_param, $response->msg)
			);
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-volume-add.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add($this->lang['label'], 'label');
		$t->add($this->lang['lang_name_generate'], 'lang_name_generate');
		$t->add($this->actions_name, 'actions_name');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Add
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function add() {
		$response = $this->get_response();
		$form     = $response->form;
		$errors = array();
		if(!$form->get_errors() && $this->response->submit()) {

			if(count($errors) > 0 || $form->get_errors()) {
				$response->error = join('<br>', $errors);
			} else {
				$tables = $this->openqrm->get('table');

				$volume_size = $form->get_request('size');
				$volume_availability_zone = $form->get_request('availability_zone');

				$volume_custom_parameter = '';
				$volume_snapshot = $form->get_request('snapshot');
				if (strlen($volume_snapshot)) {
					$volume_custom_parameter = ' -s '.$volume_snapshot;
				} else {
					$volume_custom_parameter = ' -m '.$volume_size;
				}

				$volume_iops_parameter = '';
				$volume_type = $form->get_request('type');
				$volume_iops = $form->get_request('iops');
				if ($volume_type == 'io1') {
					$volume_iops_parameter = ' -o '.$volume_iops;
				}

				require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
				$hc = new hybrid_cloud();
				$hc->get_instance_by_id($this->id);

				$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-ebs create';
				$command .= ' -i '.$hc->id;
				$command .= ' -n '.$hc->account_name;
				$command .= ' -O '.$hc->access_key;
				$command .= ' -W '.$hc->secret_key;
				$command .= ' -t '.$hc->account_type;
				$command .= ' -ar '.$this->region;
				$command .= ' -az '.$volume_availability_zone;
				$command .= ' -at '.$volume_type;
				$command .= $volume_custom_parameter;
				$command .= $volume_iops_parameter;
				$command .= ' --openqrm-ui-user '.$this->user->name;
				$command .= ' --openqrm-cmd-mode background';
				$openqrm = new openqrm_server();
				$openqrm->send_command($command, NULL, true);

				$response->msg = $this->lang['msg_added'];

				//$ev = new event();
				//$ev->log("hybrid_cloud_monitor", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-monitor-hook", $command, "", "", 0, 0, 0);

			}
		}
		return $response;
	}

	//--------------------------------------------
	/**
	 * Get Response
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {

		$size_select_arr = array();
		for ($n=1; $n<=1024; $n++) {
			$size_select_arr[] = array($n, $n);
		}
		$type_select_arr = array();
		$type_select_arr[] = array('standard', 'standard');
		$type_select_arr[] = array('io1', 'io1');

		$iops_select_arr = array();
		for ($n=100; $n<=4000; $n=$n+100) {
			$iops_select_arr[] = array($n, $n);
		}
		$snapshot_select_arr = array();
		$snapshot_select_arr[] = array('', '');

		$availability_zones_select_arr = array();
		if (file_exists($this->statfile)) {
			$lines = explode("\n", file_get_contents($this->statfile));
			if(count($lines) >= 1) {
				foreach($lines as $line) {
					if($line !== '') {
						$line = explode('@', $line);
						switch ($line[0]) {
							case 'ZONES':
								$availability_zones_select_arr[] = array($line[1],$line[1]." / ".$line[2]);
								break;
							case 'SNAPSHOTS':
								$snapshot_select_arr[] = array($line[1],$line[1]." / ".$line[2]);
								break;

						}
					}
				}
			}
		}
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'add');

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');


		$d['snapshot']['label']                       = $this->lang['form_snapshot'];
		$d['snapshot']['required']                    = false;
		$d['snapshot']['object']['type']              = 'htmlobject_select';
		$d['snapshot']['object']['attrib']['name']    = 'snapshot';
		$d['snapshot']['object']['attrib']['index']   = array(0,1);
		$d['snapshot']['object']['attrib']['options'] = $snapshot_select_arr;

		$a = $this->response->html->a();
		$a->label   = $this->lang['form_snapshots'];
		$a->handler = 'onclick="wait();"';
		$a->css     = 'add';
		$a->href    = 'index.php?plugin=hybrid-cloud&controller=hybrid-cloud-snapshot&hybrid_cloud_id='.$this->id.'&region='.$this->region;
		$d['snapshot_list']   = $a->get_string();

		$d['availability_zone']['label']                       = $this->lang['form_availability_zone'];
		$d['availability_zone']['required']                    = true;
		$d['availability_zone']['object']['type']              = 'htmlobject_select';
		$d['availability_zone']['object']['attrib']['name']    = 'availability_zone';
		$d['availability_zone']['object']['attrib']['index']   = array(0,1);
		$d['availability_zone']['object']['attrib']['options'] = $availability_zones_select_arr;

		$d['size']['label']                       = $this->lang['form_size'];
		$d['size']['required']                    = true;
		$d['size']['object']['type']              = 'htmlobject_select';
		$d['size']['object']['attrib']['name']    = 'size';
		$d['size']['object']['attrib']['index']   = array(0,1);
		$d['size']['object']['attrib']['options'] = $size_select_arr;

		$d['type']['label']                       = $this->lang['form_type'];
		$d['type']['required']                    = true;
		$d['type']['object']['type']              = 'htmlobject_select';
		$d['type']['object']['attrib']['name']    = 'type';
		$d['type']['object']['attrib']['index']   = array(0,1);
		$d['type']['object']['attrib']['options'] = $type_select_arr;
		$d['type']['object']['attrib']['selected'] = array('stanard');

		$d['iops']['label']                       = $this->lang['form_iops'];
		$d['iops']['required']                    = true;
		$d['iops']['object']['type']              = 'htmlobject_select';
		$d['iops']['object']['attrib']['name']    = 'iops';
		$d['iops']['object']['attrib']['index']   = array(0,1);
		$d['iops']['object']['attrib']['options'] = $iops_select_arr;

		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
