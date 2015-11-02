<?php
/**
 * Cloud failed-Transaction Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_transaction_failed_select
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-transaction-failed-select';



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
		require_once $this->webdir."/plugins/cloud/class/cloudtransaction.class.php";
		$this->cloudtransaction = new cloudtransaction();
		require_once $this->webdir."/plugins/cloud/class/cloudtransactionfailed.class.php";
		$this->cloudtransactionfailed = new cloudtransactionfailed();
		require_once $this->webdir."/plugins/cloud/class/clouduser.class.php";
		$this->cloud_user = new clouduser();

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
		$template = $this->response->html->template($this->tpldir."/cloud-transaction-failed-select.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($table, 'table');
		$template->add($this->lang['cloud_transaction_failed_management'], 'title');
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud failed-Transaction Select
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {
		// $this->response->html->debug();

		$head['tf_id']['title'] = ' ';
		$head['tf_id']['sortable'] = false;
		$head['tf_ct_id']['title'] = $this->lang['cloud_transaction_id'];
		$head['ct_time']['title'] = $this->lang['cloud_transaction_time'];
		$head['ct_time']['sortable'] = false;
		$head['ct_cr_id']['title'] = $this->lang['cloud_transaction_cr_id'];
		$head['ct_cr_id']['sortable'] = false;
		$head['ct_cu_id']['title'] = $this->lang['cloud_transaction_cu_name'];
		$head['ct_cu_id']['sortable'] = false;
		$head['ct_ccu_charge']['title'] = $this->lang['cloud_transaction_ccu_charge'];
		$head['ct_ccu_charge']['sortable'] = false;
		$head['ct_ccu_balance']['title'] = $this->lang['cloud_transaction_ccu_balance'];
		$head['ct_ccu_balance']['sortable'] = false;
		$head['ct_reason']['title'] = $this->lang['cloud_transaction_reason'];
		$head['ct_reason']['sortable'] = false;
		$head['ct_comment']['title'] = $this->lang['cloud_transaction_comment'];
		$head['ct_comment']['sortable'] = false;

		$table = $this->response->html->tablebuilder( 'cloud_transaction_table', $this->response->get_array($this->actions_name, 'select'));
		$table->css             = 'htmlobject_table';
		$table->border          = 0;
		$table->id              = 'cloud_transaction_failed_table';
		$table->head            = $head;
		$table->sort            = 'tf_id';
		$table->autosort        = false;
		$table->order			= 'DESC';
		$table->max             = $this->cloudtransactionfailed->get_count();
		$table->identifier      = 'tf_id';
		$table->identifier_name = 'cloud_transaction_failed_id';
		$table->actions_name    = $this->actions_name;
		$table->form_action		= $this->response->html->thisfile;
		$table->actions         = array('sync');
		$table->sort_link       = false;
		$table->limit_select = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 40, "text" => 40),
			array("value" => 50, "text" => 50),
		);
		$table->init();
		// db select
		$cloud_transaction_failed_array = $this->cloudtransactionfailed->display_overview($table->offset, $table->limit, $table->sort, $table->order);
		$ta = '';
		foreach ($cloud_transaction_failed_array as $index => $ct) {
			$this->cloudtransaction->get_instance_by_id($ct["tf_ct_id"]);
			$cloud_zone_transaction_failed_time = date("d-m-Y H-i", $this->cloudtransaction->time);
			$this->cloud_user->get_instance_by_id($this->cloudtransaction->cu_id);
			$ta[] = array(
				'tf_id' => $ct["tf_id"],
				'tf_ct_id' => $this->cloudtransaction->id,
				'ct_time' => $cloud_zone_transaction_failed_time,
				'ct_cr_id' => $this->cloudtransaction->cr_id,
				'ct_cu_id' => $this->cloud_user->name,
				'ct_ccu_charge' => "-".$this->cloudtransaction->ccu_charge,
				'ct_ccu_balance' => $this->cloudtransaction->ccu_balance,
				'ct_reason' => $this->cloudtransaction->reason,
				'ct_comment' => $this->cloudtransaction->comment,
			);
		}
		$table->body = $ta;
		return $table;
	}


}
?>
