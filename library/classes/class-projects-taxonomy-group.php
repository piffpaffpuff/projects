<?php

/**
 * Taxonomy class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Taxonomy_Group')) {
class Projects_Taxonomy_Group extends Projects_Taxonomy {

	public static $post_types_by_key = array();
		
	/**
	 * Constructor
	 */
	public function __construct() {	
		parent::__construct();
	}
	
	/**
	 * Load the class hooks
	 */
	public function load() {
		add_action('init', array($this, 'hook_init'));
		add_action('admin_init', array($this, 'hook_admin'));
	}
	
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {		
		add_action('admin_menu', array($this, 'add_pages'));
		$this->register_taxonomy_groups();
	}	
	
	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {
		// add the menu tabs at the right page
		if($this->is_taxonomy_group_tab()) {
			add_action('all_admin_notices', array($this, 'add_menu_tabs'));	
			add_filter('admin_body_class', array($this, 'body_classes'));		
		}
	}
	
	/**
	 * Body classes to remove the default icon 
	 * and title from the taxonomy edit screen.
	 */
	public function body_classes($classes) {
		$classes .= ' taxonomy-group'; 
		return $classes;
	}
	
	/**
	 * Add menu tabs
	 */
	public function add_menu_tabs() {
		// get post type associated with taxonomy
		$args = array(
			'object_type' => get_taxonomy($_GET['taxonomy'])->object_type
		);
		
		// get the taxonomies that are associated with the post type
		$taxonomies = $this->get_added_taxonomies($args);
		?>
		<div id="icon-edit" class="icon32 icon32-posts-<?php echo Projects::$post_type; ?>"><br></div>
		<h2 class="nav-tab-wrapper">
		<?php foreach($taxonomies as $taxonomy) : ?>
			<a href="<?php echo 'edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $taxonomy->name; ?>" class="nav-tab<?php $this->menu_tab_selected($taxonomy->name); ?>"><?php echo $taxonomy->labels->singular_name; ?></a>
		<?php endforeach; ?>
		</h2>
		<?php
	}
	
	/**
	 * Select the menu tab
	 */
	public function menu_tab_selected($taxonomy) {
		if($this->is_taxonomy_group_tab() && $_GET['taxonomy'] == $taxonomy) {
			echo ' nav-tab-active';
		} else {
			echo '';
		}
	}
	
	/**
	 * Check if one of the award taxonomy pages 
	 * is currently displayed.
	 */
	public function is_taxonomy_group_tab() {		
		if(!empty($_GET['taxonomy']) && !empty($_GET['post_type'])) {
			$taxonomy = get_taxonomy($_GET['taxonomy']);
			foreach(self::$post_types_by_key as $post_type) {
				//$taxonomies = get_object_taxonomies($post_type);  && in_array($taxonomy->name, $taxonomies)
				if(in_array($post_type, $taxonomy->object_type)) {
					return true;
				}
			}
			return false;
		} else {
			return false;
		}
	}

	/**
	 * Add the group pages
	 */
	public function add_pages() {		
		$parent_slug = 'edit.php?post_type=' . Projects::$post_type;
		
		foreach (self::$post_types_by_key as $post_type_by_key) {
			// get the post type object
			$post_type_object = get_post_type_object($post_type_by_key);
			
			// set the css class "current" of the menu item 
			// with a span. like this the menu item can be styled 
			// as "current". this is some kind of a hack because 
			// there are no filters or actions to add custom css 
			// classes to the submenu list items.
			if($this->is_taxonomy_group_tab()) {
				$menu_title = '<span class="current">' . $post_type_object->labels->name . '</span>';
			} else {
				$menu_title = $post_type_object->labels->name;	
			}
			
			// get the taxonomies that are part of the post type
			$taxonomies = get_object_taxonomies($post_type_by_key);
			
			// set the menu slug to the first taxonomy
			$menu_slug = 'edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $taxonomies[0];
			
			// add the page
			add_submenu_page($parent_slug, $post_type_object->labels->name, $menu_title, 'manage_categories', $menu_slug);
		}
	}
		
	/**
	 * Register the taxonomy groups
	 */
	public function register_taxonomy_groups() {
		$groups = array(
			array(
				'plural_label' => __('Award Names', 'projects'),
				'singular_label' => __('Award Name', 'projects'),
				'key' => 'name'
			),
			array(
				'plural_label' => __('Award Years', 'projects'),
				'singular_label' => __('Award Year', 'projects'),
				'key' => 'year'
			),
			array(
				'plural_label' => __('Award Categories', 'projects'),
				'singular_label' => __('Award Category', 'projects'),
				'key' => 'category'
			),
			array(
				'plural_label' => __('Award Ranks', 'projects'),
				'singular_label' => __('Award Rank', 'projects'),
				'key' => 'rank'
			)
		);	
		$this->add_taxonomy_group(__('Awards', 'projects'), __('Award', 'projects'), $groups, 'award');
	}
	
	/**
	 * Create a custom taxonomy group
	 */	
	public function add_taxonomy_group($plural_label, $singular_label, $groups, $key, $position = null) {		
		$projects = new Projects();
		$projects_type = new Projects_Type();
		$projects_installation = new Projects_Installation();
		$post_type = $projects->get_internal_name($key);

		// register a post type for the taxonomy group
		$args = array(
			'show_ui' => false, 
			'show_in_nav_menus' => false
		);
		
		$projects_type->add_type($plural_label, $singular_label, $post_type, $args);
		
		// add the group to a keyed array to refind them
		self::$post_types_by_key[$post_type] = $post_type;
	
		// create a taxonomy for every single taxonomy in the group
		foreach ($groups as $group) {
			if(empty($group['plural_label']) || empty($group['singular_label']) || empty($group['key'])) {
				continue;	
			}
			
			// register taxonomy but add it to also the projects post type
			$default_args = array(
				'hierarchical' => false, 
				'show_ui' => false, 
				'show_in_nav_menus' => false,
				'rewrite' => array('slug' => $projects_installation->slug . '/' . sprintf(__('project-%s', 'projects'), $key . '-' . $group['key']), 'with_front' => true),
				'post_type' => array(Projects::$post_type, $post_type)
			);
							
			// merge the default and additional args but overwrite with default args
			if(isset($group['args'])) {
				$args = $group['args'];			
			} else {
				$args = array();
			}
			$args = wp_parse_args($default_args, $args);
			
			// generate taxonomy name and register
			$taxonomy = $projects->get_internal_name($key . '_' . $group['key']);
			
			$this->add_taxonomy($group['plural_label'], $group['singular_label'], $taxonomy, $args);
		}
	}

	/**
	 * Get all taxonomy groups
	 */	
	public function get_added_taxonomy_groups() {
		return self::$post_types_by_key;
	}

	/**
	 * Get all taxonomies that were registered in a group
	 */	
	public function get_added_taxonomies($args = null, $type = 'objects') {
		$default_args = array(
			'object_type' => array_merge(array(Projects::$post_type), self::$post_types_by_key)
		);
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);

		return get_taxonomies($args, $type);
	}
	
	/**
	 * Add a preset
	 */	
	public function add_preset($post_type, $post_parent) {
		$post_type_object = get_post_type_object($post_type);
		$post = array(
			'post_parent' => $post_parent,
			'post_title' => $post_type_object->labels->singular_name . ' for #' . $post_parent,
			'post_type' => $post_type,
			'post_status' => 'draft'
		);
		$query = wp_insert_post($post);
		return $query;
	}
	
	/**
	 * Delete a preset
	 */	
	public function delete_preset($post_id) {
		$query = wp_delete_post($post_id, true);
		return $query;
	}
	
	/**
	 * Get presets for a post id
	 */	
	public function get_added_presets($post_parent, $post_type) {
		$args = array(
			'post_parent' => $post_parent,
			'post_type' => $post_type,
			'orderby' => 'menu_order',
			'order' => 'ASC'
		);
		$query = get_children($args);
		return $query;
	}
	
	
	/**
	 * Set a preset order
	 */	
	public function set_preset_order($post_id, $post_parent, $menu_order = null) {
		global $wpdb;
		$query = $wpdb->update($wpdb->posts, array('menu_order' => $menu_order), array('ID' => $post_id, 'post_parent' => $post_parent), array('%d'), array('%d', '%d'));
		return $query;
	}
	
	/**
	 * Relate the terms to the preset-post and the parent-post
	 */	
	public function set_preset_terms($post_id, $post_parent, $terms, $taxonomy) {
		if(is_array($terms) && empty($terms)) {
			$terms = null;
		}
		wp_set_object_terms($post_id, $terms, $taxonomy, false);
		wp_set_object_terms($post_parent, $terms, $taxonomy, false);
	}
	
}
}

?>