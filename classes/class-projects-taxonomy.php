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
		add_action('init', array($this, 'hook_init'));
	}
	
	/**
	 * Hook into the main hooks
	 */
	public function hook_init() {		
	}
}
}

?>