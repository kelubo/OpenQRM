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
<h2>{title} <a href="{external_portal_name}" target="_blank" class="btn external">{external_portal_name}</a></h2>

<form action="{thisfile}">
	{form}

	<div class="row">
		<div class="span7">
			<h3>{cloud_user_data}</h3>
			{cloud_user_forename}
			{cloud_user_lastname}
			{cloud_user_street}
			{cloud_user_city}
			{cloud_user_country}
			{cloud_user_lang}
			<hr>
			<h3>{cloud_user_address}</h3>
			{cloud_user_email}
			{cloud_user_phone}
		</div>

		<div class="span7">
			<hr>
			<h3>User credentials</h3>
			<div class="row">
				<div class="span4">
					{cloud_user_password}
				</div>
				<div class="span3">
					<input type="button" id="passgenerate" onclick="passgen.generate(); return false;"  value="{lang_password_generate}">&#160;
					<input type="button" id="passtoggle" onclick="passgen.toggle(); return false;" value="{lang_password_show}">
				</div>
			</div>
			{cloud_user_ccunits}
			{cloud_usergroup_id}
			<hr>
			<h3>{cloud_user_permissions}</h3>
			<div class="span7">
				<div class="row">
					<div class="span4">
						{cloud_user_resource_limit}
						{cloud_user_memory_limit}
						{cloud_user_disk_limit}
						{cloud_user_cpu_limit}
						{cloud_user_network_limit}
					</div>
					<div class="span3">
						<small>{cloud_user_limit_explain}</small>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="span7">{submit} {cancel}</div>
	</div>
</form>


<script type="text/javascript">
var passgen = {
	generate : function() {
		pass = GeneratePassword();
		document.getElementById('cloud_user_password').value = pass;
	},
	toggle : function() {
		vnc = document.getElementById('cloud_user_password');
		but = document.getElementById('passtoggle');
		if(vnc.type == 'password') {
			but.value = "{lang_password_hide}";
			np = vnc.cloneNode(true);
			np.type='text';
			vnc.parentNode.replaceChild(np,vnc);
		}
		if(vnc.type == 'text') {
			but.value = "{lang_password_show}";
			np = vnc.cloneNode(true);
			np.type='password';
			vnc.parentNode.replaceChild(np,vnc);
		}
	}
}
</script>
