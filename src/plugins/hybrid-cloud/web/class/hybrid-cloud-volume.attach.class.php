<?php
/**
 * Hybrid-cloud Volume attach
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_volume_attach
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
		$this->volume_name = $this->response->html->request()->get('volume_name');
		$this->response->add('volume_name', $this->volume_name);
		$this->statfile = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.attach_volume_configuration.log';		
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
		$response = $this->attach();
		if(isset($response->msg)) {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'edit', $this->message_param, $response->msg)
			);
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-volume-attach.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add($this->lang['label'], 'label');
		$t->add($this->actions_name, 'actions_name');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * attach
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function attach() {
		$response = $this->get_response();
		$form     = $response->form;
		$errors = array();
		if(!$form->get_errors() && $this->response->submit()) {

			if(count($errors) > 0 || $form->get_errors()) {
				$response->error = join('<br>', $errors);
			} else {
				$tables = $this->openqrm->get('table');

				$instance_name = $form->get_request('instances');
				$device_name = $form->get_request('devices');
				
				require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
				$hc = new hybrid_cloud();
				$hc->get_instance_by_id($this->id);

				$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-ebs attach';
				$command .= ' -i '.$hc->id;
				$command .= ' -n '.$hc->account_name;
				$command .= ' -O '.$hc->access_key;
				$command .= ' -W '.$hc->secret_key;
				$command .= ' -t '.$hc->account_type;
				$command .= ' -ar '.$this->region;
				$command .= ' -a '.$this->volume_name;
				$command .= ' -x '.$instance_name;
				$command .= ' -ad '.$device_name;
				$command .= ' --openqrm-ui-user '.$this->user->name;
				$command .= ' --openqrm-cmd-mode background';
				
				$openqrm = new openqrm_server();
				$openqrm->send_command($command, NULL, true);
				$response->msg = sprintf($this->lang['msg_attached'], $this->volume_name, $instance_name, $device_name);
				sleep(2);
				
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

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'attach');

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		
		// instance select
		$instance_select_array = array();
		if (file_exists($this->statfile)) {
			$lines = explode("\n", file_get_contents($this->statfile));
			if(count($lines) >= 1) {
				foreach($lines as $line) {
					if($line !== '') {
						$line = explode('@', $line);
						switch ($line[0]) {
							case 'INSTANCE':
								$instance_select_array[] = array($line[1],$line[1]);
								break;
						}
					}
				}
			}
		}

		// device select
		$device_select_array[] = array("/dev/sdf", "/dev/sdf");
		$device_select_array[] = array("/dev/sdg", "/dev/sdg");
		$device_select_array[] = array("/dev/sdh", "/dev/sdh");
		$device_select_array[] = array("/dev/sdi", "/dev/sdi");
		$device_select_array[] = array("/dev/sdj", "/dev/sdj");
		$device_select_array[] = array("/dev/sdk", "/dev/sdk");
		$device_select_array[] = array("/dev/sdl", "/dev/sdl");
		$device_select_array[] = array("/dev/sdm", "/dev/sdm");
		$device_select_array[] = array("/dev/sdn", "/dev/sdn");
		$device_select_array[] = array("/dev/sdo", "/dev/sdo");
		$device_select_array[] = array("/dev/sdp", "/dev/sdp");
		$device_select_array[] = array("/dev/sdq", "/dev/sdq");
		$device_select_array[] = array("/dev/sdr", "/dev/sdr");
		$device_select_array[] = array("/dev/sds", "/dev/sds");
		$device_select_array[] = array("/dev/sdt", "/dev/sdt");
		$device_select_array[] = array("/dev/sdu", "/dev/sdu");
		$device_select_array[] = array("/dev/sdv", "/dev/sdv");
		$device_select_array[] = array("/dev/sdw", "/dev/sdw");
		$device_select_array[] = array("/dev/sdx", "/dev/sdx");
		$device_select_array[] = array("/dev/sdy", "/dev/sdy");
		$device_select_array[] = array("/dev/sdz", "/dev/sdz");

		$d['instances']['label']                       = $this->lang['form_instance_name'];
		$d['instances']['required']                    = true;
		$d['instances']['object']['type']              = 'htmlobject_select';
		$d['instances']['object']['attrib']['name']    = 'instances';
		$d['instances']['object']['attrib']['index']   = array(0,1);
		$d['instances']['object']['attrib']['options'] = $instance_select_array;

		$d['devices']['label']                       = $this->lang['form_device_name'];
		$d['devices']['required']                    = true;
		$d['devices']['object']['type']              = 'htmlobject_select';
		$d['devices']['object']['attrib']['name']    = 'devices';
		$d['devices']['object']['attrib']['index']   = array(0,1);
		$d['devices']['object']['attrib']['options'] = $device_select_array;


		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
