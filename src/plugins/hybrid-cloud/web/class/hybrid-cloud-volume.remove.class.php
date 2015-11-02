<?php
/**
 *  Hybrid-cloud Volume remove
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_volume_remove
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
		$response = $this->remove();
		if(isset($response->msg)) {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'edit', $this->message_param, $response->msg)
			);
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-volume-remove.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($this->lang['label'], 'label');
		$t->add($this->lang['tab'], 'tab');
		$t->add($response->form);
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Remove
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function remove() {
		$response = $this->get_response();
		$volume_name  = $response->html->request()->get('volume_name');
		$form     = $response->form;

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		$d['param_f']['label']                       = $volume_name;
		$d['param_f']['object']['type']              = 'htmlobject_input';
		$d['param_f']['object']['attrib']['type']    = 'checkbox';
		$d['param_f']['object']['attrib']['name']    = 'volume_name';
		$d['param_f']['object']['attrib']['value']   = $volume_name;
		$d['param_f']['object']['attrib']['checked'] = true;
		$form->add($d);

		if(!$form->get_errors() && $response->submit()) {
			$errors = array();
			$message = array();
			require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
			$hc = new hybrid_cloud();
			$hc->get_instance_by_id($this->id);

			$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-ebs remove';
			$command .= ' -i '.$hc->id;
			$command .= ' -n '.$hc->account_name;
			$command .= ' -O '.$hc->access_key;
			$command .= ' -W '.$hc->secret_key;
			$command .= ' -t '.$hc->account_type;
			$command .= ' -ar '.$this->region;
			$command .= ' -a '.$volume_name;
			$command .= ' --openqrm-ui-user '.$this->user->name;
			$command .= ' --openqrm-cmd-mode background';
			$openqrm = new openqrm_server();
			$openqrm->send_command($command, NULL, true);
			$message[] = sprintf($this->lang['msg_removed_volume'], $volume_name);

			if(count($errors) === 0) {
				$response->msg = join('<br>', $message);
			} else {
				$msg = array_merge($errors, $message);
				$response->error = join('<br>', $msg);
			}
		}
		return $response;
	}

	//--------------------------------------------
	/**
	 * Get Response
	 *
	 * @access public
	 * @param string $mode
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {
		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'remove');
		$response->form = $form;
		return $response;
	}

}
?>
