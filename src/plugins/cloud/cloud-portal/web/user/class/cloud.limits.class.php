<?php
/**
 * Cloud Limits
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/
class cloud_limits
{

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $path path to dir
	 * @param htmlobject_response $response
	 */
	//--------------------------------------------
	function __construct($openqrm, $cloudconfig, $clouduserlimits, $cloudrequest) {

		$this->openqrm = $openqrm;
		$this->cloudconfig = $cloudconfig;
		$this->clouduserlimits = $clouduserlimits;
		$this->cloudrequest = $cloudrequest;

		// resource limit
		$resource_limit = 'unlimited';
		$max_apps = $this->cloudconfig->get_value_by_key('max_apps_per_user');
		if ($this->clouduserlimits->resource_limit > 0) {
			if($this->clouduserlimits->resource_limit > $max_apps) {
				$resource_limit = $max_apps;
			} else {
				$resource_limit = $this->clouduserlimits->resource_limit;
			}
		}
		else if(isset($max_apps) && $max_apps !== '') {
			$resource_limit = $max_apps;
		}
		$this->__resource = $resource_limit;

		// memory limit
		$memory_limit = 'unlimited';
		$max_memory = $this->cloudconfig->get_value_by_key('max_memory');
		if ($this->clouduserlimits->memory_limit > 0) {
			if($this->clouduserlimits->memory_limit > $max_memory) {
				$memory_limit = $max_memory;
			} else {
				$memory_limit = $this->clouduserlimits->memory_limit;
			}
		}
		else if(isset($max_memory) && $max_memory !== '') {
			$memory_limit = $max_memory;
		}
		$this->__memory = $memory_limit;

		// disk limit
		$disk_limit = 'unlimited';
		$max_disk = $this->cloudconfig->get_value_by_key('max_disk_size');
		if ($this->clouduserlimits->disk_limit > 0) {
			if($this->clouduserlimits->disk_limit > $max_disk) {
				$disk_limit = $max_disk;
			} else {
				$disk_limit = $this->clouduserlimits->disk_limit;
			}
		}
		else if(isset($max_disk) && $max_disk !== '') {
			$disk_limit = $max_disk;
		}
		$this->__disk = $disk_limit;

		// cpu limit
		$cpu_limit = 'unlimited';
		$max_cpu = $this->cloudconfig->get_value_by_key('max_cpu');
		if ($this->clouduserlimits->cpu_limit > 0) {
			if($this->clouduserlimits->cpu_limit > $max_cpu) {
				$cpu_limit = $max_cpu;
			} else {
				$cpu_limit = $this->clouduserlimits->cpu_limit;
			}
		}
		else if(isset($max_cpu) && $max_cpu !== '') {
			$cpu_limit = $max_cpu;
		}
		$this->__cpu = $cpu_limit;

		// network limit
		$nic_limit = 'unlimited';
		$max_nic = $this->cloudconfig->get_value_by_key('max_network');
		if ($this->clouduserlimits->network_limit > 0) {
			if($this->clouduserlimits->network_limit > $max_nic) {
				$nic_limit = $max_nic;
			} else {
				$nic_limit = $this->clouduserlimits->network_limit;
			}
		}
		else if(isset($max_nic) && $max_nic !== '') {
			$nic_limit = $max_nic;
		}
		$this->__network = $nic_limit;
	}

	//--------------------------------------------
	/**
	 * Get max limit
	 *
	 * @access public
	 * @param string mode
	 * @return null|integer
	 */
	//--------------------------------------------
	function max($mode) {
		if($mode === 'disk') {
			return $this->__disk;
		}
		else if($mode === 'network') {
			return $this->__network;
		}
		else if($mode === 'memory') {
			return $this->__memory;
		}
		else if($mode === 'resource') {
			return $this->__resource;
		}
		else if($mode === 'cpu') {
			return $this->__cpu;
		}
	}

	//--------------------------------------------
	/**
	 * Get limits leftover
	 *
	 * @access public
	 * @param string mode
	 * @return null|integer
	 */
	//--------------------------------------------
	function free($mode) {
		// Sytems limits donut
		$s_max    = $this->__resource;
		$s_new    = 0;
		$s_paused = 0;
		$s_active = 0;
		$d_max    = $this->__disk;
		$d_paused = 0;
		$d_active = 0;
		$d_new    = 0;
		$m_max    = $this->__memory;
		$m_paused = 0;
		$m_active = 0;
		$m_new    = 0;
		$c_max    = $this->__cpu;
		$c_paused = 0;
		$c_active = 0;
		$c_new    = 0;
		$n_max    = $this->__network;
		$n_paused = 0;
		$n_active = 0;
		$n_new    = 0;

		$requests = $this->cloudrequest->get_all_ids_per_user($this->openqrm->user()->id);
		$cloudrequest = new cloudrequest();
		foreach ($requests as $v) {
			$appliance = null;
			$cloudrequest->get_instance_by_id($v['cr_id']);
			if ((strlen($cloudrequest->appliance_id)) && ($cloudrequest->appliance_id != 0)) {
				$appliance = $this->openqrm->appliance();
				$appliance->get_instance_by_id($cloudrequest->appliance_id);
				if($appliance->state === 'stopped') {
					$s_paused++;
					$d_paused = $d_paused + $cloudrequest->disk_req;
					$m_paused = $m_paused + $cloudrequest->ram_req;
					$c_paused = $c_paused + $cloudrequest->cpu_req;;
					$n_paused = $n_paused + $cloudrequest->network_req;
				}
				if($appliance->state === 'active') {
					$s_active++;
					$d_active = $d_active + $cloudrequest->disk_req;
					$m_active = $m_active + $cloudrequest->ram_req;
					$c_active = $c_active + $cloudrequest->cpu_req;
					$n_active = $n_active + $cloudrequest->network_req;
				}
			}
			if($this->cloudconfig->get_value_by_key('auto_provision') === 'false') {
				if($this->cloudrequest->getstatus($v['cr_id']) === 'new') {
					$s_new++;
					$d_new = $d_new + $cloudrequest->disk_req;
					$m_new = $m_new + $cloudrequest->ram_req;
					$c_new = $c_new + $cloudrequest->cpu_req;
					$n_new = $n_new + $cloudrequest->network_req;
				}
			}
		}

		if($mode === 'disk') {
			return $d_max - ($d_active + $d_paused + $d_new);
		}
		else if($mode === 'network') {
			return $n_max - ($n_active + $n_paused + $n_new);
		}
		else if($mode === 'memory') {
			return $m_max - ($m_active + $m_paused + $m_new);
		}
		else if($mode === 'resource') {
			return $s_max - ($s_active + $s_paused + $s_new);
		}
		else if($mode === 'cpu') {
			return $c_max - ($c_active + $c_paused + $c_new);
		}
	}


}
?>
