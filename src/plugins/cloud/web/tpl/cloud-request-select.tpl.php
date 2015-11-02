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
<script type="text/javascript">

$(document).ready(function(){
	$("#cloudpopupInfoClose").click(function(){
		clouddisablePopup();
	});
	$("#cloudbackgroundPopup").click(function(){
		clouddisablePopup();
	});

});


var cloudpopupStatus = 0;
function cloudloadPopup(){
	if(cloudpopupStatus==0){
		$("#cloudbackgroundPopup").css({
			"opacity": "0.7"
		});
		$("#cloudbackgroundPopup").fadeIn("slow");
		$("#cloudpopupInfo").fadeIn("slow");
		cloudpopupStatus = 1;
	}
}

function clouddisablePopup(){
	if(cloudpopupStatus==1){
		$("#cloudbackgroundPopup").fadeOut("slow");
		$("#cloudpopupInfo").fadeOut("slow");
		cloudpopupStatus = 0;
	}
}


function cloudcenterPopup(){
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#cloudpopupInfo").height();
	var popupWidth = $("#cloudpopupInfo").width();
	$("#cloudpopupInfo").css({
		"position": "fixed",
		"top": "50px",
		"left": "280px" 
	});
	$("#cloudbackgroundPopup").css({
		"height": windowHeight + 20
	});
}


function cloudopenPopup(cr_id) {
	cloudcenterPopup();
	cloudloadPopup();
	cloudget_info_box(cr_id);
}



function cloudget_info_box(cr_id) {
	$.ajax({
		url: "/openqrm/base/api.php?action=plugin&plugin=cloud&controller=cloud-request&cloud-request=details&cloud_request_id=" + cr_id,
		cache: false,
		async: false,
		dataType: "text",
		success: function(response) {
			$("#cloudinfoArea").html(response);
		}
	});
}

</script>

<div id="cloudpopupInfo">
	<a id="cloudpopupInfoClose">x</a>
	<div id="cloudinfoScrollArea">
		<p id="cloudinfoArea">
	</p>
	</div>
</div>
<div id="cloudbackgroundPopup"></div>


<form action="{thisfile}">

{form}
<div id="form">
	<div style="width:500px;float:left;">
		{filter}
	</div>
	<div style="width:200px;float:right;">
		<div>{clean_up}</div>
	</div>
	<div style="clear:both;" class="floatbreaker">&#160;</div>
	{table}
</div>

</form>
