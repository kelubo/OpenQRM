<?php
/**
 * Add new hybrid-cloud account
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_add
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
		$this->openqrm = $openqrm;
		$this->file = $this->openqrm->file();
		$this->user = $openqrm->user();
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
				$this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg)
			);
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}

		$a = $this->response->html->a();
		$a->label  = $this->lang['lang_help_link'];
		$a->target = '_blank';
		$a->href   = $this->openqrm->get('baseurl').'/plugins/hybrid-cloud/hybrid-cloud-example-rc-config.php';

		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-add.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add($this->lang['label'], 'label');
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

		if(!$form->get_errors() && $this->response->submit()) {
			$fi = $form->get_request();
			$hc_account_name = $fi['hybrid_cloud_account_name'];
			$hc_account_type = $form->get_static('type');
			$hc_account_type = $hc_account_type['0'];
			$fi['hybrid_cloud_account_type'] = $hc_account_type;

			$hc_authentication = '';
			if (($hc_account_type == 'aws') || ($hc_account_type == 'euca')) {
				$hc_access_key = $fi['hybrid_cloud_access_key'];
				$hc_secret_key = $fi['hybrid_cloud_secret_key'];
				$hc_authentication = ' -O '.$hc_access_key.' -W '.$hc_secret_key;
			}
			if ($hc_account_type == 'lc-openstack') {
				$hc_username = $fi['hybrid_cloud_username'];
				$hc_password = $fi['hybrid_cloud_password'];
				$hc_host = $fi['hybrid_cloud_host'];
				$hc_port = $fi['hybrid_cloud_port'];
				$hc_tenant = $fi['hybrid_cloud_tenant'];
				$hc_endpoint = $fi['hybrid_cloud_endpoint'];
				$hc_authentication = ' -u '.$hc_username.' -p '.$hc_password.' -q '.$hc_host.' -x '.$hc_port.' -g '.$hc_tenant.' -e '.$hc_endpoint;
			}

			// check if account data is valid and working
			$file = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$hc_account_name.'.acl_check.log';
			if($this->file->exists($file)) {
				$this->file->remove($file);
			}
			$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-account check';
			$command .= ' -n '.$hc_account_name;
			$command .= ' -t '.$hc_account_type;
			$command .= $hc_authentication;
			$command .= ' --openqrm-ui-user '.$this->user->name;
			$command .= ' --openqrm-cmd-mode background';
			$server = new openqrm_server();
			$server->send_command($command, NULL, true);

			while (!$this->file->exists($file))
			{
			  usleep(10000); // sleep 10ms to unload the CPU
			  clearstatcache();
			}
			$content = $this->file->get_contents($file);
			$pos = strpos($content, "success");
			if ($pos === false) {
				$response->msg = sprintf($this->lang['msg_add_fail'], $fi['hybrid_cloud_account_name']);
			} else {
				require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
				$hc = new hybrid_cloud();
				$fi['hybrid_cloud_id']  = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
				$hc->add($fi);
				$response->msg = sprintf($this->lang['msg_added'], $fi['hybrid_cloud_account_name']);
			}
			$this->file->remove($file);
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

		$type_selected = $this->response->html->request()->get('hybrid_cloud_account_type');
		if ($type_selected == '') {
			$type_selected = 'aws';
		}
		$type[] = array('aws', 'Amazon Cloud (ec2-tools)');
		$type[] = array('euca', 'Eucalyptus Cloud (eucatools)');
		$type[] = array('lc-openstack', 'OpenStack Cloud (libcloud)');
	
		$d['name']['label']                             = $this->lang['form_name'];
		$d['name']['required']                          = true;
		$d['name']['validate']['regex']                 = '/^[a-z0-9._-]+$/i';
		$d['name']['validate']['errormsg']              = sprintf($this->lang['error_name'], 'a-z0-9._-');
		$d['name']['object']['type']                    = 'htmlobject_input';
		$d['name']['object']['attrib']['id']            = 'name';
		$d['name']['object']['attrib']['name']          = 'hybrid_cloud_account_name';
		$d['name']['object']['attrib']['type']          = 'text';
		$d['name']['object']['attrib']['css']           = 'namegen';
		$d['name']['object']['attrib']['customattribs'] = 'data-prefix="account_" data-length="8"';
		$d['name']['object']['attrib']['value']         = '';
		$d['name']['object']['attrib']['maxlength']     = 50;

		$d['type']['label']                             = $this->lang['form_type'];
		$d['type']['static']                            = true;
		$d['type']['object']['type']                    = 'htmlobject_select';
		$d['type']['object']['attrib']['id']            = 'type';
		$d['type']['object']['attrib']['name']          = 'hybrid_cloud_account_type';
		$d['type']['object']['attrib']['index']         = array(0,1);
		$d['type']['object']['attrib']['options']       = $type;
		$d['type']['object']['attrib']['customattribs'] = 'onchange="this.form.submit();"';
		$d['type']['object']['attrib']['selected']      = array($type_selected);

		if (($type_selected == 'aws') || ($type_selected == 'euca')) {
			$d['access_key']['label']                         = $this->lang['form_access_key'];
			$d['access_key']['required']                      = true;
			$d['access_key']['object']['type']                = 'htmlobject_input';
			$d['access_key']['object']['attrib']['id']        = 'access_key';
			$d['access_key']['object']['attrib']['name']      = 'hybrid_cloud_access_key';
			$d['access_key']['object']['attrib']['type']      = 'text';
			$d['access_key']['object']['attrib']['value']     = '';
			$d['access_key']['object']['attrib']['maxlength'] = 255;

			$d['secret_key']['label']                         = $this->lang['form_secret_key'];
			$d['secret_key']['required']                      = true;
			$d['secret_key']['object']['type']                = 'htmlobject_input';
			$d['secret_key']['object']['attrib']['id']        = 'secret_key';
			$d['secret_key']['object']['attrib']['name']      = 'hybrid_cloud_secret_key';
			$d['secret_key']['object']['attrib']['type']      = 'text';
			$d['secret_key']['object']['attrib']['value']     = '';
			$d['secret_key']['object']['attrib']['maxlength'] = 255;
		} else {
			$d['access_key'] = '';
			$d['secret_key'] = '';
		}


		if ($type_selected == 'lc-openstack') {
			$d['username']['label']                           = $this->lang['form_username'];
			$d['username']['required']                        = true;
			$d['username']['object']['type']                  = 'htmlobject_input';
			$d['username']['object']['attrib']['id']          = 'username';
			$d['username']['object']['attrib']['name']        = 'hybrid_cloud_username';
			$d['username']['object']['attrib']['type']        = 'text';
			$d['username']['object']['attrib']['value']       = '';
			$d['username']['object']['attrib']['maxlength']   = 255;

			$d['password']['label']                           = $this->lang['form_password'];
			$d['password']['required']                        = true;
			$d['password']['object']['type']                  = 'htmlobject_input';
			$d['password']['object']['attrib']['id']          = 'password';
			$d['password']['object']['attrib']['name']        = 'hybrid_cloud_password';
			$d['password']['object']['attrib']['type']        = 'password';
			$d['password']['object']['attrib']['value']       = '';
			$d['password']['object']['attrib']['maxlength']   = 255;

			$d['tenant']['label']                             = $this->lang['form_tenant'];
			$d['tenant']['required']                          = true;
			$d['tenant']['object']['type']                    = 'htmlobject_input';
			$d['tenant']['object']['attrib']['id']            = 'tenant';
			$d['tenant']['object']['attrib']['name']          = 'hybrid_cloud_tenant';
			$d['tenant']['object']['attrib']['type']          = 'text';
			$d['tenant']['object']['attrib']['value']         = '';
			$d['tenant']['object']['attrib']['maxlength']     = 255;

			$d['host']['label']                               = $this->lang['form_host'];
			$d['host']['required']                            = true;
			$d['host']['object']['type']                      = 'htmlobject_input';
			$d['host']['object']['attrib']['id']              = 'host';
			$d['host']['object']['attrib']['name']            = 'hybrid_cloud_host';
			$d['host']['object']['attrib']['type']            = 'text';
			$d['host']['object']['attrib']['value']           = '';
			$d['host']['object']['attrib']['maxlength']       = 255;

			$d['port']['label']                               = $this->lang['form_port'];
			$d['port']['required']                            = true;
			$d['port']['object']['type']                      = 'htmlobject_input';
			$d['port']['object']['attrib']['id']              = 'port';
			$d['port']['object']['attrib']['name']            = 'hybrid_cloud_port';
			$d['port']['object']['attrib']['type']            = 'text';
			$d['port']['object']['attrib']['value']           = '';
			$d['port']['object']['attrib']['maxlength']       = 255;

			$d['endpoint']['label']                           = $this->lang['form_endpoint'];
			$d['endpoint']['required']                        = true;
			$d['endpoint']['object']['type']                  = 'htmlobject_input';
			$d['endpoint']['object']['attrib']['id']          = 'endpoint';
			$d['endpoint']['object']['attrib']['name']        = 'hybrid_cloud_endpoint';
			$d['endpoint']['object']['attrib']['type']        = 'text';
			$d['endpoint']['object']['attrib']['value']       = '';
			$d['endpoint']['object']['attrib']['maxlength']   = 255;

		} else {
			$d['username'] = '';
			$d['password'] = '';
			$d['tenant'] = '';
			$d['host'] = '';
			$d['port'] = '';
			$d['endpoint'] = '';
		}



		$d['description']['label']                         = $this->lang['form_description'];
		$d['description']['object']['type']                = 'htmlobject_textarea';
		$d['description']['object']['attrib']['id']        = 'description';
		$d['description']['object']['attrib']['name']      = 'hybrid_cloud_description';
		$d['description']['object']['attrib']['type']      = 'text';
		$d['description']['object']['attrib']['value']     = '';
		$d['description']['object']['attrib']['maxlength'] = 255;

		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
