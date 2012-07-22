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
		// load the basepage to get the slug name
		$page = get_post(get_option('projects_base_page_id')); 
		$this->slug = $page->post_name;
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
				
		// check the collate for the term table
		$collate = '';
	    if($wpdb->supports_collation()) {
			if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
	    }
	    
		// install taxonomy term table, if it doesnt exist already
		$sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "termmeta" ." (
			`meta_id` bigint(20) unsigned NOT NULL auto_increment,
			`term_id` bigint(20) unsigned NOT NULL default '0',
			`meta_key` varchar(255) default NULL,
			`meta_value` longtext,
			PRIMARY KEY (meta_id),
			KEY term_id (term_id),
			KEY meta_key (meta_key) ) $collate;";
			
	    $wpdb->query($sql);
	    
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
				
		$this->slug = $page->post_name;
	}
}
}
?>