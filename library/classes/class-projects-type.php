<?php

/**
 * Post type class
 */
if (!class_exists('Projects_Type')) {
class Projects_Type {
		
	/**
	 * Constructor
	 */
	public function __construct() {	
	}
	
	/**
	 * Load the class hooks
	 */
	public function load() {
		register_activation_hook(Projects::$plugin_file_path, array($this, 'add_rewrite_rules'));
		register_deactivation_hook(Projects::$plugin_file_path, array($this, 'remove_rewrite_rules'));
		add_action('init', array($this, 'hook_init'));
		add_action('admin_init', array($this, 'hook_admin'));
	}
		
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {		
		// register the thumbnail and media browser image sizes
		add_image_size('project-thumbnail', 40, 40, false);
		add_image_size('project-media-manager', 240, 240, false);
		
		// set the content
		$this->register_types();
	}

	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {			
		add_filter('manage_edit-' . Projects::$post_type . '_columns', array($this, 'add_columns'));
		add_action('manage_posts_custom_column', array($this, 'create_column_content'), 10, 2);
		add_filter('manage_edit-' . Projects::$post_type . '_sortable_columns', array($this, 'add_sorting_columns'));	
		add_filter('request', array($this, 'default_column_orderby'));
		add_filter('views_edit-' . Projects::$post_type, array($this, 'add_content_views'));
	}

	/**
	 * Flush the permalinks to enable 
	 * the correct rewrite rules.
	 */
	public function add_rewrite_rules() {
		$this->register_types();
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
	 * Create custom post types
	 */
	public function register_types() {
		$this->add_type(__('Projects', 'projects'), __('Project', 'projects'), Projects::$post_type);
	}
	
	/**
	 * Create custom post type
	 */
	public function add_type($plural_label, $singular_label, $key, $args = null) {
		$projects_installation = new Projects_Installation();
		
		$labels = array(
			'name' => $plural_label,
			'singular_name' => $singular_label,
			'add_new' => __('Add New', 'projects'),
			'add_new_item' => sprintf(__('Add New %s', 'projects'), $singular_label),
			'edit_item' => sprintf(__('Edit %s', 'projects'), $singular_label),
			'new_item' => sprintf(__('New %s', 'projects'), $singular_label),
			'all_items' => sprintf(__('All %s', 'projects'), $plural_label),
			'view_item' => sprintf(__('View %s', 'projects'), $singular_label),
			'search_items' => sprintf(__('Search %s', 'projects'), $plural_label),
			'not_found' => sprintf(__('No %s found', 'projects'), $plural_label),
			'not_found_in_trash' => sprintf(__('No %s found in Trash', 'projects'), $plural_label),
			'parent_item_colon' => '',
			'menu_name' => $plural_label
		);
	
		$default_args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array('title', 'editor', 'excerpt', 'post-formats', 'thumbnail'),
			'capability_type' => 'post',
			'rewrite' => array('slug' => $projects_installation->slug),
			'menu_position' => 5,
			'has_archive' => true
		); 
		
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);
		
		// register
		register_post_type($key, $args);
	}	
	
	/**
	 * Add column views in the bulk edit zone
	 */
	public function add_content_views($views) {
		global $wp_query, $current_user;
		
		// get the hidden status taxonomy
		$args = array(
			'hide_empty' => false
		);
		
		$projects = new Projects();
		$taxonomy = $projects->get_internal_name('status');
		$terms = get_terms($taxonomy, $args);
		
		// set the views
		foreach($terms as $term) {
			if(isset($_GET[$taxonomy]) && $_GET[$taxonomy] == $term->slug) {
				$class = ' class="current"';
			} else {
				$class = '';
			}
			$views[$term->name] = '<a href="' . admin_url('edit.php?post_type=' . Projects::$post_type . '&' . $taxonomy . '=' . $term->slug) . '"' . $class . '>' . __( $term->name, 'projects' ) . ' <span class="count">(' . $term->count . ')</span></a>';
		}

		return $views;
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
		$projects_taxonomy = new Projects_Taxonomy();
		$taxonomies = $projects_taxonomy->get_added_taxonomies();

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
		$projects_taxonomy = new Projects_Taxonomy();
		$taxonomies = $projects_taxonomy->get_added_taxonomies(null, 'names');
		
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
				$projects = new Projects();
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = $projects->get_internal_name('date', true);
			}
		}
		return $args;
	}
	
	/**
	 * Create column content
	 */
	public function create_column_content($column, $post_id) {		
		if(isset($_GET['post_type']) && $_GET['post_type'] == Projects::$post_type) { 	
			$projects = new Projects();
			$projects_taxonomy = new Projects_Taxonomy();
			
			// registered taxonomies
			$taxonomies = $projects_taxonomy->get_added_taxonomies(null, 'names');
			
			// default column content
			switch ($column) {
				case 'thumbnail':
					$thumbnail_id = null;
					
					// load the thumbnail
					$projects_media = new Projects_Media();
					$attachment = $projects_media->get_project_featured_media();					
					
					if(isset($attachment)) {
						echo wp_get_attachment_image($attachment->ID, 'project-thumbnail', true );
					} else {
						echo __('None', 'projects');
					}
					break;
					
				case 'year':
					echo '<abbr>' . date_i18n('F', $projects->get_project_meta('date', $post_id)) . ', ' . $projects->get_project_meta('year', $post_id) . '</abbr><br/>' . get_post_status_object(get_post_status($post_id))->label;
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