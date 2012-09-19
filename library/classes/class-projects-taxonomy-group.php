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
			'show_ui' => true, 
			'show_in_nav_menus' => true
		);
		
		$projects_type->add_type($plural_label, $singular_label, $post_type, $args);
		
		// add the group to a keyed array to refind them
		self::$post_types_by_key[$post_type] = $post_type;
	
		// create a taxonomy for every single taxonomy in the group
		foreach ($groups as $group) {
			if(empty($group['plural_label']) || empty($group['singular_label']) || empty($group['key'])) {
				continue;	
			}
			
			// register taxonomy but add it to the projects post type and the
			// post type create for the group. this makes it easy to get all 
			// taxonomies that are part of a taxonomy group.
			$default_args = array(
				'hierarchical' => false, 
				'show_ui' => true, 
				'show_in_nav_menus' => true,
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
	 * Get all taxonomies that were registered in a group
	 */	
	public function get_added_taxonomies_names($taxonomy_group, $type = 'names') {
		$projects = new Projects();
		$post_type = $projects->get_internal_name($taxonomy_group);
		$args = array(
			'object_type' => array(Projects::$post_type, $post_type)
		);
		return $this->get_added_taxonomies($args, $type);
	}

	/**
	 * Get presets for a post id
	 */	
	public function get_added_presets($post_parent, $post_type) {
		$args = array(
			'post_parent' => $post_parent,
			'post_type' => $post_type,
			'meta_key' => '_order',
			'orderby' => 'meta_value',
			'order' => 'ASC'
		);
		$query = get_children($args);
		return $query;
	}
	
	/**
	 * Get all presetes sorted
	 */	
	public function get_added_presets_sorted($key, $join = true, $order = array()) {
		global $wpdb;
		
		$projects = new Projects();
		$post_type = $projects->get_internal_name($key);
				
		// build the sql query to get the meta for all presets
		$taxonomies = $this->get_added_taxonomies_names($post_type);
		$count = 1;
		$from = "";
		$and = "";
		$orderby = "";
		foreach($taxonomies as $taxonomy) {
			// select the meta sql query
			$from .= ", $wpdb->postmeta posts_meta_$count";
			
			// compare the meta sql query
			$and .= " AND child_posts.ID = posts_meta_$count.post_id AND posts_meta_$count.meta_key = '_$taxonomy'";
			
			// check if the current taxonomy is also an ordering field
			foreach($order as $order_field) {
				if($order_field['taxonomy'] == $taxonomy) {
					$comma = "";
					if(empty($orderby)) {
						$orderby .= " ORDER BY ";
					} else {
						$comma = ", ";
					}
					if(isset($order_field['order'])) {
						$sort = $order_field['order'];
					} else {
						$sort = "";
					}
					$orderby .= "$comma posts_meta_$count.meta_value $sort";
					break;
				}
			}
	
			$count++;
		}
		
		// query the posts
		$sql = "
			SELECT child_posts.ID, child_posts.post_parent, child_posts.post_type
			FROM $wpdb->posts child_posts $from
			WHERE child_posts.post_type = '$post_type'
			$and
			$orderby
		";
		
		$results = $wpdb->get_results($sql);
		
		// build the presets with the terms
		$presets = array();
		foreach($results as $result) {
			$preset = array();
			$terms = wp_get_object_terms($result->post_parent, $taxonomies);
			foreach($taxonomies as $taxonomy) {
				//$preset[$taxonomy] = 
			}
			echo '<pre>';
			print_r($terms);
			echo '</pre>';
			
			$presets[] = $preset;
		}
	
		return;
	}
	
	/**
	 * Add a preset
	 */	
	public function add_preset($post_parent, $post_type) {
		$post_type_object = get_post_type_object($post_type);
		
		// insert the post
		$post = array(
			'post_parent' => $post_parent,
			'post_title' => $post_type_object->labels->singular_name . ' for #' . $post_parent,
			'post_type' => $post_type,
			'post_status' => 'draft'
		);
		$post_id = wp_insert_post($post);
		
		// add the meta because wordpress can't meta_query non-existing metas
		$this->update_preset_metas($post_id, $post_type);
		return $post_id;
	}
	
	/**
	 * Update meta data for the post query and set the order
	 */	
	public function update_preset($post_id, $post_parent, $post_type, $taxonomy_terms, $order) {
		$this->update_preset_metas($post_id, $post_type, $taxonomy_terms, $order);
	}
	
	/**
	 * Delete a preset
	 */	
	public function delete_preset($post_id) {
		$num_rows = wp_delete_post($post_id, true);
		return $num_rows;
	}	
	
	/**
	 * Relate the terms to the parent-post
	 */	
	public function update_preset_metas($post_id, $post_type, $taxonomy_terms = array(), $order = 0) {
		// update the order meta in the preset
		update_post_meta($post_id, '_order', $order);
		
		// get all taxonomies associated with the post type
		$taxonomies = $this->get_added_taxonomies_names($post_type, 'object');
		
		// check if a term is set for the taxonomy, otherwise set a default
		foreach($taxonomies as $taxonomy) {
			// look if the term is set, otherwise reset the meta 
			if(is_array($taxonomy_terms) && !empty($taxonomy_terms[$taxonomy->name])) {
				$term_object = get_term($taxonomy_terms[$taxonomy->name], $taxonomy->name);
				$term_name = $term_object->name;
				$term_id = intval($term_object->term_id);
			} else {
				$term_name = null;
				$term_id = null;
			}
			
			// update the meta to quickly meta_query the post
			update_post_meta($post_id, '_' . $taxonomy->name, $term_name);				
			update_post_meta($post_id, '_' . $taxonomy->name . '_term_id', $term_id);
		}
		return $post_id;		
	}
	
	/**
	 * Get the preset meta
	 */	
	public function get_preset_metas($post_id, $post_type) {
		// get all taxonomies associated with the post type
		$taxonomies = $this->get_added_taxonomies_names($post_type);
		
		$metas = array();
		foreach($taxonomies as $taxonomy) {
			$metas[$taxonomy]['term_name'] = get_post_meta($post_id, '_' . $taxonomy, true);
			$metas[$taxonomy]['term_id'] = get_post_meta($post_id, '_' . $taxonomy . '_term_id', true);
		}		
		return $metas;
	}
	
	/**
	 * Relate the terms to the parent-post. taxonomy_terms is a
	 * key value pair with taxonomy_name => terms. where terms
	 * can be an array or an int.
	 */	
	public function set_terms($post_parent, $taxonomy_terms) {
		foreach ($taxonomy_terms as $taxonomy => $terms) {
			if(!empty($terms)) {
				if(!is_array($terms)) {
					$terms = array($terms);
				}
				
				// convert all term ids to a number
				$terms = array_map('intval', $terms);
				$terms = array_unique($terms);
			} 
			wp_set_object_terms($post_parent, $terms, $taxonomy, false);	
		}
	}
	

	
}
}

?>