<?php
/*
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


// error_reporting(E_ALL);
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
$CloudDir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/';
require_once $RootDir."/include/user.inc.php";
require_once $RootDir."/class/image.class.php";
require_once $RootDir."/class/image_authentication.class.php";
require_once $RootDir."/class/resource.class.php";
require_once $RootDir."/class/virtualization.class.php";
require_once $RootDir."/class/appliance.class.php";
require_once $RootDir."/class/storage.class.php";
require_once $RootDir."/class/deployment.class.php";
require_once $RootDir."/class/openqrm_server.class.php";
require_once $RootDir."/class/event.class.php";
# class for the hybrid cloud accounts
require_once $RootDir."/plugins/hybrid-cloud/class/hybrid-cloud.class.php";

global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;



// this function is going to be called by the monitor-hook in the resource-monitor

function openqrm_hybrid_cloud_monitor() {
	global $event;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;
	global $openqrm_server;
	global $BaseDir;
	global $RootDir;
	$now=$_SERVER['REQUEST_TIME'];


	// $event->log("hybrid_cloud_monitor", $_SERVER['REQUEST_TIME'], 2, "hybrid-cloud-monitor-hook", "Hybrid Cloud monitor hook DISABLED for now!!!", "", "", 0, 0, 0);
	// return;

	// $event->log("hybrid_cloud_monitor", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-monitor-hook", "Hybrid Cloud monitor hook", "", "", 0, 0, 0);
	$last_stats = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/web/hybrid-cloud-stat/last_statistics";
	if (file_exists($last_stats)) {
		$last_host_stats = file_get_contents($last_stats);
		$secs_after_last_host_stat = $now - $last_host_stats;
		if ($secs_after_last_host_stat > 35) {
			file_put_contents($last_stats, $now);
			$server = new openqrm_server();

			$hc = new hybrid_cloud();
			$hc_account_arr = $hc->get_ids();
			foreach ($hc_account_arr as $id) {
				$hc_account_id = $id['hybrid_cloud_id'];
				$hc->get_instance_by_id($hc_account_id);
				$hc_authentication = '';
				// for every ec2/euca cloud account monitor every configured region
				if (($hc->account_type == 'aws') || ($hc->account_type == 'euca')) {
					$hybrid_cloud_conf = $OPENQRM_SERVER_BASE_DIR.'/openqrm/plugins/hybrid-cloud/etc/openqrm-plugin-hybrid-cloud.conf';
					$hybrid_cloud_conf_arr = openqrm_parse_conf($hybrid_cloud_conf);
					$region_arr = explode(",", $hybrid_cloud_conf_arr['OPENQRM_PLUGIN_HYBRID_CLOUD_REGIONS']);
					$hc_authentication .= ' -O '.$hc->access_key;
					$hc_authentication .= ' -W '.$hc->secret_key;
				}
				// one region for openstack
				if ($hc->account_type == 'lc-openstack') {
					$region_arr = array("OpenStack");
					$hc_authentication .= ' -u '.$hc->username;
					$hc_authentication .= ' -p '.$hc->password;
					$hc_authentication .= ' -q '.$hc->host;
					$hc_authentication .= ' -x '.$hc->port;
					$hc_authentication .= ' -g '.$hc->tenant;
					$hc_authentication .= ' -e '.$hc->endpoint;
				}

				foreach ($region_arr as $region) {
					$event->log("hybrid_cloud_monitor", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-monitor-hook", "Hybrid Cloud monitor - checking Cloud statistics for Account ".$hc->account_name." - ".$region, "", "", 0, 0, 0);
					$statfile = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/hybrid-cloud/web/hybrid-cloud-stat/".$hc_account_id.".instances_statistics.log";
					$command  = $OPENQRM_SERVER_BASE_DIR.'/openqrm/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm describe --statistics true';
					$command .= ' -i '.$hc->id;
					$command .= ' -n '.$hc->account_name;
					$command .= ' -t '.$hc->account_type;
					$command .= ' -ir '.$region;
					$command .= $hc_authentication;
					$command .= ' --openqrm-cmd-mode background';
					if (file_exists($statfile))	{
						unlink($statfile);
					}
					$server->send_command($command, NULL, true);
					while (!file_exists($statfile))	{
					  usleep(10000);
					  clearstatcache();
					}

					$content = file_get_contents($statfile);
					$content = explode("\n", $content);

					$b = array();
					foreach ($content as $k => $v) {
						if($v !== '') {
							$tmp		= explode('@', $v);
							$name		= $tmp[1];
							$ami		= $tmp[2];
							$public_hostname	= $tmp[3];
							$private_hostname	= $tmp[4];
							$state		= $tmp[5];
							$keypair	= $tmp[6];
							$unknown1	= $tmp[7];
							$unknown2	= $tmp[8];
							$type		= $tmp[9];
							$date		= $tmp[10];
							$region		= $tmp[11];
							$unknown4	= $tmp[12];
							$unknown5	= $tmp[13];
							$unknown6	= $tmp[14];
							$monitoring	= $tmp[15];
							$public_ip	= $hc->format_ip_address($tmp[16]);
							$private_ip	= $hc->format_ip_address($tmp[17]);
							$unknown7	= $tmp[18];
							$unknown8	= $tmp[19];
							$store		= $tmp[20];
							$unknown9	= $tmp[21];
							$unknown10	= $tmp[22];
							$unknown11	= $tmp[23];
							$unknown12	= $tmp[24];
							$hvm		= $tmp[25];
							$virt_type	= $tmp[26];
							$mac	= '';

							// check for idle instances
							$resource = new resource();
							if ($state == 'idle') {
								$mac = $tmp[30];
								$resource->get_instance_by_mac($mac);
								$resource_fields["resource_state"]='active';
								$resource_fields["resource_lastgood"]=$now;
								$resource_fields["resource_cpunumber"]=$hc->translate_resource_components('cpu', $type);
								$resource_fields["resource_nics"]=$hc->translate_resource_components('net', $type);
								$resource_fields["resource_memtotal"]=$hc->translate_resource_components('mem', $type);
								$resource_fields["resource_memused"]="0";
								$resource_fields["resource_load"]="0";
								// restore mgmt ip
								$resource_fields["resource_ip"]=$resource->network;
								$resource->update_info($resource->id, $resource_fields);
							} else if ($state == 'running') {
								// check if existing, if not auto-create resource, image and appliance
								if ($resource->exists_by_name($name)) {
									// update stats
									$resource->get_instance_id_by_hostname($name);
									$resource->get_instance_by_id($resource->id);
									$resource_fields["resource_state"]='active';
									$resource_fields["resource_lastgood"]=$now;
									$resource_fields["resource_cpunumber"]=$hc->translate_resource_components('cpu', $type);
									$resource_fields["resource_nics"]=$hc->translate_resource_components('net', $type);
									$resource_fields["resource_memtotal"]=$hc->translate_resource_components('mem', $type);
									$resource_fields["resource_memused"]=$resource_fields["resource_memtotal"];
									$resource_fields["resource_load"]="1";
									if ((strlen($public_ip)) && ($resource->ip != $public_ip)) {
										// set public ip, update early and run nagios hook
										$resource_fields["resource_ip"]=$public_ip;
										$resource->update_info($resource->id, $resource_fields);
										// nagios enabled and started ?
										if (file_exists($RootDir."/plugins/nagios3/.running")) {
											$virtualization = new virtualization();
											$virtualization->get_instance_by_type("hybrid-cloud-vm-local");
											$hc_appliance = new appliance();
											$hc_appliance->get_instance_by_virtualization_and_resource($virtualization->id, $resource->id);
											if (strlen($hc_appliance->name)) {
												// special nagios classes
												require_once $RootDir."/plugins/nagios3/class/nagios3_service.class.php";
												require_once $RootDir."/plugins/nagios3/class/nagios3_host.class.php";
												// get the nagios service checks
												$nagios_host = new nagios3_host();
												$nagios_host->get_instance_by_appliance_id($hc_appliance->id);
												$active_nagios_services = explode(',', $nagios_host->appliance_services);
												$nagios_service_list = '';
												foreach($active_nagios_services as $service_id) {
													$nagios_service = new nagios3_service();
													$nagios_service->get_instance_by_id($service_id);
													$nagios_service_list = $nagios_service_list.",".$nagios_service->port;;
												}
												$nagios_service_list = substr($nagios_service_list, 1);
												if (strlen($nagios_service_list)) {
													// appliance has nagios service checks configured
													$nagios_appliance_stop_cmd = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/nagios3/bin/openqrm-nagios-manager remove_host -n ".$hc_appliance->name." --openqrm-cmd-mode background";
													$server->send_command($nagios_appliance_stop_cmd, NULL, true);
													sleep(2);
													$nagios_appliance_start_cmd = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/nagios3/bin/openqrm-nagios-manager add -n ".$hc_appliance->name." -i ".$public_ip." -p ".$nagios_service_list." --openqrm-cmd-mode background";
													$server->send_command($nagios_appliance_start_cmd, NULL, true);
												}

											}

										}
										// nagios finished



									}
									$resource->update_info($resource->id, $resource_fields);
								} else {
									// through error for now
									$event->log("hybrid_cloud_monitor", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-monitor-hook", "New Resource detected with name ".$name."!", "", "", 0, 0, 0);
								}
							}
						}
					}
					unlink($statfile);
				}
			}
		}
	} else {
		file_put_contents($last_stats, $now);
	}
}


// for debugging
// openqrm_hybrid_cloud_monitor();







?>

