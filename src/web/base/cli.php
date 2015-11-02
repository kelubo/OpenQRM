<?php
/*
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/
$_SERVER["DOCUMENT_ROOT"] = '/var/www/';
$_SERVER['PHP_AUTH_USER'] = 'openqrm';

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once($RootDir.'/class/openqrm.controller.class.php');
if (!file_exists('unconfigured')) {
	require_once($RootDir.'/include/user.inc.php');
}

#$controller = new openqrm_controller();
#$controller->cli($argv);
?>
