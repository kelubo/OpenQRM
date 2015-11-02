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
		{select}
		<div style="margin: 15px 0px 15px 35px;">{or_manually_add}</div>
		{manual_port}
		{manual_type}
		{manual_service}
		{manual_description}
		<div class="buttons">{submit}&#160;{cancel}</div>
	</form>
</div>
