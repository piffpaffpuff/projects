<?php

/**
 * Settings class
 */
if (!class_exists('Projects_Settings')) {
class Projects_Settings {
	
	public $slug;
	public $option_prefix;
	public $sections;
	public $fields;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->slug = 'projects_settings';
		$this->option_prefix = 'projects_settings_';
		
		$this->sections = array(
			array(
				'slug' => 'page',
				'title' => 'Page options',
				'callback' => 'create_section'
			),
			array(
				'slug' => 'edit_screen',
				'title' => 'Edit Screen Content',
				'callback' => 'create_section'
			)
		);
		
		$this->fields = array(
			array(
				'slug' => 'base_page',
				'title' => 'Base page',
				'section' => 'page',
				'value' => '',
				'description' => __('On this page the projects are displayed.', 'projects'),
				'callback' => 'create_select_field_pages',
				'sanitize' => 'sanitize_value_base_page'
			),
			array(
				'slug' => 'country',
				'title' => 'Location Country',
				'section' => 'edit_screen',
				'value' => 'US',
				'description' => __('The default country used in the "Location" box.', 'projects'),
				'callback' => 'create_select_field_countries',
				'sanitize' => 'sanitize_value'
			),
			array(
				'slug' => 'meta_fields',
				'title' => 'Additional Fields',
				'section' => 'edit_screen',
				'value' => array(),
				'description' => __('Additional fields appear in the "General" box. Use the <code>get_project_meta()</code> to access them in the theme.', 'projects'),
				'callback' => 'create_list_meta_field',
				'sanitize' => 'sanitize_value_meta_field'
			)
		);
	}

	/**
	 * Load the class hooks
	 */
	public function load() {
		add_action('admin_init', array($this, 'hook_admin'));
		add_action('admin_menu', array($this, 'add_page'));
	}

	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {				
		$this->add_fields();
		$this->flush_rules();
		add_action('wp_ajax_add_meta_field', array($this, 'add_meta_field_ajax'));
	}
				
	/**
	 * Add sub page to the Settings Menu
	 */
	public function add_page() {
		// add the page to the settings menu
		add_options_page('Projects Settings', 'Projects', 'manage_options', $this->slug, array($this, 'create_page'));
	}
	
	/**
	 * Create the page structure for the settings page
	 */	 
	public function create_page() {
		// Check the user capabilities
		if(!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		// Generate the output
		?>
	    <div class="wrap">
	    
	        <h2><?php _e('Projects Settings', 'projects'); ?></h2>
	        <form action="options.php" method="post">
	            <?php settings_fields($this->slug); ?>
	            <?php do_settings_sections($this->slug); ?>
	            <?php submit_button(); ?>
	        </form>
	        
	    </div>
	    <?php
	}
	
	/**
	 * Add fields to the settings page
	 */
	public function add_fields() {
		// add the sections
		foreach($this->sections as $section) {
			// add the section
			add_settings_section($section['slug'], $section['title'], array($this, $section['callback']), $this->slug);
		}
			
		// add the fields
		foreach($this->fields as $field) {
			// get the option from the database and set the defaults
			$option = $this->get_setting($field['slug']);
			$args = array(
				'slug' => $field['slug'], 
				'description' => $field['description'],
				'value' => empty($option) ? $field['value'] : $option
			);
			
			// add the field
			add_settings_field($field['slug'], $field['title'], array($this, $field['callback']), $this->slug, $field['section'], $args);
			
			// register the setting in the database
			register_setting($this->slug, $this->option_prefix . $field['slug'], array($this, $field['sanitize']));
		}
	}
	
	/**
	 * Create a section
	 */
	public function create_section() {
	    ?><?php 
	}
	
	/**
	 * Create a text input field
	 */
	public function create_text_field($args) {
		// field options 
		$slug = $args['slug'];
		$description = $args['description'];
		$value = $args['value'];
	    ?>
		<input type="text" name="<?php echo $this->option_prefix . $slug; ?>" value="<?php echo $value; ?>" />
		<p class="description"><?php echo $description; ?></p>
	    <?php 
	}

	/**
	 * Create select field for the pages
	 */
	public function create_select_field_pages($args) {
		// field options 
		$slug = $args['slug'];
		$description = $args['description'];
		$value = $args['value'];
		
		// create the select field 
		$args = array(
			'selected' => $value,
			'name' => $this->option_prefix . $slug
		);
		wp_dropdown_pages($args); 
	    ?>
		<p class="description"><?php echo $description; ?></p>
	    <?php 
	}

	/**
	 * Create select field for the countries
	 */
	public function create_select_field_countries($args) {
		// field options 
		$slug = $args['slug'];
		$description = $args['description'];
		$value = $args['value'];
		$projects_countries = new Projects_Countries();

		// create the select field 
	    ?>
	    <select name="<?php echo $this->option_prefix . $slug; ?>">
			<?php foreach($projects_countries->world as $code => $name) : ?>
			<option value="<?php echo $code; ?>" <?php selected($code, $value); ?>><?php echo $name; ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php echo $description; ?></p>
	    <?php 
	}
	
	/**
	 * Create edit field for the metas
	 */
	public function create_list_meta_field($args) {
		// field options 
		$slug = $args['slug'];
		$description = $args['description'];
		
		// read the values. all the names are stored in 
		// array index 0, the keys are in index 1.
		$value = $args['value'];
		$names = isset($value[0]) ? $value[0] : null;
		$keys = isset($value[1]) ? $value[1] : null;

		// nonce to check the ajax submit
		wp_nonce_field(Projects::$plugin_basename, 'projects_nonce');
									
		// create the list
		?>
		<div class="meta-field-item-list">
			<?php if(isset($names) && is_array($names)) : ?>
				<?php foreach($names as $key => $name) : ?>
					<?php $this->create_meta_field_item($name, $keys[$key]); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>	
		<p><a href="#" class="button add-meta-field"><?php _e('Add Field', 'projects'); ?></a>
		<p class="description"><?php echo $description; ?></p>
	    <?php 
	}
	
	/**
	 * Manage the meta field item with ajax
	 */
	public function add_meta_field_ajax() {
	    // Verify post data and nonce
		if(empty($_POST) || empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], Projects::$plugin_basename)) {
			die();
		}
		
		// create a meta field item
		$this->create_meta_field_item();
		exit;
	}

	/**
	 * Create a meta field item
	 */
	public function create_meta_field_item($name = '', $key = '') {
		?>
		<div class="meta-field-item">
			<fieldset>
				<label><?php _e('Name', 'projects'); ?></label>
				<input type="text" name="<?php echo $this->option_prefix; ?>meta_fields[0][]" value="<?php echo esc_attr($name); ?>">
			</fieldset>
			<fieldset>
				<label><?php _e('Key', 'projects'); ?></label>
				<input type="text" name="<?php echo $this->option_prefix; ?>meta_fields[1][]" value="<?php echo $key; ?>">
			</fieldset>
			<a href="#" class="button drag-meta-field"><?php _e('Drag', 'projects'); ?></a>
			<a href="#" class="button remove-meta-field"><?php _e('Remove', 'projects'); ?></a>
		</div>
		<?php
	}
	
	/**
	 * Sanitize the values that are stored in the database
	 */
	public function sanitize_value($value) {
	    return $value;
	}

	/**
	 * Sanitize the base page values
	 */
	public function sanitize_value_base_page($value) {
		// save a transient when the value changed
	    if(isset($value)) {
	    	$base_page_id = $this->get_setting('base_page');
	    	if($value != $base_page_id) {
		    	set_transient($this->option_prefix  . 'flush_rewrite_rules', 'true', 60);
	    	}
		} 			    
	    return $value;
	}
		
	/**
	 * Sanitize the meta fields values
	 */
	public function sanitize_value_meta_field($value) {
		// check the values
	    if(isset($value)) {
	    	$names = &$value[0];
	    	$keys = &$value[1];
			foreach($names as $index => $name) {
				if(empty($name)) {
					// remove entries when the title is empty
					unset($names[$index]);
					unset($keys[$index]);
				} else {
					// set the title as default value when the key field was empty
					$names[$index] = sanitize_text_field($name);
					if(empty($keys[$index])) {
						$keys[$index] = sanitize_title(sanitize_text_field($name));
					}
				}
			}
		} 			    
	    return $value;
	}
	
	/**
	 * Flush the rewrite rules for the base page
	 */
	public function flush_rules() {
		// flush the rules if the transient is set
		$flush = (boolean) get_transient($this->option_prefix  . 'flush_rewrite_rules');
		if($flush) {
			// rehook the post types and taxonomies, then 
			// flush the permalinks to make the new slug work.
			$projects_taxonomy_group = new Projects_Taxonomy_Group(); 
			$projects_taxonomy_group->add_rewrite_rules();
			$projects_taxonomy = new Projects_Taxonomy(); 
			$projects_taxonomy->add_rewrite_rules();
			$projects_type = new Projects_Type(); 
			$projects_type->add_rewrite_rules();
			
			// delete transient
			delete_transient($this->option_prefix  . 'flush_rewrite_rules');
		}
	}

	/**
	 * Get a setting
	 */
	public function get_setting($key) {
		$option = get_option($this->option_prefix . $key);
		if($option) {
			return $option;
		}
		return;	
	}
	
	/**
	 * Set a setting
	 */
	public function set_setting($key, $value) {
		update_option($this->option_prefix . $key, $value);	
	}
}
}

?>