<?php
/**
 * Cloud Users Appliance Login
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_login
{

var $tpldir;
var $identifier_name;
var $lang;
var $actions_name = 'cloud-ui-login';

var $OPENQRM_SERVER_BASE_DIR = "/usr/share";



	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $path path to dir
	 * @param htmlobject_response $response
	 */
	//--------------------------------------------
	function __construct($response) {
		$this->response = $response;
		$this->rootdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
	    // include classes and prepare ojects
	    require_once $this->rootdir."/plugins/cloud/class/cloudappliance.class.php";
	    $this->cloudappliance	= new cloudappliance();
	    require_once $this->rootdir."/class/appliance.class.php";
	    $this->appliance	= new appliance();
		$this->resourde		= new resource();
		require_once $this->rootdir."/plugins/cloud/class/cloudrequest.class.php";
		$this->cloudrequest	= new cloudrequest();

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
		if ($this->response->html->request()->get($this->identifier_name) === '') {
			$this->response->redirect($this->response->get_url($this->actions_name, 'appliances'));
		}
		$response = $this->login();
//		if(isset($response->error)) {
//			$_REQUEST[$this->message_param] = $response->error;
//		}
		if(isset($response->msg)) {
			$this->response->redirect($this->response->get_url($this->actions_name, 'appliances', $this->message_param, $response->msg));
		}
		$template = $this->response->html->template($this->tpldir."/cloud-ui.login.tpl.php");
		$template->add($response->html->thisfile, "thisfile");
		$template->group_elements(array('param_' => 'form'));

		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Appliance Login
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function login() {
		$form = $this->response->get_form($this->actions_name, 'login');
		$cloud_cloudappliance_id_arr = $this->response->html->request()->get($this->identifier_name);

		$show_ip_mgmt = false;
	    if (!strcmp($this->cloudconfig->get_value_by_key('ip-management'), "true")) {
			$show_ip_mgmt = true;
		}

	    if (!strcmp($this->cloudconfig->get_value_by_key('show_sshterm_login'), "true")) {
			// is sshterm plugin enabled + started ?
			if (file_exists($this->rootdir."/plugins/sshterm/.running")) {
				// get the parameters from the plugin config file
				$OPENQRM_PLUGIN_SSHTERM_CONFIG_FILE=$this->OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/sshterm/etc/openqrm-plugin-sshterm.conf";
				$store = openqrm_parse_conf($OPENQRM_PLUGIN_SSHTERM_CONFIG_FILE);
				extract($store);

				foreach($cloud_cloudappliance_id_arr as $key => $cloudappliance_id) {
					$this->cloudappliance->get_instance_by_id($cloudappliance_id);
					$this->appliance->get_instance_by_id($this->cloudappliance->appliance_id);
					$this->cloudrequest->get_instance_by_id($this->cloudappliance->cr_id);
					if ($this->cloudrequest->cu_id != $this->clouduser->id) {
						// not request of the authuser
						exit(1);
					}
					// here we check which ip to send to the user
					// check ip-mgmt
					$sshterm_login_ip = '';
					if ($show_ip_mgmt) {
						if (file_exists($this->rootdir."/plugins/ip-mgmt/.running")) {
							require_once $this->rootdir."/plugins/ip-mgmt/class/ip-mgmt.class.php";
							$ip_mgmt = new ip_mgmt();
							$appliance_first_nic_ip_mgmt_id = $ip_mgmt->get_id_by_appliance($this->cloudappliance->appliance_id, 1);
							if ($appliance_first_nic_ip_mgmt_id > 0) {
								$appliance_ip_mgmt_config_arr = $ip_mgmt->get_instance('id', $appliance_first_nic_ip_mgmt_id);
								if (isset($appliance_ip_mgmt_config_arr['ip_mgmt_address'])) {
									$sshterm_login_ip = $appliance_ip_mgmt_config_arr['ip_mgmt_address'];
								}
							}
						}
					}
					if (!strlen($sshterm_login_ip)) {
						// in case no external ip was given to the appliance we show the internal ip
						$this->resourde->get_instance_by_id($this->appliance->resources);
						$sshterm_login_ip =  $this->resourde->ip;
					}
					if (!strlen($sshterm_login_ip)) {
						continue;
					}
					$redirect_url="https://$sshterm_login_ip:$OPENQRM_PLUGIN_WEBSHELL_PORT";
					$left=50+($cloudappliance_id*50);
					$top=100+($cloudappliance_id*50);
					// add the javascript function to open an sshterm
					?>
								<script type="text/javascript">
								function open_sshterm (url) {
									sshterm_window = window.open(url, "<?php echo $sshterm_login_ip; ?>", "width=580,height=420,scrollbars=1,left=<?php echo $left; ?>,top=<?php echo $top; ?>");
									open_sshterm.focus();
								}
								open_sshterm("<?php echo $redirect_url; ?>");
								</script>
					<?php
				}
			}
		}
		flush();
		$this->response->redirect($this->response->get_url($this->actions_name, 'appliances'));
		return $form;
	}





}

?>


