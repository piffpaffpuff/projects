<?php

/**
 * Award class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Award')) {
class Projects_Award extends Projects_Taxonomy {	
	
	private $terms_by_id;
	
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
		// add_action('generate_rewrite_rules', array($this, 'create_rewrite_rules'));
		// add the menu tabs at the right page
		if($this->is_taxonomy_tab()) {
			add_action('all_admin_notices', array($this, 'add_menu_tabs'));	
			add_filter('admin_body_class', array($this, 'body_classes'));		
		}
	}
	
	/**
	 * Body classes to remove the default icon 
	 * and title from the taxonomy edit screen.
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
		if($this->is_taxonomy_tab() && $_GET['taxonomy'] == $taxonomy) {
			echo ' nav-tab-active';
		} else {
			echo '';
		}
	}
	
	/**
	 * Check if one of the award taxonomy pages 
	 * is currently displayed.
	 */
	public function is_taxonomy_tab() {
		if(!empty($_GET['taxonomy']) && !empty($_GET['post_type']) && $_GET['post_type'] == Projects::$post_type && strpos($_GET['taxonomy'], 'award_') !== false) {
			return true;
		} else {
			return false;
		}
	}
		
	/**
	 * Add a page
	 */
	public function add_page() {
		global $submenu;
		
		/* set the css class "current" of the menu item 
		with a span. like this the menu item can be styled 
		as "current". this is some kind of a hack because 
		there are no filters or actions to add custom css 
		classes to the submenu list items. */
		$name = __('Awards', 'projects');
		if($this->is_taxonomy_tab()) {
			$name = '<span class="current">' . $name . '</span>';
		}
				
		/* directly add a pseudo item page to the submenu 
		array. the item redirect to the hidden taxonomy 
		edit screen. the item is positioned at the end of 
		the submenu. */
		$page = 'edit-tags.php?post_type=' . Projects::$post_type . '&taxonomy=' . $this->projects->get_internal_name('award_name');
		$position = 500;
    	$submenu['edit.php?post_type=' . Projects::$post_type][$position] = array($name, 'manage_categories' , $page); 
	}
		
	/**
	 * Register the taxonomies
	 */
	public function register_taxonomies() {				
		// add the sub taxonomies
		$args = array(
			'hierarchical' => false, 
			'public' => true, 
			'show_ui' => false, 
			'show_in_nav_menus' => false
		);
		$this->add_taxonomy(__('Award Names', 'projects'), __('Award Name', 'projects'), 'award_name', $args);
		$this->add_taxonomy(__('Award Years', 'projects'), __('Award Year', 'projects'), 'award_year', $args);
		$this->add_taxonomy(__('Award Categories', 'projects'), __('Award Category', 'projects'), 'award_category', $args);
		$this->add_taxonomy(__('Award Ranks', 'projects'), __('Award Rank', 'projects'), 'award_rank', $args);
	}
	
	/**
	 * build award objects out of the project meta.
	 * the post_id is needed to create a unified object,
	 * because the post_id is needed when same awards
	 * are grouped together.
	 */
	public function get_awards_from_meta($meta, $post_id) {
		// create the list of ids. like this only one 
		// database call is needed to get all terms.
		if(empty($this->terms_by_id)) {
			$this->terms_by_id = array();
			$taxonomies = $this->get_added_taxonomies(null, 'names');
			$terms = get_terms($taxonomies);
					
			foreach($terms as $term) {
				$this->terms_by_id[$term->term_id] = $term;
			}
		}
		
		// build the objects list
		$objects = array();
		$awards = maybe_unserialize($meta);

		// go through the meta array and construct
		foreach($awards as $award) {
			$object = new stdClass();
			
			// create the object for every id in the meta. 
			// also add the post_id to every object.
			foreach($award as $key => $term_id) {
				// set the project count for every object
				$object->count = 1;
				// set the post_id for every object
				$object->post_id = $post_id;
				// set the term when the id was found in the list
				if(isset($this->terms_by_id[$term_id])) {
					$term = $this->terms_by_id[$term_id];
				} else {
					$term = null;
				}
				$object->{$key} = $term;
			}
			$objects[] = $object;
		}
		
		return $objects;
	}
	
	/**
	 * get the award objects for a single project
	 */
	public function get_project_awards($post_id = null) {	
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		$meta = $this->projects->get_project_meta('awards', $post_id);
		$objects = $this->get_awards_from_meta($meta, $post_id);
		return $objects;
	}
	
	/**
	 * get a sorted list of all award objects
	 */
	public function get_sorted_awards($group = true, $sort = array('project_award_year', 'project_award_name', 'project_award_category'), $order = array(-1, 1, 1)) {		
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
				meta_value IS NOT NULL", $this->projects->get_internal_name('awards', true));
		
		$metas = $wpdb->get_results($query);
		$taxonomies = $this->get_added_taxonomies(null, 'names');	
		
		// build the list by merging every objects array 
		$awards = array();
		foreach($metas as $meta) {
			// parse every meta and build the list with the objects
			$objects = $this->get_awards_from_meta($meta->meta_value, $meta->post_id);
			$awards = array_merge($awards, $objects);
		}
		
		// sort the array
		$this->sort_list($awards, $sort, $order);
		
		// group awards that are exactly the same into a single object
		if($group) {
			$grouped_awards = array();
			foreach($awards as $award) {
				// create a unique key to identify the award
				$fields = $this->get_taxonomy_grouped_award_fields($award, 'term_id');
				$key = implode('-', $fields);
				
				// add the key if it doesn't exits.
				// otherwise rise the count by one.
				if(isset($grouped_awards[$key])) {
					$grouped_awards[$key]->count += 1;
					$grouped_awards[$key]->post_id = null;
				} else {
					$grouped_awards[$key] = $award;
				}
			}
			$awards = $grouped_awards;
		}

		return $awards;
	}
	
	/**
	 * Sort the awards objects array by an arbitrary number of fields
	 */	
	function sort_list(&$list, $fields, $order) {
		// only sort when fields and order match
		if(count($fields) !== count($order)) {
			return;
		}
		
		// sort the array with closures
		usort($list, function($a, $b) use ($fields, $order) {
			for($i = 1; $i < count($fields); $i++) {
				if($a->$fields[$i-1]->name == $b->$fields[$i-1]->name) {
					return $a->$fields[$i]->name < $b->$fields[$i]->name ? $order[$i] * -1 : $order[$i] * 1;
				}
			}
			
			return $a->$fields[0]->name < $b->$fields[0]->name ? $order[0] * -1 : $order[0] * 1;
		});
	}
	
	/**
	 * Get the keyed term ids for the award object
	 */
	public function get_taxonomy_grouped_award_fields($award, $field) {	
		$fields = array();
		$taxonomies = $this->get_added_taxonomies(null, 'names');
					
		foreach($taxonomies as $taxonomy) {
			if(isset($award->$taxonomy->$field)) {
				$fields[$taxonomy] = $award->$taxonomy->$field;
			} 
		}
		
		return $fields;
	}
	
	/**
	 * Get the permalink for the award object
	 */
	public function get_award_permalink($award) {
		if($award->count > 1) {
			$fields = $this->get_taxonomy_grouped_award_fields($award, 'slug');
			return get_site_url() . '?' . http_build_query($fields);
		} else {
			return get_permalink($award->post_id);
		}
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
		
		// build the taxonomies. remove all non award taxonomies.
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