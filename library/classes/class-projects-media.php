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
		
		add_filter('upload_mimes', array($this, 'add_mime_types'));
		add_filter('icon_dir', array($this, 'attachment_mime_icons_path'));
		add_filter('icon_dir_uri', array($this, 'attachment_mime_icons_url'));
		add_filter('media_upload_tabs', array($this, 'remove_media_tabs'));
		add_filter('media_upload_media_url', array($this, 'add_tab_media_url'));
		add_filter('attachment_fields_to_edit', array($this, 'edit_media_options'), 15, 2);
		add_filter('attachment_fields_to_save', array($this, 'save_media_options'), 15, 2);
		add_action('delete_attachment', array($this, 'delete_embed_thumbnail'));
		
		add_action('wp_ajax_validate_media_url_item', array($this, 'validate_media_url_item_ajax'));
	}
	
	/**
	 * Add the meta boxes
	 */
	public function add_boxes() {			
		add_meta_box('projects-gallery-media-box', __('Media', 'projects'), array($this, 'create_box_gallery_media'), Projects::$post_type, 'normal', 'default');
	}
	
	/**
	 * Register the mime type
	 */
	public function add_mime_types($mimes) {
		/*$mimes = wp_parse_args(array(
			'csv' => 'text/csv'
		), $mimes);*/
		return $mimes;
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
	 * Replace the default file icons with some nicer ones
	 */
	function attachment_mime_icons_path($directory) {
		return Projects::$plugin_directory_path . '/images/icons'; 
	}
	
	/**
	 * Replace the default file icons with some nicer ones
	 */
	function attachment_mime_icons_url($directory) {
		return Projects::$plugin_directory_url . '/images/icons'; 
	}
	
	/**
	 * Remove the media uploader tabs
	 */
	public function remove_media_tabs($tabs) {
	    if(isset($_REQUEST['post_id'])) {
	        $post_type = get_post_type($_REQUEST['post_id']);
	        if($post_type == Projects::$post_type) {
	        	unset($tabs['type_url']);
				unset($tabs['library']);
	            unset($tabs['gallery']);
				$tabs['media_url'] = __('From URL', 'projects');
	            $tabs['gallery'] = __('Project Media', 'projects');
	        }
	    }
	    return $tabs;
	}
	
	/**
	 * Add a media uploader tab
	 */
	public function add_tab_media_url() {
		return wp_iframe(array($this, 'media_tab_media_url'), 'vimeo');
	}
	
	/**
	 * Create the media url tab. The function name
	 * has to start with media to poperly enqueue
	 * scripts and styles.
	 */	
	public function media_tab_media_url($type = null) {
		// Save the attachment
		$error = false;
		if(!empty($_POST) && isset($_POST['projects_media_url_add'])) {
			if(empty($_POST['projects_media_url_source']) || $this->get_embed($_POST['projects_media_url_source']) == false) {
				$error = true;
			} else {
				$this->create_media_url_item($_POST['post_id'], $_POST['projects_media_url_source']);
			}
		}
		
		// Show the form		
		media_upload_header();
		$post_id = intval($_REQUEST['post_id']);
		$form_action_url = admin_url('media-upload.php?tab=media_url&post_id=' . $post_id);
		$form_class = 'media-url-form type-form validate';
	    ?>
	    <form method="post" action="<?php echo esc_attr($form_action_url); ?>" class="<?php echo $form_class; ?>" id="<?php echo $type; ?>-form">
		    <input type="hidden" name="post_id" id="post_id" value="<?php echo (int) $post_id; ?>" />
		    <?php wp_nonce_field(Projects::$plugin_basename, 'projects_media_url_nonce'); ?>
		    <h3 class="media-title"><?php _e('Add media from another website', 'projects'); ?></h3>
		    <div id="media-items">
			    <div id="projects-media-url-item" class="media-item media-blank">
			    	<table class="describe">
			    		<tbody>
			    			<tr>
			    				<th class="label">
			    					<span class="alignleft"><label for="projects-media-url-source"><?php _e('URL', 'projects'); ?></label></span>
			    				</th>
			    				<td class="field">
			    					<input id="projects-media-url-source" name="projects_media_url_source" value="<?php echo ($error) ? $_POST['projects_media_url_source'] : ''; ?>" type="text" />
			    					<span class="status<?php if($error) : ?> error<?php endif; ?>">
			    						<img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" alt="" />
			    						<span class="check"></span>
			    					</span>
			    					<p class="help"><? _e('Enter an URL from YouTube, Vimeo or Flickr.', 'projects'); ?></p>
			    				</td>
			    			</tr>
			    		</tbody>
			    		<tfoot>
				    		<tr>
				    			<td></td>
				    			<td>
			    					<?php submit_button(__('Add media', 'projects'), 'button', 'projects_media_url_add', false); ?>
				    			</td>
				    		</tr>
			    		</tfoot>
			    	</table>
			    </div>
		    </div>
	    </form>
		<?php
	}

	/**
	 * Validate a media url to embed via ajax
	 */
	public function validate_media_url_item_ajax() {		
		// Verifiy post data and nonce
		if(empty($_POST) || empty($_POST['post_id']) || empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], Projects::$plugin_basename)) {
			die();
		}

		// Get the oembed
		$data = $this->get_embed($_POST['url']);
		print_r($data);
		if(empty($data)) {
			die();
		}
		
		echo 'success';
		exit;
	}
	
	/**
	 * Return the data of an oembed item instead of the html
	 */
	public function parse_oembed_data($html, $data, $url) {
		return $data;
	}
	
	/**
	 * Get an oembed data object or html
	 */
	public function get_embed($url = null, $html = false) {		
		// Internally oEmbed is used to embed media
		// http://codex.wordpress.org/Embeds
		// Use wp_oembed_add_provider() to add more
		// providers to the whitelist.
		
		// Check if the url is set
		if(empty($url)) {
			return false;
		}
		
		// Check if the media url can be embedded,
		// when the url can't be embedded:
		// $data is false when returned as object
		// or an empty string when returned as html.
		if($html) {
			// Get the html from the cache
			$projects = new Projects();
			$cache = $projects->get_project_meta('embed_' . md5($url));
			if($cache) {
				return $cache;
			}
			
			// Get the html from discovery
			$data = wp_oembed_get($url);
			return $data;
		} else {
			// Get the data object
			add_filter('oembed_dataparse', array($this, 'parse_oembed_data'), 10, 3);
			$data = wp_oembed_get($url);
			remove_filter('oembed_dataparse', array($this, 'parse_oembed_data'), 10, 3);
			return $data;
		}
		return false;
	}
	
	/**
	 * Get an oembed html
	 */
	public function get_embed_html($url) {
		return $this->get_embed($url, true);
	}
	
	/**
	 * Create a media url item
	 */
	public function create_media_url_item($parent_post_id, $url) {	
		// Check if the media can be embedded
		$data = $this->get_embed($url);
		if(empty($data)) {
			return;
		}

		// Insert the attachment
		$args = array(
			'post_title' => $data->title,
			'post_mime_type' => $data->type . '/embed'
		);
		$default_args = array(
			'post_title' => __('Title', 'projects'),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		// Merge the default and additional args
		$args = wp_parse_args($args, $default_args);
		
		// Create attachment
		$attachment_id = wp_insert_attachment($args, false, $parent_post_id);
		
		// Do not continue when the attachment couldn't be created
		if(is_wp_error($attachment_id)) {
			return;
		}
		
		// Potenitally save a thumbnail as child of the
		// embedded attachment item.
		/*if(isset($data->thumbnail_url)) {
			if(isset($data->url)) {
				$url = $data->url;
			} else {
				$url = $data->thumbnail_url;
			}
			$tmp = download_url($url);
		
			// Set variables for storage
			$path = parse_url($url, PHP_URL_PATH);
			$basename = pathinfo($path, PATHINFO_BASENAME);			
			$file_array['name'] = $basename;
			$file_array['tmp_name'] = $tmp;
		
			// If error storing temporarily, unlink
			if(is_wp_error($tmp)) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}
		
			// Do the validation and storage stuff
			$thumbnail_id = media_handle_sideload($file_array, $attachment_id);
		
			// If error storing permanently, unlink
			if(is_wp_error($thumbnail_id)) {
				@unlink($file_array['tmp_name']);
				return;
			}
		}*/
		
		// Create meta and cache
		$oembed = _wp_oembed_get_object();
		$html = $oembed->data2html($data, $url);
		$projects = new Projects();
		$projects->set_project_meta('embed_' . md5($url), $html, $parent_post_id);
		$projects->set_project_meta('embed_url', $url, $attachment_id);
		$projects->set_project_meta('embed_type', $data->type, $attachment_id);
		//$projects->set_project_meta('embed_thumbnail_id', $thumbnail_id, $attachment_id);
	}	
	
	/**
	 * Set the media fields
	 */
	public function edit_media_options($form_fields, $post) {		
		// default fields
		$form_fields['url']['value'] = '';
		$form_fields['url']['input'] = 'hidden';

		$form_fields['align']['value'] = 'left';
		$form_fields['align']['input'] = 'hidden';

		$form_fields['image-size']['value'] = 'full';
		$form_fields['image-size']['input'] = 'hidden';
		
		// create object
		$projects = new Projects();
			
		// add a custom image size field
		if(strpos($post->post_mime_type, 'image') !== false) {
			$html = '';
			$meta = $projects->get_project_meta('default_image_size', $post->ID);
			$image_sizes = get_intermediate_image_sizes();
			$image_sizes[] = 'full';
			$form_name = 'attachments[' . $post->ID . '][projects_default_image_size]';
						
			// check if the saved size is selectable to activate the 'none' option
			$downsize = image_downsize($post->ID, $meta);
			if(empty($downsize[3]) && $meta != 'full') {
				$meta = null;
			} 
			
			// build the selection
			$html .= '<select class="image-size-select" name="' . $form_name . '">';
			$html .= '<option class="image-size-item" value="" ' . selected($meta, null, false) . '>' . __('None', 'projects') . '</option>';
						
			// go through all sizes and generate the fields
			foreach($image_sizes as $image_size) {
				$css_id = 'projects-default-image-size-' . $image_size . '-' . $post->ID;				
				
				// check if the current size is selectable
				$downsize = image_downsize($post->ID, $image_size);
				if(empty($downsize[3]) && $image_size != 'full') {
					$enabled = false;
				} else {
					$enabled = true;
				}

				// add the item to the list
				$html .= '<option class="image-size-item" name="' . $form_name . '" value="' . $image_size . '" ' . selected($meta, $image_size, false) . ' ' . disabled($enabled, false, false) . '>' . ucfirst($image_size);
							
				// only show the dimensions if that choice is available
				if($enabled) {
					$html .= ' ' . sprintf('(%d &times; %d)', $downsize[1], $downsize[2]);
				}
				
				$html .= '</option>';
			}
			
			$html .= '</select>';
			
			$form_fields['projects_default_image_size']['label'] = __('Default Size', 'projects');
			$form_fields['projects_default_image_size']['input'] = 'html';
			$form_fields['projects_default_image_size']['html'] = $html;
		}

		// add the embed url
		$embed_url = $projects->get_project_meta('embed_url', $post->ID);
		if($embed_url) {
			$form_fields['projects_embed_url']['label'] = __('Embed URL', 'projects');
			$form_fields['projects_embed_url']['input'] = 'html';
			$form_fields['projects_embed_url']['html'] = '<a href="' . $embed_url . '" target="_blank">' . $embed_url . '</a>';
			
			$embed_type = $projects->get_project_meta('embed_type', $post->ID);
			$form_fields['projects_embed_type']['label'] = __('Embed Type', 'projects');
			$form_fields['projects_embed_type']['input'] = 'html';
			$form_fields['projects_embed_type']['html'] = '<label>' . ucfirst($embed_type) . '</label>';
			
			// generate thumbnail to display
			//$embed_thumbnail_id = $projects->get_project_meta('embed_thumbnail_id', $post->ID);
			//$form_fields['projects_embed_thumbnail']['label'] = __('Embed Thumbnail', 'projects');
			//$form_fields['projects_embed_thumbnail']['input'] = 'html';
			//$form_fields['projects_embed_thumbnail']['html'] = wp_get_attachment_link($embed_thumbnail_id, array(200, 200), true);
		}
		
		return $form_fields;
	}

	/**
	 * Save the media fields
	 */
	public function save_media_options($post, $attachment) {
		/* save the meta keys for the attachment
		and return the attachment object. */
		if(empty($attachment['projects_default_image_size'])) {
			delete_post_meta($post['ID'], '_projects_default_image_size');
		} else {
			update_post_meta($post['ID'], '_projects_default_image_size', $attachment['projects_default_image_size']);
  		}
  		
 		return $post;
	}
	
	/**
	 * Delete the media item child posts
	 */
	public function delete_embed_thumbnail($post_id) {
		$args = array( 
		    'post_parent' => $post_id,
		    'post_type'   => 'attachment', 
		    'numberposts' => -1, 
		    'post_status' => 'inherit'
		);
		$posts = get_children($args);
		if(is_array($posts) && count($posts) > 0) {
		    // Delete all the children of the attachment
		    foreach($posts as $post) {
		        wp_delete_post($post->ID, true);
		    }
		}
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
	public function get_project_media($post_id = null, $mime = null, $type = null) {
		$projects = new Projects();
		
		// default id
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		
		// get all media
		$args = array( 
			'post_type' => 'attachment', 
			'post_status' => 'inherit', 
			'post_parent' => $post_id, 
			'numberposts' => -1, 
			'order' => 'ASC',
			'orderby' => 'menu_order'
		); 
		
		// restrict mime
		if(!empty($mime)) {
			$args['post_mime_type'] = $mime;
		}
		
		// get all the attachments
		$attachments = get_children($args);
		
		// get the post thumbnail id 
		$post_thumbnail_id = get_post_thumbnail_id($post_id);
				
		// add the default size to the attachments
		foreach($attachments as $attachment) {		
			// set the default properties
			if($this->is_web_image($attachment->post_mime_type)) {		
				$attachment->default_size = $projects->get_project_meta('default_image_size', $attachment->ID);
			} else {
				$attachment->default_size = null;
			}
			$attachment->embed_url = $projects->get_project_meta('embed_url', $attachment->ID);
			//$attachment->embed_thumbnail_id = $projects->get_project_meta('embed_thumbnail_id', $attachment->ID);

			// filter the images
			if(!empty($type) && $type == Projects_Media::$media_type_featured) {
				// remove all except the post thumbnail from return value
				if($post_thumbnail_id != $attachment->ID) {
					unset($attachments[$attachment->ID]);
				}
			} else if(!empty($type) && $type == Projects_Media::$media_type_content) {
				// remove the post thumbnail from return value
				if($post_thumbnail_id == $attachment->ID) {
					unset($attachments[$attachment->ID]);
				}
			}
		} 

		return $attachments;
	}

	/**
	 * Get the featured media
	 */
	public function get_project_featured_media($post_id = null) {		
		$attachments = $this->get_project_media($post_id, 'image', Projects_Media::$media_type_featured);
		
		// set the first key instead of an array
		if(!empty($attachments)) {
			foreach($attachments as $attachment) {
				$attachment = $attachment;
				break;
			}
		} else {
			$attachment = null;
		}
		
		return $attachment;
	}
	
	/**
	 * Get the content media
	 */
	public function get_project_content_media($post_id = null, $mime = null) {		
		$attachments = $this->get_project_media($post_id, $mime, Projects_Media::$media_type_content);

		return $attachments;
	}
	
}
}

?>