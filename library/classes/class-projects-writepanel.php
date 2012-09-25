<?php

/**
 * Admin class
 */
if (!class_exists('Projects_Writepanel')) {
class Projects_Writepanel {
	
	public $type_featured_media;
	public $type_media;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type_featured_media = 'featured';
		$this->type_media = 'gallery';
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
		add_filter('media_upload_media_url', array($this, 'add_tab_media_url'));
		add_action('wp_ajax_load_media_list', array($this, 'load_media_list_ajax'));
		add_action('wp_ajax_add_taxonomy_group_preset', array($this, 'add_taxonomy_group_preset_ajax'));
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
	            unset($tabs['gallery']);
				//$tabs['media_url'] = __('From URL', 'projects');
	            $tabs['gallery'] = __('Project Media', 'projects');
	        }
	    }
	    return $tabs;
	}
	
	/**
	 * Add a media uploader tab
	 */
	public function add_tab_media_url() {
		return wp_iframe(array($this, 'media_tab_media_url'));
	}
	
	/**
	 * Create the media url tab. The function name
	 * has to start with media to poperly enqueue
	 * scripts and styles.
	 */
	public function media_tab_media_url() {
	    media_upload_header();
	    ?>
	    <p>Not implemented</p>
	    <?php
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
		$projects = new Projects();
		$meta = $projects->get_project_meta('featured_media', $post->ID);		
		$form_fields['projects_featured_media']['label'] = __('Featured Media', 'projects');
		$form_fields['projects_featured_media']['input'] = 'html';
		$form_fields['projects_featured_media']['html'] = '<label><input type="checkbox" name="attachments[' . $post->ID . '][projects_featured_media]" value="1" ' . checked($meta, 1, false) . ' /> ' . __('Use as featured media', 'projects') . '</label>';
		
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
		add_meta_box('projects-location-box', __('Locate', 'projects'), array($this, 'create_box_location'), Projects::$post_type, 'side', 'default');
		add_meta_box('projects-color-box', __('Color', 'projects'), array($this, 'create_box_color'), Projects::$post_type, 'side', 'default');
		
		// create the meta boxes for the taxonomy groups
		$projects_taxonomy_group = new Projects_Taxonomy_Group();
		$taxonomy_groups = $projects_taxonomy_group->get_added_taxonomy_group();
		foreach($taxonomy_groups as $taxonomy_group) {			
			// send the post type to the box content function and add the box
			$args = array('taxonomy_group' => $taxonomy_group);
			add_meta_box('projects-taxonomy-group-box-' . $taxonomy_group->name, $taxonomy_group->plural_label, array($this, 'create_box_taxonomy_group'), Projects::$post_type, 'side', 'default', $args);
		}
	}
	
	/**
	 * Remove default meta boxes
	 */
	public function remove_boxes($post_type) {
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_location($post, $metabox) {
		$projects = new Projects();
		
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
		?>
		<?php 
			$lat = $projects->get_project_meta('lat');
			$lng = $projects->get_project_meta('lng');
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
			<p class="form-fieldset"><label><span><?php _e('First Name', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[first_name]" value="<?php echo $projects->get_project_meta('first_name'); ?>" title="<?php _e('First Name', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Last Name', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[last_name]" value="<?php echo $projects->get_project_meta('last_name'); ?>" title="<?php _e('Last Name', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Company Name', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[company_name]" value="<?php echo $projects->get_project_meta('last_name'); ?>" title="<?php _e('Company Name', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Address', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[address]" value="<?php echo $projects->get_project_meta('address'); ?>" title="<?php _e('Address', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Postal Code', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[postal_code]" value="<?php echo $projects->get_project_meta('postal_code'); ?>" title="<?php _e('Code', 'projects'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('City', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[city]" value="<?php echo $projects->get_project_meta('city'); ?>" title="<?php _e('City', 'projects'); ?>"></p>
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
	public function create_box_taxonomy_group($post, $metabox) {
		$projects = new Projects();
		$projects_taxonomy_group = new Projects_Taxonomy_Group();
		$taxonomy_group = $metabox['args']['taxonomy_group'];
		
		// Use nonce for verification
		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
		
		// load presets and restructure from colmun to row based array
		$presets = $projects->get_project_meta('taxonomy_group_' . $taxonomy_group->key, $post->ID);
		$rows = array();
		if(!empty($presets)) {
			$columns = $presets;			
			foreach($columns as $key => $subarr) {
				if(!empty($subarr)) {
					foreach($subarr as $subkey => $subvalue) {
						$rows[$subkey][$key] = $subvalue;
					}
				}
			}
		}
		/*
		echo('<pre>');
		print_r($presets);
		echo('</pre>');
		*/
		?>
		<input type="hidden" class="taxonomy-group-name" value="<?php echo $taxonomy_group->name; ?>">
		<?php // reset the metadata when no presets were created ?>
		<input type="hidden" name="projects[taxonomy_group_<?php echo $taxonomy_group->key; ?>]" value="" />
		<div class="taxonomy-group-list">
			<?php // build the presets list ?>
			<?php if(isset($rows)) : ?>
				<?php foreach($rows as $row) : ?>
					<?php $this->create_taxonomy_group_preset_list_item($taxonomy_group->name, $row); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="taxonomy-group-footer">
			<a href="#" class="add-preset"><?php printf(__('Add %s', 'projects'), $taxonomy_group->singular_label); ?></a>
			<img src="<?php echo get_admin_url(); ?>images/wpspin_light.gif" class="taxonomy-group-loader" />
		</div>
		<?php
	}

	/**
	 * Create a taxonomy group preset
	 */
	public function create_taxonomy_group_preset_list_item($taxonomy_group_name, $terms_by_taxonomy = null) {
		global $post;
		
		$projects_taxonomy_group = new Projects_Taxonomy_Group();
		$taxonomies = $projects_taxonomy_group->get_added_taxonomies_by_group($taxonomy_group_name);
		
		// create the title		
		$title_placeholder = __('Untitled', 'projects');
		if(!empty($terms_by_taxonomy)) {
			// get the first term of the first taxonomy
			reset($terms_by_taxonomy);
			$first_taxonomy = key($terms_by_taxonomy);
			$title_term_id = $terms_by_taxonomy[$first_taxonomy];
			
			if(!empty($title_term_id)) {
				// find the term name for the term id
				$term = get_term(intval($title_term_id), $first_taxonomy);
				$title = $term->name;
			} else {
				$title = $title_placeholder;
			}
		} else {
			$title = $title_placeholder;
		}
		?>
		<div class="preset" id="projects-taxonomy-group-preset-<?php echo $taxonomy_group_name; ?>">
			<div class="preset-options"><h4 title="<?php echo $title_placeholder; ?>"><?php echo $title; ?></h4><a href="#" class="delete-preset"><?php _e('Delete', 'projects'); ?></a></div>
			<div class="preset-fields">
				<?php $index = 1; ?>	
				<?php foreach($taxonomies as $taxonomy) : ?>
					<?php
					$selected_term_id = $terms_by_taxonomy[$taxonomy->name];
					
					// get the terms for a taxonomy
					$args = array(
						'hide_empty' => false
					);
					$terms = get_terms($taxonomy->name, $args);
					?>
					<select name="projects[taxonomy_group_<?php echo $taxonomy->group_key; ?>][<?php echo $taxonomy->name; ?>][]" class="preset-select preset-select-field-<?php echo $index; ?>">
						<option value=""><?php printf(__('No %s', 'projects'), $taxonomy->singular_label); ?></option>
						<?php foreach($terms as $term) : ?>
							<option value="<?php echo $term->term_id; ?>" <?php selected($selected_term_id, $term->term_id, true); ?>><?php echo $term->name; ?></option>
						<?php endforeach; ?>
					</select>
					<?php $index++; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Manage the term group item with ajax
	 */
	public function add_taxonomy_group_preset_ajax() {
	    // Verify post data and nonce
		if(empty($_POST) || empty($_POST['nonce']) || empty($_POST['post_id']) || empty($_POST['taxonomy_group_name']) || !wp_verify_nonce($_POST['nonce'], Projects::$plugin_basename)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		// create a preset list item
		$this->create_taxonomy_group_preset_list_item($_POST['taxonomy_group_name']);
			
		exit;
	}

	/**
	 * Create the box color
	 */
	public function create_box_color($post, $metabox) {
		$projects = new Projects();
		
		?>
		<p class="form-fieldset"><label><span><?php _e('Background', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors code" name="projects[background_color]" value="<?php echo $projects->get_project_meta('background_color'); ?>" title="<?php _e('Background', 'projects'); ?>"></span></p>
		<p class="form-fieldset"><label><span><?php _e('Text', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors code" name="projects[text_color]" value="<?php echo $projects->get_project_meta('text_color'); ?>" title="<?php _e('Text', 'projects'); ?>"></span></p>
		<?php
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_general($post, $metabox) {
		$projects = new Projects();
		
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
		?>
		<p class="form-fieldset"><label><span><?php _e('Date', 'projects'); ?></span></label>
			<span class="input-group">
				<select class="select-date" name="projects[month]">
					<?php 
					$count = 1;
					$month_meta = $projects->get_project_meta('month');
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
					$year_meta = $projects->get_project_meta('year');
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
			'hide_empty' => false
		);
		$taxonomy = $projects->get_internal_name('status');
		$terms = get_terms($taxonomy, $args);
		?>
		<p class="form-fieldset"><label><span><?php _e('Status:', 'projects'); ?></span></label><select name="projects[status]">
			<?php foreach($terms as $term) : ?>
				<?php 
					$in_term = is_object_in_term($post->ID, $taxonomy, $term->term_id);
					if(is_wp_error($in_term)) {
						$in_term = false;
					}			
				?>
				<option value="<?php echo $term->term_id; ?>" <?php selected(true, $in_term); ?>><?php echo $term->name; ?></option>
			<?php endforeach; ?>
		</select></p>
		<?php $website = $projects->get_project_meta('website'); ?>
		<p class="form-fieldset"><label><span><?php _e('Reference No.', 'projects'); ?></span></label><input type="text" class="regular-text code" name="projects[reference]" value="<?php echo $projects->get_project_meta('reference'); ?>" title="<?php _e('Reference No.', 'projects'); ?>"></p>
		<p class="form-fieldset"><label><span><?php _e('Website', 'projects'); ?></span></label><input type="text" class="regular-text code" name="projects[website]" value="<?php echo $website; ?>" title="<?php _e('Address', 'projects'); ?>"><?php if(!empty($website)) : ?><a href="<?php echo $website; ?>" target="_blank" class="external"></a><?php endif; ?></p>
		<?php
	}
	
	/**
	 * Create the box content
	 */
	public function create_box_gallery_media($post, $metabox) {		
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
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
	public function create_box_featured_media($post, $metabox) {				
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
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
		//$post_thumbnail_id = get_post_thumbnail_id($post_id);

		// add the default size to the attachments
		foreach($attachments as $attachment) {
			$size = null;
			$meta = null;		
			
			// add the default size
			if($this->is_web_image($attachment->post_mime_type)) {		
				$size = $projects->get_project_meta('default_image_size', $attachment->ID);
				$meta = $projects->get_project_meta('featured_media', $attachment->ID);		
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
		$projects = new Projects();	
		$taxonomy = $projects->get_internal_name($key);
		$terms = wp_get_object_terms($post_id, $taxonomy, $args); 
		
		if(!is_wp_error($terms) && sizeof($terms) > 0) {
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

		/* verify this came from the our screen and with 
		proper authorization, because save_post can be 
		triggered at other times. */
		if(empty($_POST['projects_nonce']) || !wp_verify_nonce( $_POST['projects_nonce'], Projects::$plugin_basename)) {
			return $post_id;
		}
  
  		/* verify if this is an auto save routine. If it 
  		is our form has not been submitted, so we dont 
  		want to do anything. */
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
		
		/* we're authenticated: Now we need to find 
		and save the data.*/

		/* save, update or delete the custom field of the post.
		split all array keys and save them as unique meta to 
		make them queryable by wordpress. */
		if(isset($_POST['projects'])) {
			$projects = new Projects();
			$projects_taxonomy_group = new Projects_Taxonomy_Group();
			
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
				} else {
					$_POST['projects']['lat'] = null;
					$_POST['projects']['lng'] = null;
				}
			} else {
				$_POST['projects']['lat'] = null;
				$_POST['projects']['lng'] = null;
			}
			
			// save the terms of every taxonomy group
			$taxonomy_groups = $projects_taxonomy_group->get_added_taxonomy_group();
			if(!empty($taxonomy_groups)) {
				foreach($taxonomy_groups as $taxonomy_group) {
					// when the group is empty, reset all terms by 
					// creating an empty taxonomy_name=>term_id array
					if(empty($_POST['projects']['taxonomy_group_' . $taxonomy_group->key])) {
						$taxonomies = array();
						$taxonomies_objects = $projects_taxonomy_group->get_added_taxonomies_by_group($taxonomy_group->name);
						foreach($taxonomies_objects as $taxonomy_object) {
							$taxonomies[$taxonomy_object->name] = null;
						}
					} else {
						$taxonomies = $_POST['projects']['taxonomy_group_' . $taxonomy_group->key];
					}	
					
					// save the terms
					foreach($taxonomies as $taxonomy => $terms) {
						if(!empty($terms)) {
							// convert all term ids to a number
							$terms = array_map('intval', $terms);
							$terms = array_unique($terms);
						} else {
							$terms = null;
						}
						wp_set_object_terms($post_id, $terms, $taxonomy, false);
					}
					
					// clear the transcient cache
					$projects_taxonomy_group->clear_presets_meta_cache($taxonomy_group->key);
				}
			}
			
			// set the terms for the stati
			if(!empty($_POST['projects']['status'])) {
				// relate terms
				$taxonomy = $projects->get_internal_name('status');
				wp_set_object_terms($post_id, intval($_POST['projects']['status']), $taxonomy, false);
			}
			
			// save the meta
			foreach($_POST['projects'] as $key => $value) {
				/* save the key, including empty keys too, 
				because wordpress can only query for empty 
				key values but not for not existing keys.
				this may be fixed in wordpress 3.4. */
				update_post_meta($post_id, '_projects_' . $key, $value);
			}
		}
	}
	
}
}
?>