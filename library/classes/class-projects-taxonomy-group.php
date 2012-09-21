<?php

/**
 * Taxonomy class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Taxonomy_Group')) {
class Projects_Taxonomy_Group extends Projects_Taxonomy {

	public static $taxonomy_groups = array();
	public static $taxonomies = array();
	public $terms_by_id;
		
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
		// check if the current taxonomy is part of a taxonomy group.
		// if yes, show tabs for every taxonomy above the edit screen.
		$taxonomy_group = $this->get_added_taxonomy_group_by_taxonomy($_GET['taxonomy']);
		?>
		<?php if(isset($taxonomy_group)) : ?>
			<?php $taxonomies = $this->get_added_taxonomies_by_group($taxonomy_group->name); ?>
			<div id="icon-edit" class="icon32 icon32-posts-<?php echo Projects::$post_type; ?>"><br></div>
			<h2 class="nav-tab-wrapper">
			<?php foreach($taxonomies as $taxonomy) : ?>
				<a href="<?php echo 'edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $taxonomy->name; ?>" class="nav-tab<?php $this->menu_tab_selected($taxonomy->name); ?>"><?php echo $taxonomy->singular_label; ?></a>
			<?php endforeach; ?>
			</h2>
		<?php endif; ?>
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
			$taxonomy_group = $this->get_added_taxonomy_group_by_taxonomy($_GET['taxonomy']);			
			if(isset($taxonomy_group)) {
				return true;
				// && isset($taxonomy_group->taxonomies[$_GET['taxonomy']])
			}
			return false;
		}
		return false;
	}

	/**
	 * Add the group pages
	 */
	public function add_pages() {		
		$parent_slug = 'edit.php?post_type=' . Projects::$post_type;
		
		// add the taxonomy group page
		foreach (self::$taxonomy_groups as $taxonomy_group) {			
			// set the css class "current" of the menu item 
			// with a span. like this the menu item can be styled 
			// as "current". this is some kind of a hack because 
			// there are no filters or actions to add custom css 
			// classes to the submenu list items.
			if($this->is_taxonomy_group_tab() && isset($taxonomy_group->taxonomies[$_GET['taxonomy']])) {
				$menu_title = '<span class="current">' . $taxonomy_group->plural_label . '</span>';
			} else {
				$menu_title = $taxonomy_group->plural_label;	
			}
		
			// get the first taxonomy in the group to link to
			$taxonomies = $taxonomy_group->taxonomies;
			reset($taxonomies);
			$first_taxonomy_name = key($taxonomies);	

			// set the menu slug to the first taxonomy
			$menu_slug = 'edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $taxonomies[$first_taxonomy_name]->name;
			
			// add the page
			add_submenu_page($parent_slug, $taxonomy_group->plural_label, $menu_title, 'manage_categories', $menu_slug);
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
		
		$groups = array(
			array(
				'plural_label' => __('Foo Names', 'projects'),
				'singular_label' => __('Foo Name', 'projects'),
				'key' => 'name'
			),
			array(
				'plural_label' => __('Foo Years', 'projects'),
				'singular_label' => __('Foo Year', 'projects'),
				'key' => 'year'
			)
		);
		$this->add_taxonomy_group(__('Foos', 'projects'), __('Foo', 'projects'), $groups, 'foo');
	}
	
	/**
	 * Create a custom taxonomy group
	 */	
	public function add_taxonomy_group($plural_label, $singular_label, $groups, $key, $position = null) {		
		$projects = new Projects();
		$projects_type = new Projects_Type();
		$projects_installation = new Projects_Installation();
		
		// add the group to a keyed array to refind them
		$taxonomy_group_name = $projects->get_internal_name($key);
		$taxonomy_group = new stdClass();
		$taxonomy_group->name = $taxonomy_group_name;
		$taxonomy_group->key = $key;
		$taxonomy_group->plural_label = $plural_label;
		$taxonomy_group->singular_label = $singular_label;
		$taxonomy_group->position = $position;
		$taxonomy_group->taxonomies = null;
		self::$taxonomy_groups[$taxonomy_group_name] = $taxonomy_group;
	
		// create a taxonomy for every single taxonomy in the group
		foreach ($groups as $group) {
			if(empty($group['plural_label']) || empty($group['singular_label']) || empty($group['key'])) {
				continue;	
			}
			
			// register taxonomy
			$default_args = array(
				'hierarchical' => false, 
				'show_ui' => false, 
				'show_in_nav_menus' => false,
				'rewrite' => array('slug' => $projects_installation->slug . '/' . sprintf(__('project-%s', 'projects'), $key . '-' . $group['key']), 'with_front' => true),
				'post_type' => array(Projects::$post_type)
			);
							
			// merge the default and additional args
			if(isset($group['args'])) {
				$args = $group['args'];			
			} else {
				$args = array();
			}
			$args = wp_parse_args($args, $default_args);
			
			// generate taxonomy name and setup
			$taxonomy_name = $projects->get_internal_name($key . '_' . $group['key']);
			$taxonomy = new stdClass();
			$taxonomy->group_name = $taxonomy_group_name;
			$taxonomy->group_key = $key;
			$taxonomy->name = $taxonomy_name;
			$taxonomy->key = $key . '_' . $group['key'];
			$taxonomy->plural_label = $group['plural_label'];
			$taxonomy->singular_label = $group['singular_label'];
			self::$taxonomies[$taxonomy_name] = $taxonomy;
			
			// add the taxonomy to the group list for quicker finding
			$taxonomy_group->taxonomies[$taxonomy_name] = &self::$taxonomies[$taxonomy_name];
			
			// register
			$this->add_taxonomy($group['plural_label'], $group['singular_label'], $taxonomy_name, $args);
		}
	}

	/**
	 * Get taxonomy group key/name
	 */	
	public function get_added_taxonomy_group($key = null) {
		// return all
		if(empty($key)) {
			return self::$taxonomy_groups;
		}
		
		// find a specific group
		$projects = new Projects();
		$taxonomy_group_name = $projects->get_internal_name($key);
		if(isset(self::$taxonomy_groups[$taxonomy_group_name])) {
			return self::$taxonomy_groups[$taxonomy_group_name];
		}
		return;
	}
	
	/**
	 * Get taxonomy group by taxonomy key/name
	 */	
	public function get_added_taxonomy_group_by_taxonomy($key, $type = 'objects') {		
		$projects = new Projects();
		$taxonomy_name = $projects->get_internal_name($key);
		if(isset(self::$taxonomies[$taxonomy_name])) {
			$taxonomy_group = self::$taxonomy_groups[self::$taxonomies[$taxonomy_name]->group_name];
			if($type == 'names') {
				return $taxonomy_group->name;
			} else {
				return $taxonomy_group;
			}
		}
		return;
	}

	/**
	 * Get taxonmies by taxonomy group key/name
	 */	
	public function get_added_taxonomies_by_group($key, $type = 'objects') {
		$projects = new Projects();
		$taxonomy_group_name = $projects->get_internal_name($key);
		if(isset(self::$taxonomy_groups[$taxonomy_group_name])) {
			$taxonomies = self::$taxonomy_groups[$taxonomy_group_name]->taxonomies;
			if($type == 'names') {
				$names = null;
				foreach($taxonomies as $taxonomy) {
					$names[$taxonomy->name] = $taxonomy->name;
				}
				return $names;
			} else {
				return $taxonomies;
			}
		}
		return;
	}
	
	/**
	 * Get project preset
	 */	
	public function get_project_preset($key, $post_id = null) {
		if(empty($post_id)) {
			global $post;
			if(empty($post)) {
				return;
			}
			$post_id = $post->ID;
		}
		
		// check if the group exists and load it
		$projects = new Projects();
		$taxonomy_group_name = $projects->get_internal_name($key);
		$taxonomy_group = $this->get_added_taxonomy_group($taxonomy_group_name);
		
		// do not continue if the group does not exists
		if(!isset($taxonomy_group)) {
			return;
		}
		
		// get the presets for the post
		$meta_name = $projects->get_internal_name('taxonomy_group_' . $taxonomy_group->key, true);
		$presets = get_post_meta($post_id, $meta_name, true);
		
	}
	
	/**
	 * Get all presets 
	 */	
	public function get_presets($key, $join = true, $sort = array()) {
		global $wpdb;
	
		// check if the group exists and load it
		$projects = new Projects();
		$taxonomy_group_name = $projects->get_internal_name($key);
		$taxonomy_group = $this->get_added_taxonomy_group($taxonomy_group_name);
		
		// do not continue if the group does not exists
		if(!isset($taxonomy_group)) {
			return;
		}
		
		// get the presets for all posts from cache or query again 
		$cache = get_transient('get_presets_meta_' . $key);
		if($cache) {
			$results = $cache;
		} else {		
			// get presets from all posts
			$sql = $wpdb->prepare(
				"SELECT post_id, meta_value 
				FROM $wpdb->postmeta 
				WHERE meta_key = %s
				AND meta_value <> ''
				AND meta_value IS NOT NULL", 
			$projects->get_internal_name('taxonomy_group_' . $taxonomy_group->key, true));
			
			// query the meta
			$results = $wpdb->get_results($sql);
			
			// cache the query
	    	set_transient('get_presets_meta_' . $key, $results, 60*60*24);
		}
		
		// create the list of term ids. like this only one 
		// database call is needed to get all terms.
		if(empty($this->terms_by_id)) {
			$this->terms_by_id = array();
			$taxonomies = $this->get_added_taxonomies_by_group($taxonomy_group_name, 'names');
			$terms = get_terms($taxonomies);
			foreach($terms as $term) {
				$this->terms_by_id[$term->term_id] = $term;
			}
		}	
				
		// unify the preset array from every post into one
		$presets_post_ids = array('post_id' => array());
		$presets_merged = array();
		foreach($results as $result) {
			$presets = maybe_unserialize($result->meta_value);
			$presets_merged = array_merge_recursive($presets_merged, $presets);
			// push the post_id for the first taxonomy in the 
			// preset into the post_ids array. the post_id is 
			// used for the reversed sorting of the array.
			reset($presets);
			$first_preset_key = key($presets);
			foreach($presets[$first_preset_key] as $term_ids) {
				$presets_post_ids['post_id'][] = $result->post_id;
			}
		}
		
		// add the names to the names array to sort it. create
		// in the posts array the same number of rows with the
		// item information to mutilsort the posts array with 
		// columns from the names array.
		$presets_posts = array();
		$presets_names = array();
		foreach($presets_merged as $taxonomy => $term_ids) {
			foreach($term_ids as $term_key => $term_id) {
				// create the posts array
				$presets_posts[$term_key]['post_id'] = array($presets_post_ids['post_id'][$term_key]);
				$presets_posts[$term_key]['taxonomies'][$taxonomy]['term_id'] = $term_id;
				$presets_posts[$term_key]['taxonomies'][$taxonomy]['name'] = '';
				$presets_posts[$term_key]['taxonomies'][$taxonomy]['slug'] = '';
				// create the names array
				// replace the id with the name
				$presets_names[$taxonomy][$term_key] = '';
				if(!empty($this->terms_by_id[$term_id])) {
					$presets_names[$taxonomy][$term_key] = $this->terms_by_id[$term_id]->name;
					$presets_posts[$term_key]['taxonomies'][$taxonomy]['name'] = $this->terms_by_id[$term_id]->name;
					$presets_posts[$term_key]['taxonomies'][$taxonomy]['slug'] = $this->terms_by_id[$term_id]->slug;
				}
			}
		}
	
		// sort the posts array by coluns in the names array
		array_multisort($presets_names['project_award_year'], SORT_DESC, $presets_names['project_award_name'], SORT_ASC, $presets_posts);
		echo '<pre>';
		print_r($presets_posts);
		//print_r($presets_names);
		echo '</pre>';
		// group identical presets
		if($join) {
			//$taxonomies = $this->get_added_taxonomies_by_group($taxonomy_group_name);
			$presets_grouped = array();
			foreach($presets_posts as $presets_post) {
				// create a unique key to identify the preset
				$group_key = '';
				foreach($presets_post['taxonomies'] as $key => $value) {
					$group_key .= (string) $value['term_id'];
				}				
				// add the post_id to an existing group key 
				// or add a completely new group key.
				if(isset($presets_grouped[$group_key])) {
					$presets_grouped[$group_key]['post_id'] = array_merge($presets_grouped[$group_key]['post_id'], $presets_post['post_id']);
				} else {
					$presets_grouped[$group_key] = $presets_post;
					$presets_grouped[$group_key]['post_id'] = $presets_post['post_id'];
				}
			}
			$presets_posts = $presets_grouped;
		}		
		
		echo '<pre>';
		print_r($presets_posts);
		//print_r($presets_names);
		echo '</pre>';
		
		// return sorted array
		return $presets_posts;
	}
	
	/**
	 * Construct a preset object
	 */
	public function construct_presets_objects($key, $presets, $post_id) {
			/*
			$presets_posts = array();
			$presets_names = array();
			foreach($presets as $taxonomy => $term_ids) {
				foreach($term_ids as $term_key => $term_id) {
					// create the posts array
					$presets_posts[$term_key]['post_id'] = array($presets_post_ids['post_id'][$term_key]);
					$presets_posts[$term_key]['taxonomies'][$taxonomy]['term_id'] = $term_id;
					$presets_posts[$term_key]['taxonomies'][$taxonomy]['name'] = '';
					$presets_posts[$term_key]['taxonomies'][$taxonomy]['slug'] = '';
					// create the names array
					// replace the id with the name
					$presets_names[$taxonomy][$term_key] = '';
					if(!empty($this->terms_by_id[$term_id])) {
						$presets_names[$taxonomy][$term_key] = $this->terms_by_id[$term_id]->name;
						$presets_posts[$term_key]['taxonomies'][$taxonomy]['name'] = $this->terms_by_id[$term_id]->name;
						$presets_posts[$term_key]['taxonomies'][$taxonomy]['slug'] = $this->terms_by_id[$term_id]->slug;
					}
				}
			}
			return $presets_posts;
			*/
	}
	
	
	/**
	 * Clear the meta query cache for presets
	 */
	public function clear_presets_meta_cache($key) {
		return delete_transient('get_presets_meta_' . $key);
	}
	
	/**
	 * Get the permalink for the taxonomy group preset
	 */
	public function get_award_permalink($preset) {
		/*if(count($preset['post_id']) > 1) {
			$slugs = array();
			foreach($preset['taxonomies'] as $key => $value) {
				
			}
		//	$fields = $this->get_taxonomy_grouped_award_fields($award, 'slug');
			return get_site_url() . '?' . http_build_query($fields);
		} else {
			return get_permalink($preset['post_id'][0]);
		}*/
	}
}
}

?>