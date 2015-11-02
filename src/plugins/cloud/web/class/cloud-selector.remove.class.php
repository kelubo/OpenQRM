<?php
/**
 * Cloud Selector Remove Product
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_selector_remove
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_selector_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "cloud_selector_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'cloud_selector_tab';


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

		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudconfig.class.php');
		$this->cloud_config = new cloudconfig();
		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudselector.class.php');
		$this->cloudselector = new cloudselector();
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
		$id = $this->response->html->request()->get('cloud_selector_id');
		if($id !== '') {
			$response = $this->remove($id);
			if(isset($response->msg)) {
				$this->response->redirect(
					$this->response->get_url($this->actions_name,  'products', $this->message_param, $response->msg).'&product='.$response->product_type
				);
			}
		} else {
			$forwarder = $this->response->html->thisfile;
			if(isset($_SERVER["HTTP_REFERER"]) && $_SERVER["HTTP_REFERER"] !== '') {
				$f = explode('?', $_SERVER["HTTP_REFERER"]);
				if(isset($f[1])) {
					$forwarder = $forwarder.'?'.$f[1];
				}
			}
			$this->response->redirect($forwarder);
		}
	}

	//--------------------------------------------
	/**
	 * Cloud Selector Remove Product
	 *
	 * @access protected
	 * @param string $id
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function remove($id) {
		$cloud_selector_id = $id;
		$this->cloudselector->get_instance_by_id($cloud_selector_id);
		$product_type = $this->cloudselector->type;
		$this->response->msg = $this->lang['msg_remove_successful'];
		$this->response->product_type = $product_type;
		$this->cloudselector->remove($cloud_selector_id);
		return $this->response;
	}

}
?>
