<?php

/**
 * Award class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Award')) {
class Projects_Award extends Projects_Taxonomy {
	
	public $external_key;
	public $taxonomy;	
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		 
		$this->external_key = 'award';
		$this->taxonomy = $this->projects->get_internal_name($this->external_key);
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
		//add_action('admin_menu', array($this, 'add_page'));
		
		$this->register_taxonomies();
	}

	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {
		//add_action('generate_rewrite_rules', array($this, 'create_rewrite_rules'));			

		if(!empty($_GET['taxonomy']) && $_GET['taxonomy'] == $this->taxonomy) {
			add_filter('wp_dropdown_cats', array($this, 'remove_dropdown_child_terms'));
		}
	}
	
	/**
	 * Add a page
	 */
	public function add_page() {
		add_submenu_page('edit.php?post_type=' . Projects::$post_type, __('Awards 2', 'projects'), __('Awards 2', 'projects'), 'manage_options', $this->projects->get_internal_name('award2'), array($this, 'create_page'));
	}
		
	/**
	 * Create page content
	 */
	public function create_page() {	
	}

	/**
	 * Display only root level terms in the admin form
	 */
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
		$this->add_taxonomy(__('Awards', 'projects'), __('Award', 'projects'), $this->external_key);
		
		// add default terms
		$this->add_default_term($this->taxonomy, __('Name', 'projects'), 'name');
		$this->add_default_term($this->taxonomy, __('Year', 'projects'), 'year');
		$this->add_default_term($this->taxonomy, __('Category', 'projects'), 'category');
		$this->add_default_term($this->taxonomy, __('Rank', 'projects'), 'rank');
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
}
}

?>