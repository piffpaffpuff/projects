<?php

/**
 * Settings class
 */
if (!class_exists('Projects_Settings')) {
class Projects_Settings {
	
	private $hidden_submit;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->hidden_submit = 'projects_submit_hidden';
	}

	/**
	 * Load the class
	 */
	public function load() {
		add_action('admin_menu', array($this, 'add_page'));
	}
		
	/**
	 * Remove default settings
	 */
	public function remove_default_settings() {
		delete_option('projects_settings');
	}
				
	/**
	 * Add sub page to the Settings Menu
	 */
	public function add_page() {
		add_options_page('Projects Settings', 'Projects', 'administrator', 'projects-settings', array($this, 'create_page_content'));
	}
	
	/**
	 * Add the page structure to the sub page
	 */
	public function create_page_content() {
		// Check the user capabilities
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
				
		// See if the user has posted us some information
		if(isset($_POST[ $this->hidden_submit ]) && $_POST[$this->hidden_submit] == 'submitted') {
			// Save settings
			$this->save_settings();
			
			// Put an settings updated message on the screen
			?><div class="updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div><?php
		}
		
		// Page content
		?><div class="wrap">
			<?php screen_icon('options-general'); ?>
			<h2><?php _e('Projects Settings', 'projects'); ?></h2>
			<form action="" method="post">
				<h3><?php _e('Page options', 'projects'); ?></h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="projects_base_page_id"><?php _e('Projects base-page', 'projects'); ?></label>
							</th>
							<td>
								<?php 
								$args = array(
									'selected' => get_option('projects_base_page_id'),
									'name' => 'projects_base_page_id'
								);
								
								wp_dropdown_pages($args); 
								?>
							</td>
						</tr>
					</tbody>
				</table>
				<h3><?php _e('Image Sizes', 'projects'); ?></h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="projects_default_image_size"><?php _e('Default Image Size', 'projects'); ?></label>
							</th>
							<td>
								<input name="projects_default_image_size" disabled="disabled" type="text" value="<?php echo get_option('projects_default_image_size'); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th>
								<label><?php _e('Image Sizes', 'projects'); ?></label>
							</th>
							<td>
								<div class="image-sizes">
									<?php 
										$index = 0;
										$name = '';
										$width = '';
										$height = '';
										$checked = '';
										$template = '<div class="image-set">
										<label>' . __('Name', 'projects') . '</label> <input name="projects_image_sizes[item' . $index . '][name]" type="text" value="' . $name . '" class="regular-text">
										<label>' . __('Width', 'projects') . '</label> <input name="projects_image_sizes[item' . $index . '][width]" type="text" value="' . $width . '" class="small-text">
										<label>' . __('Height', 'projects') . '</label> <input name="projects_image_sizes[item' . $index . '][height]" type="text" value="' . $height . '" class="small-text">
										<input name="projects_image_sizes[item' . $index . '][crop]" type="checkbox" value="1" ' . $checked . '> <label>' . __('Crop', 'projects') . '</label>
										<a href="#" class="remove-image-size">' . __('Delete', 'projects') . '</a>
										</div>';
									?>
									<?php 
										$sizes = get_option('projects_image_sizes'); 
										if(is_array($sizes)) {
											foreach($sizes as $size) {
												$name = isset($size['name']) ? $size['name'] : '';
												if(!empty($name)) {
													$width = isset($size['width']) ? $size['width'] : '';
													$height = isset($size['height']) ? $size['height'] : '';
													$checked = checked(isset($size['crop']) ? $size['crop'] : '', 1, false);
													echo $template;
													$index++;
												}
											}
										}
									?>
								</div>
								<a href="#" id="projects-add-image-size" class="add-image-size"><?php _e('Add new Image Size', 'projects'); ?></a>
							</td>
						</tr>
					</tbody>
				</table>
				
				<input type="hidden" name="<?php echo $this->hidden_submit; ?>" value="submitted">
				
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
				</p>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Save all settings
	 */
	public function save_settings() {
		foreach ( $_POST as $key => $value ) {
			if($key != $this->hidden_submit) {
				if( empty( $value ) ) {
					delete_option( $key );
				} else {
					if ( get_option( $key ) && get_option( $key ) != $value ) {
						update_option( $key, $value );
					}
					else {
						add_option( $key, $value );
					}
				}
				
				flush_rewrite_rules();
			}
		}
	}

}
}
?>