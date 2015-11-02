<?php
/**
 * Cloud Users Requests
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_requests
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-ui';

/**
* user
* @access public
* @var string
*/
var $user;

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
		$table = $this->select();
		$template = $this->response->html->template("./tpl/cloud-ui.requests.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($table, 'table');
		$template->add($this->lang['cloud_ui_requests_title'], 'title');
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Requests
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {

		$head['cr_status']['title'] = $this->lang['cloud_ui_request_status'];
		$head['cr_id']['title'] = $this->lang['cloud_ui_request_id'];
		$head['cr_id']['hidden'] = true;
		$head['cr_appliance_hostname']['title'] = $this->lang['cloud_ui_request_appliance_name'];
		$head['cr_appliance_hostname']['hidden'] = true;

		#$head['cr_start']['title'] = $this->lang['cloud_ui_request_start'];
		#$head['cr_stop']['title'] = $this->lang['cloud_ui_request_stop'];
		#$head['cr_kernel_id']['title'] = $this->lang['cloud_ui_request_os'];
		#$head['cr_image_id']['title'] = $this->lang['cloud_ui_request_template'];
		#$head['cr_resource_type_req']['title'] = $this->lang['cloud_ui_request_system_type'];
		$head['cr_req']['title'] = '&#160;';
		$head['cr_req']['sortable'] = false;
		#$head['cr_appliance_id']['title'] = $this->lang['cloud_ui_request_appliance_id'];
		$head['cr_details']['title'] = $this->lang['cloud_ui_request_details'];
		$head['cr_details']['sortable'] = false;

		$table = $this->response->html->tablebuilder( 'cloud_request_table', $this->response->get_array($this->actions_name, 'requests'));
		$table->css             = 'htmlobject_table';
		$table->border          = 0;
		$table->id              = 'cloud_requests';
		$table->head            = $head;
		$table->sort            = 'cr_id';
		$table->order           = 'DESC';
		$table->limit           = 5;
		$table->sort_link       = false;
		$table->autosort        = false;
		$table->max		        = $this->cloudrequest->get_count_per_user($this->clouduser->id);
		$table->identifier      = 'cr_id';
		$table->identifier_name = $this->identifier_name;
		$table->actions         = array('deprovision');
		$table->actions_name    = $this->actions_name;
	    $table->form_action     = $this->response->html->thisfile;
		$table->limit_select = array(
				array("value" => 5, "text" => 5),
				array("value" => 10, "text" => 10),
				array("value" => 20, "text" => 20),
				array("value" => 30, "text" => 30),
				array("value" => 40, "text" => 40),
				array("value" => 50, "text" => 50),
				);

		// $table->form_action     = $this->response->html->thisfile;
		$table->init();

		$kernel = new kernel();
		$image = new image();
		$virtualization = new virtualization();
		
		$cloud_request_array = $this->cloudrequest->display_overview_per_user($this->clouduser->id, $table->offset, $table->limit, $table->sort, $table->order);
		$ta = '';
		foreach ($cloud_request_array as $index => $cz) {
			$this->cloudrequest->get_instance_by_id($cz['cr_id']);
			$cr_status = $this->cloudrequest->getstatus($this->cloudrequest->id);

			if(isset($this->cloudrequest->kernel_id)) {
				$kernel->get_instance_by_id($this->cloudrequest->kernel_id);
			}
			if(isset($this->cloudrequest->image_id)) {
				$image->get_instance_by_id($this->cloudrequest->image_id);
			}
			if(isset($this->cloudrequest->resource_type_req)) {
				$virtualization->get_instance_by_id($this->cloudrequest->resource_type_req);
			}

#echo '<pre>';
#print_r($this->cloudrequest);
#echo '</pre>';



			// hostname
			$appliance_hostname = '-';
			if (strlen($this->cloudrequest->appliance_hostname)) {
				$appliance_hostname = $this->cloudrequest->appliance_hostname;
			}

			$cr_req  = '<b>'.$this->lang['cloud_ui_request_id'].'</b>: '.$this->cloudrequest->id."<br>";
			$cr_req .= '<b>'.$this->lang['cloud_ui_request_appliance_name'].'</b>: '.$appliance_hostname."<br>";
			$cr_req .= '<b>'.$this->lang['cloud_ui_request_start'].'</b>: '.date("d-m-Y H:i", $this->cloudrequest->start)."<br>";
			$cr_req .= '<b>'.$this->lang['cloud_ui_request_stop'].'</b>: '.date("d-m-Y H:i", $this->cloudrequest->stop)."<br>";
			$cr_req .= '<b>'.$this->lang['cloud_ui_request_cpu'].'</b>: '.$this->cloudrequest->cpu_req."<br>";
			$cr_req .= '<b>'.$this->lang['cloud_ui_request_memory'].'</b>: '.$this->cloudrequest->ram_req." MB<br>";
			$cr_req .= '<b>'.$this->lang['cloud_ui_request_disk'].'</b>: '.$this->cloudrequest->disk_req." MB<br>";
			$cr_req .= '<b>'.$this->lang['cloud_ui_request_network'].'</b>: '.$this->cloudrequest->network_req."<br>";

			// details action
			$a = $this->response->html->a();
			$a->title   = $this->lang['cloud_ui_request_components_details'];
			$a->label   = $this->lang['cloud_ui_request_components_details'];
			$a->handler = 'onclick="javascript:cloudopenPopup('.$this->cloudrequest->id.');"';
			$a->css     = 'edit';
			$a->href    = '#';

			$ta[] = array(
				'cr_status' => $cr_status,
				'cr_id' => $this->cloudrequest->id,
				'cr_appliance_hostname' => $appliance_hostname,
				#'cr_start' => date("d-m-Y H-i", $this->cloudrequest->start),
				#'cr_stop' => date("d-m-Y H-i", $this->cloudrequest->stop),
				#'cr_kernel_id' => $kernel->name,
				#'cr_image_id' => $image->name,
				#'cr_resource_type_req' => $virtualization->name,
				'cr_req' => $cr_req,
				#'cr_appliance_id' => $this->cloudrequest->appliance_id,
				'cr_details' => $a->get_string(),
			);
		}
		$table->body = $ta;
		return $table;
	}


}

?>


