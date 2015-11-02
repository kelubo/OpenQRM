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
//-->

<h2>{label}</h2>

<div id="form">
	<form action="{thisfile}" method="GET">
	{form}
	<fieldset>
		<legend>{lang_hardware}</legend>
		<div style="float:left;">
			{name}
			{ami}
			{instance_type}
			{availability_zone}
			{subnet}
			{keypair}
			{group}
			{custom_script}
		</div>
		<div style="float:left; margin: 0 0 0 15px; width:240px;">
			{add_image}
			<div style="margin: 0 0 0 10px;">{lang_notice}</div>
		</div>
		<div class="floatbreaker" style="line-height:0px;height:0px;clear:both;">&nbsp;</div>
	</fieldset>
	<div id="buttons">{submit}&#160;{cancel}</div>
	</form>
</div>
