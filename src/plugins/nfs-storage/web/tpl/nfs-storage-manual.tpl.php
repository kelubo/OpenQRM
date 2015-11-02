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
<form action="{thisfile}" method="POST">
	<div id="form" class="manualconfig">
	<p style="width:400px;margin: 0 0 10px 0;">{explanation_1}</p>
	<p style="width:400px;margin: 0 0 20px 0;font-weight: bold;">{explanation_2}</p>
	{form}
	{config}
	<div id="buttons">{submit}&#160;{cancel}</div>
	</div>
</form>
