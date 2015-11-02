<?php
/**
 * Cloud Transaction Select
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_transaction_select
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud-transactionselect';



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
		$template = $this->response->html->template($this->tpldir."/cloud-transaction-select.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($table, 'table');
		$template->add($this->lang['cloud_transaction_management'], 'title');
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Transaction Select
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {
		// $this->response->html->debug();

		$head['ct_id']['title'] = $this->lang['cloud_transaction_id'];
		$head['ct_time']['title'] = $this->lang['cloud_transaction_time'];
		$head['ct_cr_id']['title'] = $this->lang['cloud_transaction_cr_id'];
		$head['ct_cu_id']['title'] = $this->lang['cloud_transaction_cu_name'];
		$head['ct_ccu_charge']['title'] = $this->lang['cloud_transaction_ccu_charge'];
		$head['ct_ccu_balance']['title'] = $this->lang['cloud_transaction_ccu_balance'];
		$head['ct_reason']['title'] = $this->lang['cloud_transaction_reason'];
		$head['ct_comment']['title'] = $this->lang['cloud_transaction_comment'];

		$table = $this->response->html->tablebuilder( 'cloud_transaction_table', $this->response->get_array($this->actions_name, 'select'));
		$table->css         = 'htmlobject_table';
		$table->border      = 0;
		$table->limit       = 10;
		$table->id          = 'cloud_transaction_table';
		$table->head        = $head;
		$table->sort        = 'ct_id';
		$table->autosort    = false;
		$table->order		= 'DESC';
		$table->max         = $this->cloudtransaction->get_count();
		$table->form_action = $this->response->html->thisfile;
		$table->sort_link   = false;
		$table->limit_select = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 40, "text" => 40),
			array("value" => 50, "text" => 50),
		);
		$table->init();

		// db select
		$cloud_transaction_array = $this->cloudtransaction->display_overview($table->offset, $table->limit, $table->sort, $table->order);
		$ta = '';
		foreach ($cloud_transaction_array as $index => $ct) {
			$cloud_zone_transaction_time = date("d-m-Y H-i", $ct["ct_time"]);
			$this->cloud_user->get_instance_by_id($ct["ct_cu_id"]);


			$ta[] = array(
				'ct_id' => $ct["ct_id"],
				'ct_time' => $cloud_zone_transaction_time,
				'ct_cr_id' => $ct["ct_cr_id"],
				'ct_cu_id' => $this->cloud_user->name,
				'ct_ccu_charge' => "-".$ct["ct_ccu_charge"],
				'ct_ccu_balance' => $ct["ct_ccu_balance"],
				'ct_reason' => $ct["ct_reason"],
				'ct_comment' => $ct["ct_comment"],
			);
		}
		$table->body = $ta;
		return $table;
	}




}

?>


