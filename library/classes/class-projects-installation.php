<?php

/**
 * Load class
 */
if (!class_exists('Projects_Installation')) {
class Projects_Installation {
	
	public $slug;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		
		// load the basepage to get the slug name
		$page = get_post(get_option('projects_base_page_id')); 
		if(isset($page)) {
			$this->slug = $page->post_name;
		}
	}
	
	/**
	 * Load the class hooks
	 */
	public function load() {		
		register_activation_hook(Projects::$plugin_file_path, array($this, 'add_default_settings'));
	}
	
	/**
	 * Add default settings
	 */
	public function add_default_settings() {
		global $wpdb;
					    
		// create a page that serves as base slug for all projects
		$page = get_page_by_path('projects');
		
		// add a projects page when it doesn't exist
		if(empty($page)) {
			$args = array(
				'post_title' => 'Projects',
				'post_name' => 'projects',
				'post_content' => '',
				'post_status' => 'publish',
				'post_author' => 1,
				'post_type' => 'page'
			);
			$page = get_post(wp_insert_post($args));
		} 
		
		// check if the saved page id exists
		$page_option_id = get_option('projects_base_page_id');
		if(isset($page_option_id)) {
			$saved_page = get_post($page_option_id); 
			if(!empty($saved_page)) {
				$page = $saved_page;
			}
		}
		
		// save the page id
		update_option('projects_base_page_id', $page->ID);
		$this->slug = $page->post_name;
	}
}
}
?>