<?php
/**
 * Nagios3 Automap Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class nagios3_automap_controller
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
var $lang = array(
	'tab' => 'Nagios3 AutoConfig',
	'label' => 'Nagios3 AutoConfig',
	'action_map' => 'Map openQRM network',
	'explanation_map' => 'Click on the button below to automatically map the openQRM network into Nagios.
		Please notice that generating the Nagios configuration will take some time. You can check the status in the %s',
	'explanation_automap' => 'Click on the button below to enable/disable automatic mapping of the openQRM network into Nagios.',
	'action_eventlist' => 'Event List',
	'action_enable_automap' => 'Enable automap',
	'action_disable_automap' => 'Disable automap',
	'msg_automap_on' => 'Enabled automap',
	'msg_automap_off' => 'Disabled automap',
	'msg_mapping' => 'Started mapping openQRM network',
	'please_wait' => 'Loading. Please wait ..',
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
		$this->tpldir   = $this->rootdir.'/plugins/nagios3/tpl';
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/nagios3/lang", 'nagios3-automap.ini');
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

		switch( $this->action ) {
			case 'map':
				$msg = $this->map();
			break;
			case 'enable':
				$msg = $this->enable();
			break;
			case 'disable':
				$msg = $this->disable();
			break;
		}
		$response = $this->__get_response();
		if(isset($msg)) {
			$response->redirect(
				$response->get_url($this->actions_name, '', $this->message_param, $msg)
			);
		}

		$a          = $response->html->a();
		$a->href    = $response->html->thisfile.'?base=event';
		$a->label   = $this->lang['action_eventlist'];
		$a->title   = $this->lang['action_eventlist'];
		$a->handler = 'onclick="wait();"';

		$data['label']               = $this->lang['label'];
		$data['explanation_map']     = sprintf($this->lang['explanation_map'], $a->get_string());
		$data['explanation_automap'] = $this->lang['explanation_automap'];
		$data['please_wait']         = $this->lang['please_wait'];
		$data['prefix_tab']          = $this->prefix_tab;
		$data['baseurl']             = $this->openqrm->get('baseurl');
		$data['thisfile']            = $response->html->thisfile;
		$t = $response->html->template($this->tpldir.'/nagios3-automap.tpl.php');
		$t->add($data);
		$t->add($response->form);
		$t->group_elements(array('param_' => 'form'));

		$content[1]['label']   = $this->lang['tab'];
		$content[1]['value']   = $t;
		$content[1]['target']  = $this->response->html->thisfile;
		$content[1]['request'] = $this->response->get_array($this->actions_name, '' );
		$content[1]['onclick'] = false;

		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		return $tab;

	}

	//--------------------------------------------
	/**
	 * Map the openQRM network into Nagios
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function map() {
		$cmd = $this->openqrm->get('basedir').'/plugins/nagios3/bin/openqrm-nagios-manager map';
		$cmd .= ' --openqrm-ui-user '.$this->user->name;
		$cmd .= ' --openqrm-cmd-mode fork';
		$oqs = new openqrm_server();
		$oqs->send_command($cmd, NULL, true);
		return $this->lang['msg_mapping'];
	}

	//--------------------------------------------
	/**
	 * Enable Automap
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function enable() {
		return $this->__automap('on');
	}

	//--------------------------------------------
	/**
	 * Disable Automap
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function disable() {
		return $this->__automap('off');
	}

	//--------------------------------------------
	/**
	 * Automap
	 *
	 * @access private
	 * @param enum $mode [on|off]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function __automap($mode) {
		$cmd = $this->openqrm->get('basedir').'/plugins/nagios3/bin/openqrm-nagios-manager automap -t '. $mode;
		$oqs = new openqrm_server();
		$oqs->send_command($cmd, NULL, true);
		sleep(5);
		return $this->lang['msg_automap_'.$mode];
	}

	//--------------------------------------------
	/**
	 * Get Response
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function __get_response() {
		$response = $this->response;
		$form = $response->get_form('', '');
		$auto = $response->html->input();
		$auto->type = 'submit';
		$auto->handler = 'onclick="wait();"';
		if (file_exists($this->openqrm->get('webdir')."/plugins/nagios3/.automap")) {
			$auto->name = $this->actions_name.'[disable]';
			$auto->value = $this->lang['action_disable_automap'];
		} else {
			$auto->name = $this->actions_name.'[enable]';
			$auto->value = $this->lang['action_enable_automap'];
		}
		$form->add($auto, 'automap');

		$map = $response->html->input();
		$map->type = 'submit';
		$map->name = $this->actions_name.'[map]';
		$map->value = $this->lang['action_map'];
		$map->handler = 'onclick="wait();"';
		$form->add($map, 'map');

		$response->form = $form;
		return $response;
	}

}
?>
