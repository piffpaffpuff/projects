jQuery(document).ready(function($) {

	/* -------------------------
	 * Writepanel
	 * ------------------------- */
	
	
	// color picker
	$('input.minicolors').miniColors();
	
	// media manage buttons 
	$('.media-manage').live('click', function(event) {
		event.preventDefault();
		
		// Target
		var file_frame;
		var button = $(this);
		
		// Find all attachment ids in the dom
		var ids = [];
		button.closest('#projects-media-box').find('.media-item a').each(function() {
			ids.push($(this).data('attachment-id'));
		});
		
		// Use a pseudo shortcode to send the ids to the frame 
		var shortcode = '[gallery ids="' + ids.join(',') + '"]';
		
		// Create the media frame
		if(ids.length == 0) {
			// check if there are already an ids
			file_frame = wp.media.frames.file_frame = wp.media({
				frame: 'post',
				state: 'gallery-edit',
				multiple: true
			});
			file_frame.open();
		} else {
			file_frame = wp.media.frames.file_frame = wp.media.gallery.edit(shortcode);
		}
		
		// Update the media list
		file_frame.on('update', function() {
			var controller = file_frame.state('gallery-edit');
			var library = controller.get('library');
			
			// Get all the attachment ids 
			var ids = new Array();

			// Check if it is an image
			var models = library.models;
			for(var i = 0; i < models.length; i++) {
				if(models[i].attributes.type == 'image') {
					ids.push(models[i].attributes.id);
				}
			}
			
			// Save all
			saveMediaList(ids);
		});
	});
	
	/**
	 * save the media list with ajax and output it
	 */
	function saveMediaList(ids) {		
		var data = {
			action: 'save_media_list',
			post_id: $('#post_ID').val(),
			nonce: $('#projects_nonce').val(),
			ids: ids
		};
		
		$.post(ajaxurl, data, function(response) {			
			$('#projects-media-list').empty().append(response);
		});
	}
		
	// add a preset
	$('.add-preset').on('click', function(event) {
		var taxonomy_group_name = $(this).closest('.inside').find('input.taxonomy-group-name').val();
		addTaxonomyGroupPreset(taxonomy_group_name);
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
	function addTaxonomyGroupPreset(taxonomy_group_name) {
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

