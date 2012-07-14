<?php

/**
 * Taxonomy class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Taxonomies')) {
class Projects_Taxonomies {

	public $slug;	
	public $projects;
	
	/**
	 * Constructor
	 */
	public function __construct() {	
		// load the basepage to get the slug name
		$page = get_post(get_option('projects_base_page_id')); 
		$this->slug = $page->post_name;
		
		// instances
		$this->projects = new Projects();
	}
	
	/**
	 * Load the class hooks
	 */
	public function load() {
		register_activation_hook(Projects::$plugin_file_path, array($this, 'add_default_settings'));
		add_action('init', array($this, 'hook_init'));
	}

	/**
	 * Add default settings
	 */
	public function add_default_settings() {		
		// load the basepage to get the slug name
		$page = get_post(get_option('projects_base_page_id')); 
		$this->slug = $page->post_name;
	}
	
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {		
		$this->register_taxonomies();
	}
	
	/**
	 * Register the taxonomies
	 */
	public function register_taxonomies() {		
		$this->add_taxonomy(__('Types', 'projects'), __('Type', 'projects'), 'type');
		$this->add_taxonomy(__('Techniques', 'projects'), __('Technique', 'projects'), 'technique');
		$this->add_taxonomy(__('Tasks', 'projects'), __('Task', 'projects'), 'task');
		$this->add_taxonomy(__('Agencies', 'projects'), __('Agency', 'projects'), 'agency');
		$this->add_taxonomy(__('Clients', 'projects'), __('Client', 'projects'), 'client');
		$this->add_taxonomy(__('Tags', 'projects'), __('Tag', 'projects'), 'tag', array('hierarchical' => false));
		
		// award taxonomies
		/*
		$args = array(
			'hierarchical' => false,
			'post_type' => $this->projects->get_internal_name('award')
		);
		$this->add_taxonomy(__('Names', 'projects'), __('Name', 'projects'), 'award_name', $args);
		$this->add_taxonomy(__('Years', 'projects'), __('Year', 'projects'), 'award_year', $args);
		$this->add_taxonomy(__('Categories', 'projects'), __('Category', 'projects'), 'award_category', $args);
		$this->add_taxonomy(__('Ranks', 'projects'), __('Rank', 'projects'), 'award_rank', $args);
		*/

		$this->add_awards();
	}
	
	/**
	 * Create a custom taxonomy
	 */	
	public function add_taxonomy($plural_label, $singular_label, $key, $args = null) {	
		$taxonomy_name = $this->projects->get_internal_name($key);
	
		$labels = array(
		    'name' => $plural_label,
		    'singular_name' => $singular_label,
		    'search_items' => sprintf(__('Search %s', 'projects'), $plural_label),
		    'all_items' => sprintf(__('All %s', 'projects'), $plural_label),
		    'parent_item' => sprintf(__( 'Parent %s', 'projects'), $plural_label),
    		'parent_item_colon' => sprintf(__( 'Parent %s:', 'projects'), $plural_label),
		    'edit_item' => sprintf(__('Edit %s', 'projects'), $singular_label),
		    'update_item' => sprintf(__('Update %s', 'projects'), $singular_label),
		    'add_new_item' => sprintf(__('Add New %s', 'projects'), $singular_label),
		    'new_item_name' => sprintf(__('New %s Name', 'projects'), $singular_label),
		    'separate_items_with_commas' => sprintf(__('Separate %s with commas', 'projects'), $plural_label),
		    'add_or_remove_items' => sprintf(__('Add or remove %s', 'projects'), $plural_label),
		    'choose_from_most_used' => sprintf(__('Choose from the most used %s', 'projects'), $plural_label),
		    'menu_name' => $plural_label
		);
		
		$default_args = array(
			'labels' => $labels,
	    	'rewrite' => array('slug' => $this->slug . '/' . $taxonomy_name),
	    	'hierarchical' => true,
			'show_ui' => true,
			'post_type' => Projects::$post_type
		);
		
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);
		
		// register
		register_taxonomy($taxonomy_name, $args['post_type'], $args);
	}
	
	/**
	 * Remove a custom taxonomy
	 */	
	public function remove_taxonomy($key) {
		global $wp_taxonomies;
		
		$args = array(
			'name' => $this->projects->get_internal_name($key)
		);
		
		$taxonomies = $this->get_added_taxonomies($args, 'names');

		foreach($taxonomies as $taxonomy) {
			if(taxonomy_exists($taxonomy)) {
				unset($wp_taxonomies[$taxonomy]);
			}
		}
	}
				
	/**
	 * Create award taxonomy
	 */	
	public function add_awards() {
		$external_key = 'award';
		$taxonomy = $this->projects->get_internal_name($external_key);
	
		$this->add_taxonomy(__('Awards', 'projects'), __('Award', 'projects'), $external_key);

		// name
		$existing_term_id = term_exists('name', $taxonomy);
		if(empty($existing_term_id)) {
			$args = array(
				'slug' => 'name'
			);
			$term = wp_insert_term(__('Name', 'projects'), $taxonomy, $args);
		}
		
		// year
		$existing_term_id = term_exists('year', $taxonomy);
		if(empty($existing_term_id)) {
			$args = array(
				'slug' => 'year'
			);
			$term = wp_insert_term(__('Year', 'projects'), $taxonomy, $args);
		}	
		
		// category
		$existing_term_id = term_exists('category', $taxonomy);
		if(empty($existing_term_id)) {
			$args = array(
				'slug' => 'category'
			);
			$term = wp_insert_term(__('Category', 'projects'), $taxonomy, $args);
		}
		
		// rank
		$existing_term_id = term_exists('rank', $taxonomy);
		if(empty($existing_term_id)) {
			$args = array(
				'slug' => 'rank'
			);
			$term = wp_insert_term(__('Rank', 'projects'), $taxonomy, $args);
		}
	}

	/**
	 * Get all registered taxonomies
	 */
	public function get_added_taxonomies($args = null, $type = 'objects') {
		$default_args = array(
	    	'object_type' => array(Projects::$post_type),
			'show_ui' => true
		);
		
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);

		return get_taxonomies($args, $type);
	}

}
}

?>