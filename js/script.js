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
	
	// Settings -------------------------
	
	// add image size to settings
	$('#projects-add-image-size').click(function(event) {
		
		// clone the item
		var item = $(imageSetTemplate).clone();

		// reset the values and set a new id
		var id = $('.image-set').length;
		var inputs = item.find(':input');
		inputs.val('').removeAttr('checked');
		
		// set a new item index
		inputs.each(function() {
			var name = $(this).attr('name').split('item');
			var nameAlt = name[0] + 'item' + id + name[1].substr(name[1].indexOf(']'));
			$(this).attr('name', nameAlt);
		});
		
		// enable remove button
		item.find('a.remove-image-size').click(function(event) {
			item.remove();
			event.preventDefault();
		});
		
		// append item
		$('.image-sizes').append(item);
		event.preventDefault();
	});
	
	// remove row
	$('.image-set a.remove-image-size').click(function(event) {
		$(this).parent().remove();
		event.preventDefault();
	});

});

