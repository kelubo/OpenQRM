<?php
/**
 * Hybrid-cloud S3 upload File to Bucket
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */

class hybrid_cloud_s3_upload
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
		$this->user       = $openqrm->user();
		$this->id         = $this->response->html->request()->get('hybrid_cloud_id');
		$this->response->add('hybrid_cloud_id', $this->id);
		$this->region     = $this->response->html->request()->get('region');
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
		$response = $this->upload();
		if(isset($response->msg)) {
			$this->response->redirect(
				$this->response->get_url($this->actions_name, 'files', $this->message_param, $response->msg)
			);
		}
		if(isset($response->error)) {
			$_REQUEST[$this->message_param] = $response->error;
		}
		$t = $this->response->html->template($this->tpldir.'/hybrid-cloud-s3-upload.tpl.php');
		$t->add($this->response->html->thisfile, "thisfile");
		$t->add($response->form);
		$t->add(sprintf($this->lang['label'], $this->bucket_name), 'label');
		$t->add($this->actions_name, 'actions_name');
		$t->add($this->openqrm->get('baseurl'), 'baseurl');
		$t->group_elements(array('param_' => 'form'));
		return $t;
	}

	//--------------------------------------------
	/**
	 * Add
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function upload() {
		$response = $this->get_response();
		$form     = $response->form;
		$form->display_errors = true;

		$file_permission = $response->html->request()->get('permission');

		switch ($file_permission) {
			case 'private':
				$s3_permission = S3::ACL_PRIVATE;
				break;
			case 'public-read':
				$s3_permission = S3::ACL_PUBLIC_READ;
				break;
			case 'public-read-write':
				$s3_permission = ACL_PUBLIC_READ_WRITE;
				break;
			case 'authenticated-read':
				$s3_permission = ACL_AUTHENTICATED_READ;
				break;
			default:
				$s3_permission = S3::ACL_PRIVATE;
				break;
		}

		if(!$form->get_errors() && $this->response->submit()) {
			$errors = array();
			require_once($this->openqrm->get('webdir').'/class/file.handler.class.php');
			require_once($this->openqrm->get('webdir').'/class/file.upload.class.php');
			$file = new file_handler();
			$upload = new file_upload($file);
			$error = $upload->upload('upload', $this->openqrm->get('webdir').'/tmp');
			if($error !== '') {
				$response->error = 'Error uploading '.$error['msg'].' - '.$this->openqrm->get('webdir').'/tmp';
			} else {
				$uploaded_file = $this->openqrm->get('basedir')."/web/base/tmp/".$_FILES['upload']['name'];
				$upload_name = basename($uploaded_file);
				$this->s3->putObject($this->s3->inputFile($uploaded_file, false), $this->bucket_name, $upload_name, $s3_permission);
				unlink($uploaded_file);
				$response->msg = 'uploaded '.$uploaded_file;
			}

		}
		return $response;
	}

	//--------------------------------------------
	/**
	 * Get Response
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function get_response() {

		$response = $this->response;
		$form = $response->get_form($this->actions_name, 'upload');

		$submit = $form->get_elements('submit');
		$submit->handler = 'onclick="wait();"';
		$form->add($submit, 'submit');

		$submit = $form->get_elements('cancel');
		$submit->handler = 'onclick="cancel();"';
		$form->add($submit, 'cancel');

		$permission_select_arr = '';
		$permission_select_arr[] = array("private", "private");
		$permission_select_arr[] = array("public-read", "public-read");
		$permission_select_arr[] = array("public-read-write", "public-read-write");
		$permission_select_arr[] = array("authenticated-read", "authenticated-read");


		$d['upload']['label']                       = $this->lang['form_file'];
		$d['upload']['object']['type']           = 'input';
		$d['upload']['object']['attrib']['type'] = 'file';
		$d['upload']['object']['attrib']['name'] = 'upload';
		$d['upload']['object']['attrib']['size'] = 30;

		$d['permission']['label']                       = $this->lang['form_permission'];
		$d['permission']['required']                    = true;
		$d['permission']['object']['type']              = 'htmlobject_select';
		$d['permission']['object']['attrib']['name']    = 'permission';
		$d['permission']['object']['attrib']['index']   = array(0,1);
		$d['permission']['object']['attrib']['options'] = $permission_select_arr;


		$form->add($d);
		$response->form = $form;
		return $response;
	}

}
?>
