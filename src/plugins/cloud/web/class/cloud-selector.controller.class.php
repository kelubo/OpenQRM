<?php
/**
 * Cloud Selector Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_selector_controller
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
var $tpldir;
/**
* translation
* @access public
* @var array
*/
var $lang = array(
	'tab' => 'Cloud Products',
	'label_product_group' => 'Product group',

	#'cloud_selector_title' => 'Cloud Products on portal ',
	'product_cpu' => 'CPU',
	'product_disk' => 'Disk',
	'product_ha' => 'Highavailability',
	'product_kernel' => 'Kernel',
	'product_memory' => 'Memory',
	'product_network' => 'Network',
	'product_application' => 'Applications',
	'product_resource' => 'Virtualization',


	'label_add_product' => 'New Product',
	#'cloud_selector_howto_add_product' => 'Use the slider to select product quantity and how much CCU to charge per hour',

	#'cloud_selector_equals' => 'equals',
	#'cloud_selector_ccu_per_hour' => 'CCU/h',

	'table_name' => 'Name',
	'table_rank' => 'Rank',
	'table_description' => 'Description',
	'table_price' => 'CCU/h',
	'table_quantity' => 'Quantity',

	#'cloud_selector_product_id' => 'ID',


	#'cloud_selector_product_state' => 'Status',
	'cloud_selector_add_successful' => 'Successfully added Cloud Product',
	'cloud_selector_product_exists' => 'Cloud %s Product with Quantity %s already exists. Not adding!',


	'action_remove' => 'Remove product',
	'action_disable' => 'Disable product',
	'action_enable' => 'Enable product',
	'action_sort_up' => 'Move product up to rank %s',
	'action_sort_down' => 'Move product down',

	'msg_remove_successful' => 'Successfully removed Cloud Product',
	'msg_disable_successful' => 'Successfully disabled Cloud Product',
	'msg_enable_successful' => 'Successfully enabled Cloud Product',
	'msg_sort_up_successful' => 'Sorted up Cloud Product',
	'msg_not_enabled' => 'The Cloud Product Mananger (Cloud Selector) is disabled. <br>Please enable it in the Main Cloud Configuration',

	'cloud_selector_product_sort_down_successful' => 'Sorted down Cloud Product',
	'error_NAN' => '%s must be a number',

	'form_name' => 'Name',
	'form_description' => 'Description',
	'form_price' => 'CCU/h',
	'form_quantity' => 'Quantity %s',
	'form_kernel' => 'Kernel',
	'form_application' => 'Application',
	'form_resource' => 'Virtualization',

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
		$this->rootdir  = $this->openqrm->get('rootdir');
		$this->webdir  = $this->openqrm->get('webdir');
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-selector.ini');
		$this->tpldir   = $this->webdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_selector_id";
		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudconfig.class.php');
		$this->cloud_config = new cloudconfig();


		$this->products = array('cpu','disk','kernel','memory', 'network', 'application', 'resource', 'ha');

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
			$this->action = "products";
		}
		$content = array();

		// enabled in main config ?
		$cloud_selector_enabled = $this->cloud_config->get_value_by_key('cloud_selector');
		if (!strcmp($cloud_selector_enabled, "true")) {
			switch( $this->action ) {
				case '':
				case 'products':
					$content[] = $this->products(true);
				break;
				case 'add':
					$content[] = $this->add(true);
				break;
				case 'remove':
					$content[] = $this->remove(true);
				break;
				case 'state':
					$content[] = $this->state(true);
				break;
				case 'up':
					$content[] = $this->up(true);
				break;
			}
		} else {
			$c['label']   = $this->lang['cloud_selector_name'];
			$c['value']   = '<div style="margin: 20px 0 0 15px;">'.$this->lang['cloud_selector_not_enabled'].'</div>';
			$c['onclick'] = false;
			$c['active']  = true;
			$c['target']  = $this->response->html->thisfile;
			$c['request'] = '';
			$content[] = $c;
		}

		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		return $tab;
	}

	//--------------------------------------------
	/**
	 * Cloud Selector Products
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function products( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-selector.products.class.php');
			$controller = new cloud_selector_products($this->openqrm, $this->response, $this);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_selector_name'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'products' );
		$content['onclick'] = false;
		if($this->action === 'products'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Selector Add
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function add( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-selector.add.class.php');
			$controller = new cloud_selector_add($this->openqrm, $this->response, $this);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['label_add_product'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'add' );
		$content['onclick'] = false;
		if($this->action === 'add'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Selector Remove Product
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-selector.remove.class.php');
			$controller = new cloud_selector_remove($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_selector_remove'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'remove' );
		$content['onclick'] = false;
		if($this->action === 'remove'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud Selector Product State
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function state( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-selector.state.class.php');
			$controller = new cloud_selector_state($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = '&#160;';
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'disable' );
		$content['onclick'] = false;
		if($this->action === 'disable'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Selector Sort up
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function up( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-selector.up.class.php');
			$controller = new cloud_selector_up($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-selector.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_selector_up'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'up' );
		$content['onclick'] = false;
		if($this->action === 'up'){
			$content['active']  = true;
		}
		return $content;
	}

}
?>
