<?php

/**
 * Installation class
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
		$projects_settings = new Projects_Settings();
		$page = get_post($projects_settings->get_setting('base_page')); 
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
		$projects_settings = new Projects_Settings();
		$base_page_id = $projects_settings->get_setting('base_page');
		if(isset($base_page_id)) {
			$saved_page = get_post($base_page_id); 
			if(!empty($saved_page)) {
				$page = $saved_page;
			}
		}
		
		// save the page id
		$projects_settings->set_setting('base_page', $page->ID);
		$this->slug = $page->post_name;
	}
}
}
?>