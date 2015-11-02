<?php
/**
 * Cloud Request Clean
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_request_clean
{
/**
* path to templates
* @access public
* @var string
*/
var $tpldir;
/**
* table indentifier
* @access public
* @var string
*/
var $identifier_name;
/**
* translation
* @access public
* @var array
*/
var $lang;
/**
* message param
* @access public
* @var string
*/
var $message_param = "cloud_request_msg";
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_request';

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
		$this->timeout = 60;
		$event = new event();
		$this->event = $event;
		require_once $this->rootdir."/plugins/cloud/web/class/clouduser.class.php";
		$this->cloud_user = new clouduser();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudrequest.class.php";
		$this->cloud_request = new cloudrequest();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloud_config = new cloudconfig();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudmailer.class.php";
		$this->cloud_mailer = new cloudmailer();
		if(class_exists('cloudappliance') === false) {
			require_once $this->rootdir."/plugins/cloud/web/class/cloudappliance.class.php";
		}
		$this->cloudappliance = new cloudappliance();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudimage.class.php";
		$this->cloudimage = new cloudimage();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudstorage.class.php";
		$this->cloudstorage = new cloudstorage();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudvm.class.php";
		$this->cloudvm = new cloudvm();
		require_once $this->rootdir."/plugins/cloud/web/class/cloudcreatevmlc.class.php";
		$this->cloudcreatevmlc = new cloudcreatevmlc();
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @return htmlobject_template
	 */
	//--------------------------------------------
	function action() {
		$response = $this->clean();
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'select', $this->message_param, $response->msg));
		}
		else if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}

		$template = $this->response->html->template($this->tpldir."/cloud-request-clean.tpl.php");
		$template->add($response->form->get_elements());
		$template->add($response->html->thisfile, "thisfile");
		$template->add($this->lang['cloud_request_confirm_clean'], 'confirm_clean');
		$template->group_elements(array('param_' => 'form'));

		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Request Clean
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function clean() {
		$response = $this->get_response();
		$form = $response->form;
		$confirm_clean_up = $this->response->html->request()->get('clean_up');
		if(!$form->get_errors() && $this->response->submit()) {
			$error = $this->check_consistency(true);
			if ($error === '') {
				$response->msg = 'cloud_request_clean';
			} else {
				$response->error = $this->lang['cloud_request_clean_failed'];
				$div = $this->response->html->div();
				$div->name = 'errors';
				$div->add($error);
				$d['param_f0']['object'] = $div;
				$response->form->add($d);
			}
		} else {
			$error = $this->check_consistency(false);
			if ($error === '') {
				$response->msg = $this->lang['cloud_request_clean_noop'];
			} else {
				$response->error = $this->lang['cloud_request_clean_noop_failed'];
				$div = $this->response->html->div();
				$div->name = 'errors';
				$div->add($error);
				$d['param_f0']['object'] = $div;
				$response->form->add($d);
			}
		}
		return $response;
	}

	//--------------------------------------------
	/**
	 * Get response
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {
		$response =$this->response;
		$form     = $response->get_form($this->actions_name, 'clean');
		$response->form = $form;
		return $response;
	}

	//--------------------------------------------
	/**
	 * check_consistency
	 *
	 * @access public
	 * @param bool $noop
	 * @return string empty if no errors
	 */
	//--------------------------------------------
	function check_consistency($noop) {
		$errors = array();
		$noop_str = '';
		// cloud appliance check
		$cloud_appliance_arr = $this->cloudappliance->get_all_ids();
		foreach ($cloud_appliance_arr as $capp) {
			$ca_id = $capp['ca_id'];
			$this->cloudappliance->get_instance_by_id($ca_id);
			$cr = new cloudrequest();
			$cr->get_instance_by_id($this->cloudappliance->cr_id);
			// cr not existing any more, just remove the cloud appliance
			if (!strlen($cr->status)) {
				if ($noop) {
					$this->cloudappliance->remove($ca_id);
				} else {
					// error
					$errors[] = 'cr '.$ca_id.' missing, found left-over!<br>';
				}
			}
			if ($cr->status == "6") {
				if ($noop) {
					$clean_appliance = new appliance();
					$clean_appliance->get_instance_by_id($cr->appliance_id);
					$error = $this->__remove_cloud_resource($clean_appliance->resources, true, $cr->id);
					if($error !== '') {
						$errors[] = $error;
					}
					$error = $this->__remove_cloud_image($clean_appliance->imageid, true);
					if($error !== '') {
						$errors[] = $error;
					}
					$error = $this->__remove_cloud_appliance($clean_appliance->id, true);
					if($error !== '') {
						$errors[] = $error;
					}
					$error = $this->__free_cloud_ips($cr->id, true);
					if($error !== '') {
						$errors[] = $error;
					}
					$this->cloudappliance->remove($ca_id);
				} else {
					// error
					$errors[] = "cr ".$cr->id." - found left-over!<br>";
				}
			}
		}
		unset($cr);
		// cloud image check
		$cloud_image_arr = $this->cloudimage->get_all_ids();
		foreach ($cloud_image_arr as $cim) {
			$ci_id = $cim['ci_id'];
			$this->cloudimage->get_instance_by_id($ci_id);
			$cr = new cloudrequest();
			$cr->get_instance_by_id($this->cloudimage->cr_id);
			// cr not existing any more, just remove the cloud image
			if (!strlen($cr->status)) {
				if ($noop) {
					$this->cloudimage->remove($ci_id);
				} else {
					// error
					$errors[] = "cr missing, found left-over!<br>";
				}
			}
			// cr status done
			if ($cr->status == "6") {
				if ($noop) {
					$error = $this->__remove_cloud_resource($this->cloudimage->resource_id, $noop, $cr->id);
					if($error !== '') {
						$errors[] = $error;
					}
					$error = $this->__remove_cloud_image($this->cloudimage->image_id, $noop);
					if($error !== '') {
						$errors[] = $error;
					}
					$error = $this->__remove_cloud_appliance($this->cloudimage->appliance_id, $noop);
					if($error !== '') {
						$errors[] = $error;
					}
					$error = $this->__free_cloud_ips($cr->id, $noop);
					if($error !== '') {
						$errors[] = $error;
					}
					$this->cloudimage->remove($ci_id);
				} else {
					// error
					$errors[] = "cr ".$cr->id." - found left-over!<br>";
				}
			}
		}
		// create vm lc
		$cvmlc_arr = $this->cloudcreatevmlc->get_all_ids();
		foreach ($cvmlc_arr as $cvmlc_ids) {
			$cvmlc_id = $cvmlc_ids['vc_id'];
			$this->cloudcreatevmlc->get_instance_by_id($cvmlc_id);
			$now=$_SERVER['REQUEST_TIME'];
			$vm_c_timeout = $this->cloudcreatevmlc->request_time + $this->cloudcreatevmlc->vm_create_timeout + $this->cloudcreatevmlc->vm_create_timeout;
			if ($now > $vm_c_timeout) {
				if ($noop) {
					$this->cloudcreatevmlc->remove($cvmlc_id);
				} else {
					// error
					$errors[] = "found left-over CVMLC ".$cvmlc_id." <br>";
				}
			}
		}
		// check all done requests if they still contain an appliance id
		$crl = new cloudrequest();
		$cr_list = $crl->get_all_ids();
		foreach($cr_list as $list) {
			$cr_id = $list['cr_id'];
			$cr = new cloudrequest();
			$cr->get_instance_by_id($cr_id);
			if ($cr->status != 6) {
				continue;
			}
			if (strlen($cr->appliance_id)) {
				if ($cr->appliance_id != 0) {
					if ($noop) {
						$cr->setappliance("remove", $cr->appliance_id);
					} else {
						// error
						$errors[] = "removing appliance id ".$cr->appliance_id." from request ".$cr->id." <br>";
					}
				}
			}
		}
		// check all ips
		$this->__free_cloud_ips(0, $noop);
		if ($noop) {
			$this->event->remove_by_description('Could not create instance of');
		}
		// handle errors
		if(count($errors) < 1) {
			return '';
		} else {
			return implode('', $errors);
		}
	}

	//--------------------------------------------
	/**
	 * remove resource
	 *
	 * @access public
	 * @param integer $resource_id
	 * @param bool $noop
	 * @param integer $cr_id
	 * @return string empty if no errors 
	 */
	//--------------------------------------------
	function __remove_cloud_resource($resource_id, $noop, $cr_id = NULL) {
		$error = '';
		if (($resource_id != "-1") && ($resource_id != "0")) {
			$resource = new resource();
			$resource->get_instance_by_id($resource_id);
			if (strlen($resource->hostname)) {
				if (isset($cr_id)) {
					$this->cloudcreatevmlc->get_instance_by_resource_id($resource->id);
					if ($this->cloudcreatevmlc->cr_id == $cr_id) {
						if ($noop) {
							$this->cloudcreatevmlc->remove($this->cloudcreatevmlc->id);
						} else {
							// error
							$error = " - removing CVMLC: ".$this->cloudcreatevmlc->id."<br>";
						}
					}
				}
				if ($noop) {
					$this->cloudvm->remove($resource->id, $resource->vtype, $resource->hostname, $resource->mac);
				}
			}
		}
		return $error;
	}

	//--------------------------------------------
	/**
	 * remove image
	 *
	 * @access private
	 * @param integer $image_id
	 * @param bool $noop
	 * @return string empty if no errors 
	 */
	//--------------------------------------------
	function __remove_cloud_image($image_id, $noop) {
		$error = '';
		$image = new image();
		$image->get_instance_by_id($image_id);
		if (strlen($image->name)) {
			$authblocker = new authblocker();
			$authblocker->get_instance_by_image_id($image->id);
			if ($authblocker->image_name == $image->name) {
				if ($noop) {
					$authblocker->remove($authblocker->id);
				} else{
					// error
					$error = "removing authblocker: ".$authblocker->id."<br>";
				}
			}
			$this->cloudimage->get_instance_by_image_id($image_id);
			if ($noop) {
				$this->cloudstorage->remove($this->cloudimage->id, $this->timeout);
				$image->remove($image->id);
			}
		}
		return $error;
	}

	//--------------------------------------------
	/**
	 * remove appliance
	 *
	 * @access private
	 * @param integer $appliance_id
	 * @param bool $noop
	 * @return string empty if no errors 
	 */
	//--------------------------------------------
	function __remove_cloud_appliance($appliance_id, $noop) {
		$error = '';
		$appliance = new appliance();
		$appliance->get_instance_by_id($appliance_id);
		$cloudappliance = new cloudappliance();
		$cloudappliance->get_instance_by_appliance_id($appliance->id);
		if (strlen($cloudappliance->cr_id)) {
			if ($noop) {
				$cloudappliance->remove($cloudappliance->id);
			} else{
				// error
				 $error = "removing cloudappliance ".$cloudappliance->id."<br>";
			}
		}
		if ($noop) {
			$appliance->remove($appliance->id);
		}
		return $error;
	}

	//--------------------------------------------
	/**
	 * remove cloud ips
	 *
	 * @access private
	 * @param integer $cr_id
	 * @param bool $noop
	 * @return string empty if no errors
	 */
	//--------------------------------------------
	function __free_cloud_ips($cr_id, $noop) {
		$error = '';
		$cc_conf = new cloudconfig();
		$show_ip_mgmt = $cc_conf->get_value(26);	// ip-mgmt enabled ?
		if ($cr_id == 0) {
			// check and clean all ips
			if (!strcmp($show_ip_mgmt, "true")) {
				if (file_exists($this->webdir."/plugins/ip-mgmt/.running")) {
					require_once $this->webdir."/plugins/ip-mgmt/class/ip-mgmt.class.php";
					$ip_mgmt = new ip_mgmt();
					$ip_mgmt_id_array = $ip_mgmt->get_ids();
					foreach($ip_mgmt_id_array as $ip_id) {
						$ip = $ip_mgmt->get_instance('id', $ip_id['ip_mgmt_id']);
						$state = $ip['ip_mgmt_state'];
						$token = $ip['ip_mgmt_token'];
						$found_token = false;
						// check if the appliance still exists
						if (strlen($ip['ip_mgmt_appliance_id'])) {
							$ip_appliance = new appliance();
							if ($ip_appliance->is_id_free($ip['ip_mgmt_appliance_id'])) {
								// error
								$error .="found left over ip ".$ip['ip_mgmt_address']."<br>";
							}
						}
						// check if we have a token set, if yes check all appliances for that token
						if ((strlen($token)) && ($token != '0')) {
							$ip_appliance = new appliance();
							$ip_appliance_id_array = $ip_appliance->get_all_ids();
							foreach($ip_appliance_id_array as $ip_app_id_arr) {
								$ip_app_id = $ip_app_id_arr['appliance_id'];
								$ip_appliance->get_instance_by_id($ip_app_id);
								if (strlen($ip_appliance->capabilities)) {
									$pos = strpos($ip_appliance->capabilities, $token);
									if ($pos !== false) {
										$found_token = true;
									}
								}
							}
							if (!$found_token) {
								// error
								$error .= "found left over ip with token set / appliance missing ".$ip[ip_mgmt_address]."<br>";
							}
						} else {
							if ($state == 1) {
								// error
								$error .= "found left over ip with no token set ".$ip[ip_mgmt_address]."<br>";
							}
						}
					}
				}
			}
		} else {
			// clean ips for a specific cr
			$cr_ip = new cloudrequest();
			$cr_ip->get_instance_by_id($cr_id);
			if (!strcmp($show_ip_mgmt, "true")) {
				if (file_exists($this->webdir."/plugins/ip-mgmt/.running")) {
					require_once $this->webdir."/plugins/ip-mgmt/class/ip-mgmt.class.php";
					$ip_mgmt_array = explode(",", $cr_ip->ip_mgmt);
					foreach($ip_mgmt_array as $ip_mgmt_config_str) {
						$collon_pos = strpos($ip_mgmt_config_str, ":");
						$nic_id = substr($ip_mgmt_config_str, 0, $collon_pos);
						$ip_mgmt_id = substr($ip_mgmt_config_str, $collon_pos+1);
						$ip_mgmt_fields=array();
						$ip_mgmt_fields["ip_mgmt_appliance_id"]=NULL;
						$ip_mgmt_fields["ip_mgmt_nic_id"]=NULL;
						$ip_mgmt_assign = new ip_mgmt();
						if ($noop) {
							$ip_mgmt_assign->update_ip($ip_mgmt_id, $ip_mgmt_fields);
						} else {
							// error
							$error .= "freeing up ip ".$ip_mgmt_id."<br>";
						}
					}
				}
			}
		}
		return $error;
	}

}
?>
