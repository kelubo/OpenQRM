<?php
/**
 * Cloud Documentation About
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_about_documentation
{
var $tpldir;
var $lang;
var $actions_name = 'cloud_documentation';


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
		$this->baseurl  = $this->openqrm->get('baseurl');
		require_once $this->webdir."/plugins/cloud/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
	}

	//--------------------------------------------
	/**
	 * Action About
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {
	    $external_portal_name = $this->cloud_config->get_value(3);  // 3 is the external name;
	    if (!strlen($external_portal_name)) {
		    $openqrm_server = new openqrm_server();
		    $openqrm_server_ip = $openqrm_server->get_ip_address();
		    $external_portal_name = "http://".$openqrm_server_ip."/cloud-portal";
	    }
	    $template = $this->response->html->template($this->tpldir."/cloud-documentation-about.tpl.php");
	    $template->add($this->lang['cloud_documentation_title'], 'title');
	    $template->add($this->lang['cloud_documentation_intro'], 'cloud_documentation_intro');
	    $template->add($this->lang['cloud_documentation_label'], 'cloud_documentation_label');
	    $template->add($this->lang['cloud_documentation_setup'], 'cloud_documentation_setup');
	    $template->add($this->lang['cloud_documentation_setup_title'], 'cloud_documentation_setup_title');
   	    $template->add($this->lang['cloud_documentation_setup_steps'], 'cloud_documentation_setup_steps');
	    $template->add($this->lang['cloud_documentation_users'], 'cloud_documentation_users');
	    $template->add($this->lang['cloud_documentation_create_user'], 'cloud_documentation_create_user');
	    $template->add($this->lang['cloud_documentation_ip_management'], 'cloud_documentation_ip_management');
	    $template->add($this->lang['cloud_documentation_ip_management_setup'], 'cloud_documentation_ip_management_setup');
		$template->add($this->lang['cloud_documentation_type_title'], 'cloud_documentation_type_title');
		$template->add($this->lang['cloud_documentation_type_content'], 'cloud_documentation_type_content');
		$template->add($this->lang['cloud_documentation_tested_title'], 'cloud_documentation_tested_title');
		$template->add($this->lang['cloud_documentation_tested_content'], 'cloud_documentation_tested_content');
	    $template->add($this->lang['cloud_documentation_api'], 'cloud_documentation_api');
	    $template->add($this->lang['cloud_documentation_soap'], 'cloud_documentation_soap');
	    $template->add($this->lang['cloud_documentation_lockfile'], 'cloud_documentation_lockfile');
	    $template->add(sprintf($this->lang['cloud_documentation_lockfile_details'], $this->rootdir."/web/action/cloud-conf/cloud-monitor.lock"), 'cloud_documentation_lockfile_details');
	    $template->add($this->baseurl, 'baseurl');
	    $template->add($external_portal_name, 'external_portal_name');
	    $template->add($this->response->html->thisfile, "thisfile");
	    $template->group_elements(array('param_' => 'form'));
	    return $template;
	}

}


?>
