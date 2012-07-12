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
	 * Load the class
	 */
	public function load() {
		add_action('init', array($this, 'load_hooks'));
	}
	
	/**
	 * Load the main hooks
	 */
	public function load_hooks() {		
	}
}
}

?>