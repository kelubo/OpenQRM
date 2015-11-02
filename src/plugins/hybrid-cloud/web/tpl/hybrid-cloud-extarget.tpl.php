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
<div id="form">
	<form action="{thisfile}" method="POST">
	{form}
	<div class="row">
		<div class="span5">
			{name}
		</div>
	</div>
	<div class="row">
		<div class="span5">
			{size}
		</div>
	</div>

	<div class="row">
		<div class="span5">
			{arch}
		</div>
	</div>

	<div class="row">
		<div class="span5">
			{public_key_file}
		</div>
		<div class="span2">
			<input type="button" id="public_key" onclick="filepicker.init('public_key_file'); return false;" class="browse-button" value="{lang_browse}" style="display:none;">
		</div>
	</div>

	<div class="row">
		<div class="span5">
			{private_key_file}
		</div>
		<div class="span2">
			<input type="button" id="private_key" onclick="filepicker.init('private_key_file'); return false;" class="browse-button" value="{lang_browse}" style="display:none;">
		</div>
	</div>

	<div class="row">
		<div class="span5">
			{user_id}
		</div>
	</div>

	<div class="row">
		<div class="span5">
			{submit}&#160;{cancel}
		</div>
	</div>
	</form>
</div>


	<div id="filepicker" style="display:none;position:absolute;top:15px;left:15px;"  class="function-box">
		<div class="functionbox-capation-box"
						id="caption"
						onclick="MousePosition.init();"
						onmousedown="Drag.init(document.getElementById('filepicker'));"
						onmouseup="document.getElementById('filepicker').onmousedown = null;">
				<div class="functionbox-capation">
						{lang_browser}
						<input type="button" id ="close" class="functionbox-closebutton" value="X" onclick="document.getElementById('filepicker').style.display = 'none';">
				</div>
		</div>
		<div id="canvas"></div>
	</div>




<script type="text/javascript">
MousePosition.init();
function tr_hover() {}
function tr_click() {}
var filepicker = {
        target : null,
        init : function(target) {
                this.target = target;
                mouse = MousePosition.get();
                document.getElementById('canvas').innerHTML = '<img src="{baseurl}/img/loading.gif" style="margin-top:150px;">';
                document.getElementById('filepicker').style.left = (mouse.x + -120)+'px';
                document.getElementById('filepicker').style.top  = (mouse.y - 180)+'px';
                document.getElementById('filepicker').style.display = 'block';
                $.ajax({
                         url: "{baseurl}/api.php?action=plugin&plugin=hybrid-cloud&controller=hybrid-cloud&path=/&{actions_name}=filepicker",
                        dataType: "text",
                        success: function(response) {
                                document.getElementById('canvas').innerHTML = response;
                        }
                });
        },
        browse : function(target) {
                document.getElementById('canvas').innerHTML = '<img src="{baseurl}/img/loading.gif" style="margin-top:150px;">';
                $.ajax({
                        url: "{baseurl}/api.php?action=plugin&plugin=hybrid-cloud&controller=hybrid-cloud&path="+target+"&{actions_name}=filepicker",
                        dataType: "text",
                        success: function(response) {
                                document.getElementById('canvas').innerHTML = response;
                        }
                });
        },
        insert : function(value) {
                document.getElementById(this.target).value = value;
                document.getElementById('filepicker').style.display = 'none';
        }
}
document.getElementById('public_key').style.display = 'inline';
document.getElementById('private_key').style.display = 'inline';
</script>
