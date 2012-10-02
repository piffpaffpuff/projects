<?php

/**
 * Geocode class
 */
if (!class_exists('Projects_Geocode')) {
class Projects_Geocode {

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Load the class hooks
	 */
	public function load() {
		//add_action('init', array($this, 'hook_init'));
		//add_action('admin_init', array($this, 'hook_admin'));
	}
	
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {
	}	
	
	/**
	 * Hook into the admin hooks
	 */
	public function hook_admin() {		
	}
	
	/**
	 * Get lat lon from project
	 */
	public function get_project_geocode($post_id = null) {
		if(empty($post_id)) {
			global $post;
			if(empty($post)) {
				return;
			}
			$post_id = $post->ID;
		}
		
		// get the lat lon for the post
		$projects = new Projects();
		$geocode = $projects->get_project_meta('latitude_longitude', $post_id);
		if(!empty($geocode)) {
			$geocode = $this->construct_geocode_object($geocode, $post_id);
		}
		return $geocode;		
	}
	
	/**
	 * Get all lat lon from all projects
	 */
	public function get_geocodes() {
		global $wpdb;
				
		// get the geocodes for all posts from cache or query again 
		$projects = new Projects();
		$cache = get_transient('get_geocodes_meta');
		if($cache) {
			$results = $cache;
		} else {		
			// get presets from all posts
			$sql = $wpdb->prepare(
				"SELECT post_id, meta_value 
				FROM $wpdb->postmeta 
				WHERE meta_key = %s 
				AND meta_value <> ''
				AND meta_value IS NOT NULL
				GROUP BY post_id", 
			$projects->get_internal_name('latitude_longitude', true));
			
			// query the meta
			$results = $wpdb->get_results($sql);
			
			// cache the query for 7 days
			set_transient('get_geocodes_meta', $results, 60*60*24*7);
		}
		
		// create a unified geocode element
		$geocodes = array();
		foreach($results as $result) {
			$geocodes[] = $this->construct_geocode_object($result->meta_value, $result->post_id);
		}
		return $geocodes;
	}
	
	/**
	 * Construct unified geocode object
	 */
	public function construct_geocode_object($geocode, $post_id) {	
		$geocode = maybe_unserialize($geocode);
		$data = array(
			'post_id' => $post_id,
			'latitude' => $geocode[0],
			'longitude' => $geocode[1]
		);
		return $data;
	}	
	
	/**
	 * Clear the meta query cache for the presets
	 */
	public function clear_geocodes_meta_cache() {
		return delete_transient('get_geocodes_meta');
	}
		
	/**
	 * Get lat lon from address
	 */
	public function geocode_address($address) {	
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