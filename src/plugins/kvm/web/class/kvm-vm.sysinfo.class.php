<?php
/**
 * KVM Adds/Removes an Image from a Volume
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class kvm_vm_sysinfo
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'kvm_vm_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'kvm_vm_msg';
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'kvm_vm_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'kvm_vm_identifier';
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
		$this->openqrm    = $openqrm;
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
		$response = $this->sysinfo();
		return $response;
	}

	//--------------------------------------------
	/**
	 * Sysinfo
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function sysinfo() {

		$appliance = $this->openqrm->appliance();
		$appliance->get_instance_by_id($this->response->html->request()->get('appliance_id'));
		$resource = $this->openqrm->resource();
		$resource->get_instance_by_id($appliance->resources);

		$filename = $resource->id.'.sysinfo';
		$file = $this->openqrm->get('basedir').'/plugins/kvm/web/kvm-stat/'.$filename;

		$command  = $this->openqrm->get('basedir').'/plugins/kvm/bin/openqrm-kvm-sysinfo';
		$command .= ' -u '.$this->openqrm->admin()->name.' -p '.$this->openqrm->admin()->password;
		$command .= ' --file-name '.$filename;
		#$command .= ' --openqrm-cmd-mode background';
		
		if($this->openqrm->file()->exists($file)) {
			$this->openqrm->file()->remove($file);
		}
		$resource->send_command($resource->ip, $command);
		while (!$this->openqrm->file()->exists($file)) // check if the data file has been modified
		{
		  usleep(10000); // sleep 10ms to unload the CPU
		  clearstatcache();
		}

		$d = $this->response->html->div();
		$d->id = "kvm-sysinfo";
		$d->add($this->openqrm->file()->get_contents($file));

		$this->openqrm->file()->remove($file);

		return $d;

	}

}
?>
