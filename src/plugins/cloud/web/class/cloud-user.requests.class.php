<?php
/**
 * Cloud User Requests
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_user_requests
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud_instance';



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
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {

		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/clouduser.class.php";
		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/cloudrequest.class.php";

		$username = $this->response->html->request()->get('username');
		$this->response->add('username', $username);
		$user = new clouduser($username);
		$user->get_instance_by_name($username);
		$request = new cloudrequest();
		$requests = $request->get_all_ids_per_user($user->id);

		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/cloud-request.controller.class.php";
		$controller = new cloud_request_controller($this->openqrm, $this->response);

		$str = '';
		foreach ($requests as $id) {
			$_REQUEST['cloud_request_id'] = $id['cr_id'];
			$_REQUEST[$controller->actions_name] = 'details';
			ob_start();
			$controller->api();
			$str .= ob_get_contents();
			ob_end_clean();
		}
		return $str;
	}

}
?>
