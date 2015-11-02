<?php
/**
 * Cloud Users Private Images
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_images
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
		$this->docrootdir = $_SERVER["DOCUMENT_ROOT"];
		// include classes and prepare ojects
		require_once $this->rootdir."/plugins/cloud/class/cloudimage.class.php";
		$this->cloudimage = new cloudimage();
		require_once $this->rootdir."/plugins/cloud/class/cloudprivateimage.class.php";
		$this->cloudprivateimage = new cloudprivateimage();
		$this->appliance = $this->openqrm->appliance();
		$this->image = $this->openqrm->image();
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
		$template = $this->response->html->template($this->tpldir."/cloud-ui.images.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($table, 'table');
		$template->add($this->lang['label'], 'label');
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Private Images
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function select() {

		$head['state']['title'] = $this->lang['state'];
		$head['co_id']['title'] = $this->lang['id'];
		$head['co_id']['hidden'] = true;
		$head['image_name']['title'] = $this->lang['name'];
		$head['image_name']['hidden'] = true;
		$head['data']['title'] = '&#160;';
		$head['data']['sortable'] = false;
		$head['comment']['title'] ='&#160;';
		$head['comment']['sortable'] = false;
		$head['action']['title'] = '&#160;';
		$head['action']['sortable'] = false;

		$table = $this->response->html->tablebuilder( 'image_table', $this->response->get_array($this->actions_name, 'images'));
		$table->css          = 'htmlobject_table';
		$table->limit        = 10;
		$table->id           = 'cloud_images';
		$table->head         = $head;
		$table->sort         = 'co_id';
		$table->sort_link    = false;
		$table->autosort     = true;
		$table->actions_name = $this->actions_name;
		$table->form_action  = $this->response->html->thisfile;
		$table->form_method  = 'GET';

		$arBody = array();
		$private_image_array =  $this->cloudprivateimage->display_overview_per_user($this->clouduser->id, $table->order);
		$private_image_count = 0;
		foreach ($private_image_array as $index => $private_image_db) {
			$pco_id = $private_image_db["co_id"];
			$pcomment = $private_image_db["co_comment"];
			if (!strlen($pcomment)) {
				$pcomment = '';
			}
			$this->cloudprivateimage->get_instance_by_id($pco_id);
			// get the image name
			$this->image->get_instance_by_id($this->cloudprivateimage->image_id);
			// set the active icon
			$isactive = '<span class="pill paused">paused</span>';
			if ($this->image->isactive == 1) {
				$isactive = '<span class="pill active">active</span>';
			}

			$clone_on_deploy_status = '';
			if ($this->cloudprivateimage->clone_on_deploy == 0) {
				$clone_on_deploy_status = $this->lang['state_off'];
			} else if ($this->cloudprivateimage->clone_on_deploy == 1) {
				$clone_on_deploy_status = $this->lang['state_on'];
			}
			// image actions
			$image_action = '';
			// edit
			$a = $this->response->html->a();
			$a->title   = $this->lang['action_edit'];
			$a->label   = $this->lang['action_edit'];
			$a->handler = "";
			$a->css     = 'edit';
			$a->href    = '/cloud-portal/user/index.php?cloud_ui=image_edit&'.$this->identifier_name.'='.$this->cloudprivateimage->id;
			$image_action .= $a->get_string();

			// remove
			$a = $this->response->html->a();
			$a->title   = $this->lang['action_remove'];
			$a->label   = $this->lang['action_remove'];
			$a->handler = "";
			$a->css     = 'remove';
			$a->href    = '/cloud-portal/user/index.php?cloud_ui=image_remove&'.$this->identifier_name.'='.$this->cloudprivateimage->id;
			$image_action .= $a->get_string();

			$data  = '<b>'.$this->lang['id'].':</b> '.$pco_id.'<br>';
			$data .= '<b>'.$this->lang['name'].':</b> '.$this->image->name.'<br>';
			$data .= '<b>'.$this->lang['clone_on_deploy'].':</b> '.$clone_on_deploy_status;

			$arBody[] = array(
				'co_id' => $pco_id,
				'image_name' => $this->image->name,
				'state' => $isactive,
				'data' => $data,
				'comment' => $pcomment,
				'action' => $image_action,
			);
			$private_image_count++;
		}

		$table->body = $arBody;
		$table->max = $private_image_count;

		return $table;
	}

}
?>
