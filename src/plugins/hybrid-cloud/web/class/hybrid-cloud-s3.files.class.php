<?php
/**
 *  Hybrid-cloud S3 Files in Bucket
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_s3_files
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
		# bucket
		$this->bucket_name = $this->response->html->request()->get('bucket_name');
		$this->response->add('bucket_name', $this->bucket_name);
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
		$data = $this->files();
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-s3-files.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($data);
		$t->add($this->response->get_array());
		$t->add(sprintf($this->lang['label'], $this->bucket_name), 'label');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * List files in S3 Bucket
	 *
	 * @access public
	 * @return array
	 */
	//--------------------------------------------
	function files() {

		$h = array();
		$h['files']['title'] = $this->lang['table_files'];
		$h['time']['title'] = $this->lang['table_time'];
		$h['size']['title'] = $this->lang['table_size'];
		$h['url']['title'] = $this->lang['table_url'];
		$h['remove']['title'] = '&#160;';
		$h['remove']['sortable'] = false;

		$a = $this->response->html->a();
		$a->label   = $this->lang['action_add_s3_file'];
		$a->css     = 'add';
		$a->handler = 'onclick="wait();"';
		$a->href    = $this->response->get_url($this->actions_name, "upload");
		$d['add_s3_upload']   = $a->get_string();

		$files = $this->s3->getBucket($this->bucket_name);

		$b = array();
		foreach ($files as $v) {
			if($v !== '') {
				$name		= $v['name'];
				$time		= $v['time'];
				$size		= $v['size'];
				// $hash		= $v['hash'];
				if ($size == 0) {
					$size = '0';
				}
				$time = date(DATE_RFC822, $time);

				$url = 'https://s3.amazonaws.com/'.$this->bucket_name.'/'.$name;
				$a = $this->response->html->a();
				$a->label   = $url;
				$a->css     = '';
				$a->target = '_BLANK';
				$a->href    = $url;
				$file_url = $a->get_string();
				
				$file_formatted = $this->response->html->customtag('code');
				$file_formatted->add($file_url);
				
				$a = $this->response->html->a();
				$a->label   = $this->lang['action_remove_s3_file'];
				$a->css     = 'remove';
				$a->handler = 'onclick="wait();"';
				$a->href    = $this->response->get_url($this->actions_name, "delete").'&file_name='.$name;
				$remove_file = $a->get_string();

				$b[] = array(
					'files' => $name,
					'time' => $time,
					'size' => $size,
					'url' => $file_formatted,
					'remove' => $remove_file,
				);
			}
		}

		$params = $this->response->get_array($this->actions_name, 'files');
		$table = $this->response->html->tablebuilder('hybridcloud_s3_files', $params);
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
		$d['form']  = $this->response->get_form($this->actions_name, 'import', false)->get_elements();
		$d['table'] = $table;

		return $d;
	}

}
?>
