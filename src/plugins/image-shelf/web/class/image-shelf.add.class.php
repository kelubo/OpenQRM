<?php
/**
 * Add new image-shelf
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class image_shelf_add
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'image_shelf_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'image_shelf_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "image_shelf_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'image_shelf_tab';
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
		$t = $this->response->html->template($this->tpldir.'/image-shelf-add.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add($this->lang['label'], 'label');
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

			require_once($this->openqrm->get('basedir').'/plugins/image-shelf/web/class/imageshelf.class.php');
			$imageshelf = new imageshelf();

			$fields = $form->get_request();

			// handle url
			if($fields['imageshelf_protocol'] === 'local' && !preg_match('~^/~', $fields['imageshelf_uri'])) {
			 	$fields['imageshelf_uri'] = '/'.$fields['imageshelf_uri'];
			}
			$fields['imageshelf_id']  = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
			$fields['imageshelf_uri'] = $fields['imageshelf_protocol'].'://'.$fields['imageshelf_uri'];

			$imageshelf->add($fields);
			$response->msg = sprintf($this->lang['msg'], $fields['imageshelf_name']);
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


		$protocols[] = array('local');
		$protocols[] = array('http');
		$protocols[] = array('https');
		$protocols[] = array('ftp');
		$protocols[] = array('nfs');

		$d['protocol']['label']                       = $this->lang['form_protocol'];
		$d['protocol']['required']                    = true;
		$d['protocol']['object']['type']              = 'htmlobject_select';
		$d['protocol']['object']['attrib']['id']      = 'protocol';
		$d['protocol']['object']['attrib']['name']    = 'imageshelf_protocol';
		$d['protocol']['object']['attrib']['index']   = array(0,0);
		$d['protocol']['object']['attrib']['options'] = $protocols;
	
		$d['name']['label']                             = $this->lang['form_name'];
		$d['name']['required']                          = true;
		$d['name']['validate']['regex']                 = '/^[a-z0-9._]+$/i';
		$d['name']['validate']['errormsg']              = sprintf($this->lang['error_name'], 'a-z0-9._');
		$d['name']['object']['type']                    = 'htmlobject_input';
		$d['name']['object']['attrib']['id']            = 'name';
		$d['name']['object']['attrib']['name']          = 'imageshelf_name';
		$d['name']['object']['attrib']['type']          = 'text';
		$d['name']['object']['attrib']['css']           = 'namegen';
		$d['name']['object']['attrib']['customattribs'] = 'data-prefix="is_" data-length="10"';
		$d['name']['object']['attrib']['value']         = '';
		$d['name']['object']['attrib']['maxlength']     = 20;

		$d['url']['label']                         = $this->lang['form_url'];
		$d['url']['required']                      = true;
		$d['url']['validate']['regex']             = '~^[a-z0-9:/._-]+$~i';
		$d['url']['validate']['errormsg']          = sprintf($this->lang['error_url'], 'a-z0-9/._-');
		$d['url']['object']['type']                = 'htmlobject_input';
		$d['url']['object']['attrib']['id']        = 'url';
		$d['url']['object']['attrib']['name']      = 'imageshelf_uri';
		$d['url']['object']['attrib']['type']      = 'text';
		$d['url']['object']['attrib']['value']     = '';
		$d['url']['object']['attrib']['maxlength'] = 255;

		$d['user']['label']                         = $this->lang['form_user'];
		$d['user']['object']['type']                = 'htmlobject_input';
		$d['user']['object']['attrib']['id']        = 'user';
		$d['user']['object']['attrib']['name']      = 'imageshelf_user';
		$d['user']['object']['attrib']['type']      = 'text';
		$d['user']['object']['attrib']['value']     = '';
		$d['user']['object']['attrib']['maxlength'] = 20;

		$d['password']['label']                         = $this->lang['form_password'];
		$d['password']['object']['type']                = 'htmlobject_input';
		$d['password']['object']['attrib']['id']        = 'password';
		$d['password']['object']['attrib']['name']      = 'imageshelf_password';
		$d['password']['object']['attrib']['type']      = 'text';
		$d['password']['object']['attrib']['value']     = '';
		$d['password']['object']['attrib']['maxlength'] = 20;

		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
