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
<div id="content_container">
<h1>{label}</h1>
<form action="{thisfile}" method="POST">
{form}

		<div id="cloud_account_left_column">
			{cu_email}
			{cu_forename}
			{cu_lastname}
			{cu_street}
			{cu_city}
			{cu_country}
			{cu_phone}
			<br>
			{cu_password}
			{cu_password_repeat}
			<div id="buttons">{submit}</div>
		</div>
		<div id="cloud_account_right_column">
			<h3>{details}</h3><br>
			<p>{user_name} : {user_name_value}</p>
			<p>{user_group} : {user_group_value}</p>
			<p>{cloud_user_ccus} : {cloud_user_ccus_value}</p>
			<p>{cloud_user_lang} : {cloud_user_lang_value}</p>
			{transactions}
		</div>
		<div class="floatbreaker" style="line-height:0px;clear:both;">&#160;</div>

</form>
</div>
