<?php
/**
 * Hybrid-cloud Group add
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_group_add
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
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-group-add.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add($this->lang['label'], 'label');
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

			$group_name = $form->get_request('name');
			if (!strlen($group_name)) {
				$errors[] = $this->lang['error_name'];
			}
			$group_description = $form->get_request('description');
			if (!strlen($group_description)) {
				$errors[] = $this->lang['error_description'];
			}
			$group_vpc_parameter = '';
			$group_vpc = $form->get_request('vpc');
			if (strlen($group_vpc)) {
				$group_vpc_parameter = ' -c '.$group_vpc;
			}

			if(count($errors) > 0 || $form->get_errors()) {
				$response->error = join('<br>', $errors);
			} else {
				require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
				$hc = new hybrid_cloud();
				$hc->get_instance_by_id($this->id);
				$openqrm = new openqrm_server();

				$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-group create';
				$command .= ' -i '.$hc->id;
				$command .= ' -n '.$hc->account_name;
				$command .= ' -O '.$hc->access_key;
				$command .= ' -W '.$hc->secret_key;
				$command .= ' -t '.$hc->account_type;
				$command .= ' -ar '.$this->region;
				$command .= ' -gn '.$group_name;
				$command .= ' -gd '.$group_description;
				$command .= $group_vpc_parameter;
				$command .= ' --openqrm-ui-user '.$this->user->name;
				$command .= ' --openqrm-cmd-mode background';
				$openqrm->send_command($command, NULL, true);
				sleep(4);
				$response->msg = sprintf($this->lang['msg_added_group'], $group_name);

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
		$form = $response->get_form($this->actions_name, 'add');

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		$d['name']['label']                             = $this->lang['form_name'];
		$d['name']['required']                          = true;
		$d['name']['validate']['regex']                 = '/^[a-z0-9._-]+$/i';
		$d['name']['validate']['errormsg']              = sprintf($this->lang['error_name'], 'a-z0-9._-');
		$d['name']['object']['type']                    = 'htmlobject_input';
		$d['name']['object']['attrib']['id']            = 'name';
		$d['name']['object']['attrib']['name']          = 'name';
		$d['name']['object']['attrib']['type']          = 'text';
		$d['name']['object']['attrib']['css']           = 'namegen';
		$d['name']['object']['attrib']['customattribs'] = 'data-prefix="group_" data-length="8"';
		$d['name']['object']['attrib']['value']         = '';
		$d['name']['object']['attrib']['maxlength']     = 50;

		$d['description']['label']                         = $this->lang['form_description'];
		$d['description']['required']                      = true;
		$d['description']['validate']['regex']             = '/^[a-z0-9._-]+$/i';
		$d['description']['validate']['errormsg']          = sprintf($this->lang['error_name'], 'a-z0-9._-');
		$d['description']['object']['type']                = 'htmlobject_input';
		$d['description']['object']['attrib']['id']        = 'description';
		$d['description']['object']['attrib']['name']      = 'description';
		$d['description']['object']['attrib']['type']      = 'text';
		$d['description']['object']['attrib']['value']     = '';
		$d['description']['object']['attrib']['maxlength'] = 254;

		$d['vpc']['label']                         = $this->lang['form_vpc'];
		$d['vpc']['required']                      = false;
		$d['vpc']['validate']['regex']             = '/^[a-z0-9-]+$/i';
		$d['vpc']['validate']['errormsg']          = sprintf($this->lang['error_name'], 'a-z0-9-');
		$d['vpc']['object']['type']                = 'htmlobject_input';
		$d['vpc']['object']['attrib']['id']        = 'vpc';
		$d['vpc']['object']['attrib']['name']      = 'vpc';
		$d['vpc']['object']['attrib']['type']      = 'text';
		$d['vpc']['object']['attrib']['value']     = '';
		$d['vpc']['object']['attrib']['maxlength'] = 100;

		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
