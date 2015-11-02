<?php
/**
 * Cloud UI API
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class cloud_api
{
/**
* absolute path to template dir
* @access public
* @var string
*/
var $tpldir;
/**
* absolute path to webroot
* @access public
* @var string
*/
var $rootdir;

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param cloud_controller
	 */
	//--------------------------------------------
	function __construct($openqrm, $response, $controller) {
		$this->openqrm = $openqrm;
		$this->controller = $controller;
		$this->response   = $this->controller->response;
		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloudconfig = new cloudconfig();
		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/clouduserslimits.class.php";
		$this->clouduserlimits = new clouduserlimits();
		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/cloudappliance.class.php";
		$this->cloudappliance = new cloudappliance();
		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/cloudrequest.class.php";
		$this->cloudrequest = new cloudrequest();
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 */
	//--------------------------------------------
	function action() {
		$action = $this->response->html->request()->get('action');
		switch( $action ) {
			case 'calculator':
				$this->cloud_cost_calculator();
			break;
			case 'request_details':
				$this->get_request_details();
			break;
			case 'limits':
				$this->limits();
			break;
			case 'collectd':
				$this->collectd();
			break;
			case 'novnc':
				$this->novnc();
			break;
			case 'state':
				$this->state();
			break;

		}
	}

	//--------------------------------------------
	/**
	 * Limits
	 *
	 * @access public
	 * @return JSON
	 */
	//--------------------------------------------
	function limits () {

		$this->clouduserlimits->get_instance_by_cu_id($this->openqrm->user()->id);
		require_once "cloud.limits.class.php";
		$limits = new cloud_limits($this->openqrm, $this->cloudconfig, $this->clouduserlimits, $this->cloudrequest);

		// Sytems limits donut
		$s_max    = $limits->max('resource');
		$s_new    = 0;
		$s_paused = 0;
		$s_active = 0;
		$d_max    = $limits->max('disk');
		$d_paused = 0;
		$d_active = 0;
		$d_new    = 0;
		$m_max    = $limits->max('memory');
		$m_paused = 0;
		$m_active = 0;
		$m_new    = 0;
		$c_max    = $limits->max('cpu');
		$c_paused = 0;
		$c_active = 0;
		$c_new    = 0;
		$n_max    = $limits->max('network');
		$n_paused = 0;
		$n_active = 0;
		$n_new    = 0;

		$requests = $this->cloudrequest->get_all_ids_per_user($this->openqrm->user()->id);
		foreach ($requests as $v) {
			$appliance = null;
			$this->cloudrequest->get_instance_by_id($v['cr_id']);
			if ((strlen($this->cloudrequest->appliance_id)) && ($this->cloudrequest->appliance_id != 0)) {
				$appliance = $this->openqrm->appliance();
				$appliance->get_instance_by_id($this->cloudrequest->appliance_id);
				if($appliance->state === 'stopped') {
					$s_paused++;
					$d_paused = $d_paused + $this->cloudrequest->disk_req;
					$m_paused = $m_paused + $this->cloudrequest->ram_req;
					$c_paused = $c_paused + $this->cloudrequest->cpu_req;
					$n_paused = $n_paused + $this->cloudrequest->network_req;
				}
				if($appliance->state === 'active') {
					$s_active++;
					$d_active = $d_active + $this->cloudrequest->disk_req;
					$m_active = $m_active + $this->cloudrequest->ram_req;
					$c_active = $c_active + $this->cloudrequest->cpu_req;
					$n_active = $n_active + $this->cloudrequest->network_req;
				}
			}
			if($this->cloudconfig->get_value_by_key('auto_provision') === 'false') {
				if($this->cloudrequest->getstatus($v['cr_id']) === 'new') {
					$s_new++;
					$d_new = $d_new + $this->cloudrequest->disk_req;
					$m_new = $m_new + $this->cloudrequest->ram_req;
					$c_new = $c_new + $this->cloudrequest->cpu_req;
					$n_new = $n_new + $this->cloudrequest->network_req;
				}
			}
		}

		$free = $limits->free('resource');
		$str  = '{';
		$str .= '"systems_list": [';
		$str .= '["'.$this->controller->lang['home']['free'].'",'.$free.'],';
		$str .= '["'.$this->controller->lang['home']['active'].'",'.$s_active.'],';
		$str .= '["'.$this->controller->lang['home']['paused'].'",'.$s_paused.']';
		if($this->cloudconfig->get_value_by_key('auto_provision') === 'false') {
			$str .= ',["'.$this->controller->lang['home']['new'].'",'.$s_new.']';
		}
		$str .= '],';

		$free = $limits->free('disk');
		$str .= '"disk_list": [';
		$str .= '["'.$this->controller->lang['home']['free'].'",'.$free.'],';
		$str .= '["'.$this->controller->lang['home']['active'].'",'.$d_active.'],';
		$str .= '["'.$this->controller->lang['home']['paused'].'",'.$d_paused.']';
		if($this->cloudconfig->get_value_by_key('auto_provision') === 'false') {
			$str .= ',["'.$this->controller->lang['home']['new'].'",'.$d_new.']';
		}
		$str .= '],';

		$free = $limits->free('memory');
		$str .= '"memory_list": [';
		$str .= '["'.$this->controller->lang['home']['free'].'",'.$free.'],';
		$str .= '["'.$this->controller->lang['home']['active'].'",'.$m_active.'],';
		$str .= '["'.$this->controller->lang['home']['paused'].'",'.$m_paused.']';
		if($this->cloudconfig->get_value_by_key('auto_provision') === 'false') {
			$str .= ',["'.$this->controller->lang['home']['new'].'",'.$m_new.']';
		}
		$str .= '],';

		$free = $limits->free('cpu');
		$str .= '"cpu_list": [';
		$str .= '["'.$this->controller->lang['home']['free'].'",'.$free.'],';
		$str .= '["'.$this->controller->lang['home']['active'].'",'.$c_active.'],';
		$str .= '["'.$this->controller->lang['home']['paused'].'",'.$c_paused.']';
		if($this->cloudconfig->get_value_by_key('auto_provision') === 'false') {
			$str .= ',["'.$this->controller->lang['home']['new'].'",'.$c_new.']';
		}
		$str .= '],';

		$free = $limits->free('network');
		$str .= '"network_list": [';
		$str .= '["'.$this->controller->lang['home']['free'].'",'.$free.'],';
		$str .= '["'.$this->controller->lang['home']['active'].'",'.$n_active.'],';
		$str .= '["'.$this->controller->lang['home']['paused'].'",'.$n_paused.']';
		if($this->cloudconfig->get_value_by_key('auto_provision') === 'false') {
			$str .= ',["'.$this->controller->lang['home']['new'].'",'.$n_new.']';
		}
		$str .= ']';

		$str .= '}';
		echo $str;
	}



	function cloud_cost_calculator() {
		require_once $this->rootdir."/plugins/cloud/class/cloudselector.class.php";
		$cloudselector = new cloudselector();

		$virtualization_id = $this->response->html->request()->get('virtualization');
		$kernel_id = $this->response->html->request()->get('kernel');
		$memory_val = $this->response->html->request()->get('memory');
		$cpu_val = $this->response->html->request()->get('cpu');
		$disk_val = $this->response->html->request()->get('disk');
		$network_val = $this->response->html->request()->get('network');
		$ha_val = $this->response->html->request()->get('ha');
		$apps_val = $this->response->html->request()->get('apps');

		// resource type
		$cost_virtualization = 0;
		if (strlen($virtualization_id)) {
			$virtualization = new virtualization();
			$virtualization->get_instance_by_id($virtualization_id);
			$cost_virtualization = $cloudselector->get_price($virtualization->id, "resource");
		}
		// kernel
		$cost_kernel = 0;
		if (strlen($kernel_id)) {
			$kernel = new kernel();
			$kernel->get_instance_by_id($kernel_id);
			$cost_kernel = $cloudselector->get_price($kernel->id, "kernel");
		}
		// memory
		$cost_memory = 0;
		if (strlen($memory_val)) {
			$cost_memory = $cloudselector->get_price($memory_val, "memory");
		}
		// cpu
		$cost_cpu = 0;
		if (strlen($cpu_val)) {
			$cost_cpu = $cloudselector->get_price($cpu_val, "cpu");
		}
		// disk
		$cost_disk = 0;
		if (strlen($disk_val)) {
			$cost_disk = $cloudselector->get_price($disk_val, "disk");
		}
		// network
		$cost_network = 0;
		if (strlen($network_val)) {
			$cost_network = $cloudselector->get_price($network_val, "network");
		}

		// ha
		$cost_ha = 0;
		if ($ha_val == 1) {
			$cost_ha = $cloudselector->get_price($ha_val, "ha");
		}

		// puppet apps
		$cost_app_total = 0;
		if (strlen($apps_val)) {
			$apps_val = trim($apps_val, ',');
			$application_array = explode(",", $apps_val);
			foreach ($application_array as $cloud_app) {
				$cost_app = $cloudselector->get_price($cloud_app, "application");
				$cost_app_total = $cost_app_total + $cost_app;
			}
		}

		// get cloud currency
		$currency = $this->cloudconfig->get_value_by_key('cloud_currency');   // 23 is cloud_currency
		$ccus_value = $this->cloudconfig->get_value_by_key('cloud_1000_ccus');   // 24 is cloud_1000_ccus

		// summary
		$per_appliance = $cost_virtualization + $cost_kernel + $cost_memory + $cost_cpu + $cost_disk + $cost_network + $cost_app_total + $cost_ha;
		$real_currency = $ccus_value / 1000;
		$per_hour = $per_appliance * $real_currency;
		$per_day = $per_hour * 24;
		$per_month = $per_day * 31;

		$per_hour = number_format($per_hour, 2, ",", "");
		$per_day = number_format($per_day, 2, ",", "");
		$per_month = number_format($per_month, 2, ",", "");

		$costs  = 'summary='.$per_appliance.';';
		$costs .= 'total='.$cost_app_total.' '.$currency.';';
		$costs .= 'hour='.$per_hour.' '.$currency.';';
		$costs .= 'day='.$per_day.' '.$currency.';';
		$costs .= 'month='.$per_month.' '.$currency.';';

		echo $costs;
	}

	//--------------------------------------------
	/**
	 * Collectd
	 *
	 * @access public
	 */
	//--------------------------------------------
	function collectd() {
		require_once($this->openqrm->get('basedir').'/plugins/collectd/web/class/collectd.controller.class.php');
		$controller = new collectd_controller($this->openqrm, $this->response);
		$controller->actions_name  = 'cloud_ui';
		$controller->tpldir        = $this->openqrm->get('basedir').'/plugins/collectd/web/tpl/';
		$controller->message_param = 'collectd';
		$controller->image_path    = 'api.php?action=collectd&'.$controller->actions_name.'=image';
		$controller->api();
	}

	//--------------------------------------------
	/**
	 * noVNC
	 *
	 * @access public
	 */
	//--------------------------------------------
	function novnc() {
		$image = $this->response->html->request()->get('image');
		if($image === '') {
			require_once($this->openqrm->get('basedir').'/plugins/novnc/web/class/novnc.controller.class.php');
			$controller = new novnc_controller($this->openqrm, $this->response);
			$controller->actions_name = 'novnc';
			$controller->tpldir       = $this->openqrm->get('basedir').'/plugins/novnc/web/tpl/';
			$controller->action       = 'console';
			$controller->imgurl       = 'api.php?action=novnc&image=';
			$controller->jsurl        = '/cloud-portal/novncjs/';
			$_REQUEST[$controller->actions_name] = 'console';
			$controller->api();
		} else {
			$path = $this->openqrm->get('basedir').'/plugins/novnc/web/img/'.$image;
			if($this->openqrm->file()->exists($path)) {
				$size   = filesize($path);
				$mime   = 'image/png';
				header("Pragma: public");
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: must-revalidate");
				header("Content-type: $mime");
				header("Content-Length: ".$size);
				header("Content-disposition: inline; filename=test");
				header("Accept-Ranges: ".$size);
				flush();
				readfile($path);
			}
			exit(0);
		}
	}

	//--------------------------------------------
	/**
	 * State Trace
	 *
	 * @access public
	 */
	//--------------------------------------------
	function state() {
		require_once($this->openqrm->get('basedir').'/plugins/cloud/web/class/cloudrequest.class.php');
		$request = $this->response->html->request()->get('request');
		if($request !== '') {
			$cloudrequest = new cloudrequest();
			$cloudrequest->get_instance_by_id($request);
			echo date('H:i:s', time());
			echo ' '.$cloudrequest->status;
			if($cloudrequest->appliance_id !== '') {
				$appliance = $this->openqrm->appliance();
				$appliance->get_instance_by_id($cloudrequest->appliance_id);
				echo ' '.$appliance->state;
				if( $appliance->resources !== '' && $appliance->resources !== '-1') {
					$resource = $this->openqrm->resource();
					$resource->get_instance_by_id($appliance->resources);
					echo ' '.$resource->state;
				}
			}
		}
	}

}
?>
