jQuery(document).ready(function($) {
	// make the media gallery sortable
	//$('#projects-media-list').sortable();
	//$('#projects-media-list').disableSelection();

	// Writepanel -------------------------
	
	// get lat lon to display a point on the map
	var lat = $('#projects-location-box input[name="projects[lat]"]').val();
	var lon = $('#projects-location-box input[name="projects[lon]"]').val();
	
	// maps for the location
	if(lat && lon) {
		$('#projects-location-map-wrap').show();

		// mapquest tiles layer class
        OpenLayers.Layer.MapQuestOSM = OpenLayers.Class(OpenLayers.Layer.XYZ, {
			name: 'MapQuestOSM',
			sphericalMercator: true,
			url: 'http://otile1.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png',
			clone: function(obj) {
				if (obj == null) {
					obj = new OpenLayers.Layer.OSM(
					this.name, this.url, this.getOptions());
				}
				obj = OpenLayers.Layer.XYZ.prototype.clone.apply(this, [obj]);
				return obj;
			},
			CLASS_NAME: 'OpenLayers.Layer.MapQuestOSM'
		});
	
		// map options
		var options = {
			theme: null,
			controls: [
				new OpenLayers.Control.ZoomPanel(),
				new OpenLayers.Control.Navigation()
			]
        };
		
		// create the map
		var zoom  = 16; 
		var map = new OpenLayers.Map('projects-location-map', options);
        var mapquestosm = new OpenLayers.Layer.MapQuestOSM();            
		map.addLayers([
			mapquestosm
		]);
        
        // transform the projection position from w 1984 to spherical mercator
        var fromProjection = new OpenLayers.Projection('EPSG:4326');
        var toProjection = map.getProjectionObject(); 
        var position = new OpenLayers.LonLat(lon, lat).transform(fromProjection, toProjection);
    
        // add the marker    		
		var markers = new OpenLayers.Layer.Markers('Markers');
		map.addLayer(markers);
		
		var size = new OpenLayers.Size(16, 24);
		var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
		var icon = new OpenLayers.Icon(null, size, offset);
		markers.addMarker(new OpenLayers.Marker(position, icon));
				
		// set he map zoom ans position
		map.setCenter(position, zoom);
	}
	
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

