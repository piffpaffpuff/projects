<?php

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
			<?php else : ?>
				<?php // no support for other media types yet ?>
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
function project_featured_image($size = 'thumbnail', $post_id = null) {
	global $projects;

	$attachments = get_project_featured_image($post_id);

	?>
 	<?php foreach($attachments as $attachment) : ?>
		<?php if($projects->writepanel->is_web_image($attachment->post_mime_type)) : ?>
			<?php $attachment_src = wp_get_attachment_image_src($attachment->ID, $size); ?>
			<img src="<?php echo $attachment_src[0]; ?>" />
		<?php else : ?>
				<?php // no support for other media types yet ?>
		<?php endif; ?>
	<?php endforeach; ?>
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
	$args['taxonomy'] = Projects_Post_Type::get_taxonomy_internal_name($key);
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