<?php
/**
 * Hybrid-cloud Group add Permission
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_group_add_perm
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
		$this->group_name = $response->html->request()->get('group_name');
		$this->response->add('group_name', $this->group_name);
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
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-group-add-perm.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add(sprintf($this->lang['label'], $this->group_name), 'label');
		$t->add($this->response->html->request()->get('appliance_id'), 'appliance_id');
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

			$protocol = $form->get_request('protocol');
			$portnumber = $form->get_request('port_number');

			if(count($errors) > 0 || $form->get_errors()) {
				$response->error = join('<br>', $errors);
			} else {
				require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
				$hc = new hybrid_cloud();
				$hc->get_instance_by_id($this->id);
				$openqrm = new openqrm_server();

				$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-group add_perm';
				$command .= ' -i '.$hc->id;
				$command .= ' -n '.$hc->account_name;
				$command .= ' -O '.$hc->access_key;
				$command .= ' -W '.$hc->secret_key;
				$command .= ' -t '.$hc->account_type;
				$command .= ' -ar '.$this->region;
				$command .= ' -gn '.$this->group_name;
				$command .= ' -pt '.$protocol;
				$command .= ' -pp '.$portnumber;
				$command .= ' --openqrm-ui-user '.$this->user->name;
				$command .= ' --openqrm-cmd-mode background';
				$openqrm->send_command($command, NULL, true);
				sleep(4);
				$response->msg = sprintf($this->lang['msg_added_permission'], $protocol.'/'.$portnumber, $this->group_name);

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

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'add_perm');

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		$protocol_types[] = array("tcp", "tcp");
		$protocol_types[] = array("udp", "udp");

		$d['protocol']['label']                       = $this->lang['form_protocol'];
		$d['protocol']['required']                    = true;
		$d['protocol']['object']['type']              = 'htmlobject_select';
		$d['protocol']['object']['attrib']['name']    = 'protocol';
		$d['protocol']['object']['attrib']['index']   = array(0,1);
		$d['protocol']['object']['attrib']['options'] = $protocol_types;
		$d['protocol']['object']['attrib']['selected'] = array('tcp');

		$d['port_number']['label']                             = $this->lang['form_port'];
		$d['port_number']['required']                          = true;
		$d['port_number']['validate']['regex']                 = '/^[0-9]+$/i';
		$d['port_number']['validate']['errormsg']              = sprintf($this->lang['error_portnumber'], '0-9');
		$d['port_number']['object']['type']                    = 'htmlobject_input';
		$d['port_number']['object']['attrib']['id']            = 'port_number';
		$d['port_number']['object']['attrib']['name']          = 'port_number';
		$d['port_number']['object']['attrib']['type']          = 'text';
		$d['port_number']['object']['attrib']['value']         = '';
		$d['port_number']['object']['attrib']['maxlength']     = 5;

		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
