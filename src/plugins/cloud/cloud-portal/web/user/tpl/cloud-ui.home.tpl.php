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
//<![CDATA[
var lang_systems = "{lang_systems}";
var lang_disk = "{lang_disk}";
var lang_memory = "{lang_memory}";
var lang_cpu = "{lang_cpu}";
var lang_network = "{lang_network}";
//]]>
</script>

<script src="/cloud-portal/js/jqplot.jquery.min.js" type="text/javascript"></script>
<script src="/cloud-portal/js/excanvas.min.js" type="text/javascript"></script>
<script src="/cloud-portal/js/jquery.jqplot.min.js" type="text/javascript"></script>
<script src="/cloud-portal/js/jqplot.donutRenderer.min.js" type="text/javascript"></script>
<script src="/cloud-portal/js/jqplot.canvasTextRenderer.min.js" type="text/javascript"></script>
<script src="/cloud-portal/js/jqplot.categoryAxisRenderer.min.js" type="text/javascript"></script>
<script src="/cloud-portal/js/cloud.ui.home.js" type="text/javascript"></script>

<div id="home_container">


<div style="margin: 0 0 0 40px; width: 680px;">

		<div style="float:left;width:200px;">
			<div id="chartdiv-inventory-systems"></div>
			<div id="chartdiv-inventory-systems-legend" class="donut-chart-legend"></div>
		</div>

		<div style="float:left;width:200px;">
			<div id="chartdiv-inventory-disk"></div>
			<div id="chartdiv-inventory-disk-legend" class="donut-chart-legend"></div>
		</div>

		<div style="float:left;width:150px; margin: 0 0 0 50px;">
			<h3>{label_limits}</h3><br><br>
			<p>{limit_resource} : {resource_limit_value}</p>
			<p>{limit_disk} : {disk_limit_value}</p>
			<p>{limit_memory} : {memory_limit_value}</p>
			<p>{limit_cpu} : {cpu_limit_value}</p>
			<p>{limit_network} : {network_limit_value}</p>
		</div>

<!--
		<div id="quicklinks">
			<h3>{label_quicklinks}</h3>
			<p>{quicklinks}</p>
		</div>
//-->

		<div class="floatbreaker" style="line-height:0px;clear:both;">&#160;</div>


	<div style="margin: 40px 0;clear:both;">
		<div style="float:left;width:200px;">
			<div id="chartdiv-inventory-memory"></div>
			<div id="chartdiv-inventory-memory-legend" class="donut-chart-legend"></div>
		</div>

		<div style="float:left;width:200px;">
			<div id="chartdiv-inventory-cpu"></div>
			<div id="chartdiv-inventory-cpu-legend" class="donut-chart-legend"></div>
		</div>

		<div style="float:left;width:200px;">
			<div id="chartdiv-inventory-network"></div>
			<div id="chartdiv-inventory-network-legend" class="donut-chart-legend"></div>
		</div>
		<div class="floatbreaker" style="line-height:0px;clear:both;">&#160;</div>
	</div>

</div>


<form action="{thisfile}"></form>

</div>
