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
		$this->slug = $page->post_name;
		
		// set the table names
		$wpdb->term_groups = $wpdb->prefix . 'term_groups';
	    $wpdb->term_group_relationships = $wpdb->prefix . 'term_group_relationships';
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

	    // install taxonomy groups table, if it doesnt exist already
	    $sql = "CREATE TABLE IF NOT EXISTS $wpdb->term_groups (
	    	`term_group_id` bigint(20) unsigned NOT NULL auto_increment,
	    	`taxonomy_group` varchar(32) default NULL,
	    	`term_group_order` int(11) unsigned NOT NULL default '0',
	    	PRIMARY KEY (term_group_id),
	    	KEY taxonomy_group (taxonomy_group),
	    	KEY term_group_order (term_group_order) ) $collate;";
	    
	    $wpdb->query($sql);
	    
	    // install term groups table, if it doesnt exist already
	    $sql = "CREATE TABLE IF NOT EXISTS $wpdb->term_group_relationships (
	    	`object_id` bigint(20) unsigned NOT NULL default '0',
	    	`term_group_id` bigint(20) unsigned NOT NULL default '0',
	    	`term_id` bigint(20) unsigned NOT NULL default '0',
	    	PRIMARY KEY (object_id, term_group_id),
	    	KEY term_id (term_id) ) $collate;";
	    	
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