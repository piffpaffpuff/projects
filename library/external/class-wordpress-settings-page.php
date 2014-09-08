<?php

/**
 * Settings page class
 */
if (!class_exists('WordPress_Settings_Page')) {
class WordPress_Settings_Page {

	public $sections;
	public $hook_suffix;
	
	/**
	 * Construct Page
	 *
	 * @param array $args {
	 *     An array of arguments. Optional.
	 *
	 *     @type string 	'page_title'		The title to be displayed in the browser window on the settings page.
	 *     @type string 	'menu_title' 		Menu item text for the settings page.
	 *     @type string 	'capability'		Which type of users can see this menu item.
	 *     @type string 	'slug'				The unique slug for this menu item.
	 *     @type callback 	'render_callback'	The rendering callback.
	 * }
	 */
	public function __construct($args = array()) {		
		$this->sections = array();
		$this->hook_suffix = '';
		
		// Define the default args
		$defaults = array(
			'page_title' => 'Settings Page Title', 
			'menu_title' => 'Settings Menu Title', 
			'capability' => 'manage_options',
			'slug' => 'settings-page-slug',
			'render_callback' => null
		);
		
		// Overwrite the default args
		$args = wp_parse_args($args, $defaults);
		
		// Save the args as vars
		$this->page_title = $args['page_title'];
		$this->menu_title = $args['menu_title'];
		$this->capability = $args['capability'];
		$this->slug = $args['slug'];
		$this->render_callback = $args['render_callback'];
		
		// Create the page
		$this->hook_suffix = add_options_page(
			$this->page_title, 
			$this->menu_title, 
			$this->capability, 
			$this->slug, 
			array($this, 'render_callback')
		);
	}

	/**
	 * The main render method.
	 */
	public function render_callback() {
		if($this->render_callback === null) {
			$callback = array($this, 'render_page');
		} else {
			$callback = $this->render_callback;
		}
		// Custom render callback
		call_user_func( $callback, $this );
	}

	/**
	 * Render the settings page
	 */
	public function render_page() {
		?>
			<!-- Create a header in the default WordPress 'wrap' container -->
			<div class="wrap">

				<h2><?php echo $this->page_title; ?></h2>
				
				<form action="options.php" method="post">
					<?php settings_fields($this->slug); ?>
					<?php do_settings_sections($this->slug); ?>
					<?php submit_button(); ?>
				</form>

			</div><!-- /.wrap -->
		<?php
	}

	/**
	 * Add a settings section to this settings page.
	 */
	public function add_section($args) {
		$this->sections[$args['slug']] = new WordPress_Settings_Section($args);
		return $this->sections[$args['slug']];
	}
	
	/**
	 * Get a settings section.
	 */
	public function get_section($slug) {
		return $this->sections[$slug];
	}

}
}

/**
 * Settings section class
 */
if (!class_exists('WordPress_Settings_Section')) {
class WordPress_Settings_Section {

	public $fields;

	/**
	 * Construct Section
	 *
	 * @param array $args {
	 *     An array of arguments. Required.
	 *
	 *     @type string 	'slug'				Unique identifying slug.
	 *     @type string 	'title' 			Title for the header of the section.
	 *     @type string 	'settings_page'		The page slug the section should be added.
	 *     @type callback 	'render_callback'	The rendering callback.
	 * }
	 */
	public function __construct($args) {
		$this->fields = array();
		
		// Define the default args
		$defaults = array(
			'slug' => '',
			'title' => '',
			'settings_page' => null,
			'render_callback' => null,
		);
		
		// Overwrite the default args
		$args = wp_parse_args($args, $defaults);
		
		// Save the args as vars
		$this->slug = $args['slug'];
		$this->title = $args['title'];
		$this->settings_page = $args['settings_page'];
		$this->render_callback = $args['render_callback'];

		// Create the section
		add_settings_section(
			$this->slug,
			$this->title,
			array($this, 'render_callback'),
			$this->settings_page
		);
	}

	/**
	 * The main render method.
	 *
	 * Passes object to render method
	 */
	public function render_callback() {
		if($this->render_callback === null) {
			$callback = array($this, 'render_section');
		} else {
			$callback = $this->render_callback;
		}
		// Custom render callback
		call_user_func( $callback, $this );
	}

	/**
	 * Built-in render callback that does nothing.
	 */
	public function render_section() {}

	/**
	 * Adds a settings field as a child of this section.
	 */
	public function add_field($args = array()) {
		// create a field instance
		$args['section'] = $this->slug;
		$args['settings_page'] = $this->settings_page;
		$this->fields[$args['slug']] = new WordPress_Settings_Field($args);
		return $this->fields[$args['slug']];
	}
	
	/**
	 * Get a settings field.
	 */
	public function get_field($slug) {
		return $this->fields[$slug];
	}
	
}
}

/**
 * Settings field class
 */
if (!class_exists('WordPress_Settings_Field')) {
class WordPress_Settings_Field {

	/**
	 * @param array $args {
	 *     An array of arguments. Required.
	 *
	 *     @type string 	'slug' 				Unique identifying slug.
	 *     @type string 	'title' 			Title that will be output for the field.
	 *     @type object 	'value' 			Default value for the field.
	 *     @type string 	'type'				The type of input field.
	 *     @type callback 	'render_callback' 	Render callback for the section
	 *     @type callback 	'sanitize_callback'	The sanitization callback.
	 *     @type string 	'settings_page'		Slug of the settings page this section will be shown on.
	 *     @type string 	'section'			Slug of the section this field will be shown in.
	 *     @type string 	'description'		A descriptive text for what the field is for.
	 *     @type array 		'options'			The options for a select field as key/value pair.
	 * }
	 */
	public function __construct($args = array()) {
		// Define the default args
		$defaults = array(
			'slug' => '',
			'title' => '',
			'value' => '',
			'type' => 'text',
			'render_callback' => null,
			'sanitize_callback' => null,
			'settings_page' => null,
			'section' => '',
			'description' => '',
			'options' => null
		);
		
		// Overwrite the default args
		$args = wp_parse_args($args, $defaults);

		// Save the args as vars
		$this->slug = $args['slug'];
		$this->title = $args['title'];
		$this->value = $args['value'];
		$this->type = $args['type'];
		$this->render_callback = $args['render_callback'];
		$this->sanitize_callback = $args['sanitize_callback'];
		$this->settings_page = $args['settings_page'];
		$this->section = $args['section'];
		$this->description = $args['description'];
		$this->options = $args['options'];

		// Preload the value of this field
		$option = get_option($this->slug);
		if(!empty($option)) {
			$this->value = $option;
		} 
		
		// Create the field
		add_settings_field(
			$this->slug,
			$this->title,
			array($this, 'render_callback'),
			$this->settings_page,
			$this->section
		);
		
		// Register the field slug as name for the option
		register_setting($this->settings_page, $this->slug, array($this, 'sanitize_callback'));
	}

	/**
	 * The main render method.
	 */
	public function render_callback() {
		if($this->render_callback === null) {
			// Call custom submethod depending on the type
			$sub_render_method = 'render_' . $this->type;
			call_user_func(array($this, $sub_render_method));
		} else {
			// Default method
			call_user_func($this->render_callback, $this);
		}
	}
	
	/**
	 * The main sanitization method.
	 */
	public function sanitize_callback($value) {
		if($this->sanitize_callback === null) {
			// No sanitization by default
			return $value;
		}
		// Custom sanitization callback
		return call_user_func($this->sanitize_callback, $value, $this);
	}

	/**
	 * Render a simple text input box
	 */
	public function render_text() {
		?>
		<input type="text" id="<?php echo $this->slug; ?>" name="<?php echo $this->slug; ?>" value="<?php echo $this->value; ?>" >
		<p class="description"><?php echo $this->description; ?></p>
		<?php
	}

	/**
	 * Render a textarea
	 */
	public function render_textarea() {
		?>
		<textarea id="<?php echo $this->slug; ?>" name="<?php echo $this->slug; ?>"><?php echo $this->value; ?></textarea>
		<p class="description"><?php echo $this->description; ?></p>
		<?php
	}
	
	/**
	 * Render a select
	 */
	public function render_select() {	
		?>
	    <select name="<?php echo $this->slug; ?>">
			<?php if(is_array($this->options)) : ?>
				<?php foreach($this->options as $key => $option) : ?>
				<option value="<?php echo $key; ?>" <?php selected($key, $this->value); ?>><?php echo $option; ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<p class="description"><?php echo $this->description; ?></p>
		<?php
	}
	
}
}
?>