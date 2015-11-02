<?php
/**
 * Nagios3 Appliance
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class nagios3_edit
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
	function action() {
		$response = $this->edit();
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$response->redirect(
				$response->get_url($this->actions_name, 'select', $this->message_param, $response->msg)
			);
		}
		$data['label'] = sprintf($this->lang['label_edit'], $response->appliance->name);
		$data['baseurl'] = $this->openqrm->get('baseurl');
		$data['thisfile']    = $response->html->thisfile;
		$t = $response->html->template($this->tpldir.'/nagios3.edit.tpl.php');
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
		$response     = $this->get_response();
		$form         = $response->form;
		$appliance_id = $response->html->request()->get('appliance_id');
		if($appliance_id !== '') {
			if(!$form->get_errors() && $response->submit()) {
				$host = new nagios3_host();
				$host = $host->get_instance_by_appliance_id($appliance_id);
				$old = explode(',', $host->appliance_services);
				$new = $form->get_request('services');
				if(isset($new[0]) && $new[0] !== '{empty}' ) {
					$fields['nagios3_appliance_services'] = implode(',', $new);
				} else {
					$fields['nagios3_appliance_services'] = 'false';
				}
				// if nagios3_host id is set -> update or remove
				if($host->id !== '') {
					// if no services -> remove
					if($fields['nagios3_appliance_services'] === 'false') {
						$error = $this->nagios3h->remove($host->id);
					} else {
						$error = $this->nagios3h->update($host->id, $fields);
					}
				} 
				else if($fields['nagios3_appliance_services'] !== 'false') {
					$fields['nagios3_host_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$fields['nagios3_appliance_id'] = $appliance_id;
					$error = $this->nagios3h->add($fields);
				}
				// remove new ports from old
				foreach($new as $id) {
					if($id !== '{empty}') {
						if(in_array($id, $old)) {
							$key = array_search($id, $old);
							unset($old[$key]);
						}
					}
				}
				// handle ports to set
				$set = array();
				foreach($new as $id) {
					if($id !== '{empty}') {
						$set[] = $this->nagios3s->get_instance_by_id($id)->port;
					}
				}
				// handle ports to unset
				$unset = array();
				foreach($old as $id) {
					if($id !== 'false') {
						$unset[] = $this->nagios3s->get_instance_by_id($id)->port;
					}
				}
				// autoselect? active?
				if($response->appliance->state == "active" || $response->appliance->resources == 0) {
					// unset
					if(count($unset) >= 1) {
						$res  = new resource();
						$cmd  = $this->openqrm->get('basedir').'/plugins/nagios3/bin/openqrm-nagios-manager remove_service';
						$cmd .= ' -n '. $response->appliance->name;
						$cmd .= ' -i '. $res->get_instance_by_id($response->appliance->resources)->ip;
						$cmd .= ' -p '. implode(',', $unset);
						$cmd .= ' --openqrm-ui-user '.$this->user->name;
						$cmd .= ' --openqrm-cmd-mode fork';
						$oqs = new openqrm_server();
						$oqs->send_command($cmd, NULL, true);
					}
					// set
					if(count($set) >= 1) {
						$res  = new resource();
						$cmd  = $this->openqrm->get('basedir').'/plugins/nagios3/bin/openqrm-nagios-manager add';
						$cmd .= ' -n '. $response->appliance->name;
						$cmd .= ' -i '. $res->get_instance_by_id($response->appliance->resources)->ip;
						$cmd .= ' -p '. implode(',', $set);
						$cmd .= ' --openqrm-ui-user '.$this->user->name;
						$cmd .= ' --openqrm-cmd-mode fork';
						$oqs = new openqrm_server();
						$oqs->send_command($cmd, NULL, true);
					}
				}
				$response->msg = sprintf($this->lang['msg_updated'], $response->appliance->name);
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
	 * @param enum $mode [select|edit]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {
		$id = $this->response->html->request()->get('appliance_id');
		$this->response->add('appliance_id', $id);

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'edit');

		// get appliance
		$appliance = $this->openqrm->appliance();
		$appliance = $appliance->get_instance_by_id($id);
		$response->appliance = $appliance;
		// get selected
		$selected = $this->nagios3h->get_instance_by_appliance_id($id);
		$selected = $selected->appliance_services;
		if($selected !== 'false') {
			$selected = explode(',', $selected);
		} else {
			$selected = array();
		}
		$select = array('{empty}', '&#160;');
		$content = $this->nagios3s->display_overview(0, 100000, 'nagios3_service_name', 'ASC');
		foreach($content as $v) {
			$o = $response->html->option();
			$o->value = $v['nagios3_service_id'];
			$o->label = $v['nagios3_service_name'];
			$o->title = $v['nagios3_service_description'];
			$select[] = $o;
		}
		$d['select']['label']                        = $this->lang['select_services'];
		$d['select']['object']['type']               = 'htmlobject_select';
		$d['select']['object']['attrib']['name']     = 'services[]';
		$d['select']['object']['attrib']['index']    = array(0,1);
		$d['select']['object']['attrib']['multiple'] = true;
		$d['select']['object']['attrib']['css']      = 'service_select';
		$d['select']['object']['attrib']['options']  = $select;
		$d['select']['object']['attrib']['selected']  = $selected;

		$form->add($d);

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		$response->form = $form;
		return $response;
	}

}
?>
