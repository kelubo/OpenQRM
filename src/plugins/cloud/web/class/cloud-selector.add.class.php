<?php
/**
 * Cloud Selector Add
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_selector_add
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
	function __construct($openqrm, $response, $controller) {
		$this->response = $response;
		$this->openqrm = $openqrm;
		$this->file = $this->openqrm->file();
		$this->controller = $controller;
		$this->product = 'cpu';
		$product = $this->response->html->request()->get('product');
		if($product !== '' && in_array($product, $this->controller->products)) {
			$this->product = $product;
		}
		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudselector.class.php');
		$this->cloudselector = new cloudselector();
		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudapplication.class.php');
		$this->cloudapplication = new cloudapplication();
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
		$response = $this->add();
		if(isset($response->error)) {
			$request = $response->form->get_request(null, true);
			$params  = '&product='.$response->product_type;
			if(is_array($request)) {
				foreach($request as $key => $value) {
					$params .= '&'.$key.'='.$value;
				}
			}
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'products', $this->message_param, $response->error).$params
			);
		}
		else if(isset($response->msg)) {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'products', $this->message_param, $response->msg).'&product='.$response->product_type
			);
		}
		$t = $this->response->html->template($this->tpldir."/cloud-selector-add.tpl.php");
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->group_elements(array('param_' => 'form'));

		return $t;
	}

	//--------------------------------------------
	/**
	 * Cloud Selector Add
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function add() {

		$response = $this->get_response();
		$form = $response->form;
		$product_type = $this->response->html->request()->get('product');

		if(!$form->get_errors() && $response->submit()) {
			$product_name = $this->response->html->request()->get('product_name');
			$product_quantity = $this->response->html->request()->get('product_quantity');
			$product_price = $this->response->html->request()->get('product_price');
			$product_description = $this->response->html->request()->get('product_description');
			// handle name
			if($product_type === 'cpu' || $product_type === 'network') {
				$product_name = $product_quantity;
			}
			else if($product_type === 'disk' || $product_type === 'memory') {
				if($product_quantity > 1000) {
					$product_name = ($product_quantity/1000).' GB';
				} else {
					$product_name = $product_quantity.' MB';
				}
			}
			else if($product_type === 'resource') {
				$virt = $this->openqrm->virtualization();
				$virt->get_instance_by_id($product_quantity);
				$product_name = $virt->name;
			}
			else if($product_type === 'ha') {
				$product_name = 'ha';
			}
			if ($this->cloudselector->product_exists($product_type, $product_quantity)) {
				$response->msg = sprintf($this->lang['cloud_selector_product_exists'], $product_type, $product_quantity);
			} else {
				$new_product_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
				$next_free_sort_id = $this->cloudselector->get_next_free_sort_id($product_type);
				$new_product['id'] = $new_product_id;
				$new_product['type'] = $product_type;
				$new_product['sort_id'] = $next_free_sort_id;
				$new_product['quantity'] = $product_quantity;
				$new_product['price'] = $product_price;
				$new_product['name'] = $product_name;
				$new_product['description'] = $product_description;
				$new_product['state'] = 1;
				$this->cloudselector->add($new_product);
				$response->error = $this->lang['cloud_selector_add_successful'];
			}
		}
		else if($form->get_errors() && $response->submit()) {
			$response->error = implode('<br>',$form->get_errors());
		}
		$response->product_type = $product_type;
		return $response;
	}


	function get_response() {
		$response = $this->response->response();
		$response->id = 'addform';
		$response->add('product', $this->product);
		$form = $response->get_form($this->actions_name, 'add');

		$d = array();
		$d['product_name']['label']                         = $this->lang['form_name'];
		$d['product_name']['required']                      = true;
		$d['product_name']['object']['type']                = 'htmlobject_input';
		$d['product_name']['object']['attrib']['id']        = 'product_name';
		$d['product_name']['object']['attrib']['name']      = 'product_name';
		$d['product_name']['object']['attrib']['maxlength'] = 40;

		// handle name field not used for certain products
		if(
			$this->product === 'cpu' ||
			$this->product === 'disk' ||
			$this->product === 'network' ||
			$this->product === 'memory' ||
			$this->product === 'resource' ||
			$this->product === 'ha'
		) {
			$d['product_name'] = '';
		}

		$pinput = true;
		$d['product_quantity']['label'] = sprintf($this->lang['form_quantity'], '');
		if($this->product === 'memory' || $this->product === 'disk') {
			$d['product_quantity']['label'] = sprintf($this->lang['form_quantity'], '(MB)');
		}
		else if($this->product === 'kernel') {
			$used = $this->__used_products('kernel');
			$kernel = $this->openqrm->kernel();
			$list = $kernel->get_list();
			$kernels[] = array('', '');
			foreach($list as $v) {
				if(!in_array($v['value'], $used)) {
					$kernels[] = array($v['value'], $v['label']);
				}
			}
			$d['product_quantity']['label']                       = $this->lang['form_kernel'];
			$d['product_quantity']['required']                    = true;
			$d['product_quantity']['object']['type']              = 'htmlobject_select';
			$d['product_quantity']['object']['attrib']['id']      = 'product_quantity';
			$d['product_quantity']['object']['attrib']['name']    = 'product_quantity';
			$d['product_quantity']['object']['attrib']['index']   = array(0,1);
			$d['product_quantity']['object']['attrib']['options'] = $kernels;
			$pinput = false;
		}
		else if($this->product === 'application') {
			$used = $this->__used_products('application');
			$list = $app = $this->cloudapplication->get_application_list();
			$apps[] = array('');
			foreach($list as $v) {
				if(!in_array($v, $used)) {
					$apps[] = array($v);
				}
			}
			$d['product_quantity']['label']                       = $this->lang['form_application'];
			$d['product_quantity']['required']                    = true;
			$d['product_quantity']['object']['type']              = 'htmlobject_select';
			$d['product_quantity']['object']['attrib']['id']      = 'product_quantity';
			$d['product_quantity']['object']['attrib']['name']    = 'product_quantity';
			$d['product_quantity']['object']['attrib']['index']   = array(0,0);
			$d['product_quantity']['object']['attrib']['options'] = $apps;
			$pinput = false;
		}
		else if($this->product === 'resource') {
			$used = $this->__used_products('resource');
			$virtual = $this->openqrm->virtualization();
			$list = $virtual->get_list();
			$virtuals[] = array('', '');
			foreach($list as $v) {
				if(!stripos($v['label'], 'host') && !in_array($v['value'], $used)) {
					$virtuals[] = array($v['value'], $v['label']);
				}
			}
			$d['product_quantity']['label']                       = $this->lang['form_resource'];
			$d['product_quantity']['required']                    = true;
			$d['product_quantity']['object']['type']              = 'htmlobject_select';
			$d['product_quantity']['object']['attrib']['id']      = 'product_quantity';
			$d['product_quantity']['object']['attrib']['name']    = 'product_quantity';
			$d['product_quantity']['object']['attrib']['index']   = array(0,1);
			$d['product_quantity']['object']['attrib']['options'] = $virtuals;
			$pinput = false;
		}
		else if($this->product === 'ha') {
			$has[] = array('');
			$used = $this->__used_products('ha');
			if(count($used) < 1) {
				$has[] = array('1');
			}
			$d['product_quantity']['required']                    = true;
			$d['product_quantity']['object']['type']              = 'htmlobject_select';
			$d['product_quantity']['object']['attrib']['id']      = 'product_quantity';
			$d['product_quantity']['object']['attrib']['name']    = 'product_quantity';
			$d['product_quantity']['object']['attrib']['index']   = array(0,0);
			$d['product_quantity']['object']['attrib']['options'] = $has;
			$pinput = false;
		}
		if($pinput === true) {
			$d['product_quantity']['required']                 = true;
			$d['product_quantity']['object']['type']           = 'htmlobject_input';
			$d['product_quantity']['object']['attrib']['id']   = 'product_quantity';
			$d['product_quantity']['object']['attrib']['name'] = 'product_quantity';
			$d['product_quantity']['object']['attrib']['options'] = array();
			$pinput = false;
		}

		$d['product_price']['label']                    = $this->lang['form_price'];
		$d['product_price']['required']                 = true;
		$d['product_price']['validate']['regex']        = '/^[0-9]+$/i';
		$d['product_price']['validate']['errormsg']     = sprintf($this->lang['error_NAN'], $this->lang['form_price']);
		$d['product_price']['object']['type']           = 'htmlobject_input';
		$d['product_price']['object']['attrib']['id']   = 'product_price';
		$d['product_price']['object']['attrib']['name'] = 'product_price';
		$d['product_price']['object']['attrib']['maxlength'] = 6;

		$d['product_description']['label']                    = $this->lang['form_description'];
		$d['product_description']['required']                 = true;
		$d['product_description']['object']['type']           = 'htmlobject_input';
		$d['product_description']['object']['attrib']['id']   = 'product_description';
		$d['product_description']['object']['attrib']['name'] = 'product_description';
		$d['product_description']['object']['attrib']['maxlength'] = 50;

		$form->add($d);
		$form->display_errors = false;;

		$response->form = $form;
		return $response;
	}



	function __used_products($product) {
		$r = array();
		$t = $this->cloudselector->display_overview_per_type($product);
		foreach($t as $v) {
			if(isset($v['quantity'])) {
				$r[] = $v['quantity'];
			}

		}
		return $r;
	}




}
?>
