<?php
/**
 * Cloud User new Instance
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_user_instance
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
		$this->file = $this->openqrm->file();
		$this->webdir  = $this->openqrm->get('webdir');
		$this->rootdir  = $this->openqrm->get('basedir');
	}

	//--------------------------------------------
	/**
	 * Action New
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function action() {

		require_once($this->openqrm->get('basedir').'/plugins/cloud/cloud-portal/web/user/class/cloud-ui.create.class.php');
		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/clouduser.class.php');

		$username = $this->response->html->request()->get('username');
		$this->response->add('username', $username);

		$user = new clouduser($username);
		$user->get_instance_by_name($username);
		$this->lang['label'] = $this->lang['label'] .' ('.$user->name.')';

		$response = $this->response->response();
		$response->add($this->actions_name, 'instance');
		$response->redirect = true;

		require_once($this->openqrm->get('basedir').'/web/base/class/openqrm.class.php');
		$openqrm = new openqrm($this->openqrm->file(), $user, $response);

		$controller = new cloud_ui_create($openqrm, $response);
		$controller->tpldir = $this->tpldir;
		$controller->lang = $this->lang;
		$controller->basedir = $this->openqrm->get('basedir');
		$controller->message_param = $this->message_param;

		$controller->use_api = false;
		$data = $controller->action();

		if( $data instanceof htmlobject_template || $data instanceof htmlobject_template_debug) {
			$data->add('', 'private_images_link');
			$data->add('', 'profiles_link');
			$data->add('', 'profiles');
			$data->add('none', 'display_price_list');
		} else {
			#$this->response->html->help($data);
		}
		return $data;
	}

}
?>
