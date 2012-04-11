<?php

/**
 * Menu class
 */
if (!class_exists('Projects_Menu')) {
class Projects_Menu {
	
	public $slug;

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
		register_deactivation_hook(Projects::$plugin_file_path, array($this, 'remove_default_settings'));
		add_action('init', array($this, 'load_hooks'));
		add_action('admin_init', array($this, 'load_admin_hooks'));
	}
	
	/**
	 * Add default settings
	 */
	public function add_default_settings() {		
		// get the base page slug for the post types
		$page = get_post(get_option('projects_base_page_id')); 
		$this->slug = $page->post_name;
		
		// flush the permalinks to make the custom 
		// post type rewrite rule work correctly
		$this->register_types();
		flush_rewrite_rules();
	}
	
	/**
	 * Remove default settings
	 */
	public function remove_default_settings() {
		// flush the permalinks to remove the 
		// post type rewrite rules
		flush_rewrite_rules();
	}
	
	/**
	 * Load the main hooks
	 */
	public function load_hooks() {
		// get the base page slug for the post types
		$page = get_post(get_option('projects_base_page_id')); 
		$this->slug = $page->post_name;
	
		// set the content
		$this->register_taxonomies();
		$this->register_types();
		$this->register_status();
	}

	/**
	 * Load the admin hooks
	 */
	public function load_admin_hooks() {			
		add_filter('manage_edit-' . Projects::$post_type . '_columns', array($this, 'add_columns'));
		add_action('manage_posts_custom_column', array($this, 'create_column_content'), 10, 2);
		add_filter('manage_edit-' . Projects::$post_type . '_sortable_columns', array($this, 'add_sorting_columns'));	
		
		add_action('admin_print_styles', array($this, 'add_styles'));
		add_action('admin_print_scripts-post.php', array($this, 'add_scripts'));
		add_action('admin_print_scripts-post-new.php', array($this, 'add_scripts'));
		
		// Enqueue script on settings page
		if(isset($_GET['page']) && $_GET['page'] == 'projects-settings') {
			$hook = get_plugin_page_hookname($_GET['page'], 'options-general.php');
			add_action('admin_print_scripts-' . $hook, array($this, 'add_scripts'));
		}
	}
	
	/**
	 * Add the styles
	 */
	public function add_styles() {
		wp_enqueue_style('projects', Projects::$plugin_directory_url . 'css/style.css');
	}

	/**
	 * Add the scripts
	 */
	public function add_scripts() {
		wp_enqueue_script('projects', Projects::$plugin_directory_url . 'js/script.js', array('jquery', 'jquery-ui-core'), '1.0');
	}

	/**
	 * Create custom post type
	 */
	public function register_types() {		
		$labels = array(
			'name' => __('Projects', 'projects'),
			'singular_name' => __('Project', 'projects'),
			'add_new' => __('Add New', 'projects'),
			'add_new_item' => __('Add New Project', 'projects'),
			'edit_item' => __('Edit Project', 'projects'),
			'new_item' => __('New Project', 'projects'),
			'all_items' => __('All Projects', 'projects'),
			'view_item' => __('View Projects', 'projects'),
			'search_items' => __('Search Projects', 'projects'),
			'not_found' =>  __('No Projects found', 'projects'),
			'not_found_in_trash' => __('No Projects found in Trash', 'projects'), 
			'parent_item_colon' => '',
			'menu_name' => __('Projects', 'projects')
		);
	
		$args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'post-formats'),
			'capability_type' => 'post',
			'rewrite' => array('slug' => __($this->slug)),
			'menu_position' => 4,
			'has_archive' => true
		); 
	
		register_post_type(Projects::$post_type, $args);
	}
		
	/**
	 * Register the taxonomies
	 */
	public function register_taxonomies() {		
		$this->add_taxonomy(__('Types', 'projects'), __('Type', 'projects'));
		$this->add_taxonomy(__('Techniques', 'projects'), __('Technique', 'projects'));
		$this->add_taxonomy(__('Tasks', 'projects'), __('Task', 'projects'));
		$this->add_taxonomy(__('Agencies', 'projects'), __('Agency', 'projects'));
		$this->add_taxonomy(__('Clients', 'projects'), __('Client', 'projects'));
		$this->add_taxonomy(__('Tags', 'projects'), __('Tag', 'projects'), array('herarchical' => false));
	}
	
	/**
	 * Create a custom taxonomy
	 */	
	public function add_taxonomy($name, $singular_name, $args = null) {
		$taxonomy_name = Projects::$post_type . '_' . sanitize_key($singular_name);
	
		$labels = array(
		    'name' => __($name, 'projects'),
		    'singular_name' => __($singular_name, 'projects'),
		    'search_items' =>  __('Search ' . $name, 'projects'),
		    'all_items' => __('All ' . $name, 'projects'),
		    'parent_item' => __( 'Parent ' . $name, 'projects'),
    		'parent_item_colon' => __( 'Parent ' . $name . ':', 'projects'),
		    'edit_item' => __('Edit ' . $singular_name, 'projects'),
		    'update_item' => __('Update ' . $singular_name, 'projects'),
		    'add_new_item' => __('Add New ' . $singular_name, 'projects'),
		    'new_item_name' => __('New ' . $singular_name . ' Name', 'projects'),
		    'separate_items_with_commas' => __('Separate ' . $name . ' with commas', 'projects'),
		    'add_or_remove_items' => __('Add or remove ' . $name, 'projects'),
		    'choose_from_most_used' => __('Choose from the most used ' . $name, 'projects'),
		    'menu_name' => __($name, 'projects')
		);
		
		$args = is_array($args) ? $args : array();	
		$args['rewrite'] = array('slug' => __($this->slug . '/' . $taxonomy_name));
		$args['hierarchical'] = isset($args['hierarchical']) ? $args['hierarchical'] : true;
		$args['labels'] = isset($args['labels']) ? $args['labels'] : $labels;
		$args['show_ui'] = isset($args['show_ui']) ? $args['show_ui'] : true;
		
		register_taxonomy($taxonomy_name, Projects::$post_type, $args);
	}
	
	/**
	 * Check if the taxonomy name is a label or the database query name
	 */	
	public function is_taxonomy_name($name) {
		if(strrpos($name, Projects::$post_type . '_') !== false) {
			return true;
		}
		return false;
	}
	
	/**
	 * Remove a custom taxonomy
	 */	
	public function remove_taxonomy($name) {
		global $wp_taxonomies;
		
		$args = array(
			'label' => $name
		);
		
		$taxonomies = $this->get_added_taxonomies($args, 'names');

		foreach($taxonomies as $taxonomy) {
			if(taxonomy_exists($taxonomy) && $this->is_taxonomy_name($taxonomy)) {
				unset($wp_taxonomies[$taxonomy]);
			}
		}
		
	}

	/**
	 * Register the post status
	 */
	public function register_status() {	
		$this->add_status(__('Planned', 'projects'));
		$this->add_status(__('In Progress', 'projects'));
		$this->add_status(__('Finished', 'projects'));
	}
	
	/**
	 * Create a custom post status
	 */	
	public function add_status($name, $args = null) {
		$status_name = Projects::$post_type . '_' . sanitize_key($name);

		$args = is_array($args) ? $args : array();
		$args['label'] = isset($args['label']) ? $args['label'] : __($name, 'projects');
		$args['label_count'] = isset($args['label_count']) ? $args['label_count'] : _n_noop($name . ' <span class="count">(%s)</span>', $name . ' <span class="count">(%s)</span>' );
		
		register_post_status($status_name, $args);
	}
	
	/**
	 * Add custom columns
	 */
	public function add_columns($columns) {
		unset($columns['date']);
		unset($columns['title']);
		
		// default columns
		$columns['thumbnail'] = __('Thumbnail', 'projects');
		$columns['title'] = __('Title', 'projects');
		
		// registered taxonomies
		$taxonomies = $this->get_added_taxonomies();

		foreach($taxonomies as $taxonomy) {
			$columns[$taxonomy->query_var] = __($taxonomy->labels->name, 'projects');
		}

		return $columns;
	}

	/**
	 * Add custom columns that can be sorted
	 */
	public function add_sorting_columns($columns) {
		// registered taxonomies
		$taxonomies = $this->get_added_taxonomies(null, 'names');
		
		foreach($taxonomies as $taxonomy) {
			$columns[$taxonomy] = $taxonomy;
		}
		
		return $columns;
	}
	
	/**
	 * Create column content
	 */
	public function create_column_content($column, $post_id) {		
		if(isset($_GET['post_type']) && $_GET['post_type'] == Projects::$post_type) { 
			// meta data
			$meta = get_post_meta($post_id, '_projects', true);
			
			// registered taxonomies
			$taxonomies = $this->get_added_taxonomies(null, 'names');
			
			// column content
			if($column == 'thumbnail') {
				$thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
					
				if($thumbnail_id) {
					$thumbnail = wp_get_attachment_image($thumbnail_id, array(36, 36), true );
				}
				
				if(isset($thumbnail)) {
					echo $thumbnail;
				} else {
					echo __('None', 'projects');
				}
			}
			
			// taxonomy content
			if(in_array($column, $taxonomies)) {
				if($list = get_the_term_list($post_id, $taxonomies[$column], '', ', ', '')) {
					echo $list;
				} else {
					echo __('None', 'projects');
				}
			}
		}	
	}
				
	/**
	 * Get all registered taxonomies
	 */
	public function get_added_taxonomies($args = null, $type = 'objects') {
		$args = is_array($args) ? $args : array();
		$args['show_ui'] = isset($args['show_ui']) ? $args['show_ui'] : true;
		$args['object_type'] = array(Projects::$post_type);
	
		return get_taxonomies($args, $type);
	}
}
}
?>