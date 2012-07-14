<?php

/**
 * Admin class
 */
if (!class_exists('Projects_Writepanel')) {
class Projects_Writepanel {
	
	public $type_featured_media;
	public $type_media;
	public $projects;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type_featured_media = 'featured';
		$this->type_media = 'gallery';
		$this->projects = new Projects();
	}

	/**
	 * Load the class hooks
	 */
	public function load() {
		add_action('admin_init', array($this, 'hook_admin'));
	}
	
	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {		
		add_action('admin_head-post.php', array($this, 'remove_insert_media_buttons'));
		add_action('admin_head-post-new.php', array($this, 'remove_insert_media_buttons'));
		add_action('add_meta_boxes', array($this, 'add_boxes'));
		add_action('add_meta_boxes', array($this, 'remove_boxes'), 20);
		add_filter('attachment_fields_to_edit', array($this, 'edit_media_options'), 15, 2);
		add_filter('attachment_fields_to_save', array($this, 'save_media_options'), 15, 2);
		add_filter('media_upload_tabs', array($this, 'remove_media_tabs'));
		add_action('wp_ajax_load_media_list', array($this, 'load_media_list_ajax'));
		add_action('wp_ajax_add_award_group', array($this, 'add_award_group_ajax'));
		add_filter('upload_mimes', array($this, 'add_mime_types'));

		add_action('save_post', array($this, 'save_box_data'));
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
	 * Remove the media uploader tabs
	 */
	public function remove_media_tabs($tabs) {
	    if(isset($_REQUEST['post_id'])) {
	        $post_type = get_post_type($_REQUEST['post_id']);
	        if($post_type == Projects::$post_type) {
	            unset($tabs['type_url']);
	            unset($tabs['library']);
	            $tabs['gallery'] = __('Project Media', 'projects');
	        }
	    }
	    return $tabs;
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
		
		// add a thumbnail check field
		$meta = $this->projects->get_project_meta('featured_media', $post->ID);		
		$form_fields['projects_featured_media']['label'] = __('Featured Media', 'projects');
		$form_fields['projects_featured_media']['input'] = 'html';
		$form_fields['projects_featured_media']['html'] = '<label><input type="checkbox" name="attachments[' . $post->ID . '][projects_featured_media]" value="1" ' . checked($meta, 1, false) . ' /> ' . __('Use as featured media', 'projects') . '</label>';
		
		// add a custom image size field
		if(strpos($post->post_mime_type, 'image') !== false) {
			$html = '';
			$meta = $this->projects->get_project_meta('default_image_size', $post->ID);
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
  		
  		if(empty($attachment['projects_featured_media'])) {
			delete_post_meta($post['ID'], '_projects_featured_media');
		} else {
			add_post_meta($post['ID'], '_projects_featured_media', $attachment['projects_featured_media'], true ) or update_post_meta($post['ID'], '_projects_featured_media', $attachment['projects_featured_media']);
  		}
		
		return $post;
	}

	/**
	 * Add the meta boxes
	 */
	public function add_boxes() {			
		add_meta_box('projects-featured-media-box', __('Featured Media', 'projects'), array($this, 'create_box_featured_media'), Projects::$post_type, 'normal', 'default');
		add_meta_box('projects-gallery-media-box', __('Media', 'projects'), array($this, 'create_box_gallery_media'), Projects::$post_type, 'normal', 'default');
		add_meta_box('projects-general-box', __('General', 'projects'), array($this, 'create_box_general'), Projects::$post_type, 'side', 'default');
		add_meta_box('projects-color-box', __('Color', 'projects'), array($this, 'create_box_color'), Projects::$post_type, 'side', 'default');
		add_meta_box('projects-location-box', __('Location', 'projects'), array($this, 'create_box_location'), Projects::$post_type, 'side', 'default');
	
		// add the award box when the taxonomy exists
		$taxonomy = $this->projects->get_internal_name('award');

		if(taxonomy_exists($taxonomy)) {
			add_meta_box('projects-awards-box', __('Awards', 'projects'), array($this, 'create_box_awards'), Projects::$post_type, 'side', 'default');
		}
	}
	
	/**
	 * Remove the meta boxes
	 */
	public function remove_boxes() {
		// remove the default award box when the taxonomy exists
		$taxonomy = $this->projects->get_internal_name('award');

		if(taxonomy_exists($taxonomy)) {
			remove_meta_box('tagsdiv-' . $taxonomy, Projects::$post_type, 'side');
			remove_meta_box($taxonomy . 'div', Projects::$post_type, 'side');
		}
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_location() {
		global $post;
		
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
		?>
		<?php 
			$lat = $this->projects->get_project_meta('lat');
			$lng = $this->projects->get_project_meta('lng');
			$zoom = 15;
		?>
		<?php if(!empty($lat) && !empty($lng)) : ?>
		<div class="map">
			<a href="http://maps.google.com/maps?hl=de&z=<?php echo $zoom; ?>&q=<?php echo $lat; ?>,<?php echo $lng; ?>&sll=<?php echo $lat; ?>,<?php echo $lng; ?>" target="_blank" title="<?php _e('View on Google Maps', 'toolbox-vernissage'); ?>">
				<img src="http://maps.google.com/maps/api/staticmap?sensor=false&size=320x320&zoom=<?php echo $zoom; ?>&markers=<?php echo $lat; ?>,<?php echo $lng; ?>" />
			</a>
		</div>
		<?php endif; ?>
		<div class="location">
			<p class="form-fieldset"><label><span><?php _e('First Name', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[first_name]" value="<?php echo $this->projects->get_project_meta('first_name'); ?>" title="<?php _e('First Name', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Last Name', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[last_name]" value="<?php echo $this->projects->get_project_meta('last_name'); ?>" title="<?php _e('Last Name', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Company Name', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[company_name]" value="<?php echo $this->projects->get_project_meta('last_name'); ?>" title="<?php _e('Company Name', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Address', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[address]" value="<?php echo $this->projects->get_project_meta('address'); ?>" title="<?php _e('Address', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Postal Code', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[postal_code]" value="<?php echo $this->projects->get_project_meta('postal_code'); ?>" title="<?php _e('Code', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('City', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[city]" value="<?php echo $this->projects->get_project_meta('city'); ?>" title="<?php _e('City', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Country', 'projects'); ?></span></label><select name="projects[country]">
				<?php 
				$countries = new Projects_Countries();
				$option = get_option('projects_selected_country'); 
				?>
				<?php foreach($countries->world as $code => $name) : ?>
					<option value="<?php echo $code; ?>" <?php selected($code, $option); ?>><?php printf(__('%s', 'projects'), $name); ?></option>
				<?php endforeach; ?>
			</select></p>
			<input type="hidden" name="projects[lat]" value="<?php echo $lat; ?>">
			<input type="hidden" name="projects[lng]" value="<?php echo $lng; ?>">
		</div>
		<?php
	}

	/**
	 * Create the box content
	 */
	public function create_box_awards() {		
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_award_nonce');

  		// get the meta
  		$metas = $this->projects->get_project_meta('awards');
  		$index = 0;
  		?>
		<div class="award-list" id="projects-award-list">
			<?php 
			if(!empty($metas)) {
				foreach($metas as $meta) {
					$this->create_award_group_list_item($index, $meta);
					$index++;
				}
			}
			?>
		</div>
		<div class="award-footer">
			<input type="hidden" id="projects_award_index" value="<?php echo $index; ?>">
			<a href="#" id="projects-add-award-group"><?php _e('Add Award', 'projects'); ?></a>
			<img src="<?php echo get_admin_url(); ?>images/wpspin_light.gif" id="projects-award-loader" />
		</div>
		<?php
	}
	
	/**
	 * Create an award group item
	 */
	public function create_award_group_list_item($index, $meta = null) {
		// Get the defined terms
		$taxonomy = $this->projects->get_internal_name('award');
		$slugs = array(
			'name',
			'year',
			'rank',
			'category'
		);  
		
		// create the title
		$title_placeholder = __('Untitled', 'projects');
		if(isset($meta) && isset($meta['name'])) {
			$title_term = get_term($meta['name'], $taxonomy); 
			$title = $title_term->name;
		} else {
			$title = $title_placeholder;
		}
		?>
		<div class="award-group">
			<div class="award-group-options"><h4 title="<?php echo $title_placeholder; ?>"><?php echo $title; ?></h4><a href="#" class="remove-award-group"><?php _e('Delete', 'projects'); ?></a></div>
			<div class="award-group-fields">
			<?php foreach($slugs as $slug) : ?>
				<?php 
				$term = get_term_by('slug', $slug, $taxonomy); 
				$args = array(
					'parent' => $term->term_id,
					'hide_empty' => false
				);
				$child_terms = get_terms($taxonomy, $args);
				$selected_child_term_id = (isset($meta) && isset($meta[$term->slug])) ? $meta[$term->slug] : null;
				?>
				<select name="projects[awards][<?php echo $index; ?>][<?php echo $term->slug; ?>]" class="award-select-<?php echo $term->slug; ?>">
					<option value=""><?php printf(__('No %s', 'projects'), $term->name); ?></option>
					<?php foreach($child_terms as $child_term) : ?>
						<option value="<?php echo $child_term->term_id; ?>" <?php selected($selected_child_term_id, $child_term->term_id, true); ?>><?php echo $child_term->name; ?></option>
					<?php endforeach; ?>
				</select>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Load the award item with ajax
	 */
	public function add_award_group_ajax() {
	    // Verifiy post data and nonce
		if(empty($_POST) || $_POST['index'] === null || empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], Projects::$plugin_basename)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		// create an empty item
		$this->create_award_group_list_item($_POST['index']);
			
		exit;
	}
	
	/**
	 * Create the box color
	 */
	public function create_box_color() {
		?>
		<p class="form-fieldset"><label><span><?php _e('Background', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors code" name="projects[background_color]" value="<?php echo $this->projects->get_project_meta('background_color'); ?>" title="<?php _e('Background', 'projects'); ?>"></span></p>
		<p class="form-fieldset"><label><span><?php _e('Text', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors code" name="projects[text_color]" value="<?php echo $this->projects->get_project_meta('text_color'); ?>" title="<?php _e('Text', 'projects'); ?>"></span></p>
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
		<p class="form-fieldset"><label><span><?php _e('Date', 'projects'); ?></span></label>
			<span class="input-group">
				<select class="select-date" name="projects[month]">
					<?php 
					$count = 1;
					$month_meta = $this->projects->get_project_meta('month');
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
					$year_meta = $this->projects->get_project_meta('year');
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
		<p class="form-fieldset"><label><span><?php _e('Status:', 'projects'); ?></span></label><select name="projects[status]">
			<?php foreach($stati as $status) : ?>
				<?php if($this->projects->is_internal_name($status->name)) : ?>
			<option value="<?php echo $status->name; ?>" <?php selected($status->name, $this->projects->get_project_meta('status')); ?>><?php echo $status->label; ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select></p>
		<?php $website = $this->projects->get_project_meta('website'); ?>
		<p class="form-fieldset"><label><span><?php _e('Reference No.', 'projects'); ?></span></label><input type="text" class="regular-text code" name="projects[reference]" value="<?php echo $this->projects->get_project_meta('reference'); ?>" title="<?php _e('Reference No.', 'projects'); ?>"></p>
		<p class="form-fieldset"><label><span><?php _e('Website', 'projects'); ?></span></label><input type="text" class="regular-text code" name="projects[website]" value="<?php echo $website; ?>" title="<?php _e('Address', 'projects'); ?>"><?php if(!empty($website)) : ?><a href="<?php echo $website; ?>" target="_blank" class="external"></a><?php endif; ?></p>
		<?php
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_gallery_media() {
		global $post;
		
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_media_nonce');
  		
  		?>
		<ul class="projects-media-list hide-if-no-js" id="projects-gallery-media-list">
		<?php $this->create_media_list(null, $this->type_media); ?>
		</ul>
		<p class="hide-if-no-js"><a href="media-upload.php?post_id=<?php echo $post->ID; ?>&amp;TB_iframe=1" id="projects-gallery-media-add" class="thickbox projects-media-add"><?php _e('Manage Media', 'projects'); ?></a></p>
		<?php
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_featured_media() {
		global $post;
				
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_media_nonce');
  		
  		?>
		<ul class="projects-media-list hide-if-no-js" id="projects-featured-media-list">
		<?php $this->create_media_list(null, $this->type_featured_media); ?>
		</ul>
		<p class="hide-if-no-js"><a href="media-upload.php?post_id=<?php echo $post->ID; ?>&amp;TB_iframe=1" id="projects-featured-media-add" class="thickbox projects-media-add"><?php _e('Manage featured Media', 'projects'); ?></a></p>
		<?php
	}
	
	/**
	 * Create the media list
	 */
	public function create_media_list($post_id = null, $type = null) {
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		
		$attachments = $this->get_project_media($post_id, null, $type);
		
		if ($attachments) {
			foreach($attachments as $attachment) {
				$mime = explode('/', strtolower($attachment->post_mime_type)); 
				$meta = wp_get_attachment_metadata($attachment->ID); ?>
				<li class="projects-media-item mime-<?php echo $mime[1]; ?> <?php if($mime[0] != 'image') : ?>mime-placeholder<?php endif; ?>">
					<span class="media-options"></span>
					<span class="media-content">
					<?php if($mime[0] == 'image') : ?>
						<?php $image = wp_get_attachment_image($attachment->ID, 'project-media-manager'); ?>  		   
						<?php echo $image; ?>
					<?php else : ?>
						<span class="media-type"><?php echo basename(get_attached_file($attachment->ID)); ?></span>
					<?php endif; ?>
					</span>
				</li>
				<?php
			}
		}
	}
	
	/**
	 * Load the media list with an ajax callback
	 */
	public function load_media_list_ajax() {
	    // Verifiy post data and nonce
		if(empty($_POST) || empty($_POST['post_id']) || empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], Projects::$plugin_basename)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		// Create the list
		$this->create_media_list($_POST['post_id'], $_POST['type']);
	    
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
		$mimes = wp_parse_args(array(
			'csv' => 'text/csv'
		), $mimes);
		return $mimes;
	}

	/**
	 * Get the media
	 */
	public function get_project_media($post_id = null, $mime = null, $type = null) {
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
		
		// get the post thumbnail id
		//$post_thumbnail_id = get_post_thumbnail_id($post_id);

		// add the default size to the attachments
		foreach($attachments as $attachment) {
			$size = null;
			$meta = null;		
			
			// add the default size
			if($this->is_web_image($attachment->post_mime_type)) {		
				$size = $this->projects->get_project_meta('default_image_size', $attachment->ID);
				$meta = $this->projects->get_project_meta('featured_media', $attachment->ID);		
			}
			
			// set the default size property
			$attachment->default_size = $size;
			
			// remove images
			if(!empty($type) && $type == $this->type_featured_media) {
				// remove all gallery media
				//if($post_thumbnail_id != $attachment->ID && empty($meta)) {
				if(empty($meta)) {
					unset($attachments[$attachment->ID]);
				}
			} else if(!empty($type) && $type == $this->type_media) {
				// remove all featured media
				//if($post_thumbnail_id == $attachment->ID || !empty($meta)) {
				if(!empty($meta)) {
					unset($attachments[$attachment->ID]);
				}
			}
		} 
		
		return $attachments;
	}

	/**
	 * Get the featured media
	 */
	public function get_project_featured_media($post_id = null, $mime = null) {
		$attachments = $this->get_project_media($post_id, $mime, $this->type_featured_media);
		
		return $attachments;
	}
	
	/**
	 * Get the gallery media
	 */
	public function get_project_gallery_media($post_id = null, $mime = null) {		
		$attachments = $this->get_project_media($post_id, $mime, $this->type_media);

		return $attachments;
	}

	/**
	 * Get a project taxonomy
	 */	
	public function get_project_taxonomy($post_id, $key, $hierarchical = true, $args = null) {		
		$taxonomy = $this->projects->get_internal_name($key);
		$terms = wp_get_object_terms($post_id, $taxonomy, $args); 
		
		if(!isset($terms->errors) && sizeof($terms) > 0) {
			// return the flat tree
			if(!$hierarchical) {
				return $terms;
			}
			
			// return the hierarchical tree		
			$childs = array();
		
			// find all childs
			foreach($terms as $term) {
				$childs[$term->parent][] = $term;
			}
		
			// cascade all childs
			foreach($terms as $term) {
				if (isset($childs[$term->term_id])) {
					$term->childs = $childs[$term->term_id];
				}
			}
		
			// flat the childs tree by its base node
			$tree = $childs[0];
			
			return $tree;
		}
	
		return;
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
			if(!empty($_POST['projects']['address']) && !empty($_POST['projects']['postal_code']) && !empty($_POST['projects']['city'])) {
				$address = urlencode($_POST['projects']['address'] . ', ' . $_POST['projects']['postal_code'] . ' ' . $_POST['projects']['city']);
				$url = 'http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=' . $address;
				$curl = curl_init();
				
				// query the api and read the json file
				curl_setopt ($curl, CURLOPT_URL, $url);
				curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 5);
				$json = curl_exec($curl);
				curl_close($curl);
				
				$json = json_decode($json);
				
				// set lat lng
				if(!empty($json->results)) {
					$_POST['projects']['lat'] = $json->results[0]->geometry->location->lat;
					$_POST['projects']['lng'] = $json->results[0]->geometry->location->lng;
				}
			} else {
				$_POST['projects']['lat'] = null;
				$_POST['projects']['lng'] = null;
			}
			
			// set the terms for the awards
			$taxonomy = $this->projects->get_internal_name('award');
			if(empty($_POST['projects']['awards'])) {
				$_POST['projects']['awards'] = '';
				
				// remove all related terms
				wp_set_object_terms($post_id, null, $taxonomy, false);
			} else {
				$awards = $_POST['projects']['awards'];
				$award_ids = array();
				
				// clean up the awards array
				foreach($awards as $key => $value) {
					if(empty($value['name'])) {
						// remove all awards that have no name set
						unset($_POST['projects']['awards'][$key]);
					} else {
						// add all ids to a list to relate them to the post
						foreach($value as $key => $id) {
							if(!empty($id)) {
								// add the id to the relation table
								array_push($award_ids, (int)$id);
							}
						}
					}
				}
				
				// relate terms
				wp_set_object_terms($post_id, $award_ids, $taxonomy, false);
			}

			// save the meta
			foreach($_POST['projects'] as $key => $value) {
				// save the key, including empty keys too, 
				// otherwise wordpress can't query them.
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