<?php
/**
 * Nagios3 Services add
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class nagios3_services_add
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
	 * @return htmlobject_tabmenu
	 */
	//--------------------------------------------
	function action() {
		$response = $this->add();
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$response->redirect(
				$response->get_url($this->actions_name, 'select', $this->message_param, $response->msg)
			);
		}
		$data['label']           = $this->lang['label_add'];
		$data['baseurl']         = $this->openqrm->get('baseurl');
		$data['or_manually_add'] = $this->lang['or_manually_add'];
		$data['thisfile']        = $response->html->thisfile;
		$t = $response->html->template($this->tpldir.'/nagios3-services.add.tpl.php');
		$t->add($response->form);
		$t->add($data);
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * ADD
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function add() {

		$response = $this->get_response();
		$form = $response->form;
		if(	$response->submit() && !$form->get_request('auto') ) {
			if(!$form->get_request('manual_port')) {
				$form->set_error('manual_port', sprintf($response->html->lang['form']['error_required'], $this->lang['manual_port']));
			}
			if(!$form->get_request('manual_service')) {
				$form->set_error('manual_service', sprintf($response->html->lang['form']['error_required'], $this->lang['manual_service']));
			}
			if(!$form->get_request('manual_description')) {
				$form->set_error('manual_description', sprintf($response->html->lang['form']['error_required'], $this->lang['manual_description']));
			}
		}
		if(!$form->get_errors() && $response->submit()) {
			// ignore auto if manual values are set
			if($form->get_request('manual_port') && $form->get_request('manual_service')) {
				$fields['nagios3_service_port'] = $form->get_request('manual_port');
				$fields['nagios3_service_name'] = $form->get_request('manual_service');
				if($form->get_request('manual_type')) {
					$fields['nagios3_service_type'] = $form->get_request('manual_type');
				} else {
					$fields['nagios3_service_type'] = 'tcp';
				}
				if($form->get_request('manual_description')) {
					$fields['nagios3_service_description'] = $form->get_request('manual_description');
				}
			}
			else if($form->get_request('auto')) {
				$v = explode('@',$form->get_request('auto')); 
				$fields['nagios3_service_port'] = $v[0];
				$fields['nagios3_service_name'] = $v[1];
				$fields['nagios3_service_type'] = $v[2];
				$fields['nagios3_service_description'] = $v[3];
			}		
			// check port in use			
			$nagios3 = new nagios3_service();
			$result = $nagios3->get_instance_by_port($fields['nagios3_service_port']);
			if($result->port !== '') {
				$response->error = sprintf($this->lang['error_port_in_use'], $fields['nagios3_service_port']);
			}
			if(!isset($response->error)) {
				$fields['nagios3_service_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
				$error = $nagios3->add($fields);
				if(!isset($error)) {
					$response->msg = sprintf($this->lang['msg_added'], $fields['nagios3_service_name']);
 				}
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

		$content = $this->file->get_contents("/etc/services");
		$lines = explode("\n", $content);
		$select[] = array('', '');
		foreach($lines as $line) {
			if (strstr($line, "/tcp")) {
				$line = trim($line);
				$service_start = strpos($line, "/tcp");
				$service_description_start = strpos($line, "#");
				if ($service_description_start > 0) {
					$service_description = substr($line, $service_description_start+2);
					// remove description
					$line = substr($line, 0, $service_description_start);
				} else {
					$service_description = "No description available";
				}
				// find /
				$first_slash = strpos($line, '/');
				$line = substr($line, 0, $first_slash);
				list($service_name, $service_port) = sscanf($line, "%s %d");
				$select[] = array($service_port.'@'.$service_name.'@tcp@'.$service_description, 'Port:'.$service_port.' '.$service_name);
			}
		}
		$d['select']['label']                        = $this->lang['select_service'];
		$d['select']['object']['type']               = 'htmlobject_select';
		$d['select']['object']['attrib']['name']     = 'auto';
		$d['select']['object']['attrib']['index']    = array(0,1);
		$d['select']['object']['attrib']['options']  = $select;

		$d['manual_port']['label']                         = $this->lang['manual_port'];
		$d['manual_port']['validate']['regex']             = '/^[0-9]+$/i';
		$d['manual_port']['validate']['errormsg']          = $this->lang['error_manual_port'];
		$d['manual_port']['object']['type']                = 'htmlobject_input';
		$d['manual_port']['object']['attrib']['type']      = 'text';
		$d['manual_port']['object']['attrib']['maxlength'] = 5;
		$d['manual_port']['object']['attrib']['name']      = 'manual_port';

		$type[] = array('tcp', 'tcp');
		$d['manual_type']['label']                       = $this->lang['manual_type'];
		$d['manual_type']['object']['type']              = 'htmlobject_select';
		$d['manual_type']['object']['attrib']['index']   = array(0,1);
		$d['manual_type']['object']['attrib']['options'] = $type;
		$d['manual_type']['object']['attrib']['name']    = 'manual_type';

		$d['manual_service']['label']                         = $this->lang['manual_service'];
		$d['manual_service']['object']['type']                = 'htmlobject_input';
		$d['manual_service']['object']['attrib']['type']      = 'text';
		$d['manual_service']['object']['attrib']['maxlength'] = 50;
		$d['manual_service']['object']['attrib']['name']      = 'manual_service';

		$d['manual_description']['label']                         = $this->lang['manual_description'];
		$d['manual_description']['object']['type']                = 'htmlobject_input';
		$d['manual_description']['object']['attrib']['type']      = 'text';
		$d['manual_description']['object']['attrib']['maxlength'] = 255;
		$d['manual_description']['object']['attrib']['name']      = 'manual_description';
		$form->add($d);

		$response->form = $form;
		return $response;
	}


}
?>
