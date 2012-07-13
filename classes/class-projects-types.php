<?php

/**
 * Post type class
 */
if (!class_exists('Projects_Types')) {
class Projects_Types {
	
	public $slug;
	public $projects;
	public $taxonomies;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// instances
		$this->projects = new Projects();
		$this->taxonomies = new Projects_Taxonomies();
	}
	
	/**
	 * Load the class hooks
	 */
	public function load() {
		register_activation_hook(Projects::$plugin_file_path, array($this, 'add_default_settings'));
		register_deactivation_hook(Projects::$plugin_file_path, array($this, 'remove_default_settings'));
		add_action('init', array($this, 'hook_init'));
		add_action('admin_init', array($this, 'hook_admin'));
	}
	
	/**
	 * Add default settings
	 */
	public function add_default_settings() {		
		// load the basepage to get the slug name
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
	 * Hook into the main hooks
	 */
	public function hook_init() {
		// get the base page slug for the post types
		$page = get_post(get_option('projects_base_page_id')); 
		$this->slug = $page->post_name;
		
		// register the thumbnail and media browser image sizes
		add_image_size('project-thumbnail', 40, 40, false);
		add_image_size('project-media-manager', 240, 240, false);
		
		// set the content
		$this->register_types();
		$this->register_status();
	}

	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {			
		add_filter('manage_edit-' . Projects::$post_type . '_columns', array($this, 'add_columns'));
		add_action('manage_posts_custom_column', array($this, 'create_column_content'), 10, 2);
		add_filter('manage_edit-' . Projects::$post_type . '_sortable_columns', array($this, 'add_sorting_columns'));	
		add_filter('request', array($this, 'default_column_orderby'));
	}

	/**
	 * Create custom post types
	 */
	public function register_types() {		
		$this->add_type(__('Projects', 'projects'), __('Project', 'projects'), Projects::$post_type);
		
		/* hidden post type for the awards. it is used
		to query awards and list projects for every
		award. it alsow permits to group awards by year, 
		name, etc. */
		/*
		$args = array(
			'exclude_from_search' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
		);
		$this->add_type(__('Awards', 'projects'), __('Award', 'projects'), $this->projects->get_internal_name('award'), $args);
		*/
	}
	
	/**
	 * Create custom post type
	 */
	public function add_type($plural_label, $singular_label, $key, $args = null) {		
		$labels = array(
			'name' => $plural_label,
			'singular_name' => $singular_label,
			'add_new' => __('Add New', 'projects'),
			'add_new_item' => sprintf(__('Add New %s', 'projects'), $singular_label),
			'edit_item' => sprintf(__('Edit %s', 'projects'), $singular_label),
			'new_item' => sprintf(__('New %s', 'projects'), $singular_label),
			'all_items' => sprintf(__('All %s', 'projects'), $plural_label),
			'view_item' => sprintf(__('View %s', 'projects'), $plural_label),
			'search_items' => sprintf(__('Search %s', 'projects'), $plural_label),
			'not_found' => sprintf(__('No %s found', 'projects'), $plural_label),
			'not_found_in_trash' => sprintf(__('No %s found in Trash', 'projects'), $plural_label),
			'parent_item_colon' => '',
			'menu_name' => $plural_label
		);
	
		$default_args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'post-formats'),
			'capability_type' => 'post',
			'rewrite' => array('slug' => $this->slug),
			'menu_position' => 4,
			'has_archive' => true
		); 
		
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);
		
		// register
		register_post_type($key, $args);
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
		$status_name = $this->projects->get_internal_name($key);
		
		$default_args = array(
	    	'label' => __($label, 'projects'),
			'label_count' => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' )
		);

		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);

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
		$taxonomies = $this->taxonomies->get_added_taxonomies();

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
		$taxonomies = $this->taxonomies->get_added_taxonomies(null, 'names');
		
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
				$args['meta_key'] = $this->projects->get_internal_name('date', true);
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
			$taxonomies = $this->taxonomies->get_added_taxonomies(null, 'names');
			
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
					echo date_i18n('M', $this->projects->get_meta('date', $post_id)) . ', ' . $this->projects->get_meta('year', $post_id);
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
	

}
}
?>