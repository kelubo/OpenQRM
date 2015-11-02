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



<script type="text/javascript">
		window.onload = function() {
			var th = $('#Tabelle').height();
			$('#openqrm_enterprise_footer').css("top",th + 90);

		};
</script>


<div id="content_container">

<h1>{title}</h1>
<form action="{thisfile}">
{table}
</form>

</div>