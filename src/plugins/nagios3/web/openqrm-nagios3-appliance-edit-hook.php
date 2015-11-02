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

function get_nagios3_appliance_edit($appliance_id, $openqrm, $response) {
	$p_appliance = new appliance();
	$p_appliance->get_instance_by_id($appliance_id);
	$p_resource = new resource();
	$p_resource->get_instance_by_id($p_appliance->resources);

	if ($p_resource->id !== '') {
		$a = $response->html->a();
		$a->label = '<img title="Service monitoring" alt="Service monitoring" height="24" width="24" src="'.$openqrm->get('baseurl').'/plugins/nagios3/img/plugin.png" border=0>';
		$a->href = $openqrm->get('baseurl').'/index.php?base=appliance&appliance_action=load_edit&aplugin=nagios3&appliance_id='.$appliance_id.'&nagios3_action=edit';
		return $a;
	}
}
?>
