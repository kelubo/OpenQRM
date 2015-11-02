<?php
/**
 * Cloud failed-Transaction Sync
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_transaction_failed_sync
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud_transaction_failed';



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
		$this->openqrm = $openqrm;
		$this->file = $this->openqrm->file();
		$this->webdir  = $this->openqrm->get('webdir');
		$this->rootdir  = $this->openqrm->get('basedir');
		require_once $this->webdir."/plugins/cloud/class/cloudtransaction.class.php";
		$this->cloudtransaction = new cloudtransaction();
		require_once $this->webdir."/plugins/cloud/class/cloudtransactionfailed.class.php";
		$this->cloudtransactionfailed = new cloudtransactionfailed();
		require_once $this->webdir."/plugins/cloud/class/clouduser.class.php";
		$this->cloud_user = new clouduser();

	}

	//--------------------------------------------
	/**
	 * Action New
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {
		if($this->response->html->request()->get($this->identifier_name) !== '') {
			$response = $this->sync();
			if(isset($response->msg)) {
				$this->response->redirect($this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg));
			}
			$template = $this->response->html->template($this->tpldir."/cloud-transaction-failed-sync.tpl.php");
			$template->add($response->form->get_elements());
			$template->add($response->html->thisfile, "thisfile");
			$template->add($this->lang['cloud_transaction_failed_confirm_sync'], 'confirm_sync');
			$template->group_elements(array('param_' => 'form'));
			return $template;
		} else {
			$this->response->redirect($this->response->get_url($this->actions_name, 'select'));
		}
	}

	//--------------------------------------------
	/**
	 * Cloud failed-Transaction Sync
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function sync() {

		$response = $this->get_response();
		$form = $response->form;
		if(!$form->get_errors() && $this->response->submit()) {
			$request = $form->get_request($this->identifier_name);
			if(isset($request) && is_array($request)) {
				$errors  = array();
				$message = array();
				foreach($request as $key => $cz_ug_id) {
					$this->cloudtransactionfailed->get_instance_by_id($cz_ug_id);
					$this->cloudtransaction->get_instance_by_id($this->cloudtransactionfailed->ct_id);
					//sync here
					if ($this->cloudtransaction->sync($this->cloudtransactionfailed->ct_id, false)) {
						$this->cloudtransactionfailed->remove($cz_ug_id);
						$message[] = $this->lang['cloud_transaction_synced']." - ".$cz_ug_id;
					} else {
						$message[] = $this->lang['cloud_transaction_sync_failed']." - ".$cz_ug_id;
					}
				}

				if(count($errors) === 0) {
					$response->msg = join('<br>', $message);
				} else {
					$msg = array_merge($errors, $message);
					$response->error = join('<br>', $msg);
				}
			}
		}
		return $response;
	}


	function get_response() {
		$tosync = $this->response->html->request()->get($this->identifier_name);
		$response =$this->response;
		$form     = $response->get_form($this->actions_name, 'sync');
		$d        = array();
		if( $tosync !== '' ) {
			$i = 0;
			foreach($tosync as $cz_ug_id) {
				$this->cloudtransactionfailed->get_instance_by_id($cz_ug_id);
				$d['param_f'.$i]['label']                       = $cz_ug_id;
				$d['param_f'.$i]['object']['type']              = 'htmlobject_input';
				$d['param_f'.$i]['object']['attrib']['type']    = 'checkbox';
				$d['param_f'.$i]['object']['attrib']['name']    = $this->identifier_name.'[]';
				$d['param_f'.$i]['object']['attrib']['id']      = $this->identifier_name.'_'.$i;
				$d['param_f'.$i]['object']['attrib']['value']   = $cz_ug_id;
				$d['param_f'.$i]['object']['attrib']['checked'] = true;
				$i++;
			}
		}
		$form->add($d);
		$response->form = $form;
		return $response;
	}



}

?>


