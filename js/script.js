jQuery(document).ready(function($) {

	// Writepanel -------------------------
	
	// change the date
	/*
	$('#projects-date-month').on('change', function(event) {
		var value = $(this).val();
		if(value < 10) {
			value = '0' + value;
		}
		
		$('#mm option').removeAttr('selected');
		$('#mm option[value="' + value + '"]').attr('selected', 'selected');		
	});
	
	$('#mm').on('change', function(event) {
		var value = Number($(this).val());

		$('#projects-date-month option').removeAttr('selected');
		$('#projects-date-month option[value="' + value + '"]').attr('selected', 'selected');		
	});
	
	$('#projects-date-year').on('change', function(event) {
		$('#aa').val($(this).val());
	});
	
	$('#aa').on('change', function(event) {
		var value = $(this).val();
		var option = $('#projects-date-year option[value="' + value + '"]');
		
		
		$('#projects-date-year option').removeAttr('selected');
		
		if(option.length > 0) {
		console.log(option);
			option.attr('selected', 'selected');		
		}
	});
	*/

	// color picker
	$('input.minicolors').miniColors();
		
	// reload the media list on thickbox close 
	$('#TB_overlay, #TB_closeWindowButton').live('mouseup', function(event) {
		loadMediaList('featured');
		loadMediaList('gallery');
	});
	
	/**
	 * load the media list with ajax
	 */
	function loadMediaList(type) {
		var data = {
			action: 'loadMediaList',
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

