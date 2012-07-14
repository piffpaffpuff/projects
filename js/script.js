jQuery(document).ready(function($) {

	// Writepanel -------------------------
			
	// color picker
	$('input.minicolors').miniColors();
	
	// make the images clickable. maybe this will be later replaced. 
	$('#projects-gallery-media-list').on('click', function(event) {
		$('#projects-gallery-media-add').trigger('click');		
		event.preventDefault();
	});
	$('#projects-featured-media-list').on('click', function(event) {
		$('#projects-featured-media-add').trigger('click');
		event.preventDefault();
	});
	
	// reload the media list on thickbox close 
	$('#TB_overlay, #TB_closeWindowButton').live('mouseup', function(event) {
		load_media_list('featured');
		load_media_list('gallery');
	});
	
	/**
	 * load the media list with ajax
	 */
	function load_media_list(type) {
		var data = {
			action: 'load_media_list',
			type: type,
			post_id: $('#post_ID').val(),
			nonce: $('#projects_media_nonce').val()
		};
		
		$.post(ajaxurl, data, function(response) {			
			// check the media type and load the list into the right box
			if(data.type == 'featured') {
				$('#projects-featured-media-list').empty().append(response);
			} else {
				$('#projects-gallery-media-list').empty().append(response);
			}
		});
	}
		
	// add an award group 
	$('#projects-add-award-group').on('click', function(event) {
		load_award_group();
		event.preventDefault();
	});
	
	// remove an award group 
	$('#projects-award-list .remove-award-group').live('click', function(event) {
		$(this).closest('.award-group').remove();
		event.preventDefault();
	});

	// sort award groups
	$('#projects-award-list').sortable({
		axis: 'y'
	});
	
	// set title of award groups
	$('#projects-award-list .award-select-name').live('change', function() {
		var h4 = $(this).closest('.award-group').find('h4');
		var value = $('option:selected', this).val();	
		if(value) {
			var title = $('option:selected', this).html();
		} else {
			var title = h4.attr('title');
		}
		h4.html(title);
	});

	/**
	 * load new award list group item
	 */
	function load_award_group() {
		var index = Number($('#projects_award_index').val());
		var data = {
			action: 'add_award_group',
			index: index,
			nonce: $('#projects_award_nonce').val()
		};
		
		// show the loader
		$('#projects-award-loader').css('visibility', 'visible');
		
		// raise the index for the next item
		var index = index +1;
		$('#projects_award_index').val(index);
		
		// send the request
		$.post(ajaxurl, data, function(response) {
			$('#projects-award-loader').css('visibility', 'hidden');
			$('#projects-award-list').append(response);
		});

	}
	
});

