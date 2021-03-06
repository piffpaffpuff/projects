<?php

/*
 * Plugin Name: Projects
 * Plugin URI: https://github.com/piffpaffpuff/projects
 * Description: A portfolio plugin for creative people to manage and present their projects.
 * Version: 2.0
 * Author: piffpaffpuff
 * Author URI: https://github.com/piffpaffpuff
 * License: GPL3
 *
 * Copyright (C) 2014 Triggvy Gunderson
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
	public $geocode;
	public $taxonomy;
	public $taxonomy_group;
	public $writepanel;
	public $media;
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
		require_once(ABSPATH . WPINC . '/class-wp-oembed.php');
		require_once('includes/class-projects-countries.php');	
		require_once('includes/class-projects-installation.php');	
		require_once('includes/class-projects-type.php');	
		require_once('includes/class-projects-geocode.php');	
		require_once('includes/class-projects-taxonomy.php');	
		require_once('includes/class-projects-taxonomy-group.php');	
		require_once('includes/class-projects-walkers.php');
		require_once('includes/class-projects-writepanel.php');	
		require_once('includes/class-projects-media.php');	
		require_once('includes/libraries/class-wordpress-settings-page.php');
		require_once('includes/class-projects-settings.php');
	}
	
	/**
	 * Load the code
	 */
	public function load() {
		// include the classes
		$this->includes();
		
		// construct the instances 
		$this->installation = new Projects_Installation();
		$this->geocode = new Projects_Geocode();
		$this->taxonomy_group = new Projects_Taxonomy_Group();
		$this->taxonomy = new Projects_Taxonomy();
		$this->type = new Projects_Type();
		$this->media = new Projects_Media();
		$this->writepanel = new Projects_Writepanel();
		$this->settings = new Projects_Settings();
	
		// load all hooks of the instances		
		$this->installation->load();
		$this->geocode->load();
		$this->taxonomy_group->load();
		$this->taxonomy->load();
		$this->type->load();
		$this->media->load();
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
		// create a custom ordering for the single page
		// next/previous post links. they are still not
		// fixed in 4.0 to repsect the main query order.
		add_filter('get_previous_post_join', array($this, 'adjacent_post_join'));
		add_filter('get_next_post_join', array($this, 'adjacent_post_join'));
		add_filter('get_previous_post_sort', array($this, 'adjacent_post_previous_sort'));
		add_filter('get_next_post_sort', array($this, 'adjacent_post_next_sort'));
		add_filter('get_previous_post_where', array($this, 'adjacent_post_previous_where'));
		add_filter('get_next_post_where', array($this, 'adjacent_post_next_where'));
   		
   		// modify the main query to get projects
   		// and order them by a custom order.
   		add_action('pre_get_posts', array($this, 'projects_page_query'));		
	}
	
	/**
	 * Load the admin hooks
	 */
	public function hooks_admin() {
   		add_action('admin_print_styles', array($this, 'add_styles'));
		add_action('admin_print_scripts-post.php', array($this, 'add_scripts'));
		add_action('admin_print_scripts-post-new.php', array($this, 'add_scripts'));
		add_filter('admin_body_class', array($this, 'add_admin_body_classes'));

		// Enqueue script on settings page
		if(isset($_GET['page']) && $_GET['page'] == $this->settings->slug) {
			$hook = get_plugin_page_hookname($_GET['page'], 'options-general.php');
			add_action('admin_print_scripts-' . $hook, array($this, 'add_scripts'));
		}
	}
	
	/**
	 * Add the styles
	 */
	public function add_styles() {
		wp_enqueue_style('minicolors', self::$plugin_directory_url . 'css/jquery.minicolors.css');
		wp_enqueue_style('projects', self::$plugin_directory_url . 'css/style.css');
	}
	
	/**
	 * Add the scripts
	 */
	public function add_scripts() {
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('minicolors', self::$plugin_directory_url . 'js/jquery.minicolors.min.js', array('jquery'));
		wp_enqueue_script('projects', self::$plugin_directory_url . 'js/script.js', array('jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'minicolors'));
	}
	
	/**
	 * Add admin body class
	 */
	public function add_admin_body_classes($classes) {
		global $post;
		if($post) {
			$post_type = get_post_type($post->ID);
			if(is_admin() && $post_type == self::$post_type) {
				$classes .= 'post-type-' . $post_type;
			}
		}
		return $classes;
	}
	
	/**
	 * Build the query args to get the projects
	 */
	public function build_query_args($args = null) {
		global $wp_query;

		// pagination support for the projects page. 
		// in wordpress 3.0.2 the 'paged' option was 
		// renamed to 'page'. check for both cases because
		// the archive uses the new and the page the old
		// option name.
		if($wp_query->get('paged')) {
		    $paged = $wp_query->get('paged');
		} else if($wp_query->get('page')) {
		    $paged = $wp_query->get('page');
		} else {
		    $paged = 1;
		}
		
		// set the default args. posts are ordered by 
		// date in deccending and then by post name in 
		// ascending order. multiple sort orders are
		// only supported in wordpress 4.0.
		$default_args = array(
			'post_type' => self::$post_type,
			'meta_key' => $this->order_sort_key,
			'orderby' => array( 'meta_value_num' => 'DESC', 'post_title' => 'ASC' ),
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
  		if(!is_admin() && $wp_query->is_main_query() && $this->is_projects_page()) {
    		// set the default query args
    		$args = $this->build_query_args();
    		foreach($args as $key => $value) {
    			$wp_query->set($key, $value);
    		}
			
			// set the page type to is_archive because it
			// makes the projects page consistant with 
			// post type archives.
			$wp_query->set('page_id', 0);
			$wp_query->is_page = 0;
        	$wp_query->is_singular = 0;
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
			// sort by date first and then by post title.
			$sort = "ORDER BY m.meta_value+0 DESC, p.post_title ASC LIMIT 1";
		}
		
		return $sort;
	}
	
	/**
	 * Previous Adjacents ORDER query part
	 */
	public function adjacent_post_previous_sort($sort) {
		global $wp_query, $wpdb;
		
		if($wp_query->get('post_type') == self::$post_type) {
			// sort by date first and then by post title
			// in reverse order for the back links.
			$sort = "ORDER BY m.meta_value+0 ASC, p.post_title DESC LIMIT 1";
		}
		
		return $sort;
	}
	
	/**
	 * Next Adjacents WHERE query part
	 */
	public function adjacent_post_next_where($where) {
		global $wp_query, $wpdb, $post;

		if($wp_query->get('post_type') == self::$post_type) {
			$meta = $this->get_project_meta('date');
			$where = $wpdb->prepare(" WHERE (m.meta_value+0 < %d OR (m.meta_value+0 = '%d' AND p.post_title > %s)) AND p.post_type IN (%s) AND p.post_status = 'publish' ", $meta, $meta, $post->post_title, self::$post_type);
		}
		
		return $where;
	}
	
	/**
	 * Previous Adjacents WHERE query part
	 */
	public function adjacent_post_previous_where($where) {
		global $wp_query, $wpdb, $post;

		if($wp_query->get('post_type') == self::$post_type) {
			$meta = $this->get_project_meta('date');
			$where = $wpdb->prepare(" WHERE (m.meta_value+0 > %d OR (m.meta_value+0 = '%d' AND p.post_title < %s)) AND p.post_type IN (%s) AND p.post_status = 'publish' ", $meta, $meta, $post->post_title, self::$post_type);
		}
		
		return $where;
	}

	/**
	 * Is single project item
	 */
	public function is_project() {
		global $wp_query;
		if($wp_query->is_single == true && $wp_query->get('post_type') == self::$post_type) {
			return true;
		}
		return false;
	}
	
	/**
	 * Is projects main page
	 */
	public function is_projects_page() {
		global $wp_query, $projects;
		$page_id = $wp_query->get('page_id');
 		if((isset($page_id) && $page_id == $projects->settings->get_setting('base_page')) || is_post_type_archive(self::$post_type)) {
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
	 * Set the meta value from a key
	 */
	public function set_project_meta($key, $value = null, $post_id = null) {
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		return update_post_meta($post_id, $this->get_internal_name($key, true), $value);
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
 * Get the content media
 */
function get_project_content_media($post_id = null, $mime = null) {
	global $projects;
	return $projects->media->get_project_content_media($post_id, $mime);
}

/**
 * Get the featured media
 */
function get_project_featured_media($post_id = null) {
	global $projects;
	return $projects->media->get_project_featured_media($post_id);
}

/**
 * Show the content media
 */
function project_content_media($size = 'large', $post_id = null, $mime = null) {
	global $projects;

	$attachments = get_project_content_media($post_id, $mime);
	?>
	<ul class="project-content-media">
		<?php foreach($attachments as $attachment) : ?>
			<?php if($projects->media->is_web_image($attachment->post_mime_type)) : ?>
				<?php $class = ''; ?>
				<?php if(!empty($attachment->default_size)) : ?> 
					<?php $class = ' class="' . $attachment->default_size . '"'; ?>
				<?php endif; ?>
				<li<?php echo $class; ?>>
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
					<?php if(!empty($attachment->embed_html)) : ?>
						<?php echo $attachment->embed_html; ?>
					<?php endif; ?>
				</li>
			<?php elseif($projects->media->is_mime_type($attachment->post_mime_type, 'video|m4v|mp4|ogv|webm')) : ?>
				<li<?php echo $class; ?>>
					<video src="<?php echo wp_get_attachment_url($attachment->ID); ?>" controls></video>
				</li>
			<?php elseif($projects->media->is_mime_type($attachment->post_mime_type, 'audio|m4a|mp3|oga|wav')) : ?>
				<li<?php echo $class; ?>>
					<audio src="<?php echo wp_get_attachment_url($attachment->ID); ?>" controls></audio>
				</li>
			<?php else : ?>
				<?php // no support for other media types yet ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Show the featured media. It displays only web images.
 */
function project_featured_media($size = 'thumbnail', $post_id = null) {
	global $projects;

	$attachment = get_project_featured_media($post_id);

	?>
 	<?php if(isset($attachment)) : ?>
 		<?php $attachment_src = wp_get_attachment_image_src($attachment->ID, $size); ?>
 		<img src="<?php echo $attachment_src[0]; ?>" />
 	<?php endif; ?>
 	<?php
}

/**
 * Get terms
 */
function get_project_taxonomy($key, $hierarchical = true, $args = null) {
	global $projects, $post;
	$terms = $projects->taxonomy->get_project_taxonomy($post->ID, $key, $hierarchical, $args);
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
 * Show project meta
 */
function project_meta($key) {
	echo get_project_meta($key);
}

/**
 * Get project term meta
 */
function get_project_term_meta($term_id, $key) {
	global $projects;
	return $projects->taxonomy->get_term_meta($term_id, $key);
}

/**
 * Show project website
 */
function project_website($name = null, $target = '_blank') {
	$url = get_project_meta('website');
	$url_target = 'target="' . $target . '"';
	
	if(!empty($url)) {
		if(empty($name)) {
			$name = preg_replace('(^https?://)', '', $url);
		}
		if(empty($target)) {
			$url_target = '';
		}
		?>
		<a href="<?php echo $url; ?>" title="<?php esc_attr($name); ?>" <?php echo $url_target; ?>><?php echo $name; ?></a>
		<?php
	}
}

/**
 * Get geocode from project
 */
function get_project_geocode() {
	global $projects;
	return $projects->geocode->get_project_geocode();
}

/**
 * Get all geocodes from all projects
 */
function get_projects_georss_feed_url() {
	global $projects;
	return $projects->geocode->feed_url; 
}

/**
 * Add an extra field to the writepanel
 */
function add_project_field($name, $key = null, $default = '') {
	global $projects;
	$projects->writepanel->add_field($name, $key, $default);
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