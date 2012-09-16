<?php

/**
 * Taxonomy class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Taxonomy_Group')) {
class Projects_Taxonomy_Group extends Projects_Taxonomy {

	public static $taxonomy_groups_by_key = array();
	public static $taxonomies_by_key = array();
	
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
		$taxonomy_group = $this->get_taxonomy_group($_GET['taxonomy']);
		$taxonomies = $this->get_taxonomies_in_group($taxonomy_group);
		?>
		<div id="icon-edit" class="icon32 icon32-posts-<?php echo Projects::$post_type; ?>"><br></div>
		<h2 class="nav-tab-wrapper">
		<?php foreach($taxonomies as $taxonomy) : ?>
			<?php $taxonomy_object = get_taxonomy($taxonomy); ?>
			<a href="<?php echo 'edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $taxonomy_object->name; ?>" class="nav-tab<?php $this->menu_tab_selected($taxonomy_object->name); ?>"><?php echo $taxonomy_object->labels->singular_name; ?></a>
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
		if(!empty($_GET['taxonomy']) && !empty($_GET['post_type']) && $_GET['post_type'] == Projects::$post_type && $this->get_taxonomy_group($_GET['taxonomy'])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Add the group pages
	 */
	public function add_pages() {		
		$parent_slug = 'edit.php?post_type=' . Projects::$post_type;
		
		foreach (self::$taxonomy_groups_by_key as $taxonomy_group_by_key) {
			// set the css class "current" of the menu item 
			// with a span. like this the menu item can be styled 
			// as "current". this is some kind of a hack because 
			// there are no filters or actions to add custom css 
			// classes to the submenu list items.
			if($this->is_taxonomy_group_tab()) {
				$menu_title = '<span class="current">' . $taxonomy_group_by_key->plural_label . '</span>';
			} else {
				$menu_title = $taxonomy_group_by_key->plural_label;	
			}
			
			// find the first taxonomy in the group to link to it
			$taxonomies = $this->get_taxonomies_in_group($taxonomy_group_by_key->group);
			reset($taxonomies);
			$taxonomy = key($taxonomies);
			
			// set the menu slug
			$menu_slug = 'edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $taxonomy;
			
			// add the page
			add_submenu_page($parent_slug, $taxonomy_group_by_key->plural_label, $menu_title, 'manage_categories', $menu_slug);
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
		$taxonomy_group_name = $this->projects->get_internal_name($key);
		
		// add the group to a keyed array to refind them
		self::$taxonomy_groups_by_key[$taxonomy_group_name] = new stdClass();
		self::$taxonomy_groups_by_key[$taxonomy_group_name]->group = $taxonomy_group_name;
		self::$taxonomy_groups_by_key[$taxonomy_group_name]->plural_label = $plural_label;
		self::$taxonomy_groups_by_key[$taxonomy_group_name]->singular_label = $singular_label;
		self::$taxonomy_groups_by_key[$taxonomy_group_name]->position = $position;
		self::$taxonomy_groups_by_key[$taxonomy_group_name]->taxonomies = null;
	
		// create a taxonomy for every single taxonomy in the group
		foreach ($groups as $group) {
			if(empty($group['plural_label']) || empty($group['singular_label']) || empty($group['key'])) {
				continue;	
			}
			
			$default_args = array(
				'hierarchical' => false, 
				'public' => true, 
				'show_ui' => false, 
				'show_in_nav_menus' => false
			);
									
			// merge the default and additional args but overwrite with default args
			if(isset($group['args'])) {
				$args = $group['args'];			
			} else {
				$args = array();
			}
			$args = wp_parse_args($default_args, $args);
			
			// add the taxonomy
			$taxonomy = $this->projects->get_internal_name($key . '_' . $group['key']);
			$this->add_taxonomy($group['plural_label'], $group['singular_label'], $taxonomy, $args);

			// add the taxonomy to a keyed array to refind them
			self::$taxonomies_by_key[$taxonomy] = new stdClass();
			self::$taxonomies_by_key[$taxonomy]->group = $taxonomy_group_name;
			self::$taxonomies_by_key[$taxonomy]->taxonomy = $taxonomy;
			
			// also add the taxonomy to the group for even quicker find
			self::$taxonomy_groups_by_key[$taxonomy_group_name]->taxonomies[$taxonomy] = $taxonomy;
		}
	}
		
	/**
	 * Get a custom taxonomy group by taxonomy name
	 */	
	public function get_taxonomy_group($taxonomy) {
		$taxonomy = $this->projects->get_internal_name($taxonomy);
		if(isset(self::$taxonomies_by_key[$taxonomy])) {
			return self::$taxonomies_by_key[$taxonomy]->group;
		}	
		return;
	}
	
	/**
	 * Get taxonomies that belong to a group
	 */	
	public function get_taxonomies_in_group($group) {
		$group = $this->projects->get_internal_name($group);
		if(isset(self::$taxonomy_groups_by_key[$group])) {
			return self::$taxonomy_groups_by_key[$group]->taxonomies;
		}
		return;
	}
	
	/**
	 * Get all taxonomy groups
	 */	
	public function get_added_taxonomy_groups($type = 'objects') {
		if($type == 'objects') {
			return self::$taxonomy_groups_by_key;
		} elseif($type == 'names') {
			$taxonomy_groups = array();
			foreach(self::$taxonomy_groups_by_key as $taxonomy_group_by_key) {
				$taxonomy_groups[$taxonomy_group_by_key->group] = $taxonomy_group_by_key->group;
			}
			return $taxonomy_groups;
		}
		return;
	}
	
	/**
	 * Get all taxonomies that were registered in a group
	 */	
	public function get_added_taxonomies($type = 'objects') {
		if($type == 'objects') {
			return self::$taxonomies_by_key;
		} elseif($type == 'names') {
			$taxonomies = array();
			foreach(self::$taxonomies_by_key as $taxonomy_by_key) {
				$taxonomies[$taxonomy_by_key->taxonomy] = $taxonomy_by_key->taxonomy;
			}
			return $taxonomies;
		}
		return;
	}
	
	/**
	 * add term group
	 */	
	public function add_term_group($taxonomy_group, $term_group_order = null) {
		global $wpdb;
		
		$taxonomy_group = $this->projects->get_internal_name($taxonomy_group);
		$query = $wpdb->insert(
			$wpdb->term_groups, array('taxonomy_group' => $taxonomy_group, 'term_group_order' => $term_group_order), array('%s', '%d')
		);
		
		return $wpdb->insert_id;		
	}
	
	/**
	 * update a term group
	 */	
	public function update_term_group($term_group_id, $taxonomy_group, $term_group_order = null) {
		global $wpdb;

		$taxonomy_group = $this->projects->get_internal_name($taxonomy_group);
		$query = $wpdb->update(
			$wpdb->term_groups, array('taxonomy_group' => $taxonomy_group, 'term_group_order' => $term_group_order), array('term_group_id' => $term_group_id), array('%s', '%d'), array('%d')
		);
		
		return $query;
	}

	/**
	 * delete a term group
	 */	
	public function delete_term_group($term_group_id, $taxonomy_group) {
		global $wpdb;

		$taxonomy_group = $this->projects->get_internal_name($taxonomy_group);
		$query = $wpdb->query(
			$wpdb->prepare("DELETE FROM $wpdb->term_groups WHERE term_group_id = %d", $term_group_id)
		);
		
		// unlink any relationships
		$wpdb->query(
			$wpdb->prepare("DELETE FROM $wpdb->term_group_relationships WHERE term_group_id = %d", $term_group_id)
		);
		
		return $query;
	}
	
	/**
	 * get a term group by object id
	 */	
	public function add_object_term_groups($object_id, $term_group_id, $term_id) {
		global $wpdb;
		
		// TODO check if the term_group_id exists, if not delete any relationships
				
		// TODO relate to the term group
		
		
		// TODO relate the term
		
		//$taxonomy_group = $this->projects->get_internal_name($taxonomy_group);
		
		
		/*
		// create term group relationship
		foreach($term_ids as $term_id) {
			$wpdb->insert($wpdb->term_group_relationships, 
				array('term_group_id' => $term_group_id, 'term_id' => $term_id), array('%d', '%d') 
			);
		}
		*/
		return ;
	}
	
	
}
}

?>