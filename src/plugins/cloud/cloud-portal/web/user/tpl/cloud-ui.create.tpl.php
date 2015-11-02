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

{js_formbuilder}
{js_use_api}

	window.onload = function() {
		// add event handler 
		var inputs = $( "#components_form :input" );
		for(i=0;i<inputs.length;i++) {
			$(inputs[i].id).change(function () {
					cloud_cost_calculator();
				});
		}

		// remove selected ip from the other selects
		$("#cloud_ip_select_0").change(function() {
			var sid = $("#cloud_ip_select_0 option:selected").val();
			if ((sid != -1) && (sid != -2)) {
				$("#cloud_ip_select_1 option:[value=" + sid + "]").remove();
				$("#cloud_ip_select_2 option:[value=" + sid + "]").remove();
				$("#cloud_ip_select_3 option:[value=" + sid + "]").remove();
			}
		})
		$("#cloud_ip_select_1").change(function() {
			var sid = $("#cloud_ip_select_1 option:selected").val();
			if ((sid != -1) && (sid != -2)) {
				$("#cloud_ip_select_0 option:[value=" + sid + "]").remove();
				$("#cloud_ip_select_2 option:[value=" + sid + "]").remove();
				$("#cloud_ip_select_3 option:[value=" + sid + "]").remove();
			}
		})
		$("#cloud_ip_select_2").change(function() {
			var sid = $("#cloud_ip_select_2 option:selected").val();
			if ((sid != -1) && (sid != -2)) {
				$("#cloud_ip_select_1 option:[value=" + sid + "]").remove();
				$("#cloud_ip_select_0 option:[value=" + sid + "]").remove();
				$("#cloud_ip_select_3 option:[value=" + sid + "]").remove();
			}
		})
		$("#cloud_ip_select_3").change(function() {
			var sid = $("#cloud_ip_select_3 option:selected").val();
			if ((sid != -1) && (sid != -2)) {
				$("#cloud_ip_select_1 option:[value=" + sid + "]").remove();
				$("#cloud_ip_select_2 option:[value=" + sid + "]").remove();
				$("#cloud_ip_select_0 option:[value=" + sid + "]").remove();
			}
		})

		// preset ip selects to 1 nic
		$('#cloud_ip_select_0').removeAttr("disabled");
		$('#cloud_ip_select_1').attr('disabled', 'true');
		$('#cloud_ip_select_2').attr('disabled', 'true');
		$('#cloud_ip_select_3').attr('disabled', 'true');

		// load costs
		cloud_cost_calculator();
	};

	function cloud_cost_calculator() {
		var this_cloud_id = 0;
		var virtualization = $("select[name=cloud_virtualization_select]").val();
		var kernel = $("select[name=cloud_kernel_select]").val();
		var memory = $("select[name=cloud_memory_select]").val();
		var cpu = $("select[name=cloud_cpu_select]").val();
		var disk = $("select[name=cloud_disk_select]").val();
		var network = $("select[name=cloud_network_select]").val();

		var ha = 0;
		if ($("input[name=cloud_ha_select]").is(":checked")) {
			var ha = 1;
		}

		var inputs = $( "#applications_list :input" );
		var apps = '';
		var j = 0;
		for(i=0;i<inputs.length;i++) {
			if($(inputs[i]).is(":checked")) {
				if(j == 0) {
					var apps = $(inputs[i]).val();
					j++;
				} else {
					var apps = apps+','+$(inputs[i]).val();
				}
			}
		}

		// enable/disable ip selects
		// adjust ip selects according to the nic count
		switch (network) {
			case '1':
				$('#cloud_ip_select_0').removeAttr("disabled");
				$('#cloud_ip_select_1').attr('disabled', 'true');
				$('#cloud_ip_select_2').attr('disabled', 'true');
				$('#cloud_ip_select_3').attr('disabled', 'true');
				break;
			case '2':
				$('#cloud_ip_select_0').removeAttr("disabled");
				$('#cloud_ip_select_1').removeAttr("disabled");
				$('#cloud_ip_select_2').attr('disabled', 'true');
				$('#cloud_ip_select_3').attr('disabled', 'true');
				break;
			case '3':
				$('#cloud_ip_select_0').removeAttr("disabled");
				$('#cloud_ip_select_1').removeAttr("disabled");
				$('#cloud_ip_select_2').removeAttr("disabled");
				$('#cloud_ip_select_3').attr('disabled', 'true');
				break;
			case '4':
				$('#cloud_ip_select_0').removeAttr("disabled");
				$('#cloud_ip_select_1').removeAttr("disabled");
				$('#cloud_ip_select_2').removeAttr("disabled");
				$('#cloud_ip_select_3').removeAttr("disabled");
				break;
			default:
				$('#cloud_ip_select_0').attr('disabled', 'false');
				$('#cloud_ip_select_1').attr('disabled', 'false');
				$('#cloud_ip_select_2').attr('disabled', 'false');
				$('#cloud_ip_select_3').attr('disabled', 'false');
				break;
		}

		if( use_api == true ) {
			// send ajax request to calculator
			// this connects via soap to the specific cloud-zone server to get the costs for the request
			var url = "/cloud-portal/user/api.php?action=calculator&virtualization=" + virtualization;
			url = url + "&kernel=" + kernel;
			url = url + "&memory=" + memory;
			url = url + "&cpu=" + cpu;
			url = url + "&disk=" + disk;
			url = url + "&network=" + network;
			url = url + "&ha=" + ha;
			url = url + "&apps=" + apps;

			$.ajax({
				url : url,
				type: "POST",
				cache: false,
				async: false,
				dataType: "html",
				success : function (data) {
					var costs = data.split(";");
					for( i in costs ) {
						tmp = costs[i].split('=');
						$("#price_"+tmp[0]).text(tmp[1]);
					}
				}
			});
		}
	}

	function init_image() {
		// add resources with js
		selected = document.getElementById('cloud_virtualization_select').options.selectedIndex;
		$('#cloud_virtualization_select').html('');
		for( i in formbuilder.resources ) {
			option = document.createElement("option");
			option.value = formbuilder.resources[i][0];
			option.text = formbuilder.resources[i][1];
			if(i == selected) {
				option.selected = 'selected';
			}
			document.getElementById('cloud_virtualization_select').appendChild(option);
		}
		$('#cloud_virtualization_select').change(function () {
					change_image(this);
					cloud_cost_calculator();
				});
		change_image(document.getElementById('cloud_virtualization_select'));
	}

	function change_image(element) {
		select   = document.getElementById('cloud_image_select');
		try {
			selected = select.options[select.options.selectedIndex].value;
			tag      = formbuilder.resources[element.options.selectedIndex][2];
			type     = formbuilder.resources[element.options.selectedIndex][3];
			$('#cloud_image_select').html('');
			for( i in formbuilder.images ) {
				if(formbuilder.images[i][2] == tag) {
					option = document.createElement("option");
					option.value = formbuilder.images[i][0];
					option.text = formbuilder.images[i][1];
					if(formbuilder.images[i][0] == selected) {
						option.selected = 'selected';
					}
					select.appendChild(option);
				}
			}
			if(type == 'vm-net') {
				document.getElementById('cloud_kernel_select_box').style.visibility = 'visible';
			} else {
				document.getElementById('cloud_kernel_select_box').style.visibility = 'hidden';
			}
		} catch(e) { }

		if(select.length == 0) {
			option = document.createElement("option");
			option.value = '';
			option.text = ' ';
			select.appendChild(option);
		}
	}

</script>

<div id="content_container">

	<h1>{label}</h1>

	<div style="position:relative; margin: 0 0 0 10px;">

		<div id="error_list" style="display:{display_error};">
			{error}
		</diV>

		<div id="components_list" style="display:{display_component_table};">
		<form action="{thisfile}" id="components_form">
			{form}
			<div id="hardware_slot">
				{cloud_virtualization_select}
				{cloud_image_select}
				{cloud_kernel_select}
				<script type="text/javascript"> init_image(); </script>
			</div>
			<div id="hardware_slot">
				{cloud_disk_select}
				{cloud_memory_select}
				{cloud_cpu_select}
				<div id="network_slot">
					{cloud_network_select}
					<div id="ip_slot">
						<div class="wrapper">
							{cloud_ip_select_0}
							<div class="floatbreaker" style="clear:both;">&#160;</div>
							{cloud_ip_select_1}
							<div class="floatbreaker" style="clear:both;">&#160;</div>
							{cloud_ip_select_2}
							<div class="floatbreaker" style="clear:both;">&#160;</div>
							{cloud_ip_select_3}
							<div class="floatbreaker" style="clear:both;">&#160;</div>
						</div>
						<div class="floatbreaker" style="clear:both;">&#160;</div>
					</div>
				</div>
			</div>

			<div id="applications_slot">
				<div id="applications_list">
					{cloud_applications}
					{cloud_ha_select}
				</div>
			</div>

			<div id="capabilities_slot">
				{cloud_hostname_input}
				{cloud_appliance_capabilities}
			</div>

			<div id="misc_slot">
				{cloud_profile_name}
				{submit}
				<div class="floatbreaker" style="clear:both;">&#160;</div>
			</div>
		</form>
		</div>

		<div id="profiles_slot">
			<div id="manage_list">
				<ul>
					{private_images_link}
					<li>{profiles_link}</li>
				</ul>
				<div class="floatbreaker" style="clear:both;">&#160;</div>
			</div>
			<div id="profiles_list">
				{profiles}
			</div>
		</div>

		<div id="price_list" style="display:{display_price_list};">
			<table>
			<tr>
				<td>{ccu_per_hour}:</td><td id="price_summary" class="price">&#160;</td>
			</tr><tr>
				<td>{price_hour}:</td><td id="price_hour" class="price">&#160;</td>
			</tr><tr>
				<td>{price_day}:</td><td id="price_day" class="price">&#160;</td>
			</tr><tr>
				<td>{price_month}:</td><td id="price_month" class="price">&#160;</td>
			</tr>
			</table>
			<div class="floatbreaker" style="clear:both;">&#160;</div>
		</div>

		<div class="floatbreaker" style="clear:both;">&#160;</div>
	</div>
</div>


