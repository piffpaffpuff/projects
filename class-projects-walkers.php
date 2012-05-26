<?php

/**
 * Walker class
 */
if (!class_exists('Projects_Project_Taxonomy_Walker')) {
class Projects_Project_Taxonomy_Walker extends Walker_Category {
 
	private $term_ids;
 
 	/**
 	 * Constructor
 	 */
	public function __construct($post_id, $taxonomy) {
		// fetch the list of term ids for the given post
		$args = array(
			'fields' => 'ids'
		);
		$this->term_ids = wp_get_post_terms($post_id, $taxonomy, $args);
	}
 	
 	/**
 	 * Display the element
 	 */
	public function display_element($element, &$children_elements, $max_depth, $depth = 0, $args, &$output) {
		$display = false; 
		$id = $element->term_id;
 		
 		// go through the ids
		if(in_array($id, $this->term_ids)) {
			// the current term is in the list
			$display = true;
		} elseif(isset($children_elements[$id])) {
			// the current term has children
			foreach($children_elements[$id] as $child) {
				if(in_array($child->term_id, $this->term_ids)) {
					// one of the term's children is in the list
					$display = true;
					// can stop searching now
					break;
				}
			}
		}
 		
 		// call the extended class method
		if($display) {
			parent::display_element($element, &$children_elements, $max_depth, $depth, $args, &$output);
		}
	}
}
}
?>