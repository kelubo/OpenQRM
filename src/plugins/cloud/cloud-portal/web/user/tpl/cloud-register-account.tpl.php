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

 to debug add {?}


-->

<h1>{label}</h1>
<div id="register_container">
<form action="{thisfile}" method="POST">
{form}

	{cu_name}
	{cu_forename}
	{cu_lastname}
	{cu_street}
	{cu_city}
	{cu_country}
	{cu_email}
	{cu_phone}
	<br>
	{cu_password}
	{cu_password_repeat}
	<div id="buttons">{submit}&#160;{cancel}</div>

</form>
</div>
