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

<form action="{thisfile}" enctype="multipart/form-data" method="POST">
{form}
<div class="row">
	<div class="span4">
		{upload}
		{permission}
		<div id="buttons">{submit}&#160;{cancel}</div>
	</div>
</div>
</form>
