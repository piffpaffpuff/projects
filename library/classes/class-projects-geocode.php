<?php

/**
 * Geocode class
 */
if (!class_exists('Projects_Geocode')) {
class Projects_Geocode {
	
	public $feed_name;
	public $feed_url;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// http://www.domain.com/?feed=georss&post_type=project
		$this->feed_name = 'georss';
		$this->feed_url = esc_url(get_site_url() . '/?feed=' . $this->feed_name . '&post_type=' . Projects::$post_type);
	}

	/**
	 * Load the class hooks
	 */
	public function load() {
		add_action('init', array($this, 'hook_init'));
	}
	
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {
		add_feed( $this->feed_name, array( $this, 'generate_georss' ) );
		add_action('pre_get_posts', array($this, 'edit_feed_query'), 20);
   		add_filter('post_limits', array($this, 'edit_feed_posts_per_page'));
	}	
	
	/**
	 * Modfy the feed query to return an unpaged feed
	 * with all projects that have a non empty lat and
	 * lon as meta.
	 */
	public function edit_feed_query($wp_query) {
		if($this->is_projects_georss_feed()) {
			$projects = new Projects();
			
			// query posts with lat lon meta
			$default_args = array(
				'paged' => false,
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => $projects->get_internal_name('latitude', true),
						'value' => '',
						'compare' => '!='
					),
					array(
						'key' => $projects->get_internal_name('longitude', true),
						'value' => '',
						'compare' => '!='
					)
				)
			);
			
			// set the default query args
			$args = $projects->build_query_args($default_args);
			foreach($args as $key => $value) {
				$wp_query->set($key, $value);
			}
		}
		return $wp_query;
	}
	
	/**
	 * Display all posts and do not use the syndication setting.
	 * This has to be done with a filter because wordpress 
	 * overwrites the wp_query posts_per_page with the default 
	 * setting. see:
	 * http://core.trac.wordpress.org/ticket/17853
	 */
	public function edit_feed_posts_per_page($limit) {
		global $wp_query;
		if(!is_admin() && $wp_query->is_main_query() && $wp_query->is_feed == true && $wp_query->get('feed') == $this->feed_name) {
			return '';
		}
		return $limit;
	}
	
	/**
	 * Get all lat lon from all projects
	 */
	public function generate_georss() {
		$projects = new Projects();
		?>
		<?php echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '">'; ?>
		<feed xmlns="http://www.w3.org/2005/Atom"
		      xmlns:dc="http://purl.org/dc/elements/1.1/"
		      xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
		      xmlns:georss="http://www.georss.org/georss"
		      xmlns:woe="http://where.yahooapis.com/v1/schema.rng"
		      xmlns:flickr="urn:flickr:user"
		      xmlns:media="http://search.yahoo.com/mrss/">
		    
		    <title><?php echo get_post(get_option('projects_base_page_id'))->post_title; echo ' - '; bloginfo_rss('name'); ?></title>
			<subtitle><?php the_category_rss(); ?></subtitle>
			<link rel="alternate" type="text/html" href="<?php echo $this->feed_url; ?>" />
			<updated><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></updated>
			<?php while( have_posts()) : the_post(); ?>
				<?php 
				global $post;
				$latitude = $projects->get_project_meta('latitude', $post->ID);
				$longitude = $projects->get_project_meta('longitude', $post->ID);
				$latitude_longitude = $latitude . ' ' . $longitude;
				?>
				<?php if($latitude && $longitude) : ?>
				<entry>
					<title><?php the_title_rss() ?></title>
					<link rel="alternate" type="text/html" href="<?php the_permalink_rss() ?>"/>
					<published><?php echo mysql2date('r', get_the_time('Y-m-d H:i:s')); ?></published>
					<updated><?php echo mysql2date('r', get_the_modified_time('Y-m-d H:i:s')); ?></updated>
					<content type="html"><![CDATA[Inhalt]]></content>
					<georss:point><?php echo $latitude_longitude ?></georss:point>
					<geo:lat><?php echo $latitude ?></geo:lat>
					<geo:long><?php echo $longitude ?></geo:long>
				</entry>
				<?php endif; ?>
			<?php endwhile; ?>
		</feed>
		<?php
	}
	
	/**
	 * Check if projects georss feed
	 */
	function is_projects_georss_feed() {
		global $wp_query;
		if(!is_admin() && $wp_query->is_main_query() && $wp_query->is_feed == true && $wp_query->get('feed') == $this->feed_name && $wp_query->get('post_type') == Projects::$post_type) {
			return true;
		} 
		return false;
	}
	
	/**
	 * Get lat lon from project
	 */
	public function get_project_geocode($post_id = null) {
		if(empty($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
				
		// get the lat lon for the post
		$projects = new Projects();
		$latitude = $projects->get_project_meta('latitude', $post_id);
		$longitude = $projects->get_project_meta('longitude', $post_id);
		$lat_lon = new stdClass();
		$lat_lon->latitude = null;
		$lat_lon->longitude = null;
		if($latitude && $longitude) {
			$lat_lon->latitude = $latitude;
			$lat_lon->longitude = $longitude;
		}
		return $lat_lon;		
	}

	/**
	 * Get lat lon from address
	 */
	public function locate_address($address) {	
		$lat_lon = new stdClass();
		$lat_lon->latitude = null;
		$lat_lon->longitude = null;
		$url = 'http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=' . $address;
		
		// query the api and read the json file
		$contents = wp_remote_fopen($url);
		$json = json_decode($contents);
		
		// set lat lng
		if(!empty($json->results)) {
			$lat_lon->latitude = $json->results[0]->geometry->location->lat;
			$lat_lon->longitude = $json->results[0]->geometry->location->lng;
		} 
		return $lat_lon;
	}
		
}
}

?>