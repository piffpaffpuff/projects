<?php

/**
 * Menu class
 */
if (!class_exists('Projects_Register')) {
class Projects_Register {
	
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
		
		// register the thumbnail and media browser image sizes
		add_image_size('project-thumbnail', 40, 40, false);
		add_image_size('project-media-manager', 240, 240, false);
		
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
		add_filter('request', array($this, 'default_column_orderby'));
		
		add_action('admin_print_styles', array($this, 'add_styles'));
		add_action('admin_print_scripts-post.php', array($this, 'add_scripts'));
		add_action('admin_print_scripts-post-new.php', array($this, 'add_scripts'));
		add_action('admin_print_styles-media-upload-popup', array($this, 'add_media_styles'));		
		add_action('admin_print_scripts-media-upload-popup', array($this, 'add_media_scripts'));		
		
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
		wp_enqueue_style('minicolors', Projects::$plugin_directory_url . 'css/jquery.miniColors.css');
		wp_enqueue_style('projects', Projects::$plugin_directory_url . 'css/style.css');
	}
	
	/**
	 * Add the scripts
	 */
	public function add_scripts() {
		wp_enqueue_script('minicolors', Projects::$plugin_directory_url . 'js/jquery.miniColors.min.js', array('jquery'));
		wp_enqueue_script('projects', Projects::$plugin_directory_url . 'js/script.js', array('jquery'));
	}

	/**
	 * Add the media manager styles
	 */
	public function add_media_styles() {
		$post_type = get_post_type($_GET['post_id']);
		if(!empty($post_type) && $post_type == Projects::$post_type) {
			wp_enqueue_style('projects-media', Projects::$plugin_directory_url . 'css/media-style.css');
		}
	}
	
	/**
	 * Add the media manager scripts
	 */
	public function add_media_scripts() {
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
			'view_item' => __('View Project', 'projects'),
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
			'rewrite' => array('slug' => $this->slug),
			'menu_position' => 4,
			'has_archive' => true
		); 
	
		register_post_type(Projects::$post_type, $args);
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
		$this->add_taxonomy(__('Tags', 'projects'), __('Tag', 'projects'), 'tag', array('herarchical' => false));
		$this->add_awards();
	}
	
	/**
	 * Create a custom taxonomy
	 */	
	public function add_taxonomy($plural_label, $singular_label, $key, $args = null) {	
		$taxonomy_name = self::generate_internal_name($key);
	
		$labels = array(
		    'name' => $plural_label, 'projects',
		    'singular_name' => $singular_label, 'projects',
		    'search_items' =>  sprintf(__('Search %s', 'projects'), $plural_label),
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
		
		$args = is_array($args) ? $args : array();	
		$args['rewrite'] = array('slug' => $this->slug . '/' . $taxonomy_name);
		$args['hierarchical'] = isset($args['hierarchical']) ? $args['hierarchical'] : true;
		$args['labels'] = isset($args['labels']) ? $args['labels'] : $labels;
		$args['show_ui'] = isset($args['show_ui']) ? $args['show_ui'] : true;
		
		register_taxonomy($taxonomy_name, Projects::$post_type, $args);
	}
		
	/**
	 * Remove a custom taxonomy
	 */	
	public function remove_taxonomy($key) {
		global $wp_taxonomies;
		
		$args = array(
			'name' => self::get_taxonomy_internal_name($key)
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
		$taxonomy = self::get_taxonomy_internal_name($external_key);
	
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
		$args = is_array($args) ? $args : array();
		$args['show_ui'] = isset($args['show_ui']) ? $args['show_ui'] : true;
		$args['object_type'] = array(Projects::$post_type);
	
		return get_taxonomies($args, $type);
	}
	
	/**
	 * Register the post status
	 */
	public function register_status() {	
		$this->add_status(__('Completed', 'projects'), 'completed');
		$this->add_status(__('In Progress', 'projects'), 'inprogress');
		$this->add_status(__('Planned', 'projects'), 'planned');
	}
	
	/**
	 * Create a custom post status
	 */	
	public function add_status($label, $key, $args = null) {
		$status_name = self::generate_internal_name($key);

		$args = is_array($args) ? $args : array();
		$args['label'] = isset($args['label']) ? $args['label'] : __($label, 'projects');
		$args['label_count'] = isset($args['label_count']) ? $args['label_count'] : _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' );
		
		register_post_status($status_name, $args);
	}
	
	/**
	 * Add custom columns
	 */
	public function add_columns($columns) {
		unset($columns['date']);
		unset($columns['title']);
		
		// default columns before taxonomies 
		$columns['thumbnail'] = __('Thumbnail', 'projects');
		$columns['title'] = __('Title', 'projects');
		
		// registered taxonomies
		$taxonomies = $this->get_added_taxonomies();

		foreach($taxonomies as $taxonomy) {
			$columns[$taxonomy->query_var] = __($taxonomy->labels->name, 'projects');
		}
		
		// default columns after taxonomies 
		$columns['year'] = __('Date', 'projects');
		
		return $columns;
	}

	/**
	 * Add custom columns that can be sorted
	 */
	public function add_sorting_columns($columns) {
		// default columns
		$columns['year'] = 'year';
		
		// registered taxonomies
		$taxonomies = $this->get_added_taxonomies(null, 'names');
		
		foreach($taxonomies as $taxonomy) {
			$columns[$taxonomy] = $taxonomy;
		}
		
		return $columns;
	}
	
	/**
	 * Order columns by a default column
	 */
	public function default_column_orderby($args) {
		if(isset($args['post_type']) && $args['post_type'] == Projects::$post_type) {
			if(!isset($args['orderby']) || (isset($args['orderby']) && $args['orderby'] == 'year')) {
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = '_projects_date';
			}
		}
		return $args;
	}
	
	/**
	 * Create column content
	 */
	public function create_column_content($column, $post_id) {		
		if(isset($_GET['post_type']) && $_GET['post_type'] == Projects::$post_type) { 			
			// registered taxonomies
			$taxonomies = $this->get_added_taxonomies(null, 'names');
			
			// default column content
			switch ($column) {
				case 'thumbnail':
					$thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
					
					if($thumbnail_id) {
						$thumbnail = wp_get_attachment_image($thumbnail_id, 'project-thumbnail', true );
					}
					
					if(isset($thumbnail)) {
						echo $thumbnail;
					} else {
						echo __('None', 'projects');
					}
					break;
					
				case 'year':
					echo date_i18n('M', Projects::get_meta_value('date', $post_id)) . ', ' . Projects::get_meta_value('year', $post_id);
					break;
			}
			
			// taxonomy content
			if(in_array($column, $taxonomies)) {
				$taxonomy_name = $taxonomies[$column];
				if($list = get_the_term_list($post_id, $taxonomy_name, '', ', ', '')) {
					echo $list;
				} else {
					$taxonomy = get_taxonomy($taxonomy_name);
					printf(__('No %s', 'projects'), $taxonomy->labels->name);
				}
			}			
		}	
	}
	
	/**
	 * Generate an internal name
	 */	
	public static function generate_internal_name($key) {
		return Projects::$post_type . '_' . $key;
	}

	/**
	 * Check if it is an internal name
	 */	
	public static function is_internal_name($key) {
		if(strrpos($key, Projects::$post_type . '_') !== false) {
			return true;
		} 
		return false;
	}
	
	/**
	 * Get taxonomy internal database name from key
	 */
	public static function get_taxonomy_internal_name($key) {		
		// get the taxonomy internal database name 
		if(self::is_internal_name($key)) {
			$taxonomy = $key;
		} else {
			$taxonomy = self::generate_internal_name($key);
		}
		return $taxonomy;
	}
}
}
?>