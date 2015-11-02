<?php
/**
 * image-shelf-about Controller
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
 */


class image_shelf_about_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'image_shelf_about_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'image_shelf_about_msg';
/**
* id for tabs
* @access public
* @var string
*/
var $prefix_tab = 'image_shelf_about_tab';
/**
* identifier name
* @access public
* @var string
*/
var $identifier_name = 'image_shelf_about_identifier';
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
	'documentation' => array (
		'tab' => 'About image-shelf',
		'label' => 'About image-shelf',
		'introduction_title' => 'Introduction',
		'introduction_content' => 'The image-shelf plugin provides an easy way to populate new network-deployment Images from operating-system templates.
			The operating-system templates are simply tar.gz files containing a Linux root-filesystem (with an empty /proc and /sys dir).
			The image-shelf plugin comes with a pre-configured Image-shelf sponsored by <a href="http://www.openqrm-enterprise.com/" target="_BLANK">openQRM Enterprise</a>.
			Custom Image-shelfs can be added using various different protocols (local/FTP/HTTP/HTTPS/NFS). The Image-shelf content is configured by a configuration file "image-shelf.conf" located in the same directory as the templates.
			(here an example self-explaining <a href="/openqrm/base/plugins/image-shelf/image-shelf.conf" target="_BLANK">image-shelf.conf</a>)
			<br><br>
			Please notice that the Image-Shelfs templates can be used to populate Images from type "lvm-nfs-deployment" ("lvm-storage" plugin) and "nfs-deployment" ("nfs-storage" plugin).
			From those NFS-based storage types Images can be easily transferred to e.g. Iscsi- or Aoe-deployment Images via the INSTALL_FROM deployment parameters.
			<br><br>
			<b>How to use :</b><br><ul>
			<li>Enable the &quot;nfs-storage&quot; or &quot;lvm-storage&quot; plugin</li>
			<li>Create a Storage-server from the type &quot;NFS-Storage&quot; or &quot;Lvm Storage Server (Nfs)&quot;<br>(You can use the openQRM-server itself as resource or a existing system in your network integrated with the "local-server" plugin)</li>
			<li>Create an new Volume (Image) on the NFS-Storage server via the &quot;nfs-storage&quot; or &quot;lvm-storage&quot; plugin</li>
			<li>Now Click on the Image-shelf</li>
			<li>Select an Image-Shelf from the list</li>
			<li>Select an Server-Template from the list</li>
			<li>Select the just created (empty) NFS-Image</li>
			<li>Check the Event-list for the progress of the Image creation</li>
			</ul>',

		'requirements_title' => 'Requirements',
		'requirements_list' => 'none',

		'tested_title' => 'Tested with',
		'tested_content' => 'This plugin is tested with Debian, Ubuntu and CentOS Linux distributions.',

		'provides_title' => 'Provides',
		'provides_list' => 'A mechanism to automatic populate (empty) volumes (Images) from ready-made server templaets',
			
		'type_title' => 'Plugin Type',
		'type_content' => 'Deployment',

		'deployment_title' => 'Deployment Type',
		'deployment_content' => 'Network-Deployment',

		'documentation_title' => 'Documentation',
		'network_deploymet' => 'Network-Deployment',
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
		$this->lang     = $this->user->translate($this->lang, $this->rootdir."/plugins/image-shelf/lang", 'image-shelf-about.ini');
		$this->tpldir   = $this->rootdir.'/plugins/image-shelf/tpl';
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
			$this->action = $ar;
		}
		else if(isset($action)) {
			$this->action = $action;
		}
		if($this->response->cancel()) {
			$this->action = "documentation";
		}
		$content = array();
		switch( $this->action ) {
			case '':
			case 'documentation':
				$content[] = $this->documentation(true);
			break;
		}
		$tab = $this->response->html->tabmenu($this->prefix_tab);
		$tab->message_param = $this->message_param;
		$tab->css = 'htmlobject_tabs';
		$tab->add($content);
		return $tab;
	}


	//--------------------------------------------
	/**
	 * About image-shelf
	 *
	 * @access public
	 * @param bool $hidden
	 * @return array
	 */
	//--------------------------------------------
	function documentation( $hidden = true ) {
		$data = '';
		if( $hidden === true ) {
			require_once($this->rootdir.'/plugins/image-shelf/class/image-shelf-about.documentation.class.php');
			$controller = new image_shelf_about_documentation($this->openqrm, $this->response);
			$controller->actions_name    = $this->actions_name;
			$controller->tpldir          = $this->tpldir;
			$controller->message_param   = $this->message_param;
			$controller->lang            = $this->lang['documentation'];
			$data = $controller->action();
		}
		$content['label']   = $this->lang['documentation']['tab'];
		$content['value']   = $data;
		$content['target']  = $this->response->html->thisfile;
		$content['request'] = $this->response->get_array($this->actions_name, 'documentation' );
		$content['onclick'] = false;
		if($this->action === 'documentation'){
			$content['active']  = true;
		}
		return $content;
	}


}
?>
