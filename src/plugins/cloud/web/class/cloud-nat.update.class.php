<?php
/**
 * Cloud NAT Update
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_nat_update
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_nat';


	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
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
		require_once $this->rootdir."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudnat.class.php";
		$this->cloudnat = new cloudnat();
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
		$response = $this->update();

		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'update', $this->message_param, $response->msg));
		}

		$template = $response->html->template($this->tpldir."/cloud-nat-update.tpl.php");
		$template->add($this->lang['cloud_nat_update_title'], 'title');
		$template->add($response->form->get_elements());
		$template->add($this->lang['cloud_nat_explain'], 'cloud_nat_explain');
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud NAT Update
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function update() {
		$response = $this->get_response();
		$form     = $response->form;
		if(!$form->get_errors()	&& $response->submit()) {
			$data = $form->get_request();
			// update data
			if(!$form->get_errors()) {
				if ($this->cloudnat->is_id_free(1)) {
					$data['cn_id'] = 1;
					$this->cloudnat->add($data);
				} else {
					$this->cloudnat->update(1, $data);
				}
			    // success msg
			    $response->msg = $this->lang['cloud_nat_updated'];
			}
		} 
		else if($form->get_errors()) {
			$response->error = implode('<br>', $form->get_errors());
		}
		return $response;
	}


	//--------------------------------------------
	/**
	 * Get response
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {
		$response = $this->response;
		$form = $response->get_form($this->actions_name, "update");

		$this->cloudnat->get_instance(1);

		$d = array();

		$d['cn_internal_net']['label']                     = $this->lang['cloud_nat_internal_net'];
		$d['cn_internal_net']['required']                  = true;
		$d['cn_internal_net']['object']['type']            = 'htmlobject_input';
		$d['cn_internal_net']['object']['attrib']['type']  = 'text';
		$d['cn_internal_net']['object']['attrib']['id']    = 'cn_internal_net';
		$d['cn_internal_net']['object']['attrib']['name']  = 'cn_internal_net';
		$d['cn_internal_net']['object']['attrib']['value'] = $this->cloudnat->internal_network;

		$d['cn_external_net']['label']                     = $this->lang['cloud_nat_external_net'];
		$d['cn_external_net']['required']                  = true;
		$d['cn_external_net']['object']['type']            = 'htmlobject_input';
		$d['cn_external_net']['object']['attrib']['type']  = 'text';
		$d['cn_external_net']['object']['attrib']['id']    = 'cn_external_net';
		$d['cn_external_net']['object']['attrib']['name']  = 'cn_external_net';
		$d['cn_external_net']['object']['attrib']['value'] = $this->cloudnat->external_network;

		$form->add($d);
		$form->display_errors = false;
		$response->form = $form;
		return $response;
	}
}












?>
