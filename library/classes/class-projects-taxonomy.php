<?php

/**
 * Taxonomy class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Taxonomy')) {
class Projects_Taxonomy {

	public $slug;	
	public $projects;
	public $installation;
	
	/**
	 * Constructor
	 */
	public function __construct() {	
		$this->projects = new Projects();
		$this->installation = new Projects_Installation();
	}
	
	/**
	 * Load the class hooks
	 */
	public function load() {
		register_activation_hook(Projects::$plugin_file_path, array($this, 'add_rewrite_rules'));
		register_deactivation_hook(Projects::$plugin_file_path, array($this, 'remove_rewrite_rules'));
		add_action('init', array($this, 'hook_init'));
	}
	
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {		
		$this->register_taxonomies();
	}
	
	/**
	 * Flush the permalinks to enable 
	 * the correct rewrite rules.
	 */
	public function add_rewrite_rules() {
		$this->register_taxonomies();
		flush_rewrite_rules();
	}
	
	/**
	 * Flush the permalinks to reenable 
	 * the old rewrite rules.
	 */
	public function remove_rewrite_rules() {
		flush_rewrite_rules();
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
		
		// Create a hidden taxonomy for the project stati
		$taxonomy = $this->projects->get_internal_name('status');
		$this->add_taxonomy(__('Stati', 'projects'), __('Status', 'projects'), 'status', array('hierarchical' => false, 'show_ui' => false));
		$this->add_default_term($taxonomy, __('Completed', 'projects'), 'completed');
		$this->add_default_term($taxonomy, __('In Progress', 'projects'), 'inprogress');
		$this->add_default_term($taxonomy, __('Planned', 'projects'), 'planned');
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
	    	'rewrite' => array('slug' => $this->installation->slug . '/' . sprintf(__('project-%s', 'projects'), $key), 'with_front' => true),
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
	 * Add a default term to a taxonomy
	 */	
	public function add_default_term($taxonomy, $label, $slug, $args = null) {
		$existing_term_id = term_exists($slug, $taxonomy);
		if(empty($existing_term_id)) {
			$default_args = array(
				'slug' => $slug
			);
			
			// merge the default and additional args
			$args = wp_parse_args($args, $default_args);
			
			// add the term
			$term = wp_insert_term($label, $taxonomy, $args);
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