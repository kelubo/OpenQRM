<?php
/**
 * Hybrid-cloud import parameter
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_imparams
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
	function __construct($openqrm, $response, $controller) {
		$this->response = $response;
		$this->file     = $openqrm->file();
		$this->openqrm  = $openqrm;
		$this->user       = $openqrm->user();
		$this->controller = $controller;
		$this->id = $this->response->html->request()->get('hybrid_cloud_id');
		$this->response->add('hybrid_cloud_id', $this->id);
		$this->region     = $response->html->request()->get('region');
		$this->instance_name = $this->response->html->request()->get('instance_name');
		$this->instance_keypair = $this->response->html->request()->get('instance_keypair');
		$this->instance_public_ip = $this->response->html->request()->get('instance_public_ip');
		$this->instance_public_hostname = $this->response->html->request()->get('instance_public_hostname');
		$this->image_id = $this->response->html->request()->get('image_id');
		$this->response->add('instance_name', $this->instance_name);
		$this->response->add('instance_keypair', $this->instance_keypair);
		$this->response->add('instance_public_ip', $this->instance_public_ip);
		$this->response->add('instance_public_hostname', $this->instance_public_hostname);
		$this->response->add('image_id', $this->image_id);

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
		$response = $this->imparams();
		if(isset($response->msg)) {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg)
			);
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-imparams.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add($this->response->get_array());
		$t->add(sprintf($this->lang['label_target'], $this->image_id, $this->instance_name), 'label');
		$t->add($this->lang['lang_browse'], 'lang_browse');
		$t->add($this->lang['lang_browser'], 'lang_browser');
		$t->add($this->actions_name, 'actions_name');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Import Parameter
	 *
	 * @access public
	 * @return array
	 */
	//--------------------------------------------
	function imparams() {

		$response = $this->get_response();
		$form     = $response->form;
		if(!$form->get_errors() && $this->response->submit()) {

			$request = $form->get_request();

			require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
			$hc = new hybrid_cloud();
			$hc->get_instance_by_id($this->id);

			$image = new image();
			$image->get_instance_by_id($this->image_id);
			$storage = new storage();
			$storage->get_instance_by_id($image->storageid);
			$resource = new resource();
			$resource->get_instance_by_id($storage->resource_id);

			$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-migration import_instance';
			$command .= ' -i '.$hc->id;
			$command .= ' -n '.$hc->account_name;
			$command .= ' -O '.$hc->access_key;
			$command .= ' -W '.$hc->secret_key;
			$command .= ' -p '.$this->instance_public_hostname;
			$command .= ' -k '.$this->response->html->request()->get('ssh_key_file');
			$command .= ' -t '.$hc->account_type;
			$command .= ' -x '.$this->instance_name;
			$command .= ' -s '.$resource->ip.":".$image->rootdevice;
			$command .= ' --openqrm-ui-user '.$this->user->name;
			$command .= ' --openqrm-cmd-mode background';

			$server = new openqrm_server();
			$server->send_command($command, NULL, true);

			$msg = sprintf($this->lang['msg_imported'], $this->instance_name, $hc->account_name, $image->name );
			$response->msg = $msg;
			// $event = new event();
			// $event->log("hybrid_cloud_monitor", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-monitor-hook", $command, "", "", 0, 0, 0);

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

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'imparams');

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		$d['ssh_key_file']['label']                         = sprintf($this->lang['form_ssh_key_file'], $this->instance_keypair);
		$d['ssh_key_file']['required']                      = true;
		$d['ssh_key_file']['object']['type']                = 'htmlobject_input';
		$d['ssh_key_file']['object']['attrib']['id']        = 'ssh_key_file';
		$d['ssh_key_file']['object']['attrib']['name']      = 'ssh_key_file';
		$d['ssh_key_file']['object']['attrib']['type']      = 'text';
		$d['ssh_key_file']['object']['attrib']['value']     = '';
		$d['ssh_key_file']['object']['attrib']['maxlength'] = 255;

		$form->add($d);
		$response->form = $form;
		return $response;
	}


}
?>
