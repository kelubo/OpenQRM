<?php
/**
 * Nagios3 Services remove
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class nagios3_services_remove
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
		$response = $this->remove();
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$response->redirect(
				$response->get_url($this->actions_name, 'select', $this->message_param, $response->msg)
			);
		}
		$data['label']       = $this->lang['label_delete'];
		$data['baseurl']     = $this->openqrm->get('baseurl');
		$data['thisfile']    = $response->html->thisfile;
		$t = $response->html->template($this->tpldir.'/nagios3-services.remove.tpl.php');
		$t->add($response->form);
		$t->add($data);
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Remove service
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function remove() {
		$response = $this->get_response();
		$ids      = $response->html->request()->get($this->identifier_name);
		$form     = $response->form;
		if( $ids !== '' ) {
			$i = 0;
			foreach($ids as $id) {
				$nagios3 = $this->nagios3s;
				$nagios3->get_instance_by_id($id);
				$d['param_f'.$i]['label']                       = $nagios3->name;
				$d['param_f'.$i]['object']['type']              = 'htmlobject_input';
				$d['param_f'.$i]['object']['attrib']['type']    = 'checkbox';
				$d['param_f'.$i]['object']['attrib']['name']    = $this->identifier_name.'['.$i.']';
				$d['param_f'.$i]['object']['attrib']['value']   = $id;
				$d['param_f'.$i]['object']['attrib']['checked'] = true;		
				$i++;
			}
			$form->add($d);
			if(!$form->get_errors() && $response->submit()) {

				$host = $this->nagios3h->display_overview(0, 100000, 'nagios3_host_id', 'ASC');
				$tmp  = '';
				foreach($host as $v) {
					$tmp .= $v['nagios3_appliance_services'].',';
				}
				$used = explode(',', $tmp);
				$used = array_unique($used);
				$errors  = array();
				$message = array();
				$nagios3 = $this->nagios3s;
				foreach($ids as $key => $id) {
					if(!in_array($id, $used)) {
						$nagios3->get_instance_by_id($id);
						$error = $nagios3->remove($id);
						$form->remove($this->identifier_name.'['.$key.']');
						$message[] = sprintf($this->lang['msg_deleted'], $nagios3->name);
					} 
					else {
						$errors[] = sprintf($this->lang['error_in_use'], $nagios3->get_instance_by_id($id)->name);
					}
				}
				if(count($errors) === 0) {
					$response->msg = join('<br>', $message);
				} else {
					$msg = array_merge($errors, $message);
					$response->error = join('<br>', $msg);
				}
			}
			else if($form->get_errors()) {
				$response->error = join('<br>', $form->get_errors());
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
		$this->response->add($this->identifier_name.'[]', '');

		$response = $this->response;

		$form = $response->get_form($this->actions_name, 'remove');

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
