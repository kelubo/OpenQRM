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

<h2>{title} <a href="{external_portal_name}" class="btn pull-right" target="_blank">Launch {external_portal_name}</a></h2>

<div class="row">
	<div class="span7">
		<form action="{thisfile}">
			{form}
			<h3>{cloud_mail_data}</h3>
			{cloud_mail_to}
			{cloud_mail_subject}
			{cloud_mail_body}
			
			{submit}
		</form>
	</div>
</div>