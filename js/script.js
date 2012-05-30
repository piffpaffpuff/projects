jQuery(document).ready(function($) {
	// make the media gallery sortable
	//$('#projects-media-list').sortable();
	//$('#projects-media-list').disableSelection();

	// Writepanel -------------------------
			
	// color picker
	$('#projects-general-box input.minicolors').miniColors();
	
	// make the images clickable. maybe this will be later replaced. 
	$('#projects-media-list').on('click', function(event) {
		$('#projects-media-add').trigger('click');
	});
	
	// reload the media list on thickbox close 
	$('#TB_overlay, #TB_closeWindowButton').live('mouseup', function(event) {
		load_media_list();
	});

	// load the list
	load_media_list();
	
	/**
	 * load the media list with ajax
	 */
	function load_media_list() {
		var data = {
			action: 'add_media_list',
			post_id: $('#post_ID').val(),
			nonce: $('#projects_media_nonce').val()
		};
		
		$.post(ajaxurl, data, function(response) {
			$('#projects-media-list').empty().append(response);
		});
	}
	
			
	// enable remove button
/*
	$('#projects-award-list .remove-award-group').each(function(index, element) {
		console.log(index);
		if(index == 0) {
			$(element).hide();
		}
	});
	
*/
	$('#projects-award-list .remove-award-group').live('click', function(event) {
		$(this).closest('.award-group').remove();
		event.preventDefault();
	});
		
	// add new award list group
	$('#projects-add-award-group').on('click', function(event) {
		// all groups
		var elements = $('#projects-award-list .award-group');
		var id = elements.length;
		
		// clone the item
		var element = elements.filter(':last').clone();
		
		// reset the values
		$('option', element).removeAttr('selected');
		
		// set a new item index		
		$('select', element).each(function() {
			var name = $(this).attr('name').split('award_');
			var newName = name[0] + 'award_' + id + name[1].substr(name[1].indexOf(']'));
			$(this).attr('name', newName);
		});
		
		// enable the remove button
		//$('.remove-award-group', element).show();
		
		// append element
		$('#projects-award-list').append(element);

		// prevent click
		event.preventDefault();
	});
	
});

