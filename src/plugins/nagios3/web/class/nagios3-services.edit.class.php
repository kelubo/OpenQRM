<?php
/**
 * Nagios3 Services Edit
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class nagios3_services_edit
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'nagios3_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "nagios3_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'nagios3_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'nagios3_identifier';
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
		$this->openqrm  = $openqrm;
		$this->user     = $this->openqrm->user();
		$this->rootdir  = $this->openqrm->get('webdir');
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->tpldir   = $this->rootdir.'/plugins/nagios3/tpl';

		require_once($this->openqrm->get('basedir').'/plugins/nagios3/web/class/nagios3_host.class.php');
		require_once($this->openqrm->get('basedir').'/plugins/nagios3/web/class/nagios3_service.class.php');
		$this->nagios3h = new nagios3_host();
		$this->nagios3s = new nagios3_service();
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @param string $action
	 * @return htmlobject_tabmenu
	 */
	//--------------------------------------------
	function action($action = null) {
		$response = $this->edit();
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$response->redirect(
				$response->get_url($this->actions_name, 'select', $this->message_param, $response->msg)
			);
		}
		$data['label'] = sprintf($this->lang['label_edit'], $response->nagios3->name);
		$data['baseurl'] = $this->openqrm->get('baseurl');
		$data['thisfile']    = $response->html->thisfile;
		$t = $response->html->template($this->tpldir.'/nagios3-services.edit.tpl.php');
		$t->add($response->form);
		$t->add($data);
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Edit
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function edit() {
		$response = $this->get_response();
		$form     = $response->form;
		$id       = $response->html->request()->get('service_id');
		if($id !== '') {
			if(!$form->get_errors() && $response->submit()) {
				if($form->get_request('manual_description')) {
					$fields['nagios3_service_description'] = $form->get_request('manual_description');
				}
				$error = $response->nagios3->update($id, $fields);
				$response->msg = sprintf($this->lang['msg_updated'], $response->nagios3->name);
			}
		} else {
			$response->msg = '';
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

		$this->response->add('service_id', $this->response->html->request()->get('service_id'));

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'edit');

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		$nagios3 = $this->nagios3s;
		$nagios3->get_instance_by_id($this->response->html->request()->get('service_id'));
		$d['manual_description']['label']                         = $this->lang['manual_description'];
		$d['manual_description']['object']['type']                = 'htmlobject_input';
		$d['manual_description']['object']['attrib']['type']      = 'text';
		$d['manual_description']['object']['attrib']['maxlength'] = 255;
		$d['manual_description']['object']['attrib']['name']      = 'manual_description';
		if(isset($nagios3->description)) {
			$d['manual_description']['object']['attrib']['value'] = $nagios3->description;
		}
		$response->nagios3 = $nagios3;
		if(isset($nagios3->name)) {
			$response->nagios3->name = $nagios3->name;
		}
		$form->add($d);
		$response->form = $form;

		return $response;
	}


}
?>
