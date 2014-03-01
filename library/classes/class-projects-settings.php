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
	public $page;
	public $meta_fields_list;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->slug = 'projects_settings';
		$this->option_prefix = 'projects_settings_';
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
		// deatcivate the meta fields, the ajax part is not working
		//add_action('wp_ajax_add_meta_field', array($this, 'add_meta_field_ajax'));
		$this->flush_rules();		
	}
				
	/**
	 * Add sub page to the Settings Menu
	 */
	public function add_page() {		
		// add the page to the settings menu
		$this->page = new Wordpress_Settings_Page(array(
			'slug' => $this->slug,
			'menu_title' => 'Projects',
			'page_title' => 'Projects Settings'
		));

		// add page section
		$this->page->add_section(array(
			'slug' => 'page',
			'title' => 'Page options',
			'settings_page' => $this->slug
		));
		
		// build the options list for the select field
		$pages = get_pages();
		$options = array();
		foreach($pages as $page) {
			$options[$page->ID] = $page->post_title;
		}
		
		// add page select field
		$this->page->get_section('page')->add_field(array(
			'slug' => $this->option_prefix . 'base_page',
			'title' => 'Base page',
			'value' => '',
			'type' => 'select',
			'options' => $options,
			'sanitize_callback' => array($this, 'sanitize_base_page'),
			'description' => __('On this page the projects are displayed.', 'projects')
		));
		
		// add edit screen section
		$this->page->add_section(array(
			'slug' => 'edit_screen',
			'title' => 'Edit Screen Content',
			'settings_page' => $this->slug,
		));
		
		// build the options list for the select field
		$projects_countries = new Projects_Countries();
		$locale = strtoupper(substr(get_locale(), 3));
		
		// add country select field
		$this->page->get_section('edit_screen')->add_field(array(
			'slug' => $this->option_prefix . 'country',
			'title' => 'Location Country',
			'value' => $locale,
			'type' => 'select',
			'options' => $projects_countries->world,
			'description' => __('The default country used in the "Location" box.', 'projects')
		));
		
		// add custom fields field
		/*
		$this->meta_fields_list = $this->page->get_section('edit_screen')->add_field(array(
			'slug' => $this->option_prefix . 'meta_fields',
			'title' => 'Additional Fields',
			'value' => array(),
			'render_callback' => array($this, 'render_meta_field_list'),
			'sanitize_callback' => array($this, 'sanitize_meta_field_list'),
			'description' => __('Additional fields appear in the "General" box. Use the <code>get_project_meta()</code> to access them in the theme.', 'projects')
		));
		*/
	}
	
	/**
	 * Render the meta field list
	 */
/*
	public function render_meta_field_list() {
		// read the values. all the names are stored in 
		// array index 0, the keys are in index 1.
		$names = isset($this->meta_fields_list->value[0]) ? $this->meta_fields_list->value[0] : null;
		$keys = isset($this->meta_fields_list->value[1]) ? $this->meta_fields_list->value[1] : null;

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
		<p class="description"><?php echo $this->meta_fields_list->description; ?></p>
	    <?php 
	}
*/
	
	/**
	 * Sanitize the meta fields values
	 */
/*
	public function sanitize_meta_field_list($value) {
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
*/

	/**
	 * Create a meta field item
	 */
/*
	public function create_meta_field_item($name = '', $key = '') {
		print_r($this->page);
		?>
		<div class="meta-field-item">
			<fieldset>
				<label><?php _e('Name', 'projects'); ?></label>
				<input type="text" name="<?php echo $this->meta_fields_list->slug; ?>[0][]" value="<?php echo esc_attr($name); ?>">
			</fieldset>
			<fieldset>
				<label><?php _e('Key', 'projects'); ?></label>
				<input type="text" name="<?php echo $this->meta_fields_list->slug; ?>[1][]" value="<?php echo $key; ?>">
			</fieldset>
			<a href="#" class="button drag-meta-field"><?php _e('Drag', 'projects'); ?></a>
			<a href="#" class="button remove-meta-field"><?php _e('Remove', 'projects'); ?></a>
		</div>
		<?php
	}

*/
	/**
	 * Manage the meta field item with ajax
	 */
/*
	public function add_meta_field_ajax() {
		if(empty($_POST) || empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], Projects::$plugin_basename)) {
			die();
		}
		$this->create_meta_field_item();
		exit;
	}
*/

	/**
	 * Sanitize the base page values
	 */
	public function sanitize_base_page($value) {
		// save a transient when the value changed.
		// the transient is used to determine if
		// the permalink rules should be flushed.
	    if(isset($value)) {
	    	$base_page_id = $this->get_setting('base_page');
	    	if($value != $base_page_id) {
	    		// save transient
		    	set_transient($this->option_prefix  . 'flush_rewrite_rules', 'true', 60);
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
			flush_rewrite_rules();
						
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