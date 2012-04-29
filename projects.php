<?php

/*
 * Plugin Name: Projects
 * Plugin URI: http://www.iwannegro.ch
 * Description: A projects system for graphics artists, architects, motion designers and all creative people.
 * Version: 1.0
 * Author: Iwan Negro
 * Author URI: http://www.iwannegro.ch
 * License: GPL3
 *
 * Copyright (C) 2011 Iwan Negro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
/**
 * Base class
 */
if (!class_exists('Projects')) {
class Projects {

	public static $plugin_file_path;
	public static $plugin_directory_url;
	public static $plugin_directory_path;
	public static $plugin_basename;
	public static $post_type;
	public static $slug;
	
	public $order_by; 
	public $meta_key;

	public $installation;
	public $menu;
	public $writepanel;
	public $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$plugin_file_path = __FILE__;
		self::$post_type = 'project';
		self::$plugin_directory_url = plugin_dir_url(self::$plugin_file_path);
		self::$plugin_directory_path = plugin_dir_path(self::$plugin_file_path);
		self::$plugin_basename = plugin_basename(self::$plugin_file_path);
	}
	
	/**
	 * Load the code
	 */
	public function load() {
		$this->includes();
		
		$this->order_by = 'meta_value_num';
		$this->meta_key = '_projects_date';
		
		$this->installation = new Projects_Installation();
		$this->installation->load();
		$this->menu = new Projects_Menu();
		$this->menu->load();
		$this->writepanel = new Projects_Writepanel();
		$this->writepanel->load();
		$this->settings = new Projects_Settings();
		$this->settings->load();
		
		// load hooks
		add_action('init', array($this, 'load_hooks'));
	}
	
	/**
	 * Include the classes
	 */
	public function includes() {
		require_once('class-projects-installation.php');	
		require_once('class-projects-menu.php');	
		require_once('class-projects-writepanel.php');	
		require_once('class-projects-settings.php');	
	}

	/**
	 * Load the main hooks
	 */
	public function load_hooks() {
		add_filter('get_previous_post_sort', array($this, 'adjacent_post_sort'));
		add_filter('get_next_post_sort', array($this, 'adjacent_post_sort'));

   		add_theme_support('post-thumbnails', array(Projects::$post_type));
	}
	
	/**
	 * Query projects
	 */
	public function query_projects($args = null) {
		global $projects, $paged;
	
		// pagination support when the projects 
		// page is the frontpage and for all other
		// cases too.
		if(get_query_var('paged')) {
		    $paged = get_query_var('paged');
		} else if(get_query_var('page')) {
		    $paged = get_query_var('page');
		} else {
		    $paged = 1;
		}
	
		// default args
		$args = is_array($args) ? $args : array();
		$args['post_type'] = Projects::$post_type;
		$args['orderby'] = isset($args['orderby']) ? $args['orderby'] : $this->order_by;
		$args['meta_key'] = isset($args['meta_key']) ? $args['meta_key'] : $this->meta_key;
		$args['paged'] = $paged;
		
		return query_posts($args);
	}
	
	/**
	 * Set the sort for post navigation to be the
	 */
	public function adjacent_post_sort() {
		/*
			TODO: make order by work with meta value
			
			"ORDER BY p.post_date $order LIMIT 1"
		*/
		global $wp_query;
		
		if($wp_query->get('post_type') == Projects::$post_type) {
		}
		return;
	}
		
	/**
	 * Is single project item
	 */
	public function is_project() {
		global $post;
		if(isset($post) && is_single($post) && $post->post_type == self::$post_type) {
			return true;
		}
		return false;
	}
	
	/**
	 * Is projects main page
	 */
	public function is_projects_page() {
		global $post;
		if(is_page(get_option('projects_base_page_id')) || is_post_type_archive(self::$post_type)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Is projects taxonomy
	 */
	public function is_projects_tax() {
		global $taxonomy;
		return is_tax($taxonomy);
	}
	
	/**
	 * Get the meta value from a key
	 */
	public static function get_meta_value($key, $post_id = null) {
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		return get_post_meta($post_id, '_projects_' . $key, true);
	}
}
}

/*
 * Instance
 */
$projects = new Projects();
$projects->load();

/*
 * API
 */

/**
 * Query projects
 */
function query_projects($args = null) {
	global $projects;
	return $projects->query_projects($args);
}

/**
 * Get the media
 */
function project_media($size = null, $post_id = null, $mime = null) {
	global $projects;
	$post_thumbnail_id = get_post_thumbnail_id($post_id);

	?>
	<ul class="project-media">
		<?php foreach($projects->writepanel->get_media($post_id, $mime) as $attachment) : ?>
			<?php if($post_thumbnail_id != $attachment->ID) : ?>
		<li>
			<a href="<?php echo get_attachment_link($attachment->ID); ?>">
			<?php if($projects->writepanel->is_web_image($attachment->post_mime_type)) : ?>
				<?php 
				$media_size = $size;
				
				if(empty($size)) {
					$media_size = $attachment->default_size;
				} 
				?>
				<?php echo wp_get_attachment_image($attachment->ID, $media_size); ?>
			<?php else : ?>
				
			<?php endif; ?>
			</a>
		</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Thumbnail
 */
function project_thumbnail($size = 'thumbnail', $post_id = null) {	
	global $projects;
	$attachment_id = get_post_thumbnail_id($post_id);

	// load the first image attachment id when no thumbnail
	/*
	if(empty($attachment_id)) {
		foreach($projects->writepanel->get_media() as $attachment) {
			if($projects->writepanel->is_web_image($attachment->post_mime_type)) {
				$attachment_id = $attachment->ID;
				break;
			}
		}
	} 
	*/
	
	echo wp_get_attachment_image($attachment_id, $size);
}

/**
 * Get added taxonomies
 */
function get_registered_projects_taxonomies() {
	global $projects;
	return $projects->menu->get_added_taxonomies(null, 'names');
}

/**
 * Get terms
 */
function get_project_taxonomy($name, $args = null) {
	global $projects, $post;
	
	// find the taxonomy
	if($projects->menu->is_taxonomy_name($name)) {
		$taxonomy = $name;
	} else {
		$tax_args = array(
			'label' => $name
		);
		$taxonomies = $projects->menu->get_added_taxonomies($tax_args, 'names');
		reset($taxonomies);
		$taxonomy = key($taxonomies);
	}
	
	return wp_get_post_terms($post->ID, $taxonomy, $args);
}

/**
 * List terms
 */
function project_taxonomy($name, $args = null) {
	global $projects;
		
	// output the list
	$terms = get_project_taxonomy($name, $args); 
	?>
	<ul class="project-taxonomy project-taxonomy-<?php echo sanitize_key($name); ?>">
	<?php if(!isset($terms->errors)) : ?>
		<?php foreach($terms as $term) : ?>
		<li>
			<a href="<?php echo get_term_link($term); ?>"><?php echo $term->name; ?></a>
		</li>
		<?php endforeach; ?>
	<?php endif; ?>
	</ul>
	<?php
}

/**
 * Get project meta
 */
function get_project_meta($key) {
	global $projects;
	return Projects::get_meta_value($key);
}

/**
 * Show project website
 */
function project_website($name = null, $target = '_blank') {
	$url = get_project_meta('website');
	$url_target = 'target="' . $target . '"';
	
	if(!empty($url)) {
		if(empty($name)) {
			$name = preg_replace('/(?<!href=["\'])http:\/\//', '', $url);
		}
		if(empty($target)) {
			$url_target = '';
		}
		?>
		<a href="<?php echo $url; ?>" title="<?php esc_attr($name); ?>" <?php echo $url_target; ?>><?php echo $name; ?></a>
		<?
	}
}

/**
 * Show project month
 */
function project_month() {
	echo get_project_meta('month');
}

/**
 * Show project month
 */
function project_year() {
	echo get_project_meta('year');
}

/**
 * Add taxonomy
 */
function add_projects_taxonomy($name, $singular_name, $args = null) {
	global $projects;
	$projects->menu->add_taxonomy($name, $singular_name, $args);
}

/**
 * Remove taxonomy
 */
function remove_projects_taxonomy($name) {
	global $projects;
	$projects->menu->remove_taxonomy($name);
}

/**
 * Is single project item
 */
function is_project() {
	global $projects;
	return $projects->is_project();
}

/**
 * Is projects main page
 */
function is_projects_page() {
	global $projects;
	return $projects->is_projects_page();
}

/**
 * Is projects taxonomy
 */
function is_projects_tax() {
	global $projects;
	return $projects->is_projects_tax();
}

?>