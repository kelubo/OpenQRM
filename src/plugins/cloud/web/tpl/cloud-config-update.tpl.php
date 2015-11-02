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

<h2>{title}</h2>
<form action="{thisfile}">
{form}

	<h3>Admin</h3>
	{cloud_enabled}
	{cloud_admin_email}
	{auto_provision}
	{external_portal_url}
	{request_physical_systems}
	{default_clone_on_deploy}
	{auto_create_vms}
	{public_register_enabled}

	<h3>Limits</h3>
	{max_apps_per_user}
	{max_disk_size}
	{max_memory}
	{max_cpu}
	{max_network}
	{max_network_interfaces}
	{resource_pooling}

	<h3>Network</h3>
	{ip-management}
	{cloud_nat}

	<h3>Users</h3>
	{show_collectd_graphs}
	{show_disk_resize}
	{show_private_image}
	{show_ha_checkbox}
	{show_puppet_groups}
	{show_sshterm_login}
	{appliance_hostname}

	{cloud_selector}

	<h3>Billing</h3>
	{cloud_billing_enabled}
	{cloud_currency}
	{cloud_1000_ccus}
	{auto_give_ccus}
	{deprovision_warning}
	{deprovision_pause}

	<h3>Performance</h3>
	{vm_provision_delay}
	{vm_loadbalance_algorithm}
	{max_resources_per_cr}
	{max-parallel-phase-one-actions}
	{max-parallel-phase-two-actions}
	{max-parallel-phase-three-actions}
	{max-parallel-phase-four-actions}
	{max-parallel-phase-five-actions}
	{max-parallel-phase-six-actions}
	{max-parallel-phase-seven-actions}

	<h3>Cloud-Zones</h3>
	{cloud_zones_client}
	{cloud_zones_master_ip}
	{cloud_external_ip}

	<h3>Misc</h3>
	{allow_vnc_access}


<div class="floatbreaker">&#160;</div>

	<div id="buttons">{submit}</div>

</form>


