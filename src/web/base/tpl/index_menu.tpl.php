<div id="menuSection_1"  class="menuSection first">
	{menu_1}

	<div id="Event_messages">
	<a id="Event_active_box" href="index.php?base=event&amp;event_filter=active" style="visibility:hidden;" title="active events"> <span class="pill orange" id="events_active"></span></a>
	<a id="Event_box" href="index.php?base=event&amp;event_filter=error" style="visibility:hidden;" title="error events"><span class="pill error" id="events_critical"></span></a>
	</div>

	<div class="floatbreaker">&#160;</div>

</div>

<div id="menuSection_2" class="menuSection">
{menu_2}
</div>

<script type="text/javascript">
function get_event_status() {
	$.ajax({
		url: "api.php?action=get_top_status",
		cache: false,
		async: false,
		dataType: "text",
		success: function(response) {
			if(response != '') {
				var status_array = response.split("@");
				var event_error = parseInt(status_array[6]);
				var event_active = parseInt(status_array[7]);
				$("#events_critical").html(event_error);
				if(event_error > 0) {
					document.getElementById('Event_box').style.visibility = 'visible';
				} else {
					document.getElementById('Event_box').style.visibility = 'hidden';
				}

				if(event_active > 0) {
					$("#events_active").html(event_active);
					document.getElementById('Event_active_box').style.visibility = 'visible';
				} else {
					document.getElementById('Event_active_box').style.visibility = 'hidden';
				}
			}
		}
	});
	setTimeout("get_event_status()", 5000);
}

get_event_status();
</script>
