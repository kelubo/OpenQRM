$(document).ready(function(){
	var waitElements = [
		'input.submit',
		'.htmlobject_tabs li span a',
		'.pageturn_head a:not(.disabled)',
		'.pageturn_bottom a:not(.disabled)',
		'.actiontable input',
		'.htmlobject_td.action a',
		'#profiles_slot a',
		'#transaction_link a'
	];
	// attach wait function to list elements
	$.each(waitElements, function(k, v) {
		//$(v).attr('data-message', jstranslation['please_wait']);
		$(v).attr('data-message', '');
		$(v).click(function() {  openqrm.wait(this ,'') } );
	});

	$('select.sort').attr('data-message', '');
	$('select.sort').change(function() { openqrm.wait(this ,''); this.form.submit(); } );
	$('select.order').attr('data-message', '');
	$('select.order').change(function() { openqrm.wait(this ,''); this.form.submit(); } );
	$('select.limit').attr('data-message', '');
	$('select.limit').change(function() { openqrm.wait(this ,''); this.form.submit(); } );
});

// preload waitimage chrome issue
waitimage = new Image();
waitimage.src = window.location.protocol+"//"+window.location.host+"/cloud-portal/img/ajax-loader.gif";

openqrm = {
	wait : function(element, msg) {
		// TODO: after updating jquery we should use the 'data' method
		if($(element).attr('data-message') != 'undefined') {
			msg = $(element).attr('data-message');
		}

		// create transparent background overlay
		$('body').prepend( 
			$('<div>').attr('class','modal-overlay')
		);
		// create content box with message
		$('body').prepend(
			$('<div>')
				.attr('class', 'modal-box lead')
				.append(
					waitimage
				)
		);
		// center content box on screen
		$('.modal-box').css({
			left: (($(window).width() - $('.modal-box').outerWidth())/2),
			top: (($(window).height() - $('.modal-box').outerHeight())/2 -40)
		});

	}

}

/**
 * Temporary for compatibility reasons
 */
function wait() {
	var element = $('<div>').attr('data-message', '');
	openqrm.wait(element, '');
}

function cancel() {
	var element = $('<div>').attr('data-message', '');
	openqrm.wait(element, '');
}

function Logout(element) {
	path = window.location.protocol+"//dummy:dummy@"+window.location.host+""+window.location.pathname+'?'+Math.random()*11;
	try{
		var agt=navigator.userAgent.toLowerCase();
		if (agt.indexOf("msie") != -1) {
			// IE clear HTTP Authentication
			document.execCommand("ClearAuthenticationCache");
		} else {
			var data = $.ajax({
				url : path,
				type: "POST",
				cache: false,
				async: false,
				dataType: "text",
				success : function () { }
			}).responseText;
		}
		window.location.href = element.href;
	} catch(e) { alert(e); }
}
