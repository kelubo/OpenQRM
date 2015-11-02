<?php
/**
 * Remove hybrid_cloud_account
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_remove
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
	function __construct($openqrm, $response) {
		$this->response = $response;
		$this->file     = $openqrm->file();
		$this->openqrm  = $openqrm;
		$this->response->add($this->identifier_name.'[]', '');
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
				$this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg)
			);
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-remove.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($this->lang['label_remove'], 'label');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->add($response->form);
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
		$accounts = $response->html->request()->get($this->identifier_name);
		$form     = $response->form;

		if( $accounts !== '' ) {

			require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
			$resource = new hybrid_cloud();

			$submit = $form->get_elements('submit');
			$submit->handler = 'onclick="wait();"';
			$form->add($submit, 'submit');

			$submit = $form->get_elements('cancel');
			$submit->handler = 'onclick="cancel();"';
			$form->add($submit, 'cancel');

			$i = 0;
			foreach($accounts as $id) {
				$name = $resource->get_name($id);
				$d['param_f'.$i]['label']                       = $name;
				$d['param_f'.$i]['object']['type']              = 'htmlobject_input';
				$d['param_f'.$i]['object']['attrib']['type']    = 'checkbox';
				$d['param_f'.$i]['object']['attrib']['name']    = $this->identifier_name.'['.$i.']';
				$d['param_f'.$i]['object']['attrib']['value']   = $id;
				$d['param_f'.$i]['object']['attrib']['checked'] = true;		
				$i++;
			}
			$form->add($d);
			if(!$form->get_errors() && $response->submit()) {
				$hc      = new hybrid_cloud();
				$errors  = array();
				$message = array();
				foreach($accounts as $id) {
					$hc->get_instance_by_id($id);
					$hc->remove($id);
					$form->remove($this->identifier_name.'['.$id.']');
					$message[] = sprintf($this->lang['msg_removed'], $hc->account_name);
				}
				if(count($errors) === 0) {
					$response->msg = join('<br>', $message);
				} else {
					$msg = array_merge($errors, $message);
					$response->error = join('<br>', $msg);
				}
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
