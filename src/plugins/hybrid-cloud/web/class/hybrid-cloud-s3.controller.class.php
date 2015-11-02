<?php
/**
 * hybrid_cloud S3 Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_s3_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'hybrid_cloud_s3_action';
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
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'hybrid_cloud_s3_identifier';
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
	'select' => array(
		'tab' => 'Hybrid-Cloud',
	),
	'edit' => array(
		'tab' => 'S3 Bucket List',
		'label' => 'S3 Buckets for account  %s',
		'table_name' => 'Bucket Name',
		'table_file_count' => 'Files',
		'action_add_s3_bucket' => 'Add Bucket',
		'action_remove_s3_bucket' => 'Remove Bucket',
		'action_select_s3_bucket' => 'Files in Bucket',
		'error_name' => 'Name may contain %s only',
		'msg_select_account' => 'Please select a Cloud Account!',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'remove' => array (
		'tab' => 'Remove S3 Bucket',
		'label' => 'Remove S3 Bucket',
		'msg_removed' => 'Removed S3 Bucket %s',
		'please_wait' => 'Removing S3 Bucket. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'add' => array (
		'tab' => 'Create S3 Bucket',
		'label' => 'Create S3 Bucket',
		'form_name' => 'Bucket Name',
		'error_name' => 'Name may contain %s only',
		'msg_added' => 'Created S3 Bucket %s',
		'please_wait' => 'Creating S3 Bucket. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'files' => array (
		'tab' => 'Files in S3 Bucket',
		'label' => 'Files in S3 Bucket %s',
		'table_name' => 'Bucket Name',
		'table_files' => 'Files in Bucket',
		'table_time' => 'Created',
		'table_size' => 'Filesize',
		'table_hash' => 'Hash',
		'table_url' => 'Url',
		'action_add_s3_file' => 'Upload File',
		'action_remove_s3_file' => 'Remove File',
		'error_name' => 'Name may contain %s only',
		'msg_select_account' => 'Please select a Cloud Account!',
		'please_wait' => 'Loading. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'upload' => array (
		'tab' => 'Upload File to Bucket',
		'label' => 'Upload a File to Bucket %s',
		'form_file' => 'Local File',
		'form_permission' => 'File Permission',
		'msg_added' => 'Uploaded %s to Bucket %s',
		'please_wait' => 'Uploading File. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),
	'delete' => array (
		'tab' => 'Delete S3 File',
		'label' => 'Delete S3 File %s from Bucket %s',
		'msg_removed' => 'Deleted S3 File %s',
		'please_wait' => 'Deleted S3 File. Please wait ..',
		'canceled' => 'Operation canceled. Please wait ..',
	),

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
		$this->rootdir  = $this->openqrm->get('webdir');
		$this->response = $response;
		$this->file     = $this->openqrm->file();
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/hybrid-cloud/lang", 'hybrid-cloud-s3.ini');
		$this->tpldir   = $this->rootdir.'/plugins/hybrid-cloud/tpl';
		$this->response->add('hybrid_cloud_id', $this->response->html->request()->get('hybrid_cloud_id'));
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
			if($this->action === 'add' || $this->action === 'remove') {
				$this->action = 'edit';
			} else {
				$this->action = 'files';
			}
		}
		if($this->action === '') {
			$this->action = 'edit';
		}
		// handle response
		if($this->response->html->request()->get('hybrid_cloud_id') == '') {
			$this->response->redirect('/openqrm/base/index.php?plugin=hybrid-cloud&hybrid_cloud_msg='.$this->lang['edit']['msg_select_account']);
		}
		// make sure region is set before any action
		$region_select = $this->__region_select().'<div class="floatbreaker">&#160;</div>';

		$content = array();
		// handle backtab
		$r = $this->response->get_array('hybrid_cloud_action', 'select' );
		$r['controller'] = 'hybrid-cloud';
		$content[0]['label']   = $this->lang['select']['tab'];
		$content[0]['value']   = '';
		$content[0]['target']  = $this->response->html->thisfile;
		$content[0]['request'] = $r;
		$content[0]['onclick'] = false;

		switch( $this->action ) {
			case '':
			default:
			case 'edit':
				$content[] = $this->edit(true);
			break;
			case 'add':
				$content[] = $this->edit(false);
				$content[] = $this->add(true);
			break;
			case 'remove':
				$content[] = $this->edit(false);
				$content[] = $this->remove(true);
			break;
			case 'files':
				$content[] = $this->edit(false);
				$content[] = $this->files(true);
			break;
			case 'upload':
				$content[] = $this->edit(false);
				$content[] = $this->upload(true);
			break;
			case 'delete':
				$content[] = $this->edit(false);
				$content[] = $this->delete(true);
			break;

		}
		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		$tab->custom_tab = $region_select;
		return $tab;
	}


	//--------------------------------------------
	/**
	 * API
	 *
	 * @access public
	 */
	//--------------------------------------------
	function api() {
		require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud.api.class.php');
		$controller = new hybrid_cloud_api($this);
		$controller->action();
	}

	//--------------------------------------------
	/**
	 * List S3 Buckets
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function edit( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-s3.edit.class.php');
			$controller = new hybrid_cloud_s3_edit($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['edit'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['edit']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'edit' );
		$content['onclick'] = false;
		if($this->action === 'edit'){
			$content['active']  = true;
		}
		return $content;
	}




	//--------------------------------------------
	/**
	 * Create S3 Bucket
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function add( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-s3.add.class.php');
			$controller = new hybrid_cloud_s3_add($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['add'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['add']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'add' );
		$content['onclick'] = false;
		if($this->action === 'add'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Remove S3 Bucket
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function remove( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-s3.remove.class.php');
			$controller = new hybrid_cloud_s3_remove($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['remove'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['remove']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'remove' );
		$content['onclick'] = false;
		if($this->action === 'remove'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * List S3 Files in a Bucket
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function files( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-s3.files.class.php');
			$controller = new hybrid_cloud_s3_files($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['files'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['files']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'files' );
		$content['onclick'] = false;
		if($this->action === 'files'){
			$content['active']  = true;
		}
		return $content;
	}



	//--------------------------------------------
	/**
	 * Upload File to Bucket
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function upload( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-s3.upload.class.php');
			$controller = new hybrid_cloud_s3_upload($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['upload'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['upload']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'upload' );
		$content['onclick'] = false;
		if($this->action === 'upload'){
			$content['active']  = true;
		}
		return $content;
	}




	//--------------------------------------------
	/**
	 * Delete S3 Bucket file
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function delete( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/hybrid-cloud/class/hybrid-cloud-s3.delete.class.php');
			$controller = new hybrid_cloud_s3_delete($this->openqrm, $this->response);
			$controller->actions_name  = $this->actions_name;
			$controller->tpldir        = $this->tpldir;
			$controller->prefix_tab    = $this->prefix_tab;
			$controller->lang          = $this->lang['delete'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['delete']['tab'];
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
	 * Region select
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function __region_select() {
		$response = $this->response;
		$form = $response->get_form($this->actions_name, $this->action);

		$hybrid_cloud_conf = $this->openqrm->get('basedir').'/plugins/hybrid-cloud/etc/openqrm-plugin-hybrid-cloud.conf';
		$hybrid_cloud_conf_arr = openqrm_parse_conf($hybrid_cloud_conf);
		$region_arr = explode(",", $hybrid_cloud_conf_arr['OPENQRM_PLUGIN_HYBRID_CLOUD_REGIONS']);
		$regions = array();
		foreach ($region_arr as $region) {
			$region = trim($region);
			$regions[] = array($region);
		}

		$region = $response->html->request()->get('region');
		if($region === '' && count($regions) > 0) {
			$region = $regions[0][0];
			$_REQUEST['region'] = $region;
		}
		$this->response->add('region', $region);	

		$d['region']['label']                        = '';
		$d['region']['object']['type']               = 'htmlobject_select';
		$d['region']['object']['attrib']['id']       = 'region';
		$d['region']['object']['attrib']['name']     = 'region';
		$d['region']['object']['attrib']['css']      = 'region';
		$d['region']['object']['attrib']['handler']  = 'onchange="form.submit(); return false;"';
		$d['region']['object']['attrib']['index']    = array(0,0);
		$d['region']['object']['attrib']['options']  = $regions;
		$d['region']['object']['attrib']['selected'] = array($region);

		$form->add($d);
		
		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->style = 'display:none;';
		$form->add($submit, 'cancel');
		
		return $form->get_string();
	}

}
?>
