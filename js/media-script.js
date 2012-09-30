jQuery(document).ready(function($) {
	// set the featured label on load
	$('#media-items .media-item').each(function() {
		switchLabel($(this));
	});
	
	// set the featured label on click
	$('#media-items .projects_featured_media input').on('click', function(event) {
		var item = $(this).closest('.media-item');
		switchLabel(item);
	});
	
	// switch label
	function switchLabel(item) {
		if(item.attr('id')) {
			var id = item.attr('id').split('-')[2];
	
			/* set the featured label. the label name is
			localized in the 'ProjectsScript' object. */
			var featured = $('.projects_featured_media :checked', item);
			if(featured.length > 0) {
				$('.menu_order', item).after('<div class="projects-featured-media-state">' + ProjectsScript.label_featured + '</div>');
			} else {
				$('.projects-featured-media-state', item).remove();
			}
		}
	}
	
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

