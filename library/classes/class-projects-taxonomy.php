<?php

/**
 * Taxonomy class
 */

/**
 * Post type class
 */
if (!class_exists('Projects_Taxonomy')) {
class Projects_Taxonomy {

	public $slug;	
	
	/**
	 * Constructor
	 */
	public function __construct() {	
	}
	
	/**
	 * Load the class hooks
	 */
	public function load() {
		// activation hooks
		register_activation_hook(Projects::$plugin_file_path, array($this, 'add_term_meta_table'));
		register_activation_hook(Projects::$plugin_file_path, array($this, 'add_rewrite_rules'));
		register_deactivation_hook(Projects::$plugin_file_path, array($this, 'remove_rewrite_rules'));

		// load hooks
		add_action('init', array($this, 'hook_init'));
		add_action('admin_init', array($this, 'hooks_admin'));
	}
		
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {		
		global $wpdb;

		// Register term meta table in wpdb
		$wpdb->projects_termmeta = $wpdb->prefix . 'projects_termmeta';
	
		// Register
		$this->register_taxonomies();
	}
	
	/**
	 * Load the admin hooks
	 */
	public function hooks_admin() {
	}
	
	/**
	 * Add the term meta table to store
	 * new fields per meta.
	 */
	public function add_term_meta_table() {
		global $wpdb;
		
		// Check the collation
		$collate = '';
	    if($wpdb->has_cap('collation')) {
			if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
	    }
	    
		// Install table, if it doesnt exist already
		$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "projects_termmeta" . " (
			`meta_id` bigint(20) unsigned NOT NULL auto_increment,
			`projects_term_id` bigint(20) unsigned NOT NULL default '0',
			`meta_key` varchar(255) default NULL,
			`meta_value` longtext,
			PRIMARY KEY (meta_id),
			KEY term_id (projects_term_id),
			KEY meta_key (meta_key) ) $collate;";
			
	    $wpdb->query($sql);
	}
	
	/**
	 * Flush the permalinks to enable 
	 * the correct rewrite rules.
	 */
	public function add_rewrite_rules() {
		$this->register_taxonomies();
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
	 * Register the taxonomies
	 */
	public function register_taxonomies() {		
		$this->add_taxonomy(__('Types', 'projects'), __('Type', 'projects'), 'type');
		$this->add_taxonomy(__('Techniques', 'projects'), __('Technique', 'projects'), 'technique');
		$this->add_taxonomy(__('Tasks', 'projects'), __('Task', 'projects'), 'task');
		$this->add_taxonomy(__('Agencies', 'projects'), __('Agency', 'projects'), 'agency', array('website' => true));
		$this->add_taxonomy(__('Clients', 'projects'), __('Client', 'projects'), 'client', array('website' => true));
		$this->add_taxonomy(__('Tags', 'projects'), __('Tag', 'projects'), 'tag', array('hierarchical' => false));
		
		// Create a hidden taxonomy for the project stati
		$projects = new Projects();
		$taxonomy = $projects->get_internal_name('status');
		$this->add_taxonomy(__('Stati', 'projects'), __('Status', 'projects'), 'status', array('hierarchical' => false, 'show_ui' => false, 'show_in_nav_menus' => false));
		$this->add_default_term($taxonomy, __('Completed', 'projects'), 'completed');
		$this->add_default_term($taxonomy, __('In Progress', 'projects'), 'inprogress');
		$this->add_default_term($taxonomy, __('Planned', 'projects'), 'planned');
	}
	
	/**
	 * Create a custom taxonomy
	 */	
	public function add_taxonomy($plural_label, $singular_label, $key, $args = null) {	
		$projects = new Projects();
		$projects_installation = new Projects_Installation();
		$taxonomy_name = $projects->get_internal_name($key);
	
		$labels = array(
		    'name' => $plural_label,
		    'singular_name' => $singular_label,
		    'search_items' => sprintf(__('Search %s', 'projects'), $plural_label),
		    'all_items' => sprintf(__('All %s', 'projects'), $plural_label),
		    'parent_item' => sprintf(__( 'Parent %s', 'projects'), $plural_label),
    		'parent_item_colon' => sprintf(__( 'Parent %s:', 'projects'), $plural_label),
		    'edit_item' => sprintf(__('Edit %s', 'projects'), $singular_label),
		    'update_item' => sprintf(__('Update %s', 'projects'), $singular_label),
		    'add_new_item' => sprintf(__('Add New %s', 'projects'), $singular_label),
		    'new_item_name' => sprintf(__('New %s Name', 'projects'), $singular_label),
		    'separate_items_with_commas' => sprintf(__('Separate %s with commas', 'projects'), $plural_label),
		    'add_or_remove_items' => sprintf(__('Add or remove %s', 'projects'), $plural_label),
		    'choose_from_most_used' => sprintf(__('Choose from the most used %s', 'projects'), $plural_label),
		    'menu_name' => $plural_label
		);
		
		$default_args = array(
			'labels' => $labels,
	    	'rewrite' => array('slug' => $projects_installation->slug . '/' . Projects::$post_type . '-' . $key, 'with_front' => true),
	    	'hierarchical' => true,
			'show_ui' => true,
			'post_type' => Projects::$post_type,
			'website' => false
		);
		
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);
		
		// register
		register_taxonomy($taxonomy_name, $args['post_type'], $args);
		
		// add an extra website field for the taxonomy
		// when 'website' is set to true in the args.
		if($args['website']) {
			add_action($taxonomy_name . '_add_form_fields', array($this, 'extra_fields_add_form'));
			add_action($taxonomy_name . '_edit_form_fields', array($this, 'extra_fields_edit_form'));
			add_action('created_' . $taxonomy_name, array($this, 'save_extra_fields'));
			add_action('edited_' . $taxonomy_name, array($this, 'save_extra_fields'));
			add_action('delete_' . $taxonomy_name, array($this, 'delete_extra_fields'));
		}
	}

	/**
	 * Remove a custom taxonomy
	 */	
	public function remove_taxonomy($key) {
		global $wp_taxonomies;
		
		$projects = new Projects();
		$args = array(
			'name' => $projects->get_internal_name($key)
		);
		
		$taxonomies = $this->get_added_taxonomies($args, 'names');

		foreach($taxonomies as $taxonomy) {
			if(taxonomy_exists($taxonomy)) {
				unset($wp_taxonomies[$taxonomy]);
			}
		}
	}

	/**
	 * Add a default term to a taxonomy
	 */	
	public function add_default_term($taxonomy, $label, $slug, $args = null) {
		$existing_term_id = term_exists($slug, $taxonomy);
		if(empty($existing_term_id)) {
			$default_args = array(
				'slug' => $slug
			);
			
			// merge the default and additional args
			$args = wp_parse_args($args, $default_args);
			
			// add the term
			$term = wp_insert_term($label, $taxonomy, $args);
		}
	}
	
	/**
	 * Extra fields for the "Add" screen of the taxonomy
	 */	
	public function extra_fields_add_form($term) { 
		?>
		<div class="form-field">
			<label for="website"><?php _e('Website', 'projects' ); ?></label>
			<input type="text" name="website" id="website" value="">
			<p class="description"><?php _e("The Website URL is optional. Some themes may use it. Type in a complete URL like <code>http://www.example.com</code>.", 'projects'); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Extra fields for the "Edit" screen of the taxonomy
	 */	
	public function extra_fields_edit_form($term) {
		// Check for existing taxonomy meta for term ID.
		$term_id = $term->term_id;
		$term_meta = $this->get_term_meta($term_id, 'website'); 
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="website"><?php _e('Website', 'projects' ); ?></label></th>
			<td>
				<input type="text" name="website" id="website" value="<?php echo (!empty($term_meta)) ? esc_attr($term_meta) : ''; ?>">
				<p class="description"><?php _e("The Website URL is optional. Some themes may use it. Use full urls like <code>http://www.example.com</code>.", 'projects'); ?></p>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * Save the term extra fields
	 */	
	public function save_extra_fields($term_id) {
		$projects = new Projects();	
		$key = $projects->get_internal_name('website', true);
		$this->set_term_meta($term_id, $key, $_POST['website']);
	}
	
	/**
	 * Delete the term extra fields
	 */	
	public function delete_extra_fields($term_id) {
		$projects = new Projects();	
		$key = $projects->get_internal_name('website', true);
		$this->set_term_meta($term_id, $key);
	}
	
	/**
	 * Get all registered taxonomies
	 */
	public function get_added_taxonomies($args = null, $type = 'objects') {
		$default_args = array(
	    	'object_type' => array(Projects::$post_type),
			'show_ui' => true
		);
		
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);

		return get_taxonomies($args, $type);
	}
	
	/**
	 * Get a project taxonomy
	 */	
	public function get_project_taxonomy($post_id, $key, $hierarchical = true, $args = null) {	
		$projects = new Projects();	
		$taxonomy = $projects->get_internal_name($key);
		$terms = wp_get_object_terms($post_id, $taxonomy, $args); 
		
		if(!is_wp_error($terms) && sizeof($terms) > 0) {
			// return the flat tree
			if(!$hierarchical) {
				return $terms;
			}
			
			// return the hierarchical tree		
			$childs = array();
		
			// find all childs
			foreach($terms as $term) {
				$childs[$term->parent][] = $term;
			}
		
			// cascade all childs
			foreach($terms as $term) {
				if (isset($childs[$term->term_id])) {
					$term->childs = $childs[$term->term_id];
				}
			}
		
			// flat the childs tree by its base node
			$tree = $childs[0];
			
			return $tree;
		}
	
		return;
	}
		
	/**
	 * Get term metadata from projects_termmeta table
	 */
	public function get_term_meta($term_id, $key) {
		$projects = new Projects();	
		$key = $projects->get_internal_name($key, true);
		
		return get_metadata( 'projects_term', $term_id, $key, true );
	}
	
	/**
	 * Set term metadata in projects_termmeta table
	 */
	public function set_term_meta($term_id, $key, $data = null) {
		$projects = new Projects();	
		$key = $projects->get_internal_name($key, true);
		
		// update or delete the term
		if(empty($data)) {
			return delete_metadata('projects_term', $term_id, $key);
		} else {
			return update_metadata('projects_term', $term_id, $key, $data);
		}
	}
}
}

?>