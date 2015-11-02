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
$CloudDir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/';
require_once $RootDir."/class/event.class.php";
require_once $RootDir."/include/user.inc.php";
require_once $RootDir."/include/openqrm-database-functions.php";
require_once $RootDir."/class/openqrm_server.class.php";
require_once $RootDir."/class/storage.class.php";
require_once $RootDir."/class/plugin.class.php";
require_once $RootDir."/class/image.class.php";
require_once $RootDir."/class/kernel.class.php";
require_once $RootDir."/class/resource.class.php";
require_once $RootDir."/class/virtualization.class.php";
require_once $RootDir."/class/appliance.class.php";
require_once $RootDir."/class/storage.class.php";
require_once $RootDir."/class/deployment.class.php";

// special cloud classes
require_once $RootDir."/plugins/cloud/class/clouduser.class.php";
require_once $RootDir."/plugins/cloud/class/clouduserslimits.class.php";
require_once $RootDir."/plugins/cloud/class/cloudrequest.class.php";
require_once $RootDir."/plugins/cloud/class/cloudconfig.class.php";
require_once $RootDir."/plugins/cloud/class/cloudmailer.class.php";
require_once $RootDir."/plugins/cloud/class/cloudvm.class.php";
require_once $RootDir."/plugins/cloud/class/cloudimage.class.php";
require_once $RootDir."/plugins/cloud/class/cloudappliance.class.php";
require_once $RootDir."/plugins/cloud/class/cloudapplication.class.php";

// our parent class
require_once "$RootDir/plugins/cloud/class/cloudsoap.class.php";

global $CLOUD_REQUEST_TABLE;
global $event;


class cloudsoapuser extends cloudsoap {




}


?>