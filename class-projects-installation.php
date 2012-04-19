<?php

/**
 * Load class
 */
if (!class_exists('Projects_Installation')) {
class Projects_Installation {
	
	/**
	 * Constructor
	 */
	public function __construct() {
	}
	
	/**
	 * Load the class
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
		$page_id = null;
		
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
			$page_id = wp_insert_post($args);
		} else {
			$page_id = $page->ID;
		}
		
		// set the base page
		if(!get_option('projects_base_page_id')) {
			add_option('projects_base_page_id', $page_id);
		}
		
		// set the image size
		if(!get_option('projects_default_image_size')) {
			add_option('projects_default_image_size', 'medium');
		}
	}
}
}