<?php
/**
 *  Hybrid-cloud Buckets
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_s3_edit
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_s3_action';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_s3_identifier';
/**
* message param
* @access public
* @var string
*/
var $message_param = "hybrid_cloud_s3_msg";
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'hybrid_cloud_s3_tab';
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
		$this->id         = $this->response->html->request()->get('hybrid_cloud_id');
		$this->response->add('hybrid_cloud_id', $this->id);
		$this->region     = $response->html->request()->get('region');
		# hybrid-cloud account
		$this->id         = $this->response->html->request()->get('hybrid_cloud_id');
		$this->response->add('hybrid_cloud_id', $this->id);
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);
		# s3 object
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/S3.php');
		$s3 = new S3($hc->access_key, $hc->secret_key);
		$this->s3 = $s3;

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
		$data = $this->edit();
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-s3-edit.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($data);
		$t->add($this->response->get_array());
		$t->add(sprintf($this->lang['label'], $data['name']), 'label');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * List S3 Buckets
	 *
	 * @access public
	 * @return array
	 */
	//--------------------------------------------
	function edit() {

		$h = array();
		$h['type']['title'] = '&#160;';
		$h['type']['sortable'] = false;
		$h['buckets']['title'] = $this->lang['table_name'];
		$h['count']['title'] = $this->lang['table_file_count'];
		$h['select']['title'] = '&#160;';
		$h['select']['sortable'] = false;
		$h['remove']['title'] = '&#160;';
		$h['remove']['sortable'] = false;

		$a = $this->response->html->a();
		$a->label   = $this->lang['action_add_s3_bucket'];
		$a->css     = 'add';
		$a->handler = 'onclick="wait();"';
		$a->href    = $this->response->get_url($this->actions_name, "add");
		$d['add_s3_bucket']   = $a->get_string();

		$buckets = $this->s3->listBuckets(true);

		$b = array();
		foreach ($buckets['buckets'] as $k => $v) {
			if($v !== '') {
				$name		= $v['name'];
				$remove_bucket = '';
				$file_count = count($this->s3->getBucket($name));
				if ($file_count == 0) {
					$a = $this->response->html->a();
					$a->label   = $this->lang['action_remove_s3_bucket'];
					$a->css     = 'remove';
					$a->handler = 'onclick="wait();"';
					$a->href    = $this->response->get_url($this->actions_name, "remove").'&bucket_name='.$name;
					$remove_bucket = $a->get_string();
				}

				$a = $this->response->html->a();
				$a->label   = $this->lang['action_select_s3_bucket'];
				$a->css     = 'edit';
				$a->handler = 'onclick="wait();"';
				$a->href    = $this->response->get_url($this->actions_name, "files").'&bucket_name='.$name;
				$select_bucket = $a->get_string();

				$type_icon = '';

				$b[] = array(
					'type' => $type_icon,
					'buckets' => $name,
					'count' => $file_count,
					'select' => $select_bucket,
					'remove' => $remove_bucket,
				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'edit');
		$table = $this->response->html->tablebuilder('hybridcloud_s3_edit', $params);
		$table->form_action = $this->response->html->thisfile;
		$table->offset = 0;
		$table->sort = 'name';
		$table->limit = 10;
		$table->order = 'ASC';
		$table->id = 'Tabelle';
		$table->css = 'htmlobject_table';
		$table->border = 1;
		$table->cellspacing = 0;
		$table->cellpadding = 3;
		$table->autosort = true;
		$table->sort_link = false;
		$table->max = count($b);
		$table->head = $h;
		$table->body = $b;

		// handle account name
		require_once($this->openqrm->get('basedir').'/plugins/hybrid-cloud/web/class/hybrid-cloud.class.php');
		$hc = new hybrid_cloud();
		$hc->get_instance_by_id($this->id);

		$d['name']  = $hc->account_name;
		$d['form']  = $this->response->get_form($this->actions_name, 'edit', false)->get_elements();
		$d['table'] = $table;

		return $d;
	}

}
?>
