<?php
/**
 * Cloud Request Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_request_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'cloud_request_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = "cloud_request_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'cloud_request_tab';
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
var $lang = array(
	'cloud_request_name' => 'Name',
	'cloud_request_user_id' => 'User ID',
	'cloud_request_id' => 'ID',
	'cloud_request_user' => 'User',
	'cloud_requests' => 'Cloud Requests',
	'cloud_request' => 'Cloud Request',
	'cloud_request_status' => 'Status',
	'cloud_request_management' => 'Cloud Request Management',
	'cloud_request_ccunits' => 'CCUs',
	'cloud_request_approve' => 'Approve',
	'cloud_request_deny' => 'Deny',
	'cloud_request_cancel' => 'Cancel',
	'cloud_request_deprovision' => 'Deprovision',
	'cloud_request_details' => 'Details',
	'cloud_request_delete' => 'delete',
	'cloud_request_time' => 'From',
	'cloud_request_start_time' => 'Start',
	'cloud_request_stop_time' => 'Stop',
	'cloud_request_app_id' => 'App-ID',
	'cloud_request_kernel' => 'Kernel',
	'cloud_request_image' => 'Image',
	'cloud_request_cpu_req' => 'CPU',
	'cloud_request_ram_req' => 'Memory',
	'cloud_request_disk_req' => 'Disk',
	'cloud_request_network_req' => 'Network',
	'cloud_request_resource_req' => 'Virtualization',
	'cloud_request_ha_req' => 'Highavailability',
	'cloud_request_applications' => 'Applications',
	'cloud_request_ipconfig' => 'IP Configuration',
	'cloud_request_enabled' => 'Enabled',
	'cloud_request_disabled' => 'Disabled',
	'cloud_request_confirm_delete' => 'Really delete the following Cloud Requests?',
	'cloud_request_deleted' => 'Deleted Cloud Request',
	'cloud_request_delete' => 'Delete',
	'cloud_request_not_removing' => 'Not removing Cloud Request',
	'cloud_request_confirm_approve' => 'Really approve the following Cloud Requests?',
	'cloud_request_approved' => 'Approved Cloud Request',
	'cloud_request_approve' => 'Approve',
	'cloud_request_not_approving' => 'Not approving Cloud Request',
	'cloud_request_clean' => 'Clean up',
	'cloud_request_confirm_clean' => 'Clean up Cloud Requests?',
	'cloud_request_clean_failed' => 'Could not automatically repair a Database inconstency! Please contact your openQRM Support!',
	'cloud_request_clean_noop' => 'No errors found on Database constency check',
	'cloud_request_clean_noop_failed' => 'Found errors on Database constency check',
	'cloud_request_pause' => 'Pause',
	'cloud_request_confirm_pause' => 'Really pause the following Cloud Server?',
	'cloud_request_paused' => 'Paused Cloud Server',
	'cloud_request_pause' => 'Pause',
	'cloud_request_not_pausing' => 'Not pausing Cloud Server',
	'cloud_request_unpause' => 'un-Pause',
	'cloud_request_confirm_unpause' => 'Really unpause the following Cloud Server?',
	'cloud_request_unpaused' => 'Unpaused Cloud Server',
	'cloud_request_unpause' => 'Unpause',
	'cloud_request_not_unpausing' => 'Not unpausing Cloud Server',
	'cloud_request_confirm_cancel' => 'Really cancel the following Cloud Requests?',
	'cloud_request_canceled' => 'Canceling Cloud Request',
	'cloud_request_cancel' => 'Cancel',
	'cloud_request_not_canceling' => 'Not canceling Cloud Request',
	'cloud_request_confirm_deny' => 'Really deny the following Cloud Requests?',
	'cloud_request_denied' => 'Denying Cloud Request',
	'cloud_request_deny' => 'Deny',
	'cloud_request_not_denying' => 'Not denying Cloud Request',
	'cloud_request_confirm_deprovision' => 'Really deprovision the following Cloud Requests?',
	'cloud_request_deprovisioned' => 'Deprovision Cloud Request',
	'cloud_request_deprovision' => 'Deprovision',
	'cloud_request_not_deprovisioning' => 'Not deprovisioning Cloud Request',
	'cloud_request_state_filter' => 'Filter by state',
);

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
		$this->openqrm  = $openqrm;
		$this->user     = $this->openqrm->user();
		$this->rootdir  = $this->openqrm->get('rootdir');
		$this->webdir  = $this->openqrm->get('webdir');
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
		$this->tpldir   = $this->webdir.'/plugins/cloud/tpl';
		$this->identifier_name = "cloud_request_id";
		require_once $this->webdir."/class/htmlobjects/htmlobject.class.php";
		$this->html = new htmlobject($this->webdir."/class/htmlobjects/");
		$this->html->lang = $this->user->translate($this->html->lang, $this->webdir."/plugins/cloud/lang", 'htmlobjects.ini');

	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @param string $action
	 * @return htmlobject_tabmenu
	 */
	//--------------------------------------------
	function action($action = null) {
		$this->action = '';
		$ar = $this->response->html->request()->get($this->actions_name);
		if($ar !== '') {
			if(is_array($ar)) {
				$this->action = key($ar);
			} else {
				$this->action = $ar;
			}
		} 
		else if(isset($action)) {
			$this->action = $action;
		}
		if($this->response->cancel()) {
			$this->action = "select";
		}
		$content = array();
		switch( $this->action ) {
			case '':
			case 'select':
				$content[] = $this->select(true);
			break;
			case 'approve':
				$content[] = $this->select(false);
				$content[] = $this->approve(true);
			break;
			case 'delete':
				$content[] = $this->select(false);
				$content[] = $this->delete(true);
			break;
			case 'cancel':
				$content[] = $this->select(false);
				$content[] = $this->cancel(true);
			break;
			case 'deny':
				$content[] = $this->select(false);
				$content[] = $this->deny(true);
			break;
			case 'deprovision':
				$content[] = $this->select(false);
				$content[] = $this->deprovision(true);
			break;
			case 'details':
				$content[] = $this->select(false);
				$content[] = $this->details(true);
			break;
			case 'pause':
				$content[] = $this->select(false);
				$content[] = $this->pause(true);
			break;
			case 'unpause':
				$content[] = $this->select(false);
				$content[] = $this->unpause(true);
			break;
			case 'clean':
				$content[] = $this->select(false);
				$content[] = $this->clean(true);
			break;
		}
		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		return $tab;
	}

	//--------------------------------------------
	/**
	 * Cloud Request Select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function select( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.select.class.php');
			$controller = new cloud_request_select($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->lang;
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_requests'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'select' );
		$content['onclick'] = false;
		if($this->action === 'select'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud Request Approve
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function approve( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.approve.class.php');
			$controller = new cloud_request_approve($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_request_approve'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'approve' );
		$content['onclick'] = false;
		if($this->action === 'approve'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Cloud Request Delete
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function delete( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.delete.class.php');
			$controller = new cloud_request_delete($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();

//			$this->response->html->help($data);

		}
		$this->lang     = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
		$content['label']   = $this->lang['cloud_request_delete'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'delete' );
		$content['onclick'] = false;
		if($this->action === 'delete'){
			$content['active']  = true;
		}
		return $content;
	}

	//--------------------------------------------
	/**
	 * Cloud Request Cancel
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function cancel( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.cancel.class.php');
			$controller = new cloud_request_cancel($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_request_cancel'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'cancel' );
		$content['onclick'] = false;
		if($this->action === 'cancel'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * deny
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function deny( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.deny.class.php');
			$controller = new cloud_request_deny($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_request_deny'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'deny' );
		$content['onclick'] = false;
		if($this->action === 'deny'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud Request Deprovision
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function deprovision( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.deprovision.class.php');
			$controller = new cloud_request_deprovision($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_request_deprovision'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'deprovision' );
		$content['onclick'] = false;
		if($this->action === 'deprovision'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud Request Details
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function details( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.details.class.php');
			$controller = new cloud_request_details($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_request_details'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'details' );
		$content['onclick'] = false;
		if($this->action === 'details'){
			$content['active']  = true;
		}
		return $content;
	}


	//--------------------------------------------
	/**
	 * Cloud Request Server Pause
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function pause( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.pause.class.php');
			$controller = new cloud_request_pause($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_request_pause'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'pause' );
		$content['onclick'] = false;
		if($this->action === 'pause'){
			$content['active']  = true;
		}
		return $content;
	}




	//--------------------------------------------
	/**
	 * Cloud Request Server un-Pause
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function unpause( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.unpause.class.php');
			$controller = new cloud_request_unpause($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_request_unpause'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'unpause' );
		$content['onclick'] = false;
		if($this->action === 'unpause'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Cloud Request Server Clean
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function clean( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->webdir.'/plugins/cloud/class/cloud-request.clean.class.php');
			$controller = new cloud_request_clean($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
			$controller->identifier_name = $this->identifier_name;
			$controller->message_param = $this->message_param;
			$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
			$data = $controller->action();
		}
		$content['label']   = $this->lang['cloud_request_clean'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'clean' );
		$content['onclick'] = false;
		if($this->action === 'clean'){
			$content['active']  = true;
		}
		return $content;
	}





	//--------------------------------------------
	/**
	 * Cloud Request API
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function api() {
		require_once($this->webdir.'/plugins/cloud/class/cloud-request.api.class.php');
		$controller = new cloud_request_api($this);
		$controller->actions_name  = $this->actions_name;
		$controller->tpldir        = $this->webdir.'/plugins/cloud/tpl';
		$controller->identifier_name = $this->identifier_name;
		$controller->message_param = $this->message_param;
		$controller->lang          = $this->user->translate($this->lang, $this->webdir."/plugins/cloud/lang", 'cloud-request.ini');
		$controller->action();
	}

	
	
}
?>
