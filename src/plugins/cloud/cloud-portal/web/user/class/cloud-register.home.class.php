<?php
/**
 * Cloud User Portal Home
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_register_home
{
var $tpldir;
var $lang;
var $actions_name = 'cloud-register-home';

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $path path to dir
	 * @param htmlobject_response $response
	 */
	//--------------------------------------------
	function __construct($openqrm, $response) {
		$this->response = $response;
		$this->openqrm = $openqrm;
		$this->rootdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
		$this->portaldir = '/cloud-portal/';
	}

	//--------------------------------------------
	/**
	 * Cloud User Portal Home
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {
		$t = $this->response->html->template($this->tpldir."/cloud-register-home.tpl.php");
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($this->portaldir, "portaldir");
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

}
?>
