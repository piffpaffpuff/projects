<?php

/**
 * Media class
 */
if (!class_exists('Projects_Media')) {
class Projects_Media {

	public static $media_type_content = 'content';
	public static $media_type_featured = 'featured';
	
	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Load the class hooks
	 */
	public function load() {
		add_action('init', array($this, 'hook_init'));
		add_action('admin_init', array($this, 'hook_admin'));
	}
	
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {
		// add some more oembed providers
		wp_oembed_add_provider('http://soundcloud.com/*', 'http://soundcloud.com/oembed');
	}	
	
	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {		
		add_action('admin_head-post.php', array($this, 'remove_insert_media_buttons'));
		add_action('admin_head-post-new.php', array($this, 'remove_insert_media_buttons'));
		
		// Add options not to the media manager main navigation page
		global $pagenow;
		if($pagenow != 'upload.php') {
			add_filter('media_view_strings', array($this, 'rename_media_manager_strings'), 10, 2);
			add_filter('attachment_fields_to_edit', array($this, 'edit_media_options'), 20, 2);
			add_filter('attachment_fields_to_save', array($this, 'save_media_options'), 20, 2);
		}
	}

	/**
	 * Remove the media buttons
	 */
	public function remove_insert_media_buttons() {
		global $post;

		if($post->post_type == Projects::$post_type) {
			remove_action('media_buttons', 'media_buttons');
		}
	}
		
	/**
	 * Rename some media manager strings
	 */
	public function rename_media_manager_strings($strings, $post) {
		if($post->post_type == Projects::$post_type) {
			$strings['createNewGallery'] = __('Create Media', 'projects');
			$strings['cancelGalleryTitle'] = __('Cancel', 'projects');
			
			$strings['addToGalleryTitle'] = __('Add Media', 'projects');
			$strings['insertGallery'] = $strings['addToGalleryTitle'];
			$strings['addToGallery'] = $strings['addToGalleryTitle'];
			
			$strings['createGalleryTitle'] = __('Edit Media', 'projects');
			$strings['editGalleryTitle'] = $strings['createGalleryTitle'];
			
			$strings['updateGallery'] = __('Update Media', 'projects');
	    }
	    return $strings;
	}

	/**
	 * Set the media fields
	 */
	public function edit_media_options($fields, $attachment) {		
		$projects = new Projects();
			
		// add a custom image size field
		$meta_size = $projects->get_project_meta('default_image_size', $attachment->ID);
		$image_sizes = get_intermediate_image_sizes();
		$image_sizes[] = 'full';
		
		// check if the saved size is selectable to activate the 'none' option
		$downsize = image_downsize($attachment->ID, $meta_size);
		if(empty($downsize[3]) && $meta_size != 'full') {
			$meta_size = null;
		} 	
			
		// build the selection
		$html = '';
		$html .= '<select class="image-size-select" name="attachments[' . $attachment->ID . '][default_image_size]">';
		$html .= '<option class="image-size-item" value="" ' . selected($meta_size, null, false) . '>' . __('None', 'projects') . '</option>';
		
		// go through all sizes and generate the fields
		foreach($image_sizes as $image_size) {	
			// do not add the internal plugin image sizes
			if($image_size == 'project-thumbnail' || $image_size == 'project-media-manager') {
				continue;	
			}
			
			// check if the current size is selectable
			$downsize = image_downsize($attachment->ID, $image_size);
			if(empty($downsize[3]) && $image_size != 'full') {
				$enabled = false;
			} else {
				$enabled = true;
			}
			
			// add the item to the list
			$html .= '<option class="image-size-item" value="' . $image_size . '" ' . selected($meta_size, $image_size, false) . ' ' . disabled($enabled, false, false) . '>' . ucfirst($image_size);
						
			// only show the dimensions if that choice is available
			if($enabled) {
				$html .= ' ' . sprintf('(%d &times; %d)', $downsize[1], $downsize[2]);
			}
			
			$html .= '</option>';
		}
		
		$html .= '</select>';
		
		// add the size field	
		$fields['default_image_size'] = array(
			'label' => __('Size', 'projects'),
			'input' => 'html',
			'html' => $html,
			'show_in_edit' => false
		);
		
		// add the embed url
		$meta_embed = $projects->get_project_meta('embed_url', $attachment->ID);
		$fields['embed_url'] = array(
			'label' => __('Embed URL', 'projects'),
			'input' => 'text',
			'value' => $meta_embed,
			'show_in_edit' => false
		);
		
		if($meta_embed) {	
			$fields['embed_url_link'] = array(
				'label' => null,
				'input' => 'html',
				'html' => '<a href="' . $meta_embed . '" target="_blank" class="embed-link">' . __('View link', 'projects') . '</a>',
				'show_in_edit' => false
			);
		}

		return $fields;
	}

	/**
	 * Save the media fields
	 */
	public function save_media_options($attachment, $attachment_data) {		
		// save the meta keys for the attachment
		// and return the attachment object.
		$projects = new Projects();

		if(isset($attachment_data['default_image_size'])) {
 			$projects->set_project_meta('default_image_size', $attachment_data['default_image_size'], $attachment['ID']);
 		}
 		if(isset($attachment_data['embed_url'])) {
 			$url = $attachment_data['embed_url'];
 			$meta_url = $projects->get_project_meta('embed_url', $attachment['ID']);

			// Save the meta only when the url changed
			if($url != $meta_url) {
				// Discover the embed html for an url
				$html = wp_oembed_get($url);
				
				// Save the meta
 				$projects->set_project_meta('embed_url', $url, $attachment['ID']);
 				$projects->set_project_meta('embed_html', $html, $attachment['ID']);
 			}
 		}
 		
 		return $attachment;
	}
	
	/**
	 * Check if the mime is of a certain type
	 */
	public function is_mime_type($mime, $regex) {
		if(preg_match('/' . $regex . '/i', $mime, $matches) ) {
    		return true;
		}
		return false;
	}
	
	/**
	 * Check if the mime is a web image
	 */
	public function is_web_image($mime) {
		return $this->is_mime_type($mime, 'jpg|jpeg|jpe|png|gif');
	}
	
	/**
	 * Get the media
	 */
	public function get_project_media($attachment_ids = null, $mime = null) {
		$projects = new Projects();
		
		// get all media
		$args = array( 
			'post_type' => 'attachment', 
			'post_status' => null, 
			'numberposts' => -1, 
			'include' => $attachment_ids
		); 
			
		// use post attachments when the array is empty
		if(empty($attachment_ids)) {
			global $post;

			$featured_image_id = get_post_thumbnail_id($post->ID);

			$default_args = array(
				'post_parent' => $post->ID,
				'post_status' => 'inherit',
				'order' =>'ASC',
				'orderby' => 'menu_order',
				'exclude' => array($featured_image_id)
			);
			$args = array_merge($args, $default_args);
		}
		
		// restrict mime
		if(!empty($mime)) {
			$default_args = array(
				'post_mime_type' => $mime,
			);
			$args = array_merge($args, $default_args);
		}

		// get all the attachments
		$attachments = get_posts($args);
		
		// set custom meta in the attachment object
		foreach($attachments as $attachment) {			
			$attachment->default_size = $projects->get_project_meta('default_image_size', $attachment->ID);
			$attachment->embed_url = $projects->get_project_meta('embed_url', $attachment->ID);
			$attachment->embed_html = $projects->get_project_meta('embed_html', $attachment->ID);
		}
			
		// sort the attachments by the array ids
		if(!empty($attachment_ids)) {
			$hash = array();
			$sorted = array();
			
			// create hash table 
			foreach($attachments as $attachment) {
				$hash[$attachment->ID] = $attachment;
			}
			
			// fill sorted array
			foreach($attachment_ids as $attachment_id) {
			    $sorted[] = $hash[$attachment_id];
			}
			
			$attachments = $sorted;
		}

		return $attachments;
	}
	
	/**
	 * Get the content media meta
	 */
	public function get_project_media_meta($post_id = null) {		
		// default id
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		
		// get the meta
		$projects = new Projects();
		$meta = $projects->get_project_meta('content_media', $post_id);
		
		return $meta;
	}
	
	/**
	 * Set the content media meta
	 */
	public function set_project_media_meta($ids, $sizes, $post_id) {
		// build an array of items
		$items = array(
			'attachment_id' => $ids,
			'attachment_image_size' => $sizes
		);
						
		// set the meta
		$projects = new Projects();
		$meta = $projects->set_project_meta('content_media', $items, $post_id);
		
		return $meta;
	}
	
	/**
	 * Get the featured media
	 */
	public function get_project_featured_media($post_id = null) {		
		$attachment = null;
		
		// get the featured image id
		$featured_image_id = get_post_thumbnail_id($post_id);
		
		if(!empty($featured_image_id)) {
			// return the first key instead of an array
			$attchment_ids = array($featured_image_id);
			$attachments = $this->get_project_media($attchment_ids);
			if(!empty($attachments)) {
				$attachment = reset($attachments);
			}
		} 

		return $attachment;
	}
	
	/**
	 * Get the content media
	 */
	public function get_project_content_media($post_id = null, $mime = null) {		
		$attachment_ids = array();
		
		// get the meta with the ids
		$meta = $this->get_project_media_meta($post_id);
		
		if(!empty($meta['attachment_id'])) {
			$attachment_ids = $meta['attachment_id'];
		}
		
		// get the attachment data
		$attachments = $this->get_project_media($attachment_ids, $mime);

		return $attachments;
	}
}
}

?>