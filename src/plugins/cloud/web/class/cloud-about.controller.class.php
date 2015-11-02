<?php
/**
 * Cloud Documentation Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_about_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_documentation';
/**
* message param
* @access public
* @var string
*/
var $message_param = "msg_cloud_documentation";
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
	'cloud_documentation_intro' => 'The openQRM Cloud plugin provides a fully automated request and provisioning deployment-cycle.
	    External users can submit their Cloud requests for systems via a second Web-Portal on the openQRM Server.
	    After either manually or automatic approval of the Cloud requests openQRM handles the provisioning and deployment fully automatically.',
	'cloud_documentation_setup' => 'To setup automatic deployment with the cloud-plugin first the openQRM environment needs
	    to be populated with available resources, kernels and server-images. The combination of those objects will be the base of the cloud-requests later.',
	'cloud_documentation_setup_title' => 'Setup and Requirements', 
	'cloud_documentation_setup_steps' => '<ul>
	    <li>Start some resources (phyiscal and/or virtual)</li>
	    <li>Create one (or more) storage-server</li>
	    <li>Create one (or more) server-image on the storage-servers</li></ul>',
	'cloud_documentation_users' => 'Cloud User',
	'cloud_documentation_create_user' => 'Cloud Users can be created in two different ways:
	    <br>1. User can go to http://[openqrm-server-ip]/cloud-portal and register themselves
	    <br>2. Administrators of openQRM can create Users within the Cloud-plugin UI',
	'cloud_documentation_ip_management' => 'Automatic IP-Adress assignment',
	'cloud_documentation_ip_management_setup' => 'The openQRM Cloud Plugin provides automatically network-configuration for the external interfaces of the deployed systems.
	    To enable the automatic network configuration via the "Ip-Mgmt" Plugin please follow the steps below:<ul>
	    <li>Enable and start the "Ip-Mgmt" plugin</li>
	    <li>Create one (or more) networks in the "Ip-Mgmt" plugin</li>
	    <li>Assign networks to user groups via the Ip-Mgmt configuration option</li></ul>',
	'cloud_documentation_type_title' => 'Plugin Type',
	'cloud_documentation_type_content' => 'Cloud',
	'cloud_documentation_tested_title' => 'Tested with',
	'cloud_documentation_tested_content' => 'This plugin is tested with the Debian, Ubuntu and CentOS Linux distributions.',
	'cloud_documentation_api' => 'To easily integrate with third-party provsion environments the openQRM Cloud provides a SOAP-WebService
	    for the Cloud Administrator and the Cloud Users.',
	'cloud_documentation_soap' => 'openQRM Cloud SOAP-WebService',
	'cloud_documentation_lockfile_details' => 'The Cloud creates a lockfile at <b>%s</b> to ensure transactions.',
	'cloud_documentation_lockfile' => 'Cloud Lockfile',
	'cloud_documentation_title' => 'How to use openQRM Cloud',
	'cloud_documentation_label' => 'openQRM Cloud',
	'cloud_documentation_soap_label' => 'Cloud SOAP WebService',
	'cloud_documentation_soap_title' => 'Documentation for the openQRM Cloud SOAP WebService',
	'cloud_documentation_soap_admin_label' => 'SOAP WebService for the Cloud Administrator',
	'cloud_documentation_soap_admin_functions' => 'The Cloud SOAP WebService in "admin" mode exposes the following methods :',
	'cloud_documentation_soap_user_label' => 'SOAP WebService for the Cloud Users',
	'cloud_documentation_soap_user_functions' => 'The Cloud SOAP WebService in "user" mode exposes the following methods :',
	'cloud_documentation_soap_user_wsdl' => 'The WSDL-configuration for the Cloud User SOAP WebService can be downloaded at %s',
	'cloud_documentation_soap_admin_wsdl' => 'The WSDL-configuration for the Cloud Administrator SOAP WebService can be downloaded at %s',
	'cloud_documentation_soap_design_title' => 'Basic Design',
	'cloud_documentation_soap_design' => 'The openQRM WebService is developed in PHP using its integrated SOAP functions. It is implemented conform with the SOAP Standard version 1.2.
	    <br><a href="http://www.w3.org/TR/soap12-part1/" target="_BLANK">http://www.w3.org/TR/soap12-part1/</a>
	    <br>
	    <br>
	    The openQRM Cloud SOAP-Server works in WSDL mode and provides the (automatic) provisioning- and de-provisioning functionality to a partner application.
	    <br>
	    <br>
	    Its WebService expose the Cloud-User- and Request-management of the openQRM Cloud. The functions (methods) handled by the SOAP-Server are combined into two separated PHP-Class for Administrators and Cloud Users.
	    The Classes also including methods to provide openQRM data (informations about objects in the openQRM Cloud) to a partner application.
	    <br><br>
	    Since the openQRM WebService exposes administrative actions its (SOAP-) Clients needs to be authenticated. 
	    The SOAP-Client will need to provide either a valid openQRM user name and password of an openQRM user belonging to the administrator role 
	    (in case the "Administrator part of the Cloud WebService is used) or a valid Cloud-Username plus password (in case the "User" part of the Cloud WebService is used).',

    

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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/cloud/lang", 'cloud-documentation.ini');
		$this->tpldir   = $this->rootdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_documentation_id";
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
			$this->action = "about";
		}
		$content = array();
		switch( $this->action ) {
			case '':
			case 'about':
				$content[] = $this->about(true);
				$content[] = $this->soap(false);
			break;
			case 'soap':
				$content[] = $this->about(false);
				$content[] = $this->soap(true);
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
	 * Cloud Documentation About
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function about( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-about.documentation.class.php');
			$controller = new cloud_about_documentation($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_documentation_label'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'about' );
		$content['onclick'] = false;
		if($this->action === 'about'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud Documentation SOAP API
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function soap( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/cloud/class/cloud-about.soap.class.php');
			$controller = new cloud_about_soap($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_documentation_soap_label'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'soap' );
		$content['onclick'] = false;
		if($this->action === 'soap'){
			$content['active']  = true;
		}
		return $content;
	}

	

	
}
?>
