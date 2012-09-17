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
		var taxonomy_group = $(this).closest('.inside').find('input[name="projects_taxonomy_group"]').val();
		add_taxonomy_group_preset(taxonomy_group);
		event.preventDefault();
	});

	// delete a preset
	$('.delete-preset').live('click', function(event) {
		var preset_id = $(this).closest('.preset').find('input[name="projects_preset_id"]').val();
		var taxonomy_group = $(this).closest('.inside').find('input[name="projects_taxonomy_group"]').val();
		delete_taxonomy_group_preset(preset_id, taxonomy_group);
		event.preventDefault();
	});
	
	// sort presets
	$('.taxonomy-group-list').sortable({
		axis: 'y'
	});
	
	// set title of presets
	$('.taxonomy-group-list .preset select').live('change', function() {
		var h4 = $(this).closest('.preset').find('h4');
		var value = $('option:selected', this).val();	
		if(value) {
			var title = $('option:selected', this).html();
		} else {
			var title = h4.attr('title');
		}
		h4.html(title);
	});
/*
	// sort award groups
	$('#projects-award-list').sortable({
		axis: 'y'
	});
	
	// set title of award groups
	$('#projects-award-list .award-select-project_award_name').live('change', function() {
		var h4 = $(this).closest('.award-group').find('h4');
		var value = $('option:selected', this).val();	
		if(value) {
			var title = $('option:selected', this).html();
		} else {
			var title = h4.attr('title');
		}
		h4.html(title);
	});
	*/
	
	/**
	 * load new award list group item
	 */
	/*
	function load_award_group() {
		var index = Number($('#projects_award_index').val());
		var data = {
			action: 'add_award_group',
			index: index,
			nonce: $('#projects_nonce').val()
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
	*/
	
	/**
	 * add new term list group item
	 */
	function add_taxonomy_group_preset(taxonomy_group) {
		var box = $('#projects-taxonomy-group-box-' + taxonomy_group);
		var data = {
			action: 'add_taxonomy_group_preset',
			post_id: $('#post_ID').val(),
			taxonomy_group: taxonomy_group,
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
	
	/**
	 * delete new term list group item
	 */
	function delete_taxonomy_group_preset(preset_id, taxonomy_group) {
		var box = $('#projects-taxonomy-group-box-' + taxonomy_group);
		var data = {
			action: 'delete_taxonomy_group_preset',
			post_id: $('#post_ID').val(),
			taxonomy_group: taxonomy_group,
			preset_id: preset_id,
			nonce: $('#projects_nonce').val()
		};
		
		// remove the item
		$('#projects-taxonomy-group-preset-' + taxonomy_group + '-' + preset_id, box).remove();		
	
		// send the request
		$.post(ajaxurl, data, function(response) {
		});
	}
	
});

