<?php
/**
 * Cloud Documentation SOAP API
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_about_soap
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
	    $soap_admin_function_list = '';
	    $soap_user_function_list = '';
	    $lines = file($this->webdir.'/plugins/cloud/class/cloudsoap.class.php');
	    foreach ($lines as $line_num => $line) {
		    if (strstr($line, "function ")) {
			    $function_name = str_replace("function ", "", $line);
   			    $function_name = str_replace("\t", "", $line);
			    $function_name = str_replace("{", "", $function_name);
			    $function_name = htmlspecialchars($function_name);
			    $soap_admin_function_list .= $function_name;
			    $soap_user_function_list .= $function_name;
		    }
	    }

	    $lines = file($this->webdir.'/plugins/cloud/class/cloudsoapadmin.class.php');
	    foreach ($lines as $line_num => $line) {
		    if (strstr($line, "function ")) {
			    $function_name = str_replace("function ", "", $line);
   			    $function_name = str_replace("\t", "", $line);
			    $function_name = str_replace("{", "", $function_name);
			    $function_name = htmlspecialchars($function_name);
			    $soap_admin_function_list .= $function_name;
		    }
	    }
	    $wsdl_admin_download = '<a href="/openqrm/base/plugins/cloud/soap/cloudadmin.wdsl" target="_BLANC">/openqrm/base/plugins/cloud/soap/cloudadmin.wdsl</a>';
	    $wsdl_user_download = '<a href="/cloud-portal/user/soap/clouduser.wdsl" target="_BLANC">/cloud-portal/user/soap/clouduser.wdsl</a>';
	    $template = $this->response->html->template($this->tpldir."/cloud-documentation-soap.tpl.php");
	    $template->add($this->lang['cloud_documentation_soap_title'], 'cloud_documentation_soap_title');
	    $template->add($this->lang['cloud_documentation_soap_admin_functions'], 'cloud_documentation_soap_admin_functions');
	    $template->add($this->lang['cloud_documentation_soap_admin_label'], 'cloud_documentation_soap_admin_label');
	    $template->add($this->lang['cloud_documentation_soap_user_functions'], 'cloud_documentation_soap_user_functions');
	    $template->add($this->lang['cloud_documentation_soap_user_label'], 'cloud_documentation_soap_user_label');
	    $template->add($soap_admin_function_list, 'cloud_documentation_soap_admin_function_list');
	    $template->add($soap_user_function_list, 'cloud_documentation_soap_user_function_list');
	    $template->add(sprintf($this->lang['cloud_documentation_soap_user_wsdl'], $wsdl_user_download), 'cloud_documentation_soap_user_wsdl');
	    $template->add(sprintf($this->lang['cloud_documentation_soap_admin_wsdl'], $wsdl_admin_download), 'cloud_documentation_soap_admin_wsdl');
	    $template->add($this->lang['cloud_documentation_soap_design_title'], 'cloud_documentation_soap_design_title');
	    $template->add($this->lang['cloud_documentation_soap_design'], 'cloud_documentation_soap_design');
	    $template->add($this->baseurl, 'baseurl');
	    $template->add($this->response->html->thisfile, "thisfile");
	    $template->group_elements(array('param_' => 'form'));
	    return $template;
	}

}


?>
