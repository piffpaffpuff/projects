<?php

/**
 * Admin class
 */
if (!class_exists('Projects_Writepanel')) {
class Projects_Writepanel {

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Load the class
	 */
	public function load() {
		add_action('admin_init', array($this, 'load_admin_hooks'));
	}
		
	/**
	 * Load the admin hooks
	 */
	public function load_admin_hooks() {		
		add_action('admin_head-post.php', array($this, 'remove_insert_media_buttons'));
		add_action('admin_head-post-new.php', array($this, 'remove_insert_media_buttons'));
		add_action('add_meta_boxes', array($this, 'add_boxes'));
		add_filter('attachment_fields_to_edit', array($this, 'edit_media_options'), 15, 2);
		add_filter('attachment_fields_to_save', array($this, 'save_media_options'), 15, 2);
		add_filter('media_upload_tabs', array($this, 'remove_media_tabs'));
		add_action('wp_ajax_add_media_list', array($this, 'add_media_list_ajax'));
		add_filter('upload_mimes', array($this, 'add_mime_types'));

		add_action('save_post', array($this, 'save_box_data'));
	}
		
	/**
	 * Remove the media buttons
	 */
	public function remove_insert_media_buttons() {
		remove_action('media_buttons', 'media_buttons');
	}

	/**
	 * Remove the media uploader tabs
	 */
	public function remove_media_tabs($tabs) {
	    if(isset($_REQUEST['post_id'])) {
	        $post_type = get_post_type($_REQUEST['post_id']);
	        if($post_type == Projects::$post_type) {
      			unset($tabs['library']);
	            unset($tabs['type_url']);
	        }
	    }
	    return $tabs;
	}

	/**
	 * Set the media fields
	 */
	public function edit_media_options($form_fields, $post) {			
		$form_fields['image_alt']['value'] = '';
		$form_fields['image_alt']['input'] = 'hidden';
		
		$form_fields['post_excerpt']['value'] = '';
		$form_fields['post_excerpt']['input'] = 'hidden';
		
		$form_fields['post_content']['value'] = '';
		$form_fields['post_content']['input'] = 'hidden';
		
		$form_fields['url']['value'] = '';
		$form_fields['url']['input'] = 'hidden';
		
		$form_fields['align']['value'] = 'left';
		$form_fields['align']['input'] = 'hidden';
		
		$form_fields['image-caption']['value'] = 'caption';
		$form_fields['image-caption']['input'] = 'hidden';
		
		$form_fields['image-size']['value'] = 'full';
		$form_fields['image-size']['input'] = 'hidden';
				
		// add a custom image size field
		if(strpos($post->post_mime_type, 'image') !== false) {
			$html = '';
			$meta_size = Projects::get_meta_value('default_image_size', $post->ID);
			$image_sizes = get_intermediate_image_sizes();
			$image_sizes[] = 'full';
			
			if(empty($meta_size)) {
				$meta_size = get_option('projects_default_image_size');
			}
			
			// go through all sizes and generate the fields
			foreach($image_sizes as $image_size) {
				$css_id = 'projects-default-image-size-' . $image_size . '-' . $post->ID;
				$form_name = 'attachments['.$post->ID.'][projects_default_image_size]';
				$checked= '';
				
				// check if the size is selectable
				$downsize = image_downsize($post->ID, $image_size);
				if(!empty($downsize[3]) || $image_size == 'full') {
					$enabled = true;
				} else {
					$enabled = false;
				}
				
				// check if the current size is the saved size
				if ($image_size == $meta_size) {
					// select the size
					if($enabled) {
						$checked = ' checked="checked"';
					} else {
						$meta_size = '';
					}
				} elseif(empty($meta_size) && $enabled && $image_size != 'thumbnail') {
					// if it is not enabled, default to the first available size that's bigger than a thumbnail
					$meta_size = $image_size;
					$checked = ' checked="checked"';
				}
				
				// output the html
				$html .= '<div class="image-size-item"><input type="radio" '.disabled($enabled, false, false).' name="'.$form_name.'" id="'.$css_id.'" value="'.$image_size.'" '.$checked.' />';
				$html .= '<label for="'.$css_id.'">'.ucfirst($image_size).'</label>';
				
				// only show the dimensions if that choice is available
				if($enabled) {
					$html .= '<label for="'.$css_id.'" class="help">'.sprintf('(%d&nbsp;&times;&nbsp;%d)', $downsize[1], $downsize[2]).'</label>';
				}
	
				$html .= '</div>';
			}
			
			$form_fields['projects_default_image_size']['label'] = __('Size', 'projects');
			$form_fields['projects_default_image_size']['input'] = 'html';
			$form_fields['projects_default_image_size']['html'] = $html;
		}
			
		return $form_fields;
	}
	
	/**
	 * Save the media fields
	 */
	public function save_media_options($post, $attachment) {
		// $attachment part of the form $_POST ($_POST[attachments][postID])
		// $post attachments wp post array - will be saved after returned
		// $post['post_type'] == 'attachment'
		if(empty($attachment['projects_default_image_size'])) {
			delete_post_meta($post['ID'], '_projects_default_image_size');
		} else {
			add_post_meta($post['ID'], '_projects_default_image_size', $attachment['projects_default_image_size'], true ) or update_post_meta($post['ID'], '_projects_default_image_size', $attachment['projects_default_image_size']);
  		}
		
		return $post;
	}

	/**
	 * Add the meta boxes
	 */
	public function add_boxes() {			
		add_meta_box('projects-media-box', __('Media', 'projects'), array($this, 'create_box_media'), Projects::$post_type, 'normal', 'default');
		add_meta_box('projects-general-box', __('General', 'projects'), array($this, 'create_box_general'), Projects::$post_type, 'side', 'default');
		add_meta_box('projects-location-box', __('Location', 'projects'), array($this, 'create_box_location'), Projects::$post_type, 'side', 'default');
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_location() {
		global $post;
		
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
		?>
		<div id="projects-location-map-wrap">
			<div id="projects-location-map"></div>
		</div>
		<p class="form-field"><label><span><?php _e('First Name:', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[first_name]" value="<?php echo Projects::get_meta_value('first_name'); ?>" title="<?php _e('Name', 'projects'); ?>"></p>
		<p class="form-field"><label><span><?php _e('Last Name:', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[last_name]" value="<?php echo Projects::get_meta_value('last_name'); ?>" title="<?php _e('Name', 'projects'); ?>"></p>
		<p class="form-field"><label><span><?php _e('Address:', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[address]" value="<?php echo Projects::get_meta_value('address'); ?>" title="<?php _e('Address', 'projects'); ?>"></p>
		<p class="form-field"><label><span><?php _e('Postal Code:', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[postal_code]" value="<?php echo Projects::get_meta_value('postal_code'); ?>" title="<?php _e('Code', 'projects'); ?>"></p>
		<p class="form-field"><label><span><?php _e('City:', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[city]" value="<?php echo Projects::get_meta_value('city'); ?>" title="<?php _e('City', 'projects'); ?>"></p>
		<p class="form-field"><label><span><?php _e('Country:', 'projects'); ?></span></label><select name="projects[country]">
			<?php $country = Projects::get_meta_value('country'); ?>
			<option value="CH" <?php selected('CH', $country); ?>><?php _e('Switzerland', 'projects'); ?></option>
			<option value="LI" <?php selected('LI', $country); ?>><?php _e('Liechtenstein', 'projects'); ?></option>
			<option value="DE" <?php selected('DE', $country); ?>><?php _e('Germany', 'projects'); ?></option>
			<option value="AT" <?php selected('AT', $country); ?>><?php _e('Austria', 'projects'); ?></option>
		</select></p>
		<input type="hidden" name="projects[lat]" value="<?php echo Projects::get_meta_value('lat'); ?>">
		<input type="hidden" name="projects[lon]" value="<?php echo Projects::get_meta_value('lon'); ?>">
		<?php
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_general() {
		global $post;

		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
		
		?>
		<p class="form-field"><label><span><?php _e('Date:', 'projects'); ?></span></label>
			<span class="input-group">
				<select class="select-date" name="projects[month]">
					<?php 
					$count = 1;
					$month_meta = Projects::get_meta_value('month');
					if(empty($month_meta)) {
						$month_meta = date_i18n('n');
					}
					?>
					<?php while($count <= 12) : ?>
					<option value="<?php echo $count; ?>" <?php selected($count, $month_meta); ?>><?php echo date_i18n('m', mktime(0, 0, 0, $count, 1)); ?>-<?php echo date_i18n('M', mktime(0, 0, 0, $count, 1)); ?></option>						
					<?php $count++; ?>
					<?php endwhile; ?>
				</select>
				<select class="select-date" name="projects[year]">
					<?php 
					$count = 0;
					$year = date_i18n('Y');
					$year_meta = Projects::get_meta_value('year');
					?>
					<?php while($count <= 100) : ?>
					<option value="<?php echo $year - $count; ?>" <?php selected($year - $count, $year_meta); ?>><?php echo $year - $count; ?></option>						
					<?php $count++; ?>
					<?php endwhile; ?>
				</select>
			</span>
		</p>
		<?php
		$args = array(
			'_builtin' => false
		);
		$stati = get_post_stati($args, 'objects');
		?>
		<p class="form-field"><label><span><?php _e('Status:', 'projects'); ?></span></label><select name="projects[status]">
			<?php foreach($stati as $status) : ?>
				<?php if(strrpos($status->name, Projects::$post_type . '_') !== false) : ?>
			<option value="<?php echo $status->name; ?>" <?php selected($status->name, Projects::get_meta_value('status')); ?>><?php echo $status->label; ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select></p>
		<p class="form-field"><label><span><?php _e('Reference No.:', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[reference]" value="<?php echo Projects::get_meta_value('reference'); ?>" title="<?php _e('Reference No.', 'projects'); ?>"></p>
		<p class="form-field"><label><span><?php _e('Website:', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[website]" value="<?php echo esc_url(Projects::get_meta_value('website')); ?>" title="<?php _e('Address', 'projects'); ?>"></p>
		<p class="form-field"><label><span><?php _e('Background:', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors" name="projects[background_color]" value="<?php echo Projects::get_meta_value('background_color'); ?>" title="<?php _e('Background', 'projects'); ?>"></span></p>
		<p class="form-field"><label><span><?php _e('Text:', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors" name="projects[text_color]" value="<?php echo Projects::get_meta_value('text_color'); ?>" title="<?php _e('Text', 'projects'); ?>"></span></p>
		<?php
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_media() {
		global $post;
		
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_media_nonce');
  		
  		?>
		<ul class="hide-if-no-js" id="projects-media-list">
		</ul>
		<p class="hide-if-no-js"><a href="media-upload.php?post_id=<?php echo $post->ID; ?>&amp;TB_iframe=1&amp;width=640&amp;height=505" id="projects-media-add" class="thickbox"><?php _e('Add Media', 'projects'); ?></a></p>
		<?php
	}
		
	/**
	 * Create the media list with an ajax callback
	 */
	public function add_media_list_ajax() {
	    // Verifiy post data and nonce
		if(empty($_POST) || empty($_POST['post_id']) || empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], Projects::$plugin_basename)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		// Create the list
		$post_id = $_POST['post_id'];
		$attachments = $this->get_media($post_id);
		$post_thumbnail_id = get_post_thumbnail_id($post_id);

		if ($attachments) {
			foreach($attachments as $attachment) {
				if($attachment->ID != $post_thumbnail_id) {
					$mime = explode('/', strtolower($attachment->post_mime_type)); 
					$meta = wp_get_attachment_metadata($attachment->ID); ?>
					<li class="projects-media-item mime-<?php echo $mime[1]; ?> <?php if($mime[0] != 'image') : ?>mime-placeholder<?php endif; ?>">
						<span class="media-options"></span>
						<span class="media-content">
						<?php if($mime[0] == 'image') : ?>
							<?php $src = wp_get_attachment_image_src($attachment->ID, array(200, 200)); ?>  		   
							<img id="projects-media-<?php echo $attachment->ID; ?>" alt="<?php echo $attachment->ID; ?>" src="<?php echo $src[0]; ?>" width="<?php echo $src[1]; ?>" height="<?php echo $src[2]; ?>" />
						<?php else : ?>
							<span class="media-type"><span class="media-name"><?php echo $attachment->post_title; ?></span><span class="media-suffix"><?php echo $mime[1]; ?></span></span>
						<?php endif; ?>
						</span>
					</li>
					<?php
				}
			}
		}
	    
		exit;
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
	 * Register the mime type
	 */
	public function add_mime_types($mimes) {
		$mimes = array_merge($mimes, array(
			'csv' => 'text/csv'
		));
		return $mimes;
	}

	/**
	 * Get the media
	 */
	public function get_media($post_id = null, $mime = null) {
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
		
		// the the attachments
		$attachments = get_children($args);
		
		// add the default size to the attachments
		foreach($attachments as $attachment) {
			$size = null;
			
			if($this->is_web_image($attachment->post_mime_type)) {		
				$size = Projects::get_meta_value('default_image_size', $attachment->ID);

				// fall back to options setting
				if(empty($size)) {
					$size = get_option('projects_default_image_size');
				}
			}
			
			// add the default size to the attachment
			$attachment->default_size = $size;
		} 

		return $attachments;
	}
	
	/**
	 * Save the box data
	 */
	public function save_box_data() {
		global $post_id;

		// verify this came from the our screen and with 
		// proper authorization, because save_post can be 
		// triggered at other times
		if(empty($_POST['projects_nonce']) || !wp_verify_nonce( $_POST['projects_nonce'], Projects::$plugin_basename)) {
			return $post_id;
		}
  
  		// verify if this is an auto save routine. If it is 
  		// our form has not been submitted, so we dont want 
  		// to do anything
  		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    		return $post_id;
    	}
  		
  		// Check permissions
		if($_POST['post_type'] ==  'page') {
			if(!current_user_can('edit_page', $post_id)) {
				return $post_id;
			}
		} else {
			if(!current_user_can('edit_post', $post_id)) {
				return $post_id;
			}
		}
		
		// we're authenticated: Now we need to find and 
		// save the data.

		// save, update or delete the custom field of the post.
		// split all array keys and save them as unique 
		// meta to make them queryable by wordpress.
		if(isset($_POST['projects'])) {
			// create a date entry to make querying by month or year easy 
			$_POST['projects']['date'] = mktime(0, 0, 0, $_POST['projects']['month'], 1, $_POST['projects']['year']);
			
			// query map service to geocode the location
			$_POST['projects']['lat_lng'] = '';
			
			if(!empty($_POST['projects']['address']) && !empty($_POST['projects']['postal_code']) && !empty($_POST['projects']['city'])) {
				$address = urlencode($_POST['projects']['address'] . ', ' . $_POST['projects']['postal_code'] . ' ' . $_POST['projects']['city']);
				$url = 'http://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . $address;
				$curl = curl_init();
				
				// query the api and read the json file
				curl_setopt ($curl, CURLOPT_URL, $url);
				curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 5);
				$json = curl_exec($curl);
				curl_close($curl);
				
				$json = json_decode($json);
				
				// set lat lng
				if(!empty($json)) {
					$_POST['projects']['lat'] = $json[0]->lat;
					$_POST['projects']['lon'] = $json[0]->lon;
				}
			} 
			
			// save the meta
			foreach($_POST['projects'] as $key => $value) {
				// save the key, including empty keys too, 
				// otherwise wordpres won't query them.
				// this is probably fixed in wordpress 3.4
				update_post_meta($post_id, '_projects_' . $key, $value);
				/*
				if(empty($value)) {		
					delete_post_meta($post_id, '_projects_' . $key);
				} else {
					update_post_meta($post_id, '_projects_' . $key, $value);
				}
				*/
			}
		}
	}
	
}
}
?>