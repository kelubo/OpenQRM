<?php
/**
 * image_shelf Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class image_shelf_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'image_shelf_action';
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
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'image_shelf_identifier';
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
var $lang = array(
	'select' => array (
		'tab' => 'Image-shelf',
		'label' => 'Select Image-shelf',
		'action_remove' => 'remove',
		'action_edit' => 'edit',
		'action_add' => 'Add a new image-shelf',
		'table_id' => 'Id',
		'table_name' => 'Name',
		'table_protocol' => 'Protocol',
		'table_uri' => 'Uri',
		'please_wait' => 'Loading. Please wait ..',
	),
	'add' => array (
		'tab' => 'New image-shelf',
		'label' => 'Add a new image-shelf',
		'msg' => 'Added image-shelf %s',
		'form_url' => 'Uri',
		'form_protocol' => 'Protocol',
		'form_user' => 'User',
		'form_password' => 'Password',
		'form_name' => 'Name',
		'error_name' => 'Name may contain %s only',
		'error_url' => 'Url may contain %s only',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'remove' => array (
		'label' => 'Remove image-shelf(s)',
		'msg' => 'Removed image-shelf %s',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'template' => array (
		'tab' => 'Image-shelf Template',
		'label' => 'Select Template from image-shelf %s',
		'action_edit' => 'edit',
		'table_file' => 'File',
		'table_distribution' => 'Distribution',
		'table_application' => 'Application',
		'table_size' => 'Size',
		'table_password' => 'Password',
		'table_maintainer' => 'Maintainer',
		'please_wait' => 'Loading. Please wait ..',
	),
	'target' => array (
		'tab' => 'Image-shelf Target',
		'label' => 'Select a target image for template %s',
		'action_edit' => 'edit',
		'table_name' => 'Name',
		'table_id' => 'ID',
		'table_version' => 'Version',
		'table_deployment' => 'Deployment',
		'table_isactive' => 'Active',
		'table_comment' => 'Comment',
		'table_name' => 'Name',
		'table_type' => 'Type',
		'table_image' => 'Image',
		'please_wait' => 'Loading. Please wait ..',
		'msg' => 'Populating image %s from template %s',
	),

);

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
		$this->tpldir   = $this->rootdir.'/plugins/image-shelf/tpl';
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/image-shelf/lang", 'image-shelf.ini');
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
	function action($action = null) {
		$this->action = '';
		$ar = $this->response->html->request()->get($this->actions_name);
		if($ar !== '') {
			if(is_array($ar)) {
				$this->action = key($ar);
			} else {
				$this->action = $ar;
			}
		} 
		else if(isset($action)) {
			$this->action = $action;
		}
		if($this->response->cancel()) {
			$this->action = "select";
		}

		// handle response
		if($this->action === 'template') {
			$this->response->add('imageshelf_id', $this->response->html->request()->get('imageshelf_id'));
		}
		if($this->action === 'target') {
			$this->response->add('imageshelf_id', $this->response->html->request()->get('imageshelf_id'));
			$this->response->add('file', $this->response->html->request()->get('file'));
		}

		$content = array();
		switch( $this->action ) {
			case '':
			case 'select':
				$content[] = $this->select(true);
			break;
			case 'add':
				$content[] = $this->select(false);
				$content[] = $this->add(true);
			break;
			case 'remove':
				$content[] = $this->select(false);
				$content[] = $this->remove(true);
			break;
			case 'template':
				$content[] = $this->select(false);
				$content[] = $this->template(true);
			break;
			case 'target':
				$content[] = $this->select(false);
				$content[] = $this->template(false);
				$content[] = $this->target(true);
			break;
		}

		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		return $tab;
	}

	
	//--------------------------------------------
	/**
	 * Select Image Shelf
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/image-shelf/class/image-shelf.select.class.php');
			$controller = new image_shelf_select($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->message_param   = $this->message_param;
			$controller->prefix_tab      = $this->prefix_tab;
			$controller->identifier_name = $this->identifier_name;
			$controller->lang            = $this->lang['select'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['select']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'select' );
		$content['onclick'] = false;
		if($this->action === 'select'){
			$content['active']  = true;
		}
		return $content;
	}
	
	//--------------------------------------------
	/**
	 * Add Image Shelf
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function add( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/image-shelf/class/image-shelf.add.class.php');
			$controller                  = new image_shelf_add($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->message_param   = $this->message_param;
			$controller->identifier_name = $this->identifier_name;
			$controller->lang            = $this->lang['add'];
			$controller->rootdir         = $this->rootdir;
			$controller->prefix_tab      = $this->prefix_tab;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['add']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'add' );
		$content['onclick'] = false;
		if($this->action === 'add' || $this->action === $this->lang['select']['action_add']){
			$content['active']  = true;
		}
		return $content;
	}	

	//--------------------------------------------
	/**
	 * Remove Image Shelf
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/image-shelf/class/image-shelf.remove.class.php');
			$controller                  = new image_shelf_remove($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->message_param   = $this->message_param;
			$controller->identifier_name = $this->identifier_name;
			$controller->lang            = $this->lang['remove'];
			$controller->rootdir         = $this->rootdir;
			$controller->prefix_tab      = $this->prefix_tab;
			$data = $controller->action();
		}
		$content['label']   = 'Remove';
		$content['hidden']  = true;
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'remove' );
		$content['onclick'] = false;
		if($this->action === 'remove' || $this->action === $this->lang['select']['action_remove']){
			$content['active']  = true;
		}
		return $content;
	}
	
	//--------------------------------------------
	/**
	 * Image Shelf Template
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function template( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/image-shelf/class/image-shelf.template.class.php');
			$controller = new image_shelf_template($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->message_param   = $this->message_param;
			$controller->prefix_tab      = $this->prefix_tab;
			$controller->identifier_name = $this->identifier_name;
			$controller->lang            = $this->lang['template'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['template']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'template' );
		$content['onclick'] = false;
		if($this->action === 'template'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Image Shelf Target
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function target( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/image-shelf/class/image-shelf.target.class.php');
			$controller = new image_shelf_target($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->message_param   = $this->message_param;
			$controller->prefix_tab      = $this->prefix_tab;
			$controller->identifier_name = $this->identifier_name;
			$controller->lang            = $this->lang['target'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['target']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'target' );
		$content['onclick'] = false;
		if($this->action === 'target'){
			$content['active']  = true;
		}
		return $content;
	}

}
?>
