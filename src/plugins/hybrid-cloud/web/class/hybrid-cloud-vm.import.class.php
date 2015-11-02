<?php
/**
 * Adds/removes an Instance plus AMI in openQRM
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_vm_import
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_vm_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_vm_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_vm_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_vm_tab';
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
var $lang = array();

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
		$this->response   = $response;
		$this->file       = $openqrm->file();
		$this->openqrm    = $openqrm;
		$this->user       = $openqrm->user();
		$this->id         = $this->response->html->request()->get('hybrid_cloud_id');
		$this->response->add('hybrid_cloud_id', $this->id);
		$this->region     = $this->response->html->request()->get('region');
		$this->statfile = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/hybrid-cloud-stat/'.$this->id.'.describe_configuration.log';

		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);
		$this->hc = $hc;
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
		$response = $this->import();
		$this->response->redirect(
			$this->response->get_url($this->actions_name, 'edit', $this->message_param, $response)
		);
	}

	//--------------------------------------------
	/**
	 * Adds/removes resource, image and appliance objects
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function import() {
		$response = '';
		$errors       = array();
		$message      = array();
		$event = new event();
		
		$instance_command = $this->response->html->request()->get('instance_command');
		if( $instance_command !== '' ) {
			switch ($instance_command) {
				case 'add':
					
					// instance_command=add
					// &instance_name='.$name.'
					// &instance_mac='.$mac.'
					// &instance_public_ip='.$public_ip.'
					// &instance_type='.$type.'
					// &instance_keypair='.$keypair.'
					// &instance_region='.$region.'
					// &instance_ami='.$ami;
					$now = $_SERVER['REQUEST_TIME'];
					$openqrm = new openqrm_server();

					$instance_name = $this->response->html->request()->get('instance_name');
					$instance_mac = $this->response->html->request()->get('instance_mac');
					$instance_public_ip = $this->response->html->request()->get('instance_public_ip');
					$instance_type = $this->response->html->request()->get('instance_type');
					$instance_keypair = $this->response->html->request()->get('instance_keypair');
					$instance_region = $this->response->html->request()->get('instance_region');
					$instance_ami = $this->response->html->request()->get('instance_ami');

					// create resource, image and appliance
					$event->log("import", $_SERVER['REQUEST_TIME'], 5, "hybrid-cloud-vm-import", "Importing ".$instance_name." - ".$instance_mac." - ".$instance_public_ip." - ".$instance_type." - ".$instance_keypair." - ".$instance_region." - ".$instance_ami.".", "", "", 0, 0, 0);
					$import_resource = new resource();
					$deployment = new deployment();
					$deployment->get_instance_by_name('ami-deployment');
					$virtualization = new virtualization();
					$virtualization->get_instance_by_type("hybrid-cloud-vm-local");

					// create resource
					$resid = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
					// send command to the openQRM-server
					$openqrm->send_command('openqrm_server_add_resource '.$resid.' '.$instance_mac.' '.$instance_public_ip);
					// add to openQRM database
					$resource_fields["resource_id"] = $resid;
					$resource_fields["resource_ip"] = $instance_public_ip;
					$resource_fields["resource_mac"] = $instance_mac;
					$resource_fields["resource_kernel"] = 'local';
					$resource_fields["resource_kernelid"] = 0;
					$resource_fields["resource_localboot"] = 0;
					$resource_fields["resource_hostname"] = $this->hc->account_type.$resid;
					$resource_fields["resource_vtype"] = $virtualization->id;
					$resource_fields["resource_vhostid"] = 0;
					$import_resource->add($resource_fields);
					$import_resource->get_instance_by_mac($instance_mac);
					// update stats
					#if ($state == 'running') {
						$rfields["resource_state"]='idle';
					#$rfields["resource_lastgood"]=$now;
					#} else {
					#	$rfields["resource_state"]='off';
					#}
					#$import_resource->update_info($import_resource->id, $rfields);
					// set account id in resource capabilities
					$import_resource->set_resource_capabilities("HCACL", $this->id);

					// auto create image object
					$storage = new storage();
					$storage->get_instance_by_name('ami-image-storage');
					$image = new image();
					$image->get_instance_by_name($instance_ami);
					if ((isset($image->id)) && ($image->id > 0)) {
						$image_exists = true;
					} else {
						$image_fields = array();
						$vm_image_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
						$image_fields["image_id"] = $vm_image_id;
						$image_fields['image_name'] = $instance_ami;
						$image_fields['image_type'] = 'ami-deployment';
						$image_fields['image_rootfstype'] = 'local';
						$image_fields['image_isactive'] = 0;
						$image_fields['image_storageid'] = $storage->id;
						$image_fields['image_comment'] = "Image Object for AMI $instance_ami";
						$image_fields['image_rootdevice'] = $instance_ami;
						$image->add($image_fields);
						# update image object
						$image->get_instance_by_id($vm_image_id);
						// update resource with image infos
						$rfields["resource_id"] = $resid;
						$rfields["resource_image"] = $image->name;
						$rfields["resource_imageid"] = $image->id;
						$import_resource->update_info($import_resource->id, $rfields);
						$import_resource->get_instance_by_mac($instance_mac);
					}
					// create the appliance
					$appliance = new appliance();
					$appliance->get_instance_by_name($instance_name);
					if ((isset($appliance->id)) && ($appliance->id > 0)) {
						$appliance_exists = true;
					} else {
						$new_appliance_id = (int)str_replace(".", "", str_pad(microtime(true), 15, "0"));
						$afields['appliance_id'] = $new_appliance_id;
						$afields['appliance_name'] = $this->hc->account_type.$resid;
						$afields['appliance_resources'] = $resid;
						$afields['appliance_kernelid'] = '1';
						$afields['appliance_imageid'] = $image->id;
						$afields["appliance_virtual"]= 0;
						$afields["appliance_virtualization"]=$virtualization->id;
						$afields['appliance_wizard'] = '';
						$afields['appliance_comment'] = 'Cloud VM Appliance for Resource '.$resid;
						$appliance->add($afields);
						// update state/start+stoptime
						$aufields['appliance_stoptime']=$now;
						$aufields['appliance_starttime']='';
						$aufields['appliance_state']='stopped';
						$appliance->update($new_appliance_id, $aufields);
					}

					$hc_authentication = '';
					if (($this->hc->account_type == 'aws') || ($this->hc->account_type == 'euca')) {
						$hc_authentication .= ' -O '.$this->hc->access_key;
						$hc_authentication .= ' -W '.$this->hc->secret_key;
						$hc_authentication .= ' -ir '.$this->response->html->request()->get('region');
						$hc_authentication .= ' -iz '.$instance_region;
					}
					if ($this->hc->account_type == 'lc-openstack') {
						$hc_authentication .= ' -u '.$this->hc->username;
						$hc_authentication .= ' -p '.$this->hc->password;
						$hc_authentication .= ' -q '.$this->hc->host;
						$hc_authentication .= ' -x '.$this->hc->port;
						$hc_authentication .= ' -g '.$this->hc->tenant;
						$hc_authentication .= ' -e '.$this->hc->endpoint;
					}

					$command  = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/bin/openqrm-hybrid-cloud-vm create';
					$command .= ' -i '.$this->hc->id;
					$command .= ' -n '.$this->hc->account_name;
					$command .= ' -t '.$this->hc->account_type;
					$command .= $hc_authentication;
					$command .= ' -in '.$this->hc->account_type.$resid;
					$command .= ' -im '.$instance_mac;
					$command .= ' -a '.$instance_ami;
					$command .= ' -it '.$instance_type;
					$command .= ' -ik '.$instance_keypair;
					if ($this->hc->account_type == 'aws') {
						$command .= ' -subnet '.$this->response->html->request()->get('instance_subnet');
					} else {
						# TODO
						$command .= ' -ig '.$form->get_request('group');
					}
					$command .= ' --openqrm-ui-user '.$this->user->name;
					$command .= ' --openqrm-cmd-mode background';
					$openqrm->send_command($command, NULL, true);

				$message[] = sprintf($this->lang['msg_imported'], $instance_name);
				break;

			}
			
			if(count($errors) === 0) {
				$response = join('<br>', $message);
			} else {
				$msg = array_merge($errors, $message);
				$response = join('<br>', $msg);
			}
		} else {
			$response = '';
		}
		return $response;
	}


}
?>
