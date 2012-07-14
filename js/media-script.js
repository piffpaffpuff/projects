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
});

