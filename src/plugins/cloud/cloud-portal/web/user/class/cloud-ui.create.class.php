<?php
/**
 * Create Cloud User Request
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/

class cloud_ui_create
{

var $identifier_name;
var $lang;
var $actions_name;

var $cloud_max_applications = 20;
var $cloud_max_network = 4;

/**
* user
* @access public
* @var string
*/
var $user;
/**
* cloud-id
* @access public
* @var int
*/
var $cloud_id;
/**
* config
* @access public
* @var object
*/
var $config;
/**
* use api
* @access public
* @var string
*/
var $use_api = true;

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
		$this->response  = $response;
		$this->rootdir   = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
		$this->openqrm   = $openqrm;
		$this->clouduser = $this->openqrm->user();
	
		// include classes and prepare ojects
		require_once $this->rootdir."/plugins/cloud/class/clouduserslimits.class.php";
		$this->clouduserlimits = new clouduserlimits();
		$this->clouduserlimits->get_instance_by_cu_id($this->openqrm->user()->id);

		require_once $this->rootdir."/plugins/cloud/class/cloudselector.class.php";
		$this->cloudselector = new cloudselector();
		require_once $this->rootdir."/plugins/cloud/class/cloudprivateimage.class.php";
		$this->cloudprivateimage = new cloudprivateimage();
		require_once $this->rootdir."/plugins/cloud/class/cloudrequest.class.php";
		$this->cloudrequest = new cloudrequest();
		require_once $this->rootdir."/plugins/cloud/class/cloudmailer.class.php";
		$this->cloudmailer = new cloudmailer();
		require_once $this->rootdir."/plugins/cloud/class/cloudprofile.class.php";
		$this->cloudprofile = new cloudprofile();
		require_once $this->rootdir."/plugins/cloud/class/cloudconfig.class.php";
		$this->cloudconfig = new cloudconfig();

		require_once "cloud.limits.class.php";
		$this->cloud_limits = new cloud_limits($this->openqrm, $this->cloudconfig, $this->clouduserlimits, $this->cloudrequest);
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
		$response = $this->form();
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		if(isset($response->msg)) {
			$param = '';
			if(isset($response->saved_profile)) {
				$param = '&profile='.$response->saved_profile;
				$this->response->redirect(
					$this->response->get_url($this->actions_name, 'create', $this->message_param, $response->msg).$param
				);
			} else {
				$this->response->redirect(
					$this->response->get_url($this->actions_name, 'appliances', $this->message_param, $response->msg)
				);
			}
		}

		$t = $this->response->html->template($this->tpldir."/cloud-ui.create.tpl.php");

		// Billing
		$billing = $this->cloudconfig->get_value_by_key('cloud_billing_enabled');
		if ($billing === 'true') {
			$t->add('block', 'display_price_list');
		} else {
			$t->add('none', 'display_price_list');
		}

		// check resource and max_apps_per_user
		$apps = $this->cloudrequest->get_all_active_ids($this->clouduser->id);
		if(count($apps) >= $this->cloud_limits->max('resource')) {
			$t->add('none', 'display_component_table');
			$t->add('block', 'display_error');
			$t->add('none', 'display_price_list');
			$t->add(sprintf($this->lang['error_resource_limit'], $this->cloud_limits->max('resource')), 'error');
		}
		else if($this->cloudconfig->get_value_by_key('cloud_enabled') === 'false') {
			$t->add('none', 'display_component_table');
			$t->add('block', 'display_error');
			$t->add('none', 'display_price_list');
			$t->add($this->lang['error_cloud_disabled'], 'error');
		}
		else if($billing === 'true' && $this->clouduser->ccunits < 1) {
			$t->add('none', 'display_component_table');
			$t->add('block', 'display_error');
			$t->add('none', 'display_price_list');
			$t->add($this->lang['error_ccus_low'], 'error');
		} else {
			$t->add('block', 'display_component_table');
			$t->add('none', 'display_error');
			$t->add('', 'error_resource_limit');
		}

		// Private images
		$a = '';
		if (!strcmp($this->cloudconfig->get_value_by_key('show_private_image'), "true")) {
			$a = $this->response->html->a();
			$a->label = $this->lang['label_private_images'];
			$a->href  = $this->response->get_url($this->actions_name, 'images');
			$a = '<li>'.$a->get_string().'</li>';
		}
		$t->add($a, "private_images_link");

		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($this->lang['label'], 'label');
		$t->add($this->lang['table_components'], 'table_components');
		$t->add($this->lang['table_ccus'], 'table_ccus');
		$t->add($this->lang['table_ips'], 'table_ips');
		$t->add($this->lang['price_hour'],'price_hour');
		$t->add($this->lang['price_day'],'price_day');
		$t->add($this->lang['price_month'],'price_month');
		$t->add($this->lang['ccu_per_hour'],'ccu_per_hour');

		$t->add($response->form->get_elements());
		$t->group_elements(array('param_' => 'form', 'cloud_application_select_' => 'cloud_applications'));

		// Profiles
		$a = $this->response->html->a();
		$a->label = $this->lang['label_profiles'];
		$a->css   = 'last';
		$a->href  = $this->response->get_url($this->actions_name, 'profiles');
		$t->add($a, 'profiles_link');

		$profiles = $this->cloudprofile->display_overview_per_user($this->clouduser->id, 'ASC');
		$profile_action = '';
		foreach ($profiles as $k => $v) {
			$a = $this->response->html->a();
			$a->label   = $v['pr_name'];
			$a->href    = $this->response->get_url($this->actions_name, 'create').'&profile='.$v['pr_id'];
			if($this->response->html->request()->get('profile') === $v['pr_id']) {
				$a->css = 'selected';
			}
			$profile_action .= $a->get_string().'<br>';
		}
		$t->add($profile_action, 'profiles');

		// add js image switcher data
		$t->add($this->js_resources,'js_formbuilder');
		
		// api switch
		$this->use_api === true ? $use_api = 'true' : $use_api = 'false'; 
		$t->add('var use_api = '.$use_api.';','js_use_api');

		return $t;
	}

	//--------------------------------------------
	/**
	 * Create Cloud User Request
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function form() {
		$errors  = array();
		$message = array();
		$response = $this->get_response();
		$form     = $response->form;
		if(!$form->get_errors() && $response->submit()) {
			$data = $form->get_request();

			// check image fits virtualization
			$virt = $this->openqrm->virtualization();
			$virt->get_instance_by_id($data['cloud_virtualization_select']);
			$tmp = $virt->type;
			// tag network vms
			if(strstr($tmp, "-net")) {
				$tmp = 'vm-net';
			}
			// store virtualization type
			$virt_types[$tmp] = $virt->get_plugin_name();
			$img = $this->openqrm->image();
			$img->get_instance_by_id($data['cloud_image_select']);
			if($this->__get_virt_tag($img, $virt_types) === '') {
				$form->set_error('cloud_image_select', $this->lang['error_image_no_fit']);
			}
			// check limits
			if($data['cloud_memory_select'] > $this->cloud_limits->free('memory')) {
				$form->set_error('cloud_memory_select', $this->lang['error_limit_exceeded']);
			}
			if($data['cloud_disk_select'] > $this->cloud_limits->free('disk')) {
				$form->set_error('cloud_disk_select', $this->lang['error_limit_exceeded']);
			}
			if($data['cloud_network_select'] > $this->cloud_limits->free('network')) {
				$form->set_error('cloud_network_select', $this->lang['error_limit_exceeded']);
			}
			if($data['cloud_cpu_select'] > $this->cloud_limits->free('cpu')) {
				$form->set_error('cloud_cpu_select', $this->lang['error_limit_exceeded']);
			}
			// check hostname
			if(isset($data['cloud_hostname_input'])) {
				$chk_hostname = $this->openqrm->appliance();
				$chk_hostname->get_instance_by_name($data['cloud_hostname_input']);
				if ($chk_hostname->id > 0) {
					$form->set_error('cloud_hostname_input', sprintf($this->lang['error_hostname'], $data['cloud_hostname_input']));
				}
			}

			if(!$form->get_errors()) {

				$application_groups_str = '';
				$ip_mgmt_config_str = '';
				// add request
				$now = $_SERVER['REQUEST_TIME'];
				$cr["cr_cu_id"] = $this->clouduser->id;
				$cr['cr_start'] = $now;
				$cr['cr_request_time'] = $now;
				$cr['cr_stop'] = $now + 830000000;
				$cr['cr_resource_quantity'] = 1;
				// form data
				$cr['cr_resource_type_req'] = $data['cloud_virtualization_select'];
				$cr['cr_kernel_id'] = $data['cloud_kernel_select'];
				$cr['cr_image_id'] = $data['cloud_image_select'];
				$cr['cr_ram_req'] = $data['cloud_memory_select'];
				$cr['cr_cpu_req'] = $data['cloud_cpu_select'];
				$cr['cr_disk_req'] = $data['cloud_disk_select'];
				$cr['cr_network_req'] = $data['cloud_network_select'];
				// capabilities input
				if(isset($data['cloud_appliance_capabilities'])) {
					$cr['cr_appliance_capabilities'] = $data['cloud_appliance_capabilities'];
				}
				// hostname input
				if (isset($data['cloud_hostname_input'])) {
					$cr['cr_appliance_hostname'] = $data['cloud_hostname_input'];
				}
				// apps
				for ($a = 0; $a < $this->cloud_max_applications; $a++) {
					if (isset($data['cloud_application_select_'.$a])) {
						$application_groups_str .= $data['cloud_application_select_'.$a].",";
					}
				}
				$application_groups_str = rtrim($application_groups_str, ",");
				$cr['cr_puppet_groups'] = $application_groups_str;
				// ips
				$max_network_interfaces = $this->cloudconfig->get_value_by_key('max_network_interfaces');
				for ($a = 0; $a <= $max_network_interfaces; $a++) {
					if (isset($data['cloud_ip_select_'.$a])) {
						$ip_mgmt_id = $data['cloud_ip_select_'.$a];
						if ($ip_mgmt_id != -1) {
							$nic_no = $a + 1;
							$ip_mgmt_config_str .= $nic_no.":".$ip_mgmt_id.",";
						}
					}
				}
				$ip_mgmt_config_str = rtrim($ip_mgmt_config_str, ",");
				$cr['cr_ip_mgmt'] = $ip_mgmt_config_str;
				// ha
				if (isset($data['cloud_ha_select'])) {
					$cr["cr_ha_req"] = 1;
				}
				// clone on deploy
				$clone_on_deploy = $this->cloudconfig->get_value_by_key('default_clone_on_deploy');
				if (!strcmp($clone_on_deploy, "true")) {
					$cr["cr_shared_req"] = 1;
				} else {
					$cr["cr_shared_req"] = 0;
				}
			
				// save as profile or request directly
				if (isset($data['cloud_profile_name'])) {
					$profile_name = $data['cloud_profile_name'];
					// check profile name not in use
					$profiles = $this->cloudprofile->display_overview_per_user($this->clouduser->id, 'ASC');
					if(count($profiles) > 0) {
						foreach($profiles as $profile) {
							if ($profile['pr_name'] === $profile_name) {
								$errors[] = sprintf($this->lang['msg_profile_in_use'], $profile_name);
								break;
							}
						}
					}
					// check max profile number
					$pr_count = $this->cloudprofile->get_count_per_user($this->clouduser->id);
					if ($pr_count >= $this->cloudprofile->max_profile_count) {
						$errors[] = sprintf($this->lang['error_max_profiles'],$this->cloudprofile->max_profile_count);
					}
					// add profile
					if(count($errors) === 0) {
						// remap fields from cr to pr
						$pr['pr_request_time'] = $cr['cr_request_time'];
						$pr['pr_start'] = $cr['cr_start'];
						$pr['pr_stop'] = $cr['cr_stop'];
						$pr['pr_kernel_id'] = $cr['cr_kernel_id'];
						$pr['pr_image_id'] = $cr['cr_image_id'];
						$pr['pr_ram_req'] = $cr['cr_ram_req'];
						$pr['pr_cpu_req'] = $cr['cr_cpu_req'];
						$pr['pr_disk_req'] = $cr['cr_disk_req'];
						$pr['pr_network_req'] = $cr['cr_network_req'];
						$pr['pr_resource_quantity'] = $cr['cr_resource_quantity'];
						$pr['pr_resource_type_req'] = $cr['cr_resource_type_req'];
						if(isset($cr['cr_ha_req'])) {
							$pr['pr_ha_req'] = $cr['cr_ha_req'];
						}
						$pr['pr_shared_req'] = $cr['cr_shared_req'];
						$pr['pr_puppet_groups'] = $cr['cr_puppet_groups'];
						$pr['pr_ip_mgmt'] = $cr['cr_ip_mgmt'];
						$pr['pr_name'] = $profile_name;
						// hostname
						if (isset($cr['cr_appliance_hostname'])) {
							$pr['pr_appliance_hostname'] = $cr['cr_appliance_hostname'];
						}
						// capabilities
						if(isset($cr['cr_appliance_capabilities'])) {
							$pr['pr_appliance_capabilities'] = $cr['cr_appliance_capabilities'];
						}
						$pr['pr_cu_id'] = $this->clouduser->id;
						$pr['pr_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
						$this->cloudprofile->add($pr);
					
						$response->msg = sprintf($this->lang['msg_saved_profile'], $profile_name);
						$response->saved_profile = $pr['pr_id'];
					} else {
						$msg = array_merge($errors, $message);
						$response->error = implode('<br>', $msg);
					}
				
				} else {
					$cr['cr_id'] = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					$this->cloudrequest->add($cr);
					// mail to admin
					$cc_admin_email = $this->cloudconfig->get_value_by_key('cloud_admin_email');
					$this->cloudmailer->to = $cc_admin_email;
					$this->cloudmailer->from = $cc_admin_email;
					$this->cloudmailer->subject = sprintf($this->lang['mailer_create_subject'], $this->clouduser->name);
					$this->cloudmailer->template = $this->basedir."/plugins/cloud/etc/mail/new_cloud_request.mail.tmpl";
					$arr = array('@@USER@@' => $this->clouduser->name, '@@ID@@' => $cr['cr_id'], '@@OPENQRM_SERVER_IP_ADDRESS@@' => $_SERVER['SERVER_NAME'], '@@CLOUDADMIN@@' => $cc_admin_email);
					$this->cloudmailer->var_array = $arr;
					$this->cloudmailer->send();
					// success msg
					$response->msg = $this->lang['msg_created'];
				}
			} else {
				$response->error = implode('<br>', $form->get_errors());
			}
		} elseif ($form->get_errors()) {
			$response->error = implode('<br>', $form->get_errors());
		}
		return $response;
	}

	//--------------------------------------------
	/**
	 * Get response
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {
		$response = $this->response;
		$form = $response->get_form($this->actions_name, "create");

		if($response->html->request()->get('profile')) {
			require_once $this->rootdir."/plugins/cloud/class/cloudprofile.class.php";
			$this->cloudprofile	= new cloudprofile();
			$this->cloudprofile->get_instance_by_id($response->html->request()->get('profile'));
			$msg = $response->html->request()->get($this->message_param);
			if($msg !== '') {
				$msg = $msg.'<br>';
			}
			$_REQUEST[$this->message_param] = $msg.sprintf($this->lang['msg_loading_profile'],$this->cloudprofile->name);
		}
		// pre-define select arrays
		$kernel_list = array();
		$cloud_image_select_arr = '';
		$cloud_virtualization_select_arr = '';
		$virtualization_list_select = array();
		$cloud_memory_select_arr = '';
		$cloud_cpu_select_arr = '';
		$cloud_disk_select_arr = '';
		$cloud_network_select_arr = '';
		$cloud_ha_select_arr = '';
		$cloud_application_select_arr = array();
		$product_application_description_arr = array();
		$ip_mgmt_list_per_user_arr = array();

		// global limits
		$max_resources_per_cr = 1;

		// big switch ##############################################################
		//  : either show what is provided in the cloudselector
		//  : or show what is available
		// check if cloud_selector feature is enabled
		$cloud_selector_enabled = $this->cloudconfig->get_value_by_key('cloud_selector');	// cloud_selector
		$virt_types = array();
		if (!strcmp($cloud_selector_enabled, "true")) {
			// show what is provided by the cloudselectors
			// cpus
			$product_array = $this->cloudselector->display_overview_per_type("cpu");
			$available_cpunumber = array();
			foreach ($product_array as $index => $cloudproduct) {
				// is product enabled ?
				if ($cloudproduct["state"] == 1) {
					$cs_cpu = $cloudproduct["quantity"];
					if ($cs_cpu <= $this->cloud_limits->free('cpu')) {
						$available_cpunumber[] = array("value" => $cloudproduct["quantity"], "label" => $cloudproduct["name"]);
					}
				}
			}

			// disk size
			$disk_size_select = array();
			$product_array = $this->cloudselector->display_overview_per_type("disk");
			foreach ($product_array as $index => $cloudproduct) {
				// is product enabled ?
				if ($cloudproduct["state"] == 1) {
					$cs_disk = $cloudproduct["quantity"];
					if ($cs_disk <= $this->cloud_limits->free('disk')) {
						$disk_size_select[] = array("value" => $cloudproduct["quantity"], "label" => $cloudproduct["name"]);
					}
				}
			}

			// kernel
			$product_array = $this->cloudselector->display_overview_per_type("kernel");
			foreach ($product_array as $index => $cloudproduct) {
				// is product enabled ?
				if ($cloudproduct["state"] == 1) {
					$kernel_list[] = array("value" => $cloudproduct["quantity"], "label" => $cloudproduct["name"]);
				}
			}

			// memory sizes
			$available_memtotal = array();
			$product_array = $this->cloudselector->display_overview_per_type("memory");
			foreach ($product_array as $index => $cloudproduct) {
				// is product enabled ?
				if ($cloudproduct["state"] == 1) {
					$cs_memory = $cloudproduct["quantity"];
					 if ($cs_memory <= $this->cloud_limits->free('memory')) {
							$available_memtotal[] = array("value" => $cloudproduct["quantity"], "label" => $cloudproduct["name"]);
					}
				}
			}

			// network cards
			$max_network_interfaces_select = array();
			$product_array = $this->cloudselector->display_overview_per_type("network");
			foreach ($product_array as $index => $cloudproduct) {
				// is product enabled ?
				if ($cloudproduct["state"] == 1) {
					$cs_metwork = $cloudproduct["quantity"];
					 if ($cs_metwork <= $this->cloud_limits->free('network')) {
						$max_network_interfaces_select[] = array("value" => $cloudproduct["quantity"], "label" => $cloudproduct["name"]);
					}
				}
			}

			// application classes
			// check if to show application
			$show_puppet_groups = $this->cloudconfig->get_value_by_key('show_puppet_groups');	// show_puppet_groups
			if (!strcmp($show_puppet_groups, "true")) {
				$product_array = $this->cloudselector->display_overview_per_type("application");
				foreach ($product_array as $index => $cloudproduct) {
					// is product enabled ?
					if ($cloudproduct["state"] == 1) {
						$application_product_name = $cloudproduct["description"];
						$application_class_name = $cloudproduct["quantity"];
						$cloud_application_select_arr[] = array("value" => $application_class_name, "label" => $application_product_name);
						$product_application_description_arr[$application_product_name] = $cloudproduct["name"];
					}
				}
			}

			// virtualization types
			$product_array = $this->cloudselector->display_overview_per_type("resource");
			$virt = $this->openqrm->virtualization();
			foreach ($product_array as $key => $product) {
				// is product enabled ?
				if ($product["state"] == 1) {
					$virt->get_instance_by_id($product["quantity"]);
					$tmp = $virt->type;
					// tag network vms
					if(strstr($tmp, "-net")) {
						$tmp = 'vm-net';
					}
					// store virtualization type
					$virt_types[$tmp] = $virt->get_plugin_name();
					$str = $this->__get_virt_tag($tmp, $virt_types);
					$virtualization_list_select[] = array("value" => $product["quantity"], "label" => $product["description"].'  '.$str);
					$js_resources[] = array($product["quantity"], $product["description"], $str, $tmp);
				}
			}
		} else {
			// show what is available in openQRM
			$kernel = $this->openqrm->kernel();
			$kernel_list = array();
			$kernel_list = $kernel->get_list();
			// remove the openqrm kernelfrom the list
			array_shift($kernel_list);

			// virtualization types
			$virt = $this->openqrm->virtualization();
			$virtualization_list_select = array();
			$virt_list = $virt->get_list();
			// check if to show physical system type
			$cc_request_physical_systems = $this->cloudconfig->get_value_by_key('request_physical_systems');	// request_physical_systems
			if (!strcmp($cc_request_physical_systems, "false")) {
				array_shift($virt_list);
			}
			// filter out the virtualization hosts
			foreach ($virt_list as $id => $product) {
				if (!strstr($product['label'], "Host")) {
					$virt->get_instance_by_id($product['value']);
					$tmp = $virt->type;
					// tag network vms
					if(strstr($tmp, "-net")) {
						$tmp = 'vm-net';
					}
					// store virtualization type
					$virt_types[$tmp] = $virt->get_plugin_name();
					$str = $this->__get_virt_tag($tmp, $virt_types);
					$js_resources[] =  array($product['value'], $product['label'], $str, $tmp);
					$virtualization_list_select[] = array("value" => $product['value'], "label" => $product['label'].' '.$str);
				}
			}

			// prepare the array for the network-interface select
			$max_network_interfaces_select = array();
			$max_network_interfaces = $this->cloudconfig->get_value_by_key('max_network_interfaces');	// max_network_interfaces
			for ($mnet = 1; $mnet <= $max_network_interfaces; $mnet++) {
				$max_network_interfaces_select[] = array("value" => $mnet, "label" => $mnet);
			}

# TODO
# better cpu and memory generation

			// get list of available resource parameters
			$resource_p = $this->openqrm->resource();
			$resource_p_array = $resource_p->get_list();
			// remove openQRM resource
			array_shift($resource_p_array);
			// gather all available values in arrays
			$available_cpunumber_uniq = array();
			$available_cpunumber = array();
			$available_cpunumber[] = array("value" => "0", "label" => "Auto");
			$available_memtotal_uniq = array();
			$available_memtotal = array();
			$available_memtotal[] = array("value" => "0", "label" => "Auto");
			foreach($resource_p_array as $res) {
				$res_id = $res['resource_id'];
				$tres = $this->openqrm->resource();
				$tres->get_instance_by_id($res_id);
				if (strlen($tres->cpunumber) && intval($tres->cpunumber) !== 0  && !in_array($tres->cpunumber, $available_cpunumber_uniq)) {
					$available_cpunumber[] = array("value" => $tres->cpunumber, "label" => $tres->cpunumber);
					$available_cpunumber_uniq[] .= $tres->cpunumber;
				}
				if (strlen($tres->memtotal) && !in_array($tres->memtotal, $available_memtotal_uniq)) {
					if($tres->memtotal < 1000) {
						$size = $tres->memtotal." MB";
					} else {
						$size = ($tres->memtotal/1000)." GB";
					}
					$available_memtotal[] = array("value" => $tres->memtotal, "label" => $size);
					$available_memtotal_uniq[] .= $tres->memtotal;
				}
			}

			// disk size select
			$disk_size_select = array();
			$max_disk_size = $this->cloud_limits->free('disk');
			if (1000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 1000, "label" => '1 GB');
			}
			if (2000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 2000, "label" => '2 GB');
			}
			if (3000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 3000, "label" => '3 GB');
			}
			if (4000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 4000, "label" => '4 GB');
			}
			if (5000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 5000, "label" => '5 GB');
			}
			if (10000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 10000, "label" => '10 GB');
			}
			if (20000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 20000, "label" => '20 GB');
			}
			if (50000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 50000, "label" => '50 GB');
			}
			if (100000 <= $max_disk_size) {
				$disk_size_select[] = array("value" => 100000, "label" => '100 GB');
			}

			// check if to show puppet
			$show_puppet_groups = $this->cloudconfig->get_value_by_key('show_puppet_groups'); // show_puppet_groups
			if (!strcmp($show_puppet_groups, "true")) {
				// is puppet enabled ?
				if (file_exists($this->rootdir."/plugins/puppet/.running")) {
					require_once $this->rootdir."/plugins/puppet/class/puppet.class.php";
					$puppet_group_dir = $this->rootdir."/plugins/puppet/puppet/manifests/groups";
					global $puppet_group_dir;
					$puppet_group_array = array();
					$puppet = new puppet();
					$puppet_group_array = $puppet->get_available_groups();
					foreach ($puppet_group_array as $index => $puppet_g) {
						$puid=$index+1;
						$puppet_info = $puppet->get_group_info($puppet_g);
						$cloud_application_select_arr[] = array("value" => "puppet/".$puppet_g, "label" => $puppet_g);
						$product_application_description_arr[$puppet_g] = $puppet_info;
					}
				}
			}
		}

		// show available images or private images which are enabled
		$img = $this->openqrm->image();
		$image_list = array();
		$image_list_tmp = array();
		$image_list_tmp = $img->get_list();
		// remove the openqrm + idle image from the list
		array_shift($image_list_tmp);
		array_shift($image_list_tmp);
		// check if private image feature is enabled
		$show_private_image = $this->cloudconfig->get_value_by_key('show_private_image');	// show_private_image
		if (!strcmp($show_private_image, "true")) {
			// private image feature enabled
			$private_image_list = $this->cloudprivateimage->get_all_ids();
			foreach ($private_image_list as $index => $cpi) {
				$cpi_id = $cpi["co_id"];
				$this->cloudprivateimage->get_instance_by_id($cpi_id);
				if ($this->clouduser->id == $this->cloudprivateimage->cu_id) {
					$img = $this->openqrm->image();
					$img->get_instance_by_id($this->cloudprivateimage->image_id);
					// do not show active images
					if ($img->isactive == 1) {
						continue;
					}
					// only show the non-shared image to the user if it is not attached to a resource
					// because we don't want users to assign the same image to two appliances
					$priv_cloud_im = new cloudimage();
					$priv_cloud_im->get_instance_by_image_id($this->cloudprivateimage->image_id);
					if($priv_cloud_im->resource_id == 0 || $priv_cloud_im->resource_id == -1) {
						// get virtualization tag
						$str = $this->__get_virt_tag($img, $virt_types);
						// only show images for available virtualizations
						if($str !== '') {
							$image_list[] = array("value" => $img->id, "label" => $img->name.' '.$str);
							$js_images[] = array($img->id, $img->name, $str);
						}
					}
				} else if ($this->cloudprivateimage->cu_id == 0) {
					$img = $this->openqrm->image();
					$img->get_instance_by_id($this->cloudprivateimage->image_id);
					if ($img->isactive == 1) {
						continue;
					}
					// get virtualization tag
					$str = $this->__get_virt_tag($img, $virt_types);
					// only show images for available virtualizations
					if($str !== '') {
						$image_list[] = array("value" => $img->id, "label" => $img->name.' '.$str);
						$js_images[] = array($img->id, $img->name, $str);
					}
				}
			}
		} else {
			// private image feature is not enabled
			// do not show the image-clones from other requests
			foreach($image_list_tmp as $list) {
				$iname = $list['label'];
				$iid = $list['value'];
				$img = $this->openqrm->image();
				$img->get_instance_by_id($iid);
				// do not show active images
				if ($img->isactive == 1) {
					continue;
				}
				if (!strstr($iname, ".cloud_")) {
					// get virtualization tag
					$str = $this->__get_virt_tag($img, $virt_types);
					// only show images for available virtualizations
					if($str !== '') {
						$image_list[] = array("value" => $iid, "label" => $iname.' '.$str);
						$js_images[] = array($img->id, $img->name, $str);
					}
				}
			}
		}

		// check ip-mgmt
		$show_ip_mgmt = $this->cloudconfig->get_value_by_key('ip-management'); // ip-mgmt enabled ?
		$ip_mgmt_select = '';
		$ip_mgmt_title = '';
		$ip_mgmt_list_per_user_arr[] = array("value" => -2, "label" => "Auto");
		$ip_mgmt_list_per_user_arr[] = array("value" => -1, "label" => "None");
		if (!strcmp($show_ip_mgmt, "true")) {
			if (file_exists($this->rootdir."/plugins/ip-mgmt/.running")) {
				require_once $this->rootdir."/plugins/ip-mgmt/class/ip-mgmt.class.php";
				$ip_mgmt = new ip_mgmt();
				$ip_mgmt_list_per_user = $ip_mgmt->get_list_by_user($this->clouduser->cg_id);
				foreach($ip_mgmt_list_per_user as $list) {
					$ip_mgmt_id = $list['ip_mgmt_id'];
					$ip_mgmt_name = trim($list['ip_mgmt_name']);
					$ip_mgmt_address = trim($list['ip_mgmt_address']);
					$ip_mgmt_list_per_user_arr[] = array("value" => $ip_mgmt_id, "label" => $ip_mgmt_address.' ('.$ip_mgmt_name.')');
				}
			}
		}

		// check if cloud_selector feature is enabled
		$cloud_appliance_hostname = '';
		$cloud_appliance_hostname_input = '';
		$cloud_appliance_hostname_help = '';
		$cloud_appliance_hostname_enabled = $this->cloudconfig->get_value_by_key('appliance_hostname');	// appliance_hostname
		if (!strcmp($cloud_appliance_hostname_enabled, "true")) {
			$cloud_appliance_hostname = 'Hostname setup';
			$cloud_appliance_hostname_help = '<small>Multiple appliances get the postfix <b>_[#no]</b></small>';
		}

		$cloud_memory_select_arr = array();
		if(isset($available_memtotal)) {
			$cloud_memory_select_arr = $available_memtotal;
		}
		$cloud_disk_select_arr = array();
		if(isset($disk_size_select)) {
			$cloud_disk_select_arr = $disk_size_select;
		}



		// Sort Image List
		if(count($image_list) > 0) {
			foreach ($image_list as $key => $row) {
				$label[$key] = strtolower($row['label']);
			}
			array_multisort($label, SORT_ASC, SORT_STRING, $image_list);
		}

		$cloud_image_select_arr = $image_list;
		$cloud_virtualization_select_arr = $virtualization_list_select;
		$cloud_cpu_select_arr = $available_cpunumber;
		$cloud_network_select_arr = $max_network_interfaces_select;
		#$cloud_ha_select_arr = $show_ha;
		$cloud_kernel_select_arr = $kernel_list;

		$d = array();

		$d['cloud_virtualization_select']['label']                       = $this->lang['type'];
		$d['cloud_virtualization_select']['required']                    = true;
		$d['cloud_virtualization_select']['object']['type']              = 'htmlobject_select';
		$d['cloud_virtualization_select']['object']['attrib']['index']   = array('value', 'label');
		$d['cloud_virtualization_select']['object']['attrib']['id']      = 'cloud_virtualization_select';
		$d['cloud_virtualization_select']['object']['attrib']['name']    = 'cloud_virtualization_select';
		$d['cloud_virtualization_select']['object']['attrib']['options'] = $cloud_virtualization_select_arr;
		if(isset($this->cloudprofile->resource_type_req)) {
			$d['cloud_virtualization_select']['object']['attrib']['selected'] = array($this->cloudprofile->resource_type_req);
		}

		$d['cloud_kernel_select']['label']                       = $this->lang['kernel'];
		$d['cloud_kernel_select']['required']                    = true;
		$d['cloud_kernel_select']['object']['type']              = 'htmlobject_select';
		$d['cloud_kernel_select']['object']['attrib']['index']   = array('value', 'label');
		$d['cloud_kernel_select']['object']['attrib']['id']      = 'cloud_kernel_select';
		$d['cloud_kernel_select']['object']['attrib']['name']    = 'cloud_kernel_select';
		$d['cloud_kernel_select']['object']['attrib']['options'] = $cloud_kernel_select_arr;
		if(isset($this->cloudprofile->kernel_id)) {
			$d['cloud_kernel_select']['object']['attrib']['selected'] = array($this->cloudprofile->kernel_id);
		}

		$d['cloud_image_select']['label']                       = $this->lang['image'];
		$d['cloud_image_select']['required']                    = true;
		$d['cloud_image_select']['object']['type']              = 'htmlobject_select';
		$d['cloud_image_select']['object']['attrib']['index']   = array('value', 'label');
		$d['cloud_image_select']['object']['attrib']['id']      = 'cloud_image_select';
		$d['cloud_image_select']['object']['attrib']['name']    = 'cloud_image_select';
		$d['cloud_image_select']['object']['attrib']['options'] = $cloud_image_select_arr;
		if(isset($this->cloudprofile->image_id)) {
			$d['cloud_image_select']['object']['attrib']['selected'] = array($this->cloudprofile->image_id);
		}

		$d['cloud_memory_select']['label']                       = $this->lang['ram'];
		$d['cloud_memory_select']['required']                    = true;
		$d['cloud_memory_select']['object']['type']              = 'htmlobject_select';
		$d['cloud_memory_select']['object']['attrib']['index']   = array('value', 'label');
		$d['cloud_memory_select']['object']['attrib']['id']      = 'cloud_memory_select';
		$d['cloud_memory_select']['object']['attrib']['name']    = 'cloud_memory_select';
		$d['cloud_memory_select']['object']['attrib']['options'] = $cloud_memory_select_arr;
		if(isset($this->cloudprofile->ram_req)) {
			$d['cloud_memory_select']['object']['attrib']['selected'] = array($this->cloudprofile->ram_req);
		}

		$d['cloud_cpu_select']['label']                       = $this->lang['cpu'];
		$d['cloud_cpu_select']['required']                    = true;
		$d['cloud_cpu_select']['object']['type']              = 'htmlobject_select';
		$d['cloud_cpu_select']['object']['attrib']['index']   = array('value', 'label');
		$d['cloud_cpu_select']['object']['attrib']['id']      = 'cloud_cpu_select';
		$d['cloud_cpu_select']['object']['attrib']['name']    = 'cloud_cpu_select';
		$d['cloud_cpu_select']['object']['attrib']['options'] = $cloud_cpu_select_arr;
		if(isset($this->cloudprofile->cpu_req)) {
			$d['cloud_cpu_select']['object']['attrib']['selected'] = array($this->cloudprofile->cpu_req);
		}

		$d['cloud_disk_select']['label']                       = $this->lang['disk'];
		$d['cloud_disk_select']['required']                    = true;
		$d['cloud_disk_select']['object']['type']              = 'htmlobject_select';
		$d['cloud_disk_select']['object']['attrib']['index']   = array('value', 'label');
		$d['cloud_disk_select']['object']['attrib']['id']      = 'cloud_disk_select';
		$d['cloud_disk_select']['object']['attrib']['name']    = 'cloud_disk_select';
		$d['cloud_disk_select']['object']['attrib']['options'] = $cloud_disk_select_arr;
		if(isset($this->cloudprofile->disk_req)) {
			$d['cloud_disk_select']['object']['attrib']['selected'] = array($this->cloudprofile->disk_req);
		}

		$d['cloud_network_select']['label']                       = $this->lang['network'];
		$d['cloud_network_select']['required']                    = true;
		$d['cloud_network_select']['object']['type']              = 'htmlobject_select';
		$d['cloud_network_select']['object']['attrib']['index']   = array('value', 'label');
		$d['cloud_network_select']['object']['attrib']['id']      = 'cloud_network_select';
		$d['cloud_network_select']['object']['attrib']['name']    = 'cloud_network_select';
		$d['cloud_network_select']['object']['attrib']['options'] = $cloud_network_select_arr;
		if(isset($this->cloudprofile->network_req)) {
			$d['cloud_network_select']['object']['attrib']['selected'] = array($this->cloudprofile->network_req);
		}

		// ips
		$ip_loop = 0;
		if (count($ip_mgmt_list_per_user_arr) > 0) {
			$max = 0;
			foreach($cloud_network_select_arr as $v) {
				if($v['value'] > $max) {
					$max = $v['value'];
				}
			}
			for($i = 0; $i < $max; $i++) {
				$nic_no = $ip_loop + 1;
				$d['cloud_ip_select_'.$ip_loop]['label']                       = 'IP '.$nic_no;
				$d['cloud_ip_select_'.$ip_loop]['object']['type']              = 'htmlobject_select';
				$d['cloud_ip_select_'.$ip_loop]['object']['attrib']['index']   = array('value', 'label');
				$d['cloud_ip_select_'.$ip_loop]['object']['attrib']['id']      = 'cloud_ip_select_'.$ip_loop;
				$d['cloud_ip_select_'.$ip_loop]['object']['attrib']['name']    = 'cloud_ip_select_'.$ip_loop;
				$d['cloud_ip_select_'.$ip_loop]['object']['attrib']['options'] = $ip_mgmt_list_per_user_arr;
				if($i === $max-1) {
					$d['cloud_ip_select_'.$ip_loop]['css'] = 'last';
				}
				$ip_loop++;
			}
			for ($f = $ip_loop; $f < 4; $f++) {
				$d['cloud_ip_select_'.$f] = ' ';
			}
		} else {
			for ($f = $ip_loop; $f < 4; $f++) {
				$d['cloud_ip_select_'.$f] = ' ';
			}
		}

		// application
		$apps_selected = explode(',', $this->cloudprofile->puppet_groups);
		$product_loop = 0;
		if (count($cloud_application_select_arr) > 0) {
			foreach($cloud_application_select_arr as $application) {
				$product_name = $application['label'];
				$product_description = $application['value'];
				$d['cloud_application_select_'.$product_loop]['label']                     = $product_name;
				$d['cloud_application_select_'.$product_loop]['object']['type']            = 'htmlobject_input';
				$d['cloud_application_select_'.$product_loop]['object']['attrib']['type']  = 'checkbox';
				$d['cloud_application_select_'.$product_loop]['object']['attrib']['id']    = 'cloud_application_select'.$product_loop;
				$d['cloud_application_select_'.$product_loop]['object']['attrib']['name']  = 'cloud_application_select_'.$product_loop;
				$d['cloud_application_select_'.$product_loop]['object']['attrib']['value'] = $product_description;
				$d['cloud_application_select_'.$product_loop]['object']['attrib']['title'] = $product_application_description_arr[$product_name];
				if(in_array($product_description, $apps_selected)) {
					$d['cloud_application_select_'.$product_loop]['object']['attrib']['checked'] = true;
				}
				$product_loop++;
			}
			for ($f = $product_loop; $f < $this->cloud_max_applications; $f++) {
				$d['cloud_application_select_'.$f] = ' ';
			}
		} else {
			for ($f = $product_loop; $f < $this->cloud_max_applications; $f++) {
				$d['cloud_application_select_'.$f] = ' ';
			}
		}

		// ha
		$ha = false;
		$d['cloud_ha_select'] = '';
		if ($this->cloudconfig->get_value_by_key('cloud_selector') === 'true') {
			if(count($this->cloudselector->display_overview_per_type("ha")) > 0) {
				$ha = true;
			}
		}
		else if ($this->cloudconfig->get_value_by_key('show_ha_checkbox') === 'true') {
			$ha = true;
		}
		if($ha === true) {
			$d['cloud_ha_select']['label']                     = $this->lang['ha'];
			$d['cloud_ha_select']['object']['type']            = 'htmlobject_input';
			$d['cloud_ha_select']['object']['attrib']['type']  = 'checkbox';
			$d['cloud_ha_select']['object']['attrib']['id']    = 'cloud_ha_select';
			$d['cloud_ha_select']['object']['attrib']['name']  = 'cloud_ha_select';
			$d['cloud_ha_select']['object']['attrib']['value'] = 'ha';
			if($this->cloudprofile->ha_req === '1') {
				$d['cloud_ha_select']['object']['attrib']['checked'] = true;
			}
		}

		// capabilities
		$d['cloud_appliance_capabilities']['label']                         = $this->lang['capabilities'];
		$d['cloud_appliance_capabilities']['object']['type']                = 'htmlobject_textarea';
		$d['cloud_appliance_capabilities']['object']['attrib']['id']        = 'cloud_appliance_capabilities';
		$d['cloud_appliance_capabilities']['object']['attrib']['name']      = 'cloud_appliance_capabilities';
		$d['cloud_appliance_capabilities']['object']['attrib']['maxlength'] = 1000;
		$d['cloud_appliance_capabilities']['object']['attrib']['value']     = $this->cloudprofile->appliance_capabilities;

		// check if user are allowed to set the hostname
		$d['cloud_hostname_input'] = '';
		if ($this->cloudconfig->get_value_by_key('appliance_hostname') === "true") {
			$d['cloud_hostname_input']['label']                         = $this->lang['hostname'];
			$d['cloud_hostname_input']['validate']['regex']             = $this->openqrm->get('regex', 'hostname');
			$d['cloud_hostname_input']['validate']['errormsg']          = 'Hostname must be '.$this->openqrm->get('regex', 'hostname').' only';
			$d['cloud_hostname_input']['object']['type']                = 'htmlobject_input';
			$d['cloud_hostname_input']['object']['attrib']['type']      = 'text';
			$d['cloud_hostname_input']['object']['attrib']['id']        = 'cloud_hostname_input';
			$d['cloud_hostname_input']['object']['attrib']['name']      = 'cloud_hostname_input';
			$d['cloud_hostname_input']['object']['attrib']['maxlength'] = 255;
			$d['cloud_hostname_input']['object']['attrib']['value']     = $this->cloudprofile->appliance_hostname;
		}

		// save as profile
		$d['cloud_profile_name']['label']                         = $this->lang['save_as_profile'];
		$d['cloud_profile_name']['required']                      = false;
		$d['cloud_profile_name']['validate']['regex']             = '~^[a-z0-9]+$~i';
		$d['cloud_profile_name']['validate']['errormsg']          = sprintf($this->lang['error_save_as_profile'],'[a-z0-9]');
		$d['cloud_profile_name']['object']['type']                = 'htmlobject_input';
		$d['cloud_profile_name']['object']['attrib']['type']      = 'text';
		$d['cloud_profile_name']['object']['attrib']['id']        = 'cloud_profile_name';
		$d['cloud_profile_name']['object']['attrib']['name']      = 'cloud_profile_name';
		$d['cloud_profile_name']['object']['attrib']['maxlength'] = 15;

		$str  = 'var formbuilder = {'."\n";
		$str .= 'resources:['."\n";
		$i = 1;
		if(isset($js_resources)) {
			foreach ($js_resources as $k => $v) {
				$str .= '['.$v[0].',"'.$v[1].'","'.$v[2].'","'.$v[3].'"]';
				if($i < count($js_resources)) {
					$str .= ','."\n";
				}
				$i++;
			}
		}
		$str .= '],'."\n";
		$str .= 'images:['."\n";
		$i = 1;
		if(isset($js_images)) {
			// Sort Image List
			if(count($js_images) > 0) {
				$label = array();
				foreach ($js_images as $key => $row) {
					$label[$key] = $row[1];
				}
				array_multisort($label, SORT_ASC, SORT_STRING, $js_images);
			}
			// build json
			foreach ($js_images as $k => $v) {
				$str .= '['.$v[0].',"'.$v[1].'","'.$v[2].'"]';
				if($i < count($js_images)) {
					$str .= ','."\n";
				}
				$i++;
			}
		}
		$str .= ']'."\n";
		$str .= '};'."\n";
		$this->js_resources = $str;

		$form->add($d);

		// check profiles
		$profile = $this->response->html->request()->get('profile');
		if($profile !== '') {
			$profile = $this->cloudprofile->name;
			if(isset($this->cloudprofile->resource_type_req)) {
				$p = false;
				foreach($cloud_virtualization_select_arr as $v) {
					if($v['value'] === $this->cloudprofile->resource_type_req) {
						$p = true;
						break;
					}
				}
				if($p === false) {
					$form->set_error('cloud_virtualization_select', sprintf($this->lang['error_profile'], $this->lang['type'], $profile));
				}
			}
			if(isset($this->cloudprofile->image_id)) {
				$p = false;
				foreach($cloud_image_select_arr as $v) {
					if($v['value'] === $this->cloudprofile->image_id) {
						$p = true;
						break;
					}
				}
				if($p === false) {
					$form->set_error('cloud_image_select',  sprintf($this->lang['error_profile'], $this->lang['image'], $profile));
				}
			}
			if(isset($this->cloudprofile->kernel_id)) {
				$p = false;
				foreach($cloud_kernel_select_arr as $v) {
					if($v['value'] === $this->cloudprofile->kernel_id) {
						$p = true;
						break;
					}
				}
				if($p === false) {
					$form->set_error('cloud_kernel_select',  sprintf($this->lang['error_profile'], $this->lang['kernel'], $profile));
				}
			}
			if(isset($this->cloudprofile->disk_req)) {
				$p = false;
				foreach($cloud_disk_select_arr as $v) {
					if($v['value'] == $this->cloudprofile->disk_req) {
						$p = true;
						break;
					}
				}
				if($p === false) {
					$form->set_error('cloud_disk_select',  sprintf($this->lang['error_profile'], $this->lang['disk'].' ('.$this->cloudprofile->disk_req.' MB)', $profile));
				}
			}
			if(isset($this->cloudprofile->ram_req)) {
				$p = false;
				foreach($cloud_memory_select_arr as $v) {
					if($v['value'] == $this->cloudprofile->ram_req) {
						$p = true;
						break;
					}
				}
				if($p === false) {
					$form->set_error('cloud_memory_select',  sprintf($this->lang['error_profile'], $this->lang['ram'].' ('.$this->cloudprofile->ram_req.' MB)', $profile));
				}
			}
			if(isset($this->cloudprofile->cpu_req)) {
				$p = false;
				foreach($cloud_cpu_select_arr as $v) {
					if($v['value'] == $this->cloudprofile->cpu_req) {
						$p = true;
						break;
					}
				}
				if($p === false) {
					$form->set_error('cloud_cpu_select',  sprintf($this->lang['error_profile'], $this->lang['cpu'].' ('.$this->cloudprofile->cpu_req.')', $profile));
				}
			}
			if(isset($this->cloudprofile->network_req)) {
				$p = false;
				foreach($cloud_network_select_arr as $v) {
					if($v['value'] == $this->cloudprofile->network_req) {
						$p = true;
						break;
					}
				}
				if($p === false) {
					$form->set_error('cloud_network_select',  sprintf($this->lang['error_profile'], $this->lang['network'].' ('.$this->cloudprofile->network_req.')', $profile));
				}
			}
		}

		$form->display_errors = false;
		$response->form = $form;
		return $response;
	}

	//--------------------------------------------
	/**
	 * Match virtualization with image
	 *
	 * @access protected
	 * @param string|object $img
	 * @param array $virttypes
	 * @return string
	 */
	//--------------------------------------------
	function __get_virt_tag($img, $virttypes) {
		$str = '';
		$tag = '';
		if(is_string($img)) {
			$tag = $img;
		}
		else if (is_object($img)) {
			if(isset($img->type) && $img->type !== '') {
				$deployment = $this->openqrm->deployment();
				$deployment->get_instance_by_name($img->type);
				$tag  = '';
				if($img->is_network_deployment() === true) {
					$tag = 'vm-net';
				} else {
					$tag = $deployment->storagetype.'-vm-local';
				}
			}
		}
		$mark = array_search($tag, array_keys($virttypes));
		if(is_integer($mark)) {
			$str = '*';
			for($i=0;$i<$mark;$i++) {
				$str .= '*';
			}
		}
		return $str;
	}

}
?>
