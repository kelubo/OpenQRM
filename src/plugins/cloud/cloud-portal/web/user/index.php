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

// add custom-branding header
require_once "custom-branding-header.php";

require_once($_SERVER["DOCUMENT_ROOT"].'/openqrm/base/include/user.inc.php');
require_once('class/cloud.controller.class.php');
$controller = new cloud_controller();
echo $controller->ui()->get_string();

// reserve some space
?>
<br>
<br>
<br>
<br>
