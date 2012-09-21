<?php

/*
 * Plugin Name: Projects
 * Plugin URI: https://github.com/piffpaffpuff/projects
 * Description: A portfolio plugin for creative people to manage and present their projects.
 * Version: 1.0
 * Author: piffpaffpuff
 * Author URI: https://github.com/piffpaffpuff
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
 * Main class
 */
if (!class_exists('Projects')) {
class Projects {

	public static $plugin_file_path;
	public static $plugin_directory_url;
	public static $plugin_directory_path;
	public static $plugin_basename;
	public static $post_type;
	public static $slug;
	
	public $installation;
	public $type;
	public $taxonomy;
	public $taxonomy_group;
	public $writepanel;
	public $settings;
	
	public $order_sort_key;

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$plugin_file_path = __FILE__;
		self::$post_type = 'project';
		self::$plugin_directory_url = plugin_dir_url(self::$plugin_file_path);
		self::$plugin_directory_path = plugin_dir_path(self::$plugin_file_path);
		self::$plugin_basename = plugin_basename(self::$plugin_file_path);
		
		// order projects by key
		$this->order_sort_key = $this->get_internal_name('date', true);
	}
	
	/**
	 * Include the classes
	 */
	public function includes() {
		require_once('library/classes/class-projects-countries.php');	
		require_once('library/classes/class-projects-installation.php');	
		require_once('library/classes/class-projects-type.php');	
		require_once('library/classes/class-projects-taxonomy.php');	
		require_once('library/classes/class-projects-taxonomy-group.php');	
		require_once('library/classes/class-projects-walkers.php');
		require_once('library/classes/class-projects-writepanel.php');	
		require_once('library/classes/class-projects-settings.php');
	}
	
	/**
	 * Load the code
	 */
	public function load() {
		// include the classes
		$this->includes();
		
		// construct the instances 
		$this->installation = new Projects_Installation();
		$this->taxonomy = new Projects_Taxonomy();
		$this->taxonomy_group = new Projects_Taxonomy_Group();
		$this->type = new Projects_Type();
		$this->writepanel = new Projects_Writepanel();
		$this->settings = new Projects_Settings();
	
		// load all hooks of the instances		
		$this->installation->load();
		$this->taxonomy->load();
		$this->taxonomy_group->load();
		$this->type->load();
		$this->writepanel->load();
		$this->settings->load();
		
		// load hooks
		add_action('plugins_loaded', array($this, 'load_translation'));
		add_action('init', array($this, 'hooks_init'));
		add_action('admin_init', array($this, 'hooks_admin'));
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
	public function hooks_init() {
 		remove_theme_support('post-thumbnails');
		
		add_filter('get_previous_post_join', array($this, 'adjacent_post_join'));
		add_filter('get_next_post_join', array($this, 'adjacent_post_join'));
		add_filter('get_previous_post_sort', array($this, 'adjacent_post_previous_sort'));
		add_filter('get_next_post_sort', array($this, 'adjacent_post_next_sort'));
		add_filter('get_previous_post_where', array($this, 'adjacent_post_previous_where'));
		add_filter('get_next_post_where', array($this, 'adjacent_post_next_where'));
   		
   		add_action('pre_get_posts', array($this, 'projects_page_query'));
   		
   		add_action('admin_print_styles', array($this, 'add_styles'));
		add_action('admin_print_scripts-post.php', array($this, 'add_scripts'));
		add_action('admin_print_scripts-post-new.php', array($this, 'add_scripts'));
		add_action('admin_print_styles-media-upload-popup', array($this, 'add_media_styles'));		
		add_action('admin_print_scripts-media-upload-popup', array($this, 'add_media_scripts'));		
	}
	
	/**
	 * Load the main hooks
	 */
	public function hooks_admin() {
		// Enqueue script on settings page
		if(isset($_GET['page']) && $_GET['page'] == 'projects-settings') {
			$hook = get_plugin_page_hookname($_GET['page'], 'options-general.php');
			add_action('admin_print_scripts-' . $hook, array($this, 'add_scripts'));
		}
	}
	
	/**
	 * Add the styles
	 */
	public function add_styles() {
		wp_enqueue_style('minicolors', self::$plugin_directory_url . 'css/jquery.miniColors.css');
		wp_enqueue_style('projects', self::$plugin_directory_url . 'css/style.css');
	}
	
	/**
	 * Add the scripts
	 */
	public function add_scripts() {
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('minicolors', self::$plugin_directory_url . 'js/jquery.miniColors.min.js', array('jquery'));
		wp_enqueue_script('projects', self::$plugin_directory_url . 'js/script.js', array('jquery'));
	}

	/**
	 * Add the media manager styles
	 */
	public function add_media_styles() {
		$post_type = get_post_type($_GET['post_id']);
		if(!empty($post_type) && $post_type == self::$post_type) {
			wp_enqueue_style('projects-media', self::$plugin_directory_url . 'css/media-style.css');
		}
	}
	
	/**
	 * Add the media manager scripts
	 */
	public function add_media_scripts() {
		$post_type = get_post_type($_GET['post_id']);
		if(!empty($post_type) && $post_type == self::$post_type) {
			wp_enqueue_script('projects-media', self::$plugin_directory_url . 'js/media-script.js');
			
			// localize the script to send some properties
			wp_localize_script('projects-media', 'ProjectsScript', array(
				'label_featured' => __( 'Featured', 'projects' )
			));
		}
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
			'meta_key' => $this->order_sort_key,
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
		
		if($wp_query->get('post_type') == self::$post_type) {
			// select the meta table info
			$join = $wpdb->prepare(" INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id AND m.meta_key = %s", $this->order_sort_key);
		}

		return $join;
	}
	
	/**
	 * Next Adjacents ORDER query part
	 */
	public function adjacent_post_next_sort($sort) {
		global $wp_query, $wpdb;
		
		if($wp_query->get('post_type') == self::$post_type) {
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
		
		if($wp_query->get('post_type') == self::$post_type) {
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

		if($wp_query->get('post_type') == self::$post_type) {
			$operator = '<';
			$meta = $this->get_project_meta('date');
			$where = $wpdb->prepare(" WHERE (m.meta_value+0 $operator %d OR (m.meta_value+0 = '%d' AND p.post_name $operator %s)) AND p.post_type IN (%s) AND p.post_status = 'publish' ", $meta, $meta, $post->post_name, self::$post_type);
		}
		
		return $where;
	}
	
	/**
	 * Previous Adjacents WHERE query part
	 */
	public function adjacent_post_previous_where($where) {
		global $wp_query, $wpdb, $post;

		if($wp_query->get('post_type') == self::$post_type) {
			$operator = '>';
			$meta = $this->get_project_meta('date');
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
	public function get_project_meta($key, $post_id = null) {
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		return get_post_meta($post_id, $this->get_internal_name($key, true), true);
	}
	
	/**
	 * Check if the key is prefixed with a 'project' string
	 */	
	public function is_internal_name($key) {
		$position = strrpos($key, self::$post_type);
		if($position === 0 || $position === 1) {
			return true;
		} 
		return false;
	}

	/**
	 * Generate an internal name
	 */	
	public function get_internal_name($key, $meta_key = false) {
		// check if the key already contains a 'project' prefix
		if($this->is_internal_name($key)) {
			return $key;
		} else {
			if($meta_key == true) {
				return '_' . self::$post_type . 's_' . $key;
			} else {
				return self::$post_type . '_' . $key;
			}
		}
	}

}
}

/*
 * Instance
 */
$projects = new Projects();
$projects->load();

/*
 * Template functions
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
function get_project_gallery_media($post_id = null, $mime = null) {
	global $projects;
	return $projects->writepanel->get_project_gallery_media($post_id, $mime);
}

/**
 * Get the featured
 */
function get_project_featured_media($post_id = null, $mime = null) {
	global $projects;
	return $projects->writepanel->get_project_featured_media($post_id, $mime);
}

/**
 * Show the gallery
 */
function project_gallery_media($size = 'large', $post_id = null, $mime = null) {
	global $projects;

	$attachments = get_project_gallery_media($post_id, $mime);
	
	?>
	<ul class="project-gallery-media">
		<?php foreach($attachments as $attachment) : ?><?php if($projects->writepanel->is_web_image($attachment->post_mime_type)) : ?><li>
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
			</li><?php elseif($projects->writepanel->is_mime_type($attachment->post_mime_type, 'video|m4v|mp4|ogv|webm')) : ?>
			<li>
				<video src="<?php echo wp_get_attachment_url($attachment->ID); ?>" controls></video>
			</li><?php elseif($projects->writepanel->is_mime_type($attachment->post_mime_type, 'audio|m4a|mp3|oga|wav')) : ?>
			<li>
				<audio src="<?php echo wp_get_attachment_url($attachment->ID); ?>" controls></audio>
			</li><?php else : ?>
				<?php // no support for other media types yet ?>
		<?php endif; ?><?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Show the featured image
 */
function project_featured_media($size = 'thumbnail', $post_id = null) {
	global $projects;

	$attachments = get_project_featured_media($post_id);

	?>
 	<?php foreach($attachments as $attachment) : ?><?php $attachment_src = wp_get_attachment_image_src($attachment->ID, $size); ?>
 		<img src="<?php echo $attachment_src[0]; ?>" /><?php endforeach; ?>
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
	global $projects, $post;
	$args = is_array($args) ? $args : array();
	$args['taxonomy'] = $projects->get_internal_name($key);
	$args['walker'] = new Projects_Project_Taxonomy_Walker($post->ID, $args['taxonomy']);
	return wp_list_categories($args);
}

/**
 * Get project meta
 */
function get_project_meta($key) {
	global $projects;
	return $projects->get_project_meta($key);
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
 * Get all taxonomy group presets
 */
function get_projects_taxonomy_group_presets($taxonomy_group, $sort = null, $join = null) {
	global $projects;
	return $projects->taxonomy_group->get_presets($taxonomy_group, $sort, $join);
}

/**
 * Get project taxonomy group presets
 */
function get_project_taxonomy_group_presets($taxonomy_group, $post_id = null) {
	global $projects;
	return $projects->taxonomy_group->get_project_presets($taxonomy_group, $post_id);
}

/**
 * Get taxonomy group preset permalink
 */
function get_projects_taxonomy_group_preset_permalink($preset) {
	global $projects;
	return $projects->taxonomy_group->get_preset_permalink($preset);
}

/**
 * Add taxonomy
 */
function add_projects_taxonomy($plural_label, $singular_label, $key, $args = null) {
	global $projects;
	$projects->taxonomy->add_taxonomy($plural_label, $singular_label, $key, $args);
}

/**
 * Remove taxonomy
 */
function remove_projects_taxonomy($key) {
	global $projects;
	$projects->taxonomy->remove_taxonomy($key);
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