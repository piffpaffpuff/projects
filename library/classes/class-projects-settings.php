<?php

/**
 * Settings class
 */
if (!class_exists('Projects_Settings')) {
class Projects_Settings {
	
	public $slug;
	public $option_prefix;
	
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
		$this->flush_rules();		
	}
				
	/**
	 * Add sub page to the Settings Menu
	 */
	public function add_page() {		
		// add the page to the settings menu
		$this->page = new Wordpress_Settings_Page(array(
			'slug' => $this->slug,
			'menu_title' => __('Projects', 'projects'),
			'page_title' => __('Projects Settings', 'projects')
		));

		// add page section
		$this->page->add_section(array(
			'slug' => 'page',
			'title' => __('Page options', 'projects'),
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
			'title' => __('Base page', 'projects'),
			'value' => '',
			'type' => 'select',
			'options' => $options,
			'sanitize_callback' => array($this, 'sanitize_base_page'),
			'description' => __('On this page the projects are displayed.', 'projects')
		));
		
		// build the options list for the select field
		$projects_countries = new Projects_Countries();
		$locale = strtoupper(substr(get_locale(), 3));
		
		// add country select field
		$this->page->get_section('page')->add_field(array(
			'slug' => $this->option_prefix . 'country',
			'title' => __('Country', 'projects'),
			'value' => $locale,
			'type' => 'select',
			'options' => $projects_countries->world,
			'description' => __('The default country used in the "Location" box.', 'projects')
		));
	}
	
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