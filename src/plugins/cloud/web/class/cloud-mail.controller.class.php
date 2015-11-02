<?php
/**
 * Cloud Mail Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_mail_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_mail';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg-cloud-mail";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'project_tab';
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
	'cloud_mail_list' => 'Cloud Mail',
	'cloud_mail_name' => 'Name',
	'cloud_mail_id' => 'ID',
	'cloud_mail_send_successful' => 'Successful send mail to Cloud User %s',
	'cloud_mail_send_error' => 'Error while sending mail to Cloud User %s',
	'cloud_mail_title' => 'Send Mail to Cloud Users of portal ',
	'cloud_mail_data' => 'Mail',
	'cloud_mail_to' => 'To',
	'cloud_mail_subject' => 'Subject',
	'cloud_mail_body' => 'Body',
	'cloud_mail_all_users' => 'All Users',
    
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
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-mail.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_mail_id";
		require_once $this->rootdir."/class/htmlobjects/htmlobject.class.php";
		$this->html = new htmlobject($this->rootdir."/class/htmlobjects/");
		$this->html->lang = $this->user->translate($this->html->lang, $this->rootdir."/plugins/cloud/lang", 'htmlobjects.ini');

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
			$this->action = "send";
		}
		$content = array();
		switch( $this->action ) {
			case '':
			case 'send':
				$content[] = $this->send(true);
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
	 * Cloud Send Mail
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function send( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-mail.send.class.php');
			$controller = new cloud_mail_send($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_mail_list'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'send' );
		$content['onclick'] = false;
		if($this->action === 'send'){
			$content['active']  = true;
		}
		return $content;
	}



}
?>
