jQuery(document).ready(function($) {	
	// check the media url in the 'from-url' tab
	$('#projects-media-url-source').on('blur', function(event) {
		var field = $(this);
		var data = {
			action: 'validate_media_url_item',
			post_id: $('#post_id').val(),
			url: field.val(),
			nonce: $('#projects_media_url_nonce').val()
		};
		
		var status = field.parent().find('.status');
		$('img', status).show();
		// send the request
		$.post(ajaxurl, data, function(response) {
			console.log(response);
			$('img', status).hide();
			if(response) {
				status.addClass('success');
				status.removeClass('error');
			} else {
				status.removeClass('success');
				status.addClass('error');
			}
		});
	});

});

