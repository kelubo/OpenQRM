<?php
/**
 * Cloud Users Profiles
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_profiles
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
		// include classes and prepare ojects
		require_once $this->rootdir."/plugins/cloud/class/cloudprofile.class.php";
		$this->cloudprofile	= new cloudprofile();
		require_once $this->rootdir."/plugins/cloud/class/cloudicon.class.php";
		$this->cloudicon	= new cloudicon();
		$this->cloud_object_icon_size=48;
		
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
		$table = $this->profiles();
		$template = $this->response->html->template("./tpl/cloud-ui.profiles.tpl.php");
		$template->add($this->response->html->thisfile, "thisfile");
		$template->add($table, 'table');
		$template->add($this->lang['label_profiles'], 'label');
		return $template;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Profiles
	 *
	 * @access protected
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function profiles() {
		
		$head['pr_name']['title'] = $this->lang['name'];
		$head['pr_name']['hidden'] = true;

		$head['pr_image']['title'] = $this->lang['image'];
		$head['pr_image']['hidden'] = true;

		$head['pr_type']['title'] = $this->lang['type'];
		$head['pr_type']['hidden'] = true;

		$head['pr_description']['title'] = $this->lang['label_profiles'];
		$head['pr_description']['sortable'] = false;
		$head['action']['title'] = '&#160;';
		$head['action']['sortable'] = false;
		
		$table = $this->response->html->tablebuilder( 'cloud_profile_table', $this->response->get_array($this->actions_name, 'profiles'));
		
		$ta = array();
		$cloudprofile_array = $this->cloudprofile->display_overview_per_user($this->clouduser->id, $table->order);
		foreach ($cloudprofile_array as $index => $cloudprofile_db) {
			$pr_id = $cloudprofile_db["pr_id"];
			$pr_name = $cloudprofile_db["pr_name"];
			$pr_description = $cloudprofile_db["pr_description"];
			if (!strlen($pr_description)) {
				$pr_description = '-';
			}

			$profile_action = '';

			$a = $this->response->html->a();
			$a->title   = $this->lang['action_enable_profile'];
			$a->label   = '&#160;';
			$a->handler = "";
			$a->css     = 'enable';
			$a->href    = '/cloud-portal/user/index.php?cloud_ui=create&profile='.$pr_id;
			$profile_action .= $a->get_string();
			
			$a = $this->response->html->a();
			$a->title   = $this->lang['action_remove_profile'];
			$a->label   = '&#160;';
			$a->handler = "";
			$a->css     = 'remove';
			$a->href    = '/cloud-portal/user/index.php?cloud_ui=profile_remove&object_type=1&'.$this->identifier_name.'='.$pr_id;
			$profile_action .= $a->get_string();

			$blacklist = array(
					'pr_id',
					'pr_cu_id',
					'pr_appliance_id',
					'pr_status',
					'pr_request_time',
					'pr_start',
					'pr_stop',
					'pr_lastbill',
					'pr_deployment_type_req',
					'pr_description',
					'pr_shared_req',
					'pr_resource_quantity',
					'pr_ip_mgmt'
				);
			$data = '<div>';
			foreach($cloudprofile_db as $key => $value) {
				if(!in_array($key, $blacklist)) {
					$k = str_replace('pr_','',$key);
					$k = str_replace('_id','',$k);
					$k = str_replace('_req','',$k);
					$k = str_replace('appliance_','',$k);
					$k = str_replace('resource_','',$k);
					switch($k) {
						case 'disk':
							if(intval($value) >= 1000) {
								$value = $value/1000 .' GB';
							} else {
								$value = $value .' MB';
							}
						break;
						case 'cpu':
							if(intval($value) === 0) {
								$value = 'Auto';
							}
						break;
						case 'ram':
							$value = intval($value);
							if($value === 0) {
								$value = 'Auto';
							}
							elseif(intval($value) >= 1000) {
								$value = $value/1000 .' GB';
							} else {
								$value = $value .' MB';
							}
						break;
						case 'image':
							$image = $this->openqrm->image();
							$image->get_instance_by_id($value);
							$value = $image->name;
							if($value === '') {
								$value = '<span class="error">not found</span>';
							}
						break;
						case 'kernel':
							$kernel = $this->openqrm->kernel();
							$kernel->get_instance_by_id($value);
							$value = $kernel->name;
							if($value === '') {
								$value = '<span class="error">not found</span>';
							}
						break;
						case 'type':
							$vt = $this->openqrm->virtualization();
							$vt->get_instance_by_id($value);
							$value = $vt->name;
							if($value === '') {
								$value = '<span class="error">not found</span>';
							}
						break;
						case 'puppet_groups':
							$value = str_replace(',', ', ', $value);
						break;
					}
					$label = $k;
					if(array_key_exists($k, $this->lang)) {
						$label = $this->lang[$k];
					}
					$data .= '<b>'.$label.'</b> '.$value.'<br>';
				}
			}
			$data .= '</div>';

			$ta[] = array(
				'pr_name' => $cloudprofile_db["pr_name"],
				'pr_image' => $cloudprofile_db["pr_image_id"],
				'pr_type' => $cloudprofile_db["pr_resource_type_req"],
				'pr_description' => $data,
				'action' => $profile_action,
			);
		}
		$table->id = 'cloud_profiles';
		$table->css = 'htmlobject_table';
		$table->sort = 'pr_name';
		$table->order = 'ASC';
		$table->limit = 10;
		$table->form_action = $this->response->html->thisfile;
		$table->form_method = 'GET';
		$table->head = $head;
		$table->body = $ta;
		$table->autosort = true;
		$table->max = $this->cloudprofile->get_count_per_user($this->clouduser->id);
		$table->body = $ta;

		return $table;
	}

}
?>
