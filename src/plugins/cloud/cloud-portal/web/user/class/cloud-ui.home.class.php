<?php
/**
 * Cloud Users Home
 *
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/


class cloud_ui_home
{

var $identifier_name;
var $lang;
var $actions_name = 'cloud_ui';

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
	 * @param openqrm $openqrm
	 */
	//--------------------------------------------
	function __construct($openqrm, $response) {
		$this->openqrm = $openqrm;
		$this->response = $response;
		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/clouduserslimits.class.php";
		$this->clouduserlimits = new clouduserlimits();
		$this->clouduserlimits->get_instance_by_cu_id($this->openqrm->user()->id);

		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/cloudappliance.class.php";
		$this->cloudappliance = new cloudappliance();
		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/cloudrequest.class.php";
		$this->cloudrequest = new cloudrequest();
		require_once $this->openqrm->get('basedir')."/plugins/cloud/web/class/cloudconfig.class.php";
		$this->cloudconfig = new cloudconfig();

		require_once "cloud.limits.class.php";
		$this->cloud_limits = new cloud_limits($this->openqrm, $this->cloudconfig, $this->clouduserlimits, $this->cloudrequest);
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @param enum $type [file|folder]
	 * @return htmlobject_template
	 */
	//--------------------------------------------
	function action() {
		$t = $this->response->html->template("./tpl/cloud-ui.home.tpl.php");
		$t = $this->home($t);
		$t->add($this->response->html->thisfile, "thisfile");
		return $t;
	}

	//--------------------------------------------
	/**
	 * Cloud Users Home
	 *
	 * @access public
	 * @return htmlobject_response
	 */
	//--------------------------------------------
	function home($t) {

		// Limits
		$t->add($this->lang['home']['label_limits'], "label_limits");
		$t->add($this->lang['home']['limit_resource'], "limit_resource");
		$t->add($this->cloud_limits->max('resource'), "resource_limit_value");
		$t->add($this->lang['home']['limit_memory'], "limit_memory");
		$t->add(round(($this->cloud_limits->max('memory') / 1000), 2, PHP_ROUND_HALF_DOWN).' GB', "memory_limit_value");
		$t->add($this->lang['home']['limit_disk'], "limit_disk");
		$t->add(round(($this->cloud_limits->max('disk') / 1000), 2, PHP_ROUND_HALF_DOWN).' GB', "disk_limit_value");
		$t->add($this->lang['home']['limit_cpu'], "limit_cpu");
		$t->add($this->cloud_limits->max('cpu'), "cpu_limit_value");
		$t->add($this->lang['home']['limit_network'], "limit_network");
		$t->add($this->cloud_limits->max('network'), "network_limit_value");

		// js translation
		$t->add($this->lang['home']['limit_resource'], 'lang_systems');
		$t->add($this->lang['home']['limit_disk'], 'lang_disk');
		$t->add($this->lang['home']['limit_memory'], 'lang_memory');
		$t->add($this->lang['home']['limit_cpu'], 'lang_cpu');
		$t->add($this->lang['home']['limit_network'], 'lang_network');

		return $t;
	}

}
?>
