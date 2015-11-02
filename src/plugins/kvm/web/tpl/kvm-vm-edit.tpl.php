<!--
/*
    openQRM Enterprise developed by openQRM Enterprise GmbH.

    All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

    This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
    The latest version of this license can be found here: src/doc/LICENSE.txt

    By using this software, you acknowledge having read this license and agree to be bound thereby.

                http://openqrm-enterprise.com

    Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/
-->

<h2>{label}</h2>

<div id="kvm_edit">
	<div class="row">
		<div class="span3">
			<div><b>{lang_id}</b>: {id}</div>
			<div><b>{lang_name}</b>: {name}</div>
			<div><b>{lang_resource}</b>: {resource}</div>
			<div><b>{lang_state}</b>: {state}</div>
		</div>
		<div id="addbuttons">
			{add_local_vm}<br>
			{add_network_vm}
		</div>
	</div>
	<div style="clear:both;" class="floatbreaker">&#160;</div>
	{table}
</div>
