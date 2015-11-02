<?php
/**
 * nagios-about Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class nagios3_about_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'nagios3_about_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'nagios3_about_msg';
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'nagios3_about_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'nagios3_about_identifier';
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
	'documentation' => array (
		'tab' => 'About Nagios',
		'label' => 'About Nagios',
		'introduction_title' => 'Introduction',
		'introduction_content' => 'The "Nagios" plugin integrates  <a href="http://nagios.org/" target="_BLANK">Nagios</a> into openQRM.
			It provides a convenient method to either fully automatically map and monitor your complete openQRM management network or
			to easily manually configure custom service checks per Appliance.',

		'introduction_title1' => 'Automatic Configuration',
		'introduction_content1' => 'To generate and/or update the Nagios configuration for the openQRM-network
		and managed servers use the "Config / AutoConfig" link in the Nagios-plugin menu. The nagios-configuration
		will be created automatically by scanning the network via the "nmap" utility.
		The output of the nmap run is used by "nmap2nagios-ng" to generate the Nagios-configuration.',

		'introduction_title2' => 'Custom Configuration',
		'introduction_content2' => 'Use Plugins -> Monitoring -> Nagios3 -> Config -> Services to create new Service Objects to monitor<br>
			Use Plugins -> Monitoring -> Nagios3 -> Config -> Appliances to setup custom service checks',

		'requirements_title' => 'Requirements',
		'requirements_list' => '<li>none</li>',

		'tested_title' => 'Tested with',
		'tested_content' => 'This plugin is tested with the Debian, Ubuntu and CentOS Linux distributions.',

		'provides_title' => 'Provides',
		'provides_list' => '<li>Automatically monitors System and Services of the Appliances</li>',
		'type_title' => 'Plugin Type',
		'type_content' => 'Monitoring',

		'documentation_title' => 'Documentation',
		'use_case_title' => 'Use-Case',
	),

	'usage' => array (
		'tab' => 'About Nagios',
		'label' => 'Nagios Use-Cases',
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
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/nagios3/lang", 'nagios3-about.ini');
		$this->tpldir   = $this->rootdir.'/plugins/nagios3/tpl';
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
			$this->action = $ar;
		}
		else if(isset($action)) {
			$this->action = $action;
		}
		if($this->response->cancel()) {
			$this->action = "documentation";
		}
		$content = array();
		switch( $this->action ) {
			case '':
			case 'documentation':
				$content[] = $this->documentation(true);
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
	 * About Nagios
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function documentation( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/nagios3/class/nagios3-about.documentation.class.php');
			$controller = new nagios3_about_documentation($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->message_param   = $this->message_param;
			$controller->lang            = $this->lang['documentation'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['documentation']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'documentation' );
		$content['onclick'] = false;
		if($this->action === 'documentation'){
			$content['active']  = true;
		}
		return $content;
	}


}
?>
