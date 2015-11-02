<?php
/**
 * Cloud Users Transactions
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_transaction
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
/**
* cloud-id
* @access public
* @var int
*/
var $cloud_id;

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $path path to dir
	 * @param htmlobject_response $response
	 */
	//--------------------------------------------
	function __construct($openqrm, $response) {
		$this->openqrm = $openqrm;
		$this->response = $response;
		$this->rootdir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
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
		$template = $this->response->html->template("./tpl/cloud-ui.transaction.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($table, 'table');
		$template->add($this->lang['label'], 'label');
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Transactions
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {

		$head['ct_id']['title'] = $this->lang['id'];
		$head['ct_id']['hidden'] = true;
		$head['ct_cr_id']['title'] = $this->lang['request_id'];
		$head['ct_cr_id']['hidden'] = true;
		$head['ct_time']['title'] = $this->lang['date'];
		$head['ct_time']['hidden'] = true;
		$head['ct_ccu_charge']['title'] = $this->lang['charge'];
		$head['ct_ccu_charge']['hidden'] = true;
		$head['ct_ccu_balance']['title'] = $this->lang['balance'];
		$head['ct_ccu_balance']['hidden'] = true;
		$head['ct_reason']['title'] = $this->lang['reason'];
		$head['ct_reason']['hidden'] = true;

		$head['ct_data']['title'] = "&#160;";
		$head['ct_data']['sortable'] = false;
		$head['ct_comment']['title'] = '&#160;';
		$head['ct_comment']['sortable'] = false;

		require_once $this->rootdir."/plugins/cloud/class/cloudtransaction.class.php";
		$cloud_transaction = new cloudtransaction();
		
		$table = $this->response->html->tablebuilder( 'cloud_transaction_table', $this->response->get_array($this->actions_name, 'transaction'));
		$table->css          = 'htmlobject_table';
		$table->limit        = 10;
		$table->id           = 'cloud_transactions';
		$table->head         = $head;
		$table->sort         = 'ct_id';
		$table->order        = 'DESC';
		$table->sort_link    = false;
		$table->form_action  = $this->response->html->thisfile;
		$table->form_method  = 'GET';
		$table->max          = $cloud_transaction->get_count_per_clouduser($this->clouduser->id);
		$table->autosort     = true;
		$table->limit_select = array(
			array("value" => 10, "text" => 10),
			array("value" => 20, "text" => 20),
			array("value" => 30, "text" => 30),
			array("value" => 40, "text" => 40),
			array("value" => 50, "text" => 50),
		);
		$table->init();

		$cloud_array = $cloud_transaction->display_overview_per_clouduser($this->clouduser->id, $table->offset, $table->limit, $table->sort, $table->order);
		$ta = '';
		foreach ($cloud_array as $index => $ct) {
			$cloud_transaction_time = date("d-m-Y H:i", $ct["ct_time"]);

			$data  = '<b>'.$this->lang['id'].':</b> '. $ct["ct_id"].'<br>';
			$data .= '<b>'.$this->lang['request_id'].':</b> '. $ct["ct_cr_id"].'<br>';
			$data .= '<b>'.$this->lang['date'].':</b> '.$cloud_transaction_time.'<br>';
			$data .= '<b>'.$this->lang['charge'].':</b> '.$ct["ct_ccu_charge"].'<br>';
			$data .= '<b>'.$this->lang['balance'].':</b> '.$ct["ct_ccu_balance"].'<br>';
			$data .= '<b>'.$this->lang['reason'].':</b> '.$ct["ct_reason"];

			$ta[] = array(
				'ct_id' => $ct["ct_id"],
				'ct_time' => $ct["ct_time"],
				'ct_cr_id' => $ct["ct_cr_id"],
				'ct_ccu_charge' => "-".$ct["ct_ccu_charge"],
				'ct_ccu_balance' => $ct["ct_ccu_balance"],
				'ct_reason' => $ct["ct_reason"],
				'ct_data' => $data,
				'ct_comment' => $ct["ct_comment"],
			);
		}
		$table->body = $ta;
		return $table;
	}

}
?>
