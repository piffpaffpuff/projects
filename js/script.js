jQuery(document).ready(function($) {

	// Writepanel -------------------------
			
	// color picker
	$('input.minicolors').miniColors();
		
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
			nonce: $('#projects_nonce').val()
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
	
		
	// add a preset
	$('.add-preset').on('click', function(event) {
		var taxonomy_group_name = $(this).closest('.inside').find('input.taxonomy-group-name').val();
		add_taxonomy_group_preset(taxonomy_group_name);
		event.preventDefault();
	});

	// delete a preset
	$('.delete-preset').live('click', function(event) {
		$(this).closest('.preset').remove();
		event.preventDefault();
	});
	
	// sort presets
	$('.taxonomy-group-list').sortable({
		axis: 'y'
	});
	
	// set title of presets
	$('.taxonomy-group-list .preset select.preset-select-field-1').live('change', function() {
		var h4 = $(this).closest('.preset').find('h4');
		var value = $('option:selected', this).val();	
		if(value) {
			var title = $('option:selected', this).html();
		} else {
			var title = h4.attr('title');
		}
		h4.html(title);
	});
	
	/**
	 * add new term list group item
	 */
	function add_taxonomy_group_preset(taxonomy_group_name) {
		var box = $('#projects-taxonomy-group-box-' + taxonomy_group_name);
		var data = {
			action: 'add_taxonomy_group_preset',
			post_id: $('#post_ID').val(),
			taxonomy_group_name: taxonomy_group_name,
			nonce: $('#projects_nonce').val()
		};
		
		// show the loader
		$('.taxonomy-group-loader', box).css('visibility', 'visible');
				
		// send the request
		$.post(ajaxurl, data, function(response) {
			$('.taxonomy-group-loader', box).css('visibility', 'hidden');
			$('.taxonomy-group-list', box).append(response);
		});
	}
	
});

