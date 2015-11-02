<?php
/**
 * Cloud Request Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/



class cloud_request_select
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-requestselect';



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
		$this->rootdir  = $this->openqrm->get('rootdir');
		$this->webdir  = $this->openqrm->get('webdir');
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
		$response = $this->select();
		$template = $this->response->html->template($this->tpldir."/cloud-request-select.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($response->table, 'table');
		$template->add($response->clean, 'clean_up');
		$template->add($this->lang['cloud_request_management'], 'title');
		$template->add($this->lang['cloud_request'], 'cloud_request');
		$template->add($this->lang['cloud_request_details'], 'cloud_request_details');
		$template->add($response->filter, 'filter');
		$template->add($response->form);
		$template->group_elements(array('param_' => 'form'));
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Request Select
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'select');
		$response->form = $form;

		require_once $this->webdir."/plugins/cloud/class/clouduser.class.php";
		$cloud_user = new clouduser();
		require_once $this->webdir."/plugins/cloud/class/cloudusergroup.class.php";
		$cloud_group = new cloudusergroup();
		require_once $this->webdir."/plugins/cloud/class/cloudrequest.class.php";
		$cloud_request = new cloudrequest();
		
		$active_state_icon="/openqrm/base/img/active.png";
		$inactive_state_icon="/openqrm/base/img/idle.png";

		$a = $this->response->html->a();
		$a->label   = $this->lang['cloud_request_clean'];
		$a->css     = 'add';
		$a->handler = 'onclick="wait();"';
		$a->href    = $this->response->get_url($this->actions_name, "clean");
		$clean_up_button  = $a->get_string();


		$head['cr_status']['title'] = '&#160;';
		$head['cr_status']['sortable'] = false;
		$head['cr_id']['title'] = $this->lang['cloud_request_id'];
		$head['cr_id']['hidden'] = true;
		$head['cr_cu_id']['title'] = $this->lang['cloud_request_user'];
		$head['cr_cu_id']['hidden'] = true;
		$head['cr_request_time']['title'] = $this->lang['cloud_request_time'];
		$head['cr_request_time']['hidden'] = true;
		$head['cr_start']['title'] = $this->lang['cloud_request_start_time'];
		$head['cr_start']['hidden'] = true;
		$head['cr_stop']['title'] = $this->lang['cloud_request_stop_time'];
		$head['cr_stop']['hidden'] = true;
		$head['cr_appliance_id']['title'] = $this->lang['cloud_request_app_id'];
		$head['cr_appliance_id']['hidden'] = true;
		$head['info']['title'] = '&#160;';
		$head['info']['sortable'] = false;
		$head['cr_details']['title'] = '&#160;';
		$head['cr_details']['sortable'] = false;

		$table = $response->html->tablebuilder( 'cloud_request_table', $response->get_array($this->actions_name, 'select'));
		$table->css             = 'htmlobject_table';
		$table->id              = 'cloud_requests';
		$table->head            = $head;
		$table->sort            = 'cr_id';
		$table->order           = 'DESC';
		$table->sort_link       = false;
		$table->autosort        = false;
		$table->limit           = 10;
		$table->max             = $cloud_request->get_count();
		$table->identifier      = 'cr_id';
		$table->identifier_name = $this->identifier_name;
		$table->actions         = array('approve', 'cancel', 'delete', 'deny', 'deprovision');
		$table->actions_name    = $this->actions_name;
		$table->init();

		$cloud_request_array = $cloud_request->display_overview(0, 10000, $table->sort, $table->order);
		$ta = '';
		foreach ($cloud_request_array as $index => $cz) {
			$cloud_request->get_instance_by_id($cz['cr_id']);
			if (
				$this->response->html->request()->get('requests_filter') === '' ||
				($this->response->html->request()->get('requests_filter') == $cloud_request->status )
			) {
				$cloud_user->get_instance_by_id($cloud_request->cu_id);
				$cr_status = '<span class="pill">'.$cloud_request->getstatus($cloud_request->id).'</span>';
				// details action
				$a = $this->response->html->a();
				$a->title   = $this->lang['cloud_request_details'];
				$a->label   = $this->lang['cloud_request_details'];
				$a->handler = 'onclick="javascript:cloudopenPopup('.$cloud_request->id.'); return false;"';
				$a->css     = 'edit_nojs';
				$a->href    = $this->response->get_url($this->actions_name, 'details')."&".$this->identifier_name."=".$cloud_request->id;
				$request_details = $a->get_string();

				// server pause action
				$request_pause = '';
				$appliance_state = '';
				if (($cloud_request->status == 3) && ($cloud_request->appliance_id != '')) {

					$appliance = new appliance();
					$appliance->get_instance_by_id($cloud_request->appliance_id);
					if ($appliance->state == 'active') {
						$a = $this->response->html->a();
						$a->title   = $this->lang['cloud_request_pause'];
						$a->label   = $this->lang['cloud_request_pause'];
						$a->css     = 'edit';
						$a->href    = $this->response->get_url($this->actions_name, 'pause')."&".$this->identifier_name."=".$cloud_request->id;
						$request_pause = $a->get_string();
						$appliance_state = $cloud_request->appliance_id.' - <span class="pill">'.$appliance->state.'</span>';
					} else if ($appliance->state == 'stopped') {
						$a = $this->response->html->a();
						$a->title   = $this->lang['cloud_request_unpause'];
						$a->label   = $this->lang['cloud_request_unpause'];
						$a->css     = 'edit';
						$a->href    = $this->response->get_url($this->actions_name, 'unpause')."&".$this->identifier_name."=".$cloud_request->id;
						$request_pause = $a->get_string();
						$appliance_state = $cloud_request->appliance_id.' - <span class="pill">'.$appliance->state.'</span>';
					}
				}
				$request_details = $request_pause.$request_details;

				$info  = '<b>'.$this->lang['cloud_request_id'].'</b>: '.$cloud_request->id.'<br>';
				$info .= '<b>'.$this->lang['cloud_request_user'].'</b>: '.$cloud_user->name.'<br>';
				$info .= '<b>'.$this->lang['cloud_request_time'].'</b>: '.date("Y-m-d H:i:s", $cloud_request->request_time).'<br>';
				$info .= '<b>'.$this->lang['cloud_request_start_time'].'</b>: '.date("Y-m-d H:i:s", $cloud_request->start).'<br>';
				$info .= '<b>'.$this->lang['cloud_request_stop_time'].'</b>: '.date("Y-m-d H:i:s", $cloud_request->stop).'<br>';
				$info .= '<b>'.$this->lang['cloud_request_app_id'].'</b>: '.$appliance_state.'<br>';

				$ta[] = array(
					'cr_status' => $cr_status,
					'cr_id' => $cloud_request->id,
					'cr_cu_id' => $cloud_user->name,
					'cr_request_time' => date("Y-m-d H:i:s", $cloud_request->request_time),
					'cr_start' => date("Y-m-d H:i:s", $cloud_request->start),
					'cr_stop' => date("Y-m-d H:i:s", $cloud_request->stop),
					'info' => $info,
					'cr_appliance_id' => $appliance_state,
					'cr_details' => $request_details,
				);
			}
		}

		// Filter
		$list = $cloud_request->getstates();
		$filter = array();
		$filter[] = array('', '');
		foreach( $list as $l) {
			$filter[] = array( $l[0], $l[1]);
		}
		$select = $this->response->html->select();
		$select->add($filter, array(0,1));
		$select->name = 'requests_filter';
		$select->handler = 'onchange="wait();this.form.submit();return false;"';
		$select->selected = array($this->response->html->request()->get('requests_filter'));
		$box = $this->response->html->box();
		$box->add($select);
		$box->id = 'requests_filter';
		$box->css = 'htmlobject_box';
		$box->label = $this->lang['cloud_request_state_filter'];

		$table->body = $ta;
		$table->max  = count($ta);

		$response->clean = $clean_up_button;
		$response->table = $table;
		$response->filter = $box;
		return $response;
	}

}
?>
