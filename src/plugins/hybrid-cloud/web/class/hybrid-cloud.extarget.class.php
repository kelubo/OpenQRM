<?php
/**
 * hybrid_cloud_export Target
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_extarget
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
		$this->user       = $openqrm->user();
		$this->id = $this->response->html->request()->get('hybrid_cloud_id');
		$this->response->add('hybrid_cloud_id', $this->id);
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
		$response = $this->select();
		if(isset($response->msg)) {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg)
			);
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-extarget.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add($this->lang['lang_browse'], 'lang_browse');
		$t->add($this->lang['lang_browser'], 'lang_browser');
		$t->add($this->actions_name, 'actions_name');
		$t->add(sprintf($this->lang['label_target'],  $response->image->name, $response->hc->account_name), 'label');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Select
	 *
	 * @access public
	 * @return array
	 */
	//--------------------------------------------
	function select() {
		$response = $this->get_response();
		$form     = $response->form;
		if(!$form->get_errors() && $this->response->submit()) {

			$request = $form->get_request();
			$hc      = $response->hc;
			$image   = $response->image;

			$storage = new storage();
			$storage->get_instance_by_id($image->storageid);
			$resource = new resource();
			$resource->get_instance_by_id($storage->resource_id);

			$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-migration export_image';
			$command .= ' -i '.$hc->id;
			$command .= ' -n '.$hc->account_name;
			$command .= ' -O '.$hc->access_key;
			$command .= ' -W '.$hc->secret_key;
			$command .= ' -t '.$hc->account_type;
			$command .= ' -s '.$resource->ip.":".$image->rootdevice;
			$command .= ' -m '.$request['size'];
			$command .= ' -a '.$request['name'];
			$command .= ' -r '.$request['arch'];
			$command .= ' -u '.$request['user_id'];
			$command .= ' -y '.$request['private_key_file'];
			$command .= ' -z '.$request['public_key_file'];
			$command .= ' -l '.$this->region;
			$command .= ' --openqrm-ui-user '.$this->user->name;
			$command .= ' --openqrm-cmd-mode background';

			$server = new openqrm_server();
			$server->send_command($command, NULL, true);

			$response->msg = sprintf($this->lang['msg_exported'], $image->name, $hc->account_name );
			// $ev = new event();
			// $ev->log("hybrid_cloud_monitor", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-monitor-hook", $command, "", "", 0, 0, 0);
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

		$this->response->add('hybrid_cloud_id', $this->response->html->request()->get('hybrid_cloud_id'));
		$this->response->add('image_id', $this->response->html->request()->get('image_id'));

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'extarget');

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		$d['name']['label']                             = $this->lang['form_name'];
		$d['name']['required']                          = true;
		$d['name']['validate']['regex']                 = '/^[a-z0-9]+$/i';
		$d['name']['validate']['errormsg']              = sprintf($this->lang['error_name'], 'a-z0-9');
		$d['name']['object']['type']                    = 'htmlobject_input';
		$d['name']['object']['attrib']['name']          = 'name';
		$d['name']['object']['attrib']['id']            = 'name';
		$d['name']['object']['attrib']['type']          = 'text';
		$d['name']['object']['attrib']['css']           = 'namegen';
		$d['name']['object']['attrib']['customattribs'] = 'data-prefix="export" data-length="6"';
		$d['name']['object']['attrib']['maxlength']     = 50;
		$d['name']['object']['attrib']['minlength']     = 8;

		$size[] = array('500', '500 MB');
		$size[] = array('1000', '1 GB');
		$size[] = array('2000', '2 GB');
		$size[] = array('3000', '3 GB');
		$size[] = array('4000', '4 GB');
		$size[] = array('5000', '5 GB');
		$size[] = array('10000', '10 GB');

		$d['size']['label']                        = $this->lang['form_size'];
		$d['size']['object']['type']               = 'htmlobject_select';
		$d['size']['object']['attrib']['name']     = 'size';
		$d['size']['object']['attrib']['index']    = array(0,1);
		$d['size']['object']['attrib']['options']  = $size;

		$arch[] = array('x86_64');
		$arch[] = array('i368');

		$d['arch']['label']                        = $this->lang['form_architecture'];
		$d['arch']['object']['type']               = 'htmlobject_select';
		$d['arch']['object']['attrib']['name']     = 'arch';
		$d['arch']['object']['attrib']['index']    = array(0,0);
		$d['arch']['object']['attrib']['options']  = $arch;

		$d['public_key_file']['label']                         = $this->lang['form_public_key_file'];
		$d['public_key_file']['required']                      = true;
		$d['public_key_file']['object']['type']                = 'htmlobject_input';
		$d['public_key_file']['object']['attrib']['id']        = 'public_key_file';
		$d['public_key_file']['object']['attrib']['name']      = 'public_key_file';
		$d['public_key_file']['object']['attrib']['type']      = 'text';
		$d['public_key_file']['object']['attrib']['value']     = '';
		$d['public_key_file']['object']['attrib']['maxlength'] = 255;

		$d['private_key_file']['label']                         = $this->lang['form_private_key_file'];
		$d['private_key_file']['required']                      = true;
		$d['private_key_file']['object']['type']                = 'htmlobject_input';
		$d['private_key_file']['object']['attrib']['id']        = 'private_key_file';
		$d['private_key_file']['object']['attrib']['name']      = 'private_key_file';
		$d['private_key_file']['object']['attrib']['type']      = 'text';
		$d['private_key_file']['object']['attrib']['value']     = '';
		$d['private_key_file']['object']['attrib']['maxlength'] = 255;

		$d['user_id']['label']                         = $this->lang['form_user_id'];
		$d['user_id']['required']                      = true;
		$d['user_id']['object']['type']                = 'htmlobject_input';
		$d['user_id']['object']['attrib']['id']        = 'user_id';
		$d['user_id']['object']['attrib']['name']      = 'user_id';
		$d['user_id']['object']['attrib']['type']      = 'text';
		$d['user_id']['object']['attrib']['value']     = '';
		$d['user_id']['object']['attrib']['maxlength'] = 255;


		$form->add($d);
		$response->form = $form;

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->response->html->request()->get('hybrid_cloud_id'));
		$response->hc = $hc;

		$img = $this->openqrm->image();
		$img->get_instance_by_id($this->response->html->request()->get('image_id'));
		$response->image = $img;

		return $response;
	}


}
?>
