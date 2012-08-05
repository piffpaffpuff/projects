<?php

/**
 * Award class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Award')) {
class Projects_Award extends Projects_Taxonomy {	
	
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
	 * Hook into the init hooks
	 */
	public function hook_init() {
		add_action('admin_menu', array($this, 'add_page'));
		$this->register_taxonomies();
	}

	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {
		//add_action('generate_rewrite_rules', array($this, 'create_rewrite_rules'));
		// add the menu tabs at the right page
		if(!empty($_GET['taxonomy']) && !empty($_GET['post_type']) && $_GET['post_type'] == Projects::$post_type && strpos($_GET['taxonomy'], 'award_') !== false) {
			add_action('all_admin_notices', array($this, 'add_menu_tabs'));	
			add_filter('admin_body_class', array($this, 'body_classes'));		
		}
		
		// 
		/*
if(!empty($_GET['taxonomy']) && $_GET['taxonomy'] == $this->taxonomy) {
			add_filter('wp_dropdown_cats', array($this, 'remove_dropdown_child_terms'));
		}
*/
	}
	
	/**
	 * Body classes
	 */
	public function body_classes($classes) {
		$classes .= ' award-taxonomies'; 
		return $classes;
	}
	
	/**
	 * Add menu tabs
	 */
	public function add_menu_tabs() {
		?>
		<div id="icon-edit" class="icon32 icon32-posts-<?php echo Projects::$post_type; ?>"><br></div>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo admin_url('edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $this->projects->get_internal_name('award_name')); ?>" class="nav-tab<?php $this->selected_menu_tab('award_name'); ?>"><?php echo __('Award Name', 'projects'); ?></a>
			<a href="<?php echo admin_url('edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $this->projects->get_internal_name('award_year')); ?>" class="nav-tab<?php $this->selected_menu_tab('award_year'); ?>"><?php echo __('Award Year', 'projects'); ?></a>
			<a href="<?php echo admin_url('edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $this->projects->get_internal_name('award_category')); ?>" class="nav-tab<?php $this->selected_menu_tab('award_category'); ?>"><?php echo __('Award Category', 'projects'); ?></a>
			<a href="<?php echo admin_url('edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $this->projects->get_internal_name('award_rank')); ?>" class="nav-tab<?php $this->selected_menu_tab('award_rank'); ?>"><?php echo __('Award Rank', 'projects'); ?></a>
		</h2>
		<?php
	}
		
	/**
	 * Select the menu tab
	 */
	public function selected_menu_tab($key) {
		$taxonomy = $this->projects->get_internal_name($key);
		if(!empty($_GET['taxonomy']) && !empty($_GET['post_type']) && $_GET['post_type'] == Projects::$post_type && $_GET['taxonomy'] == $taxonomy) {
			echo ' nav-tab-active';
		} else {
			echo '';
		}
	}
		
	/**
	 * Add a page
	 */
	public function add_page() {
		//add_submenu_page('edit.php?post_type=' . Projects::$post_type, __('Awards 2', 'projects'), __('Awards 2', 'projects'),  'manage_options', 'award_taxonomies', array($this, 'create_page'));
		global $submenu;
		$page = 'edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $this->projects->get_internal_name('award_name');
		$position = 500;
    	$submenu['edit.php?post_type=' . Projects::$post_type][$position] = array(__('Awards 2', 'projects'), 'manage_options' , $page); 
	}
		
	/**
	 * Create page content
	 */
	/*
	public function create_page() {	
		wp_redirect( home_url() );
		exit;
	}
	*/

	/**
	 * Display only root level terms in the admin form
	 */
	/*
public function remove_dropdown_child_terms($output) {
		$args = array(
			'taxonomy' => $this->taxonomy,
			'hide_empty' => false,
			'parent' => 0,
			'depth' => 1
		);		

		$terms = get_terms($this->taxonomy, $args);
		
		ob_start();
		?>
		<select name="parent" id="parent" class="postform">
		<?php foreach($terms as $term) : ?>
			<option class="level-0" value="<?php echo $term->term_id; ?>"><?php echo $term->name; ?></option>
		<?php endforeach; ?>
		</select>
		<?php
		return ob_get_clean();
	}
	
*/
	/**
	 * Create rewrite rules for multiple terms urls
	 */
	/*
	public function create_rewrite_rules() {
		global $wp_rewrite;
		print_r($wp_rewrite);
		$new_rules = array(
			$this->installation->slug . '/project-award/?([^=]+)' => 'index.php?post_type=' . Projects::$post_type . '&' . $this->taxonomy . '=' . $wp_rewrite->preg_index(1)
		);
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}
	*/

	/**
	 * Register the taxonomies
	 */
	public function register_taxonomies() {		
		//$this->add_taxonomy(__('Awards', 'projects'), __('Award', 'projects'), $this->external_key);
		
		// add the sub taxonomies
		$args = array(
			'hierarchical' => false, 
			'show_tagcloud' => false, 
			'public' => true, 
			'show_ui' => false, 
			'show_in_nav_menus' => false
		);
		$this->add_taxonomy(__('Award Names', 'projects'), __('Award Name', 'projects'), 'award_name', $args);
		$this->add_taxonomy(__('Award Years', 'projects'), __('Award Year', 'projects'), 'award_year', $args);
		$this->add_taxonomy(__('Award Categories', 'projects'), __('Award Category', 'projects'), 'award_category', $args);
		$this->add_taxonomy(__('Award Ranks', 'projects'), __('Award Rank', 'projects'), 'award_rank', $args);

		
		// add default terms
		/*
$this->add_default_term($this->taxonomy, __('Name', 'projects'), 'name');
		$this->add_default_term($this->taxonomy, __('Year', 'projects'), 'year');
		$this->add_default_term($this->taxonomy, __('Category', 'projects'), 'category');
		$this->add_default_term($this->taxonomy, __('Rank', 'projects'), 'rank');
*/
	}
	
	/**
	 * Return a sorted list of all array. see 
	 * array_multisort for the sorting parameters.
	 */
	public function get_awards_sorted($sort = array('year', SORT_DESC, 'name', SORT_ASC, 'category', SORT_ASC)) {
		global $wpdb;
		
		// get all award sets from the post meta table
		$query = $wpdb->prepare(
			"SELECT 
				post_id, meta_value 
			FROM 
				$wpdb->postmeta 
			WHERE
				meta_key = %s
			AND 
				meta_value <> ''
			AND
				meta_value IS NOT NULL",
		$this->projects->get_internal_name('awards', true));
		
		$results = $wpdb->get_results($query);
		
		/* get the terms and create an assciative
		array. like this the term names can be
		quickly found by key/id. */
		$term_names = array();
		$terms = get_terms($this->projects->get_internal_name($this->external_key));
		
		foreach($terms as $term) {
			$term_names[$term->term_id] = $term->name;
		}
		
		/* deserialize all award groups and save 
		them into a flat array. */
		$award_groups = array();

		foreach($results as $result) {
			$awards = maybe_unserialize($result->meta_value);
			foreach($awards as $award) {
				// create a unique key to identify the award group
				$key = $award['name'] . '-' . $award['year'] . '-' . $award['category'] . '-' . $award['rank'];
				
				// all award ids for a tax query
				$award['term_id'] = array(
					$award['name'],
					$award['year'],
					$award['category'],
					$award['rank']
				);
				
				/* get the term name for every term id in 
				the award group. then replace the id with 
				the name. */
				if(isset($term_names[$award['name']])) {
					$award['name'] = $term_names[$award['name']];
				}
				if(isset($term_names[$award['year']])) {
					$award['year'] = $term_names[$award['year']];
				}
				if(isset($term_names[$award['category']])) {
					$award['category'] = $term_names[$award['category']];
				}
				if(isset($term_names[$award['rank']])) {
					$award['rank'] = $term_names[$award['rank']];
				}
				
				/* add the new group only if not an identical 
				already exits, if so, just add the post id to
				the existing. */
				if(array_key_exists($key, $award_groups)) {
					$award_groups[$key]['post_id'][] = $result->post_id;
				} else {
					$award['post_id'][] = $result->post_id;
					$award_groups[$key] = $award;
				}
			}
		}

		/* sort the award groups by the desired  column by
		regrouping them into a table array and use a
		multisort. */	
		$award_columns = array();
		
		foreach($award_groups as $key => $value) {
			$award_columns['name'][$key] = strtolower($value['name']);
			$award_columns['year'][$key] = $value['year'];
			$award_columns['category'][$key] = strtolower($value['category']);
			$award_columns['rank'][$key] = strtolower($value['rank']);
			$award_columns['post_id'][$key] = $value['post_id'];
		}
				
		/* in the function prameters the string is always 
		the column by which the array should be sorted.
		replace in the $sort parameters the strings with
		the array items. */	
		foreach($sort as $key => $value) {
			if(is_string($value)) {
				$sort[$key] = $award_columns[$value];
			}
		}
		
		/* merge the function parameters. use indirectly the 
		call_user_func_array to pass dynamic paramters to 
		the array_multisort function. pass a reference of 
		the array that should be sorted because the sorted 
		array isn't returned by array_multisort. */
		$args = array_merge($sort, array(&$award_groups));
		call_user_func_array('array_multisort', $args);

		return $award_groups;
	}
	
	
	public function get_awards_sorted2($sort = array('project_award_year', SORT_DESC, 'project_award_name', SORT_ASC, 'project_award_category', SORT_ASC)) {
		global $wpdb;
		
		// get all award sets from the post meta table
		$query = $wpdb->prepare(
			"SELECT 
				post_id, meta_value 
			FROM 
				$wpdb->postmeta 
			WHERE
				meta_key = %s
			AND 
				meta_value <> ''
			AND
				meta_value IS NOT NULL",
		$this->projects->get_internal_name('awards', true));
		
		$results = $wpdb->get_results($query);
		
		/* get the terms and create an assciative
		array. like this the term names can be
		quickly found by key/id. */
		$taxonomies = $this->get_added_taxonomies(null, 'names');		
		$terms = get_terms($taxonomies);
		$term_names = array();
		foreach($terms as $term) {
			$term_names[$term->term_id] = $term->name;
		}
		
		/* deserialize all award groups and save 
		them into a flat array. */
		$award_groups = array();
		foreach($results as $result) {
			$awards = maybe_unserialize($result->meta_value);
			foreach($awards as $award) {
				// create a unique key to identify the award group
				$key = implode('-', $award);

				// all award ids for a tax query
				$term_ids = array();
				foreach($award as $taxonomy => $term_id) {
					if(!empty($term_id)) {
						$term_ids[] = $term_id;
					}
				}
				
				/* get the term name for every term id in 
				the award group. then replace the id with 
				the name. */
				foreach($award as $taxonomy => $term_id) {
					if(isset($term_names[$term_id])) {
						$award[$taxonomy] = $term_names[$term_id];
					}
				}
				
				// set the term ids
				$award['term_id'] = $term_ids;
				
				/* add the new group only if not an identical 
				already exits, if so, just add the post id to
				the existing. */
				if(array_key_exists($key, $award_groups)) {
					$award_groups[$key]['post_id'][] = $result->post_id;
				} else {
					$award['post_id'][] = $result->post_id;
					$award_groups[$key] = $award;
				}				
			}
		}
		
		/* rebuild the awards list from an item array
		into a column array where the the same value
		of every item is in one array entry. */	
		$award_columns = array();
		
		foreach($award_groups as $award_group) {
			foreach($taxonomies as $taxonomy) {
				$award_columns[$taxonomy][] = strtolower($award_group[$taxonomy]);
			}
			/*
			$award_columns['name'][] = strtolower($award_group[$this->projects->get_internal_name('award_name')]);
			$award_columns['year'][] = $award_group[$this->projects->get_internal_name('award_year')];
			$award_columns['category'][] = strtolower($award_group[$this->projects->get_internal_name('award_category')]);
			$award_columns['rank'][] = strtolower($award_group[$award_group[$this->projects->get_internal_name('award_rank')]);
			*/
		}
				
		/* in the function parameters the string is always 
		the column by which the array should be sorted.
		replace in the $sort parameters the strings with
		the array items. then pick the array columns that
		should be sorted. */	
		foreach($sort as $key => $value) {
			if(is_string($value)) {
				$sort[$key] = $award_columns[$value];
			}
		}

		/* merge the function parameters. use indirectly the 
		call_user_func_array to pass dynamic paramters to 
		the array_multisort function. pass a reference of 
		the array that should be sorted because the sorted 
		array isn't returned by array_multisort. */
		$args = array_merge($sort, array(&$award_groups));
		call_user_func_array('array_multisort', $args);

		return $award_groups;
	}
	
	/**
	 * Get the permalink of multiple terms
	 * http://thereforei.am/2011/10/28/advanced-taxonomy-queries-with-pretty-urls/
	 */
	public function get_term_permalink($term_id, $operator = '+') {				
		if(is_array($term_id) && sizeof($term_id) > 0) {
			$args = array(
				'include' => $term_id
			);
			
			$terms = get_terms($this->taxonomy, $args);
			$size = sizeof($terms);
			$query = get_site_url() . '/' . $this->installation->slug . '/?' . $this->taxonomy . '=';
			
			// build the query
			foreach($terms as $index => $term) {
				$query .= $term->slug;
			
				if($index != $size - 1) {
					$query .= $operator;
				}
			}
			
			return $query;
		} 
		
		return;
	}
	
	/**
	 * Get the permalink for the award
	 */
	public function get_award_permalink($term_id, $operator = '+') {				
		
	}
	
		/**
	 * Get the permalink for the award
	 */
	public function get_award_name($format = '{%name, }{%name, }{%name, }{%name, }') {				
		
	}
	
	/**
	 * Get all registered taxonomies
	 */
	public function get_added_taxonomies($args = null, $type = 'objects') {
		$default_args = array(
	    	'object_type' => array(Projects::$post_type),
	    	'show_ui' => false,
	    	'_builtin' => false
	    );
		
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);
		
		// build the taxonomies
		$taxonomies = get_taxonomies($args, $type);
		foreach($taxonomies as $key => $taxonomy) {
			if(strpos($key, '_award_') === false) {
				unset($taxonomies[$key]);
			}
		}
		return $taxonomies;
	}
}
}

?>