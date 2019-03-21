<?php

/**
 * Writepanel class
 */
if (!class_exists('Projects_Writepanel')) {
class Projects_Writepanel {

	public $fields;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->fields = array();
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
 		add_theme_support('post-thumbnails');

		add_action('add_meta_boxes', array($this, 'add_boxes'));
		add_action('add_meta_boxes', array($this, 'remove_boxes'), 20);
		add_action('wp_ajax_add_taxonomy_group_preset', array($this, 'add_taxonomy_group_preset_ajax'));
		add_action('wp_ajax_load_media_list', array($this, 'load_media_list_ajax'));
		add_action('wp_ajax_save_media_list', array($this, 'save_media_list_ajax'));

		add_filter('wp_insert_post_data', array($this, 'change_post_date'));
		add_action('save_post', array($this, 'save_box_data'));
	}

	/**
	 * Add the meta boxes
	 */
	public function add_boxes() {
		add_meta_box('projects-media-box', __('Media', 'projects'), array($this, 'create_box_media'), Projects::$post_type, 'normal', 'default');
		add_meta_box('projects-general-box', __('General', 'projects'), array($this, 'create_box_general'), Projects::$post_type, 'side', 'default');
		//add_meta_box('projects-location-box', __('Location', 'projects'), array($this, 'create_box_location'), Projects::$post_type, 'side', 'default');
		//add_meta_box('projects-color-box', __('Color', 'projects'), array($this, 'create_box_color'), Projects::$post_type, 'side', 'default');

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
	 * Add a new meta field that should be visible in the boxes
	 */
	public function add_field($name, $key = null, $default = '') {
		$projects = new Projects();

		// set a default key
		if(empty($key)) {
			$key = sanitize_title(sanitize_text_field($name));
		}

		// create a field object and add it to the list
		$field = new stdClass;
		$field->key = $key;
		$field->name = $name;
		$field->default = $default;
		$this->fields[$key] = $field;
	}

	/**
	 * Remove a field from the boxes
	 */
	public function remove_field($key) {
		if(isset($this->fields[$key])) {
			unset($this->fields[$key]);
		}
	}

	/**
	 * Create the box location
	 */
	public function create_box_location($post, $metabox) {
		$projects = new Projects();

		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
		?>
		<?php
			$lat = $projects->get_project_meta('latitude');
			$lng = $projects->get_project_meta('longitude');
			$zoom = 15;
		?>
		<?php if(!empty($lat) && !empty($lng)) : ?>
		<div class="map">
			<a href="http://maps.google.com/maps?hl=de&z=<?php echo $zoom; ?>&q=<?php echo $lat; ?>,<?php echo $lng; ?>&sll=<?php echo $lat; ?>,<?php echo $lng; ?>" target="_blank">
				<img src="http://maps.google.com/maps/api/staticmap?sensor=false&size=320x320&zoom=<?php echo $zoom; ?>&markers=<?php echo $lat; ?>,<?php echo $lng; ?>" />
			</a>
		</div>
		<?php endif; ?>
		<div class="location">
			<p class="form-fieldset"><label><span><?php _e('First name', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[first_name]" value="<?php echo $projects->get_project_meta('first_name'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Last name', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[last_name]" value="<?php echo $projects->get_project_meta('last_name'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Company', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[company_name]" value="<?php echo $projects->get_project_meta('company_name'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Address', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[address]" value="<?php echo $projects->get_project_meta('address'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Postal code', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[postal_code]" value="<?php echo $projects->get_project_meta('postal_code'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('City', 'projects'); ?></span></label><input type="text" class="regular-text" name="projects[city]" value="<?php echo $projects->get_project_meta('city'); ?>"></p>
			<p class="form-fieldset"><label><span><?php _e('Country', 'projects'); ?></span></label><select name="projects[country]">
				<?php
					$projects_countries = new Projects_Countries();
					$projects_settings = new Projects_Settings();
					$meta = $projects->get_project_meta('country');
					if(empty($meta)) {
						$meta = $projects_settings->get_setting('country');
					}
				?>
				<?php foreach($projects_countries->world as $code => $name) : ?>
					<option value="<?php echo $code; ?>" <?php selected($code, $meta); ?>><?php echo $name; ?></option>
				<?php endforeach; ?>
			</select></p>
			<input type="hidden" name="projects[lat]" value="<?php echo $lat; ?>">
			<input type="hidden" name="projects[lng]" value="<?php echo $lng; ?>">
		</div>
		<?php
	}

	/**
	 * Create the box taxonomy group
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
			<div class="preset-options"><h4 title="<?php echo $title_placeholder; ?>"><?php echo $title; ?></h4><a href="#" class="delete-preset"><?php _e('Remove', 'projects'); ?></a></div>
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
			die();
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
		<p class="form-fieldset"><label><span><?php _e('Color 1', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors code" name="projects[color_1]" value="<?php echo $projects->get_project_meta('color_1'); ?>"></span></p>
		<p class="form-fieldset"><label><span><?php _e('Color 2', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors code" name="projects[color_2]" value="<?php echo $projects->get_project_meta('color_2'); ?>"></span></p>
		<p class="form-fieldset"><label><span><?php _e('Color 3', 'projects'); ?></span></label><span class="input-group"><input type="text" class="regular-text minicolors code" name="projects[color_3]" value="<?php echo $projects->get_project_meta('color_3'); ?>"></span></p>
		<?php
	}

	/**
	 * Create the box general
	 */
	public function create_box_general($post, $metabox) {
		$projects = new Projects();

		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
		?>
		<p class="form-fieldset"><label><span><?php _e('Date', 'projects'); ?></span></label>
			<span class="input-group">
				<select id="projects-date-month" class="select-date" name="projects[month]">
					<?php
					$count = 1;
					$month_meta = $projects->get_project_meta('month');
					if(empty($month_meta)) {
						$month_meta = date_i18n('n');
					}
					?>
					<?php while($count <= 12) : ?>
						<option value="<?php echo $count; ?>" <?php selected($count, $month_meta); ?>><?php echo date_i18n('F', mktime(0, 0, 0, $count, 1)); ?></option>
						<?php $count++; ?>
					<?php endwhile; ?>
				</select>
				<select id="projects-date-year" class="select-date" name="projects[year]">
					<?php
					$count = 0;
					$year = date_i18n('Y');
					$year_start = $year + 1;
					$year_meta = $projects->get_project_meta('year');
					$year_total = apply_filters('projects_date_total_years', 10);

					if(empty($year_meta)) {
						$year_meta = $year;
					}
					?>
					<?php while($count <= $year_total) : ?>
						<option value="<?php echo $year_start - $count; ?>" <?php selected($year_start - $count, $year_meta); ?>><?php echo $year_start - $count; ?></option>
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
		<p class="form-fieldset"><label><span><?php _e('Status', 'projects'); ?></span></label><select name="projects[status]">
			<?php foreach($terms as $term) : ?>
				<?php
					$in_term = is_object_in_term($post->ID, $taxonomy, $term->term_id);
					if(is_wp_error($in_term)) {
						$in_term = false;
					}
				?>
				<option value="<?php echo $term->term_id; ?>" <?php selected(true, $in_term); ?>><?php _e($term->name, 'projects'); ?></option>
			<?php endforeach; ?>
		</select></p>
		<?php $website = $projects->get_project_meta('website'); ?>
		<p class="form-fieldset"><label><span><?php _e('Website', 'projects'); ?></span></label><input type="text" class="regular-text code" name="projects[website]" value="<?php echo $website; ?>" placeholder="http://"><?php if(!empty($website)) : ?><a href="<?php echo $website; ?>" target="_blank" class="external"></a><?php endif; ?></p>
		<?php $redirect = $projects->get_project_meta('redirect'); ?>
		<p class="form-fieldset"><label class="selectit"><input value="1" type="checkbox" name="projects[redirect]" <?php if($redirect==1) : ?>checked="checked"<?php endif; ?>> <?php _e('Redirect to Website', 'projects'); ?></label></p>
		<?php // add additional fields ?>
		<?php foreach($this->fields as $field) : ?>
			<?php
				$meta = $projects->get_project_meta($field->key);
				if(empty($meta)) {
					$meta = $field->default;
				}
			?>
			<p class="form-fieldset"><label><span><?php echo $field->name; ?></span></label><input type="text" class="regular-text code" name="projects[<?php echo $field->key; ?>]" value="<?php echo $meta; ?>"></p>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Create the box media
	 */
	public function create_box_media($post, $metabox) {
		// Use nonce for verification
  		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
  		?>
		<ul id="projects-media-list" class="media-list hide-if-no-js">
		<?php $this->create_media_list(); ?>
		</ul>
		<p class="hide-if-no-js"><a href="#" id="projects-media-manage" class="media-manage"><?php _e('Manage media', 'projects'); ?></a></p>
		<?php
	}

	/**
	 * Create the box media list
	 */
	public function create_media_list($post_id = null) {
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}

		$projects_media = new Projects_Media();
		$attachments = $projects_media->get_project_content_media($post_id);
		if ($attachments) {
			foreach($attachments as $attachment) {
				$mime = explode('/', strtolower($attachment->post_mime_type));
				$meta = wp_get_attachment_metadata($attachment->ID);
				$source = wp_get_attachment_image_src($attachment->ID, 'project-media-manager'); ?>
				<li class="media-item mime-<?php echo $mime[1]; ?> <?php if($mime[0] != 'image') : ?>mime-placeholder<?php endif; ?>">
					<a href="#" class="media-manage" data-attachment-id="<?php echo esc_html($attachment->ID); ?>">
						<span class="media-options"></span>
						<span class="media-content"><img src="<?php echo $source[0]; ?>"></span>
						<span class="media-title"><?php echo esc_html($attachment->post_title); ?></span>
					</a>
				</li>
				<?php
			}
		}
	}

	/**
	 * Save the media list with an ajax callback
	 */
	public function save_media_list_ajax() {
	    // Verifiy post data and nonce
		if(empty($_POST) || empty($_POST['post_id']) || empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], Projects::$plugin_basename)) {
			die();
		}

		// Save the list as metadata
		$projects_media = new Projects_Media();
		$projects_media->set_project_media_meta($_POST['ids'], array(), $_POST['post_id']);

	    // output the new media list
	    $this->create_media_list($_POST['post_id']);

		exit;
	}

	/**
	 * Pre saving the box data to change the date
	 */
	public function change_post_date($data) {
		// edit the date
		if(isset($_POST['projects'])) {
			$data['post_date'] = date_i18n('Y-m-d H:i:s', mktime(12, 0, 0, $_POST['projects']['month'], 1, $_POST['projects']['year']));
			$data['post_date_gmt'] = get_gmt_from_date($data['post_date']);
		}

		return $data;
	}

	/**
	 * Save the box data
	 */
	public function save_box_data($post_id) {
		// Verify this came from the our screen and with
		// proper authorization, because save_post can be
		// triggered at other times.
		if(empty($_POST['projects_nonce']) || !wp_verify_nonce( $_POST['projects_nonce'], Projects::$plugin_basename)) {
			return $post_id;
		}

  		// Verify if this is an auto save routine. If it
  		// is our form has not been submitted, so we dont
  		// want to do anything.
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

		// We're authenticated: Now we need to find
		// and save the data.

		// Save, update or delete the custom field of the post.
		// split all array keys and save them as unique meta to
		// make them queryable by wordpress.
		if(isset($_POST['projects'])) {
			$projects = new Projects();
			$projects_taxonomy_group = new Projects_Taxonomy_Group();

			// create a date entry to make querying by month or year easy
			$_POST['projects']['date'] = mktime(0, 0, 0, $_POST['projects']['month'], 1, $_POST['projects']['year']);

			// set default values for checkboxes
			if(empty($_POST['projects']['redirect'])) {
				$_POST['projects']['redirect'] = 0;
			}

			// query map service to geocode the location
			$projects_geocode = new Projects_Geocode();
			if(!empty($_POST['projects']['address']) || !empty($_POST['projects']['postal_code']) || !empty($_POST['projects']['city'])) {
				$address = urlencode($_POST['projects']['address'] . ', ' . $_POST['projects']['postal_code'] . ' ' . $_POST['projects']['city']);
				$geocode = $projects_geocode->locate_address($address);

				// set lat lng
				$_POST['projects']['latitude'] = $geocode->latitude;
				$_POST['projects']['longitude'] = $geocode->longitude;
			} else {
				$_POST['projects']['latitude'] = null;
				$_POST['projects']['longitude'] = null;
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

			// Save the meta
			foreach($_POST['projects'] as $key => $value) {
				// Save the key, including empty keys too,
				// because wordpress can only query for empty
				// key values but not for not existing keys.
				// This may be different in WordPress 3.5.
				update_post_meta($post_id, '_projects_' . $key, $value);
			}
		}
	}

}
}
?>
