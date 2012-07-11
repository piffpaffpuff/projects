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
	
	public $meta_key;

	public $installation;
	public $register;
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
		
		$this->meta_key = '_projects_date';
		
		$this->installation = new Projects_Installation();
		$this->installation->load();
		$this->register = new Projects_Register();
		$this->register->load();
		$this->writepanel = new Projects_Writepanel();
		$this->writepanel->load();
		$this->settings = new Projects_Settings();
		$this->settings->load();
		
		// load hooks
		add_action('plugins_loaded', array($this, 'load_translation'));
		add_action('init', array($this, 'load_hooks'));
	}
	
	/**
	 * Include the classes
	 */
	public function includes() {
		require_once('classes/class-projects-installation.php');	
		require_once('classes/class-projects-register.php');	
		require_once('classes/class-projects-walkers.php');  
		require_once('classes/class-projects-writepanel.php');	
		require_once('classes/class-projects-settings.php');	
	}
	
	/**
	 * Load the translations
	 */
	public function load_translation() {
   		load_plugin_textdomain('projects', false, dirname(self::$plugin_basename) . '/languages/');
	}
	
	/**
	 * Load the main hooks
	 */
	public function load_hooks() {
   		remove_theme_support('post-thumbnails');
		add_filter('get_previous_post_join', array($this, 'adjacent_post_join'));
		add_filter('get_next_post_join', array($this, 'adjacent_post_join'));
		add_filter('get_previous_post_sort', array($this, 'adjacent_post_previous_sort'));
		add_filter('get_next_post_sort', array($this, 'adjacent_post_next_sort'));
		add_filter('get_previous_post_where', array($this, 'adjacent_post_previous_where'));
		add_filter('get_next_post_where', array($this, 'adjacent_post_next_where'));
   		add_action('pre_get_posts', array($this, 'projects_page_query'));
	}
	
	/**
	 * Build the query args to get the projects
	 */
	public function build_query_args($args = null) {
		global $wp_query;
		
		/* pagination support for the projects page. 
		in wordpress 3.0.2 the 'paged' option was 
		renamed to 'page'. check for both cases because
		the archive uses the new and the page the old
		option name. */
		if($wp_query->get('paged')) {
		    $paged = $wp_query->get('paged');
		} else if($wp_query->get('page')) {
		    $paged = $wp_query->get('page');
		} else {
		    $paged = 1;
		}
		
		/* set the default args. posts with the 
		same date are ordered by title DESC because 
		wordpress doesn't support multiple ordering 
		yet. */
		$default_args = array(
			'post_type' => self::$post_type,
			'meta_key' => $this->meta_key,
			'orderby' => 'meta_value_num',
			'order' => 'DESC',
			'paged' => $paged
		);
		
		// merge the default and additional args
		$args = wp_parse_args($args, $default_args);

		return $args;
	}
	
	/**
	 * Hook into the main query to get projects
	 */
	public function projects_page_query($wp_query) {
    	if($wp_query->is_main_query() && $this->is_projects_page()) {
    		// set the default query args
    		$args = $this->build_query_args();
    		foreach($args as $key => $value) {
    			$wp_query->set($key, $value);
    		}
			
			/* set the page type to is_archive if the 
			projects page is also the front page. */
			$wp_query->set('page_id', 0);
			$wp_query->is_page = '';
        	$wp_query->is_singular = '';		
        	$wp_query->is_archive = 1;		
        	$wp_query->is_post_type_archive = 1;
		}

    	return $wp_query;
	}
	
	/**
	 * Query projects
	 */
	public function query_projects($args = null) {		 
		$args = $this->build_query_args($args);
		return query_posts($args);
	}
	
	/**
	 * Get projects
	 */
	public function get_projects($args = null) {
		$args = $this->build_query_args($args);
		return new WP_Query($args);
	}
		
	/**
	 * Adjacents JOIN query part
	 */
	public function adjacent_post_join($join) {
		global $wp_query, $wpdb;
		
		if($wp_query->get('post_type') == Projects::$post_type) {
			// select the meta table info
			$join = $wpdb->prepare(" INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id AND m.meta_key = %s", $this->meta_key);
		}

		return $join;
	}
	
	/**
	 * Next Adjacents ORDER query part
	 */
	public function adjacent_post_next_sort($sort) {
		global $wp_query, $wpdb;
		
		if($wp_query->get('post_type') == Projects::$post_type) {
			// sort both by the same order because $wp_query
			// doesn't support multiple different orders.
			$sort = "ORDER BY m.meta_value+0 DESC, p.post_name DESC LIMIT 1";
		}
		
		return $sort;
	}
	
	/**
	 * Previous Adjacents ORDER query part
	 */
	public function adjacent_post_previous_sort($sort) {
		global $wp_query, $wpdb;
		
		if($wp_query->get('post_type') == Projects::$post_type) {
			// sort both by the same order because $wp_query
			// doesn't support multiple different orders.
			$sort = "ORDER BY m.meta_value+0 ASC, p.post_name ASC LIMIT 1";
		}
		
		return $sort;
	}
	
	/**
	 * Next Adjacents WHERE query part
	 */
	public function adjacent_post_next_where($where) {
		global $wp_query, $wpdb, $post;

		if($wp_query->get('post_type') == Projects::$post_type) {
			$operator = '<';
			$meta = self::get_meta_value('date');
			$where = $wpdb->prepare(" WHERE (m.meta_value+0 $operator %d OR (m.meta_value+0 = '%d' AND p.post_name $operator %s)) AND p.post_type IN (%s) AND p.post_status = 'publish' ", $meta, $meta, $post->post_name, self::$post_type);
		}
		
		return $where;
	}
	
	/**
	 * Previous Adjacents WHERE query part
	 */
	public function adjacent_post_previous_where($where) {
		global $wp_query, $wpdb, $post;

		if($wp_query->get('post_type') == Projects::$post_type) {
			$operator = '>';
			$meta = self::get_meta_value('date');
			$where = $wpdb->prepare(" WHERE (m.meta_value+0 $operator %d OR (m.meta_value+0 = '%d' AND p.post_name $operator %s)) AND p.post_type IN (%s) AND p.post_status = 'publish' ", $meta, $meta, $post->post_name, self::$post_type);
		}
		
		return $where;
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
		global $wp_query;
		if($wp_query->get('page_id') == get_option('projects_base_page_id') || $wp_query->get('page_id') == get_option('page_on_front') || is_post_type_archive(self::$post_type)) {
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
 * Get projects
 */
function get_projects($args = null) {
	global $projects;
	return $projects->get_projects($args);
}

/**
 * Get the gallery
 */
function get_project_gallery($post_id = null, $mime = null) {
	global $projects;
	return $projects->writepanel->get_project_gallery($post_id, $mime);
}

/**
 * Get the featured
 */
function get_project_featured_image($post_id = null, $mime = null) {
	global $projects;
	return $projects->writepanel->get_project_featured_image($post_id, $mime);
}

/**
 * Show the gallery
 */
function project_gallery($size = null, $post_id = null, $mime = null) {
	global $projects;

	$attachments = get_project_gallery($post_id, $mime);
	
	?>
	<ul class="project-media">
		<?php foreach($attachments as $attachment) : ?>
		<li>
			<a href="<?php echo get_attachment_link($attachment->ID); ?>">
			<?php if($projects->writepanel->is_web_image($attachment->post_mime_type)) : ?>
				<?php 				
				// overwrite the size when the attachment has set a custom one
				if(!empty($attachment->default_size)) {
					$media_size = $attachment->default_size;
				} else {
					$media_size = $size;
				}
				?>
				<?php $attachment_src = wp_get_attachment_image_src($attachment->ID, $media_size); ?>
				<img src="<?php echo $attachment_src[0]; ?>" />
			<?php endif; ?>
			</a>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Show the featured image
 */
function project_thumbnail($size = 'thumbnail', $post_id = null) {
	global $projects;

	$attachments = get_project_featured_image($post_id);
	$attachments_count = count($attachments);

	?>
	<?php if($attachments_count > 0) : ?>
		<?php if($attachments_count == 1) : ?>
		 	<?php foreach($attachments as $attachment) : ?>
				<?php if($projects->writepanel->is_web_image($attachment->post_mime_type)) : ?>
					<?php $attachment_src = wp_get_attachment_image_src($attachment->ID, $size); ?>
					<img src="<?php echo $attachment_src[0]; ?>" />
				<?php endif; ?>
			<?php endforeach; ?>
		<?php else : ?>
			<ul class="project-featured-image">
				<?php foreach($attachments as $attachment) : ?>
				<li>
				<?php if($projects->writepanel->is_web_image($attachment->post_mime_type)) : ?>
					<?php $attachment_src = wp_get_attachment_image_src($attachment->ID, $size); ?>
					<img src="<?php echo $attachment_src[0]; ?>" />
				<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>	
	<?php
}

/**
 * Get terms
 */
function get_project_taxonomy($key, $hierarchical = true, $args = null) {
	global $projects, $post;
	$terms = $projects->writepanel->get_project_taxonomy($post->ID, $key, $hierarchical, $args);
	return $terms;
}

/**
 * Show terms
 */
function project_taxonomy($key, $args = null) {
	global $post;
	$args = is_array($args) ? $args : array();
	$args['taxonomy'] = Projects_Register::get_taxonomy_internal_name($key);
	$args['walker'] = new Projects_Project_Taxonomy_Walker($post->ID, $args['taxonomy']);
	return wp_list_categories($args);
}

/**
 * Get project meta
 */
function get_project_meta($key) {
	global $projects;
	return Projects::get_meta_value($key);
}

/**
 * Show project meta
 */
function project_meta($key) {
	global $projects;
	return get_project_meta($key);
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
 * Add taxonomy
 */
function add_projects_taxonomy($plural_label, $singular_label, $key, $args = null) {
	global $projects;
	$projects->register->add_taxonomy($plural_label, $singular_label, $key, $args);
}

/**
 * Remove taxonomy
 */
function remove_projects_taxonomy($key) {
	global $projects;
	$projects->register->remove_taxonomy($key);
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