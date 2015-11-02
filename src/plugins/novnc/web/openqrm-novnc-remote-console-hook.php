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

// error_reporting(E_ALL);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/openqrm-server-config.php";
require_once "$RootDir/class/htmlobjects/htmlobject.class.php";
global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
$event = new event();
global $event;

function openqrm_novnc_remote_console($resource_id) {
	global $event;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_EXEC_PORT;

	$html = new htmlobject($OPENQRM_SERVER_BASE_DIR.'/openqrm/web/base/class/htmlobjects');
	$a = $html->a();
	$a->label = 'noVNC';
	$a->css = 'badge';
	$a->href = '/openqrm/base/index.php?plugin=novnc&controller=novnc&novnc_action=console&resource_id='.$resource_id;
	$a->handler = 'onclick="wait();"';

	return $a;
}



// this functions implements the stop action for the vnc remote console
// deprecated - stop implemented via timeout
function openqrm_novnc_disable_remote_console($vncserver, $vncport, $vm_res_id, $vm_mac, $resource_name) {
	#global $event;
	#global $OPENQRM_SERVER_BASE_DIR;
	#// stop the novnc proxy
	#$novnc_stop_command = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/novnc/bin/openqrm-novnc-manager disable-remoteconsole -n ".$resource_name." -d ".$vm_res_id." -m ".$vm_mac." -i ".$vncserver." -v ".$vncport;
	#$output = shell_exec($novnc_stop_command);
}


?>
