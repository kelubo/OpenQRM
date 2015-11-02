<?php
/**
 * Cloud Selector Sort up
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/

class cloud_selector_up
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
/**
* path to templates
* @access public
* @var string
*/


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
		$response = $this->up();
		if(isset($response->msg)) {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'products', $this->message_param, $response->msg).'&product='.$response->product_type
			);
		}
	}

	//--------------------------------------------
	/**
	 * Cloud Selector Sort up
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function up() {
		$cloud_selector_id = $this->response->html->request()->get('cloud_selector_id');
		$this->cloudselector->get_instance_by_id($cloud_selector_id);
		$product_type = $this->cloudselector->type;
		$this->response->msg = $this->lang['msg_sort_up_successful'];
		$this->response->product_type = $product_type;
		$this->cloudselector->sort("up", $cloud_selector_id, $product_type);
		return $this->response;
	}

}
?>
