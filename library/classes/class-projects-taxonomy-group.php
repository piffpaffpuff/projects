<?php

/**
 * Taxonomy group class
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
	 * Flush the permalinks to enable 
	 * the correct rewrite rules.
	 */
	public function add_rewrite_rules() {
		$this->register_taxonomy_groups();
		flush_rewrite_rules();
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
	public function get_project_presets($key, $post_id = null) {
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
		$presets_objects = array();
		
		// do not continue if the group does not exists
		if(!isset($taxonomy_group)) {
			return $presets_objects;
		}
		
		// get the presets for the post
		$result = $projects->get_project_meta('taxonomy_group_' . $taxonomy_group->key, $post_id);
		
		// stop when no results are available
		if(empty($results)) {
			return $presets_objects;
		}
		
		// create the presets objects for the meta
		$presets_objects = $this->construct_presets_objects($taxonomy_group_name, $result, $post_id);
		
		return $presets_objects;
	}
	
	/**
	 * Get all presets. the sort must be a an array with the taxonomy
	 * name as string by which should be. followed by one or more 
	 * sort options as constant (not string!). read the docs about 
	 * array_multisort for a complete list of sort options.
	 *
	 * 		$sort = array(
	 *			'project_award_year',
	 *			SORT_DESC,
	 *			'project_award_name',
	 *			SORT_ASC
	 * 		);
	 */	
	public function get_presets($key, $sort = null, $join = true) {
		global $wpdb;
	
		// check if the group exists and load it
		$projects = new Projects();
		$taxonomy_group_name = $projects->get_internal_name($key);
		$taxonomy_group = $this->get_added_taxonomy_group($taxonomy_group_name);
		$presets_posts = array();
		
		// do not continue if the group does not exists
		if(!isset($taxonomy_group)) {
			return $presets_posts;
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
			
			// cache the query for 7 days
	    	set_transient('get_presets_meta_' . $key, $results, 60*60*24*7);
		}
		
		// stop when no results are available
		if(empty($results)) {
			return $presets_posts;
		}
					
		// unify the preset array from every post into one.
		// at the same time create a posts array where all 
		// presets are regrouped by row.
		$presets_merged = array();
		foreach($results as $result) {
			$presets = maybe_unserialize($result->meta_value);
			// construct the prestes rows and merge it into the posts array
			$presets_objects = $this->construct_presets_objects($taxonomy_group_name, $presets, $result->post_id);
			$presets_posts = array_merge($presets_posts, $presets_objects);
			// merge the presets into one big column ordered array
			$presets_merged = array_merge_recursive($presets_merged, $presets);
		}
		
		// replace in the merged presets array all term ids
		// with their corresponding name. those names are later 
		// used to sort the columns.
		$presets_names = array();
		foreach($presets_merged as $taxonomy => $term_ids) {
			foreach($term_ids as $term_key => $term_id) {
				// replace the id with the name. it is important to
				// also add wmpty values otherwise the column count
				// is inconsistent for the sorting.
				if(empty($this->terms_by_id[$term_id])) {
					$presets_names[$taxonomy][$term_key] = '';
				} else {
					$presets_names[$taxonomy][$term_key] = $this->terms_by_id[$term_id]->name;
				}
			}
		}
		
		// sort the array
		if(isset($sort) && !empty($sort)) {
			// parse the sort string, check if a the culmun 
			// exists in the presets_names, then pass this 
			// column to the array_multisort.
			foreach($sort as $key => $value) {
				if(is_string($value)) {
					// generate the internal name in case the key was passed
					$taxonomy_name = $projects->get_internal_name($value);
					if(isset($presets_names[$taxonomy_name])) {
						// pass the value as reference
						$sort[$key] = &$presets_names[$taxonomy_name];
					}
				}
			}
			
			
			// pass the table that should be sorted as last 
			// paramter by reference.
			$sort[] = &$presets_posts;
	
			// sort the array with an array multisort. this is called
			// as user function to pass dynamic parameters.
			call_user_func_array('array_multisort', $sort);
		}
		
		// group identical presets
		if($join) {
			//$taxonomies = $this->get_added_taxonomies_by_group($taxonomy_group_name);
			$presets_grouped = array();
			foreach($presets_posts as $presets_post) {
				// create a unique key to identify the preset
				$group_key = 'group';
				foreach($presets_post['taxonomies'] as $key => $value) {
					$group_key .= (string) $value['term_id'];
				}				
				// add the post_id to an existing group key 
				// or add a completely new group key.
				if(isset($presets_grouped[$group_key])) {
					// combine the post_id value. also check if a 
					// post id is added multiple times. mostly this 
					// is the case when a single post has two times 
					// equal presets (probably empty ones).
					$presets_grouped[$group_key]['post_id'] = array_unique(array_merge($presets_grouped[$group_key]['post_id'], $presets_post['post_id']));
				} else {
					$presets_grouped[$group_key] = $presets_post;
					$presets_grouped[$group_key]['post_id'] = $presets_post['post_id'];
				}
			}
			$presets_posts = $presets_grouped;
		}		

		// free some memory and return the sorted array
		$presets_merged = null;
		$presets_names = null;
		/*echo '<pre>';
		print_r($presets_posts);
		echo '</pre>';*/
		return $presets_posts;
	}
	
	/**
	 * Construct a preset object
	 */
	public function construct_presets_objects($key, $presets, $post_id) {
		$projects = new Projects();
		$taxonomy_group_name = $projects->get_internal_name($key);
	
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
	
		// create the rows array. in the presets array the
		// fields are grouped by taxonomy column. in the rows
		// array they are regrouped by row. 
		$presets_objects = array();
		foreach($presets as $taxonomy => $term_ids) {
			foreach($term_ids as $term_key => $term_id) {
				// create the posts array
				$presets_objects[$term_key]['post_id'] = array($post_id);
				$presets_objects[$term_key]['taxonomies'][$taxonomy]['term_id'] = $term_id;
				$presets_objects[$term_key]['taxonomies'][$taxonomy]['name'] = '';
				$presets_objects[$term_key]['taxonomies'][$taxonomy]['slug'] = '';
				if(!empty($this->terms_by_id[$term_id])) {
					$presets_objects[$term_key]['taxonomies'][$taxonomy]['name'] = $this->terms_by_id[$term_id]->name;
					$presets_objects[$term_key]['taxonomies'][$taxonomy]['slug'] = $this->terms_by_id[$term_id]->slug;
				}
			}
		}
		return $presets_objects;
	}
	
	
	/**
	 * Clear the meta query cache for the presets
	 */
	public function clear_presets_meta_cache($key) {
		return delete_transient('get_presets_meta_' . $key);
	}
	
	/**
	 * Get the permalink for the taxonomy group preset
	 */
	public function get_preset_permalink($preset) {
		if(count($preset['post_id']) > 1) {
			$taxonomy_slugs = array();
			foreach($preset['taxonomies'] as $taxonomy => $term) {
				$taxonomy_slugs[$taxonomy] = $term['slug'];
			}
			return get_site_url() . '?' . urlencode(http_build_query($taxonomy_slugs));
		} else {
			return get_permalink($preset['post_id'][0]);
		}
	}
}
}

?>