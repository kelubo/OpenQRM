<?php
/**
 * Hybrid-cloud Keypair add
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_keypair_add
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_keypair_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_keypair_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_keypair_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_keypair_tab';
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
		$this->region     = $this->response->html->request()->get('region');
		$this->statfile = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_configuration.log';
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);
		$this->hc = $hc;
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
			// wizard
			if(isset($this->user->wizard_name) && $this->user->wizard_name === 'appliance' && $this->user->wizard_step == 2) {
				$this->response->redirect(
					$this->response->html->thisfile.'?base=appliance&appliance_action=step'.$this->user->wizard_step.'&appliance_id='.$this->user->wizard_id.'&resource_id='.$response->resource_id
				);
			} else {
				$this->response->redirect(
					$this->response->get_url($this->actions_name, 'edit', $this->message_param, $response->msg)
				);
			}
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-keypair-add.tpl.php');
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
			
			$keypair_name = $form->get_request('name');
			if (!strlen($keypair_name)) {
				$errors[] = $this->lang['error_name'];
			}
			
			if(count($errors) > 0 || $form->get_errors()) {
				$response->error = join('<br>', $errors);
			} else {

				$hc_authentication = '';
				if (($this->hc->account_type == 'aws') || ($this->hc->account_type == 'euca')) {
					$hc_authentication .= ' -O '.$this->hc->access_key;
					$hc_authentication .= ' -W '.$this->hc->secret_key;
					$hc_authentication .= ' -ir '.$this->region;
				}
				if ($this->hc->account_type == 'lc-openstack') {
					$keypair_content = $form->get_request('keypair_content');
					if (!strlen($keypair_content)) {
						$errors[] = $this->lang['error_keypair_content'];
					}
					$keypair_content = str_replace(' ', '%', $keypair_content);
					$hc_authentication .= ' -u '.$this->hc->username;
					$hc_authentication .= ' -p '.$this->hc->password;
					$hc_authentication .= ' -q '.$this->hc->host;
					$hc_authentication .= ' -x '.$this->hc->port;
					$hc_authentication .= ' -g '.$this->hc->tenant;
					$hc_authentication .= ' -e '.$this->hc->endpoint;
					$hc_authentication .= ' -c '.$keypair_content;
				}

				$openqrm = new openqrm_server();
				$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-keypair create';
				$command .= ' -i '.$this->hc->id;
				$command .= ' -n '.$this->hc->account_name;
				$command .= ' -t '.$this->hc->account_type;
				$command .= $hc_authentication;
				$command .= ' -k '.$keypair_name;
				$command .= ' --openqrm-ui-user '.$this->user->name;
				$command .= ' --openqrm-cmd-mode background';
				$openqrm->send_command($command, NULL, true);
				sleep(4);
				$response->msg = sprintf($this->lang['msg_added'], $keypair_name);
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
		$d['name']['validate']['regex']                 = '/^[a-z0-9._]+$/i';
		$d['name']['validate']['errormsg']              = sprintf($this->lang['error_name'], 'a-z0-9._');
		$d['name']['object']['type']                    = 'htmlobject_input';
		$d['name']['object']['attrib']['id']            = 'name';
		$d['name']['object']['attrib']['name']          = 'name';
		$d['name']['object']['attrib']['type']          = 'text';
		$d['name']['object']['attrib']['css']           = 'namegen';
		$d['name']['object']['attrib']['customattribs'] = 'data-prefix="kp" data-length="6"';
		$d['name']['object']['attrib']['value']         = '';
		$d['name']['object']['attrib']['maxlength']     = 50;

		if ($this->hc->account_type == 'lc-openstack') {
			$d['keypair_content']['label']                             = $this->lang['form_keypair'];
			$d['keypair_content']['required']                          = true;
			$d['keypair_content']['object']['type']                    = 'htmlobject_input';
			$d['keypair_content']['object']['attrib']['id']            = 'keypair_content';
			$d['keypair_content']['object']['attrib']['name']          = 'keypair_content';
			$d['keypair_content']['object']['attrib']['type']          = 'text';
			$d['keypair_content']['object']['attrib']['value']         = '';
		}
		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
