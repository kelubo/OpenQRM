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

{?}

-->
<div id="content_container">
	<h1>{label}</h1>
	<div style="float:left;">
	<form action="{thisfile}" method="POST">
		{form}
		{cpu}
		{memory}
		{disk}
		{comment}
		<div id="buttons">{submit}&#160;{cancel}</div>
	</form>
	</div>
	<div style="float:left;margin:0 0 0 50px;width: 230px;">
		<h3>{label_update_notice}</h3>
		<div>{update_cpu_notice}</div>
		<div>{update_disk_notice}</div>

	</div>

</div>
