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
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once $RootDir."/include/user.inc.php";
require_once $RootDir."/class/openqrm.class.php";
require_once $RootDir."/plugins/hybrid-cloud/class/hybrid-cloud.controller.class.php";
require_once $RootDir."/class/htmlobjects/htmlobject.class.php";
$html = new htmlobject($RootDir."/class/htmlobjects/");
$response = $html->response();

require_once($RootDir.'/class/file.handler.class.php');
$file = new file_handler();

require_once($RootDir.'/class/user.class.php');
$user = new user($_SERVER['PHP_AUTH_USER']);
$user->set_user();

$openqrm = new openqrm($file, $user);
$openqrm->init();

$controller = new hybrid_cloud_controller($openqrm, $response);
$controller->api();
?>
