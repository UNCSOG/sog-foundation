<?php

// This is a sample cell type to show how a cell can be created using the API.
// This file is included at the bottom of the functions.php file.

function register_reference_cell_init() {

	register_dd_layout_cell_type ('reference-cell',
		array (
			// The name of the cell type.
			'name' => __('Reference cell', 'theme-context'),

			// css class name for the icon. This is displayed in the popup when creating new cell types.
			'cell-image-url' => 'footer-cell',

			// A description of the cell type. This is displayed in the popup when creating new cell types.
			'description' => __('A cell that demonstrates the Layouts plugin API.', 'theme-context'),

			// Category used to group cell types together.
			'category' => __('Example cells', 'theme-context'),

			// The text for the button that is displayed in the popup when creating new cell types.
			'button-text' => __('Assign Reference cell', 'theme-context'),

			// The dialog title to be displayed when creating a new cell of this type
			'dialog-title-create' => __('Create a new Reference cell', 'theme-context'),

			// The dialog title to be displayed when editing a cell of this type
			'dialog-title-edit' => __('Edit Reference cell', 'theme-context'),

			// The function name of a callback function that supplies the user
			// interface for creating or editing the cell type.
			// Can be left blank if the cell type has no UI.
			'dialog-template-callback' => 'reference_cell_dialog_template_callback',

			// The function name of a callback function that returns the HTML to be rendered in the front end.
			// This function will receive the $cell_settings that the user has entered via the cell edit dialog.
			'cell-content-callback' => 'reference_cell_content_callback',

			// The function name of a callback function that returns the HTML for displaying the cell in the editor.
			'cell-template-callback' => 'reference_cell_template_callback',

			// The class name or names to add when the cell is output. Separate class names with a space.
			'cell-class' => 'reference-cell',

			// Preview image URL
			'preview-image-url' => 'http://placehold.it/350x150',

			// Custom CSS registering.
			// It's an array of arrays. Each array registers one CSS file and takes the same attributes as wp_register_style(): http://codex.wordpress.org/Function_Reference/wp_register_style

			// Example:

			/*
			'register-styles' => array(
				array('my_custom_css_1', $src, $deps, $ver, $media),
				array('my_custom_css_2', $src, $deps, $ver, $media),
			),
			*/

			'register-styles' => array(
				array('reference_cell_css', WPDDL_RELPATH . '/reference-cell/reference-cell.css', null, WPDDL_VERSION, null),
			),

			// Custom JS registering.
			// It's an array of arrays. Each array registers one JS file and takes the same attributes as wp_register_script(): http://codex.wordpress.org/Function_Reference/wp_register_script

			// Example:

			/*
			'register-styles' => array(
				array('my_custom_js_1', $src, $deps, $ver, $in_footer),
				array('my_custom_js_2', $src, $deps, $ver, $in_footer),
			),
			*/

			'register-scripts' => array(
				array('reference_cell_js', WPDDL_RELPATH . '/reference-cell/reference-cell.js', array('jquery'), WPDDL_VERSION, true)
			)
		)
	);
}
add_action( 'init', 'register_reference_cell_init' );

/*
 * Callback function that returns the user interface for the cell dialog.
 * Notice that we are using 'text_data' for the input element name.
 * This can then be accessed during frontend render using
 * $cell_settings['text_data']
 */

function reference_cell_dialog_template_callback() {
	ob_start();
	?>

		<?php
			/*
			 * Use the the_ddl_name_attr function to get the
			 * name of the text box. Layouts will then handle loading and
			 * saving of this UI element automatically.
			 */
		?>

		<div class="ddl-form reference-cell">

			<?php // Display simple text input ?>
			<h3>
				<?php _e('Text input', 'theme-context' ); ?>
			</h3>
			<p>
				<label for="<?php the_ddl_name_attr('text_data'); ?>"><?php _e('Cell data', 'theme-context' ); ?></label>
				<input type="text" name="<?php the_ddl_name_attr('text_data'); ?>">
				<span class="desc"><?php _e('Cell description', 'theme-context' ); ?></span>
			</p>

			<?php
				// Display repeatable fields
				// Form elements wrapped in ddl_repeat_start() and ddl_repeat_end() functions will be repeatable
			?>
			<h3>
				<?php _e('Repeatable fields', 'theme-context' ); ?>
			</h3>
			<?php ddl_repeat_start('gallery', __('Add another item', 'theme-context' ), 4); // $group_name, $button_label, $max_items ?>
			<p>
				<label for="<?php the_ddl_name_attr('gallery_item'); ?>"><?php _e('Add image URL', 'theme-context' ); ?></label>
				<input type="text" name="<?php the_ddl_name_attr('gallery_item'); ?>">
			</p>
			<?php ddl_repeat_end(); ?>

		</div>

	<?php
	return ob_get_clean();
}

// Callback function for displaying the cell in the editor.
function reference_cell_template_callback() {

	// This should return an empty string or the attribute to display.
	// In this case display the 'text_data' attribute in the cell template.
	return 'text_data';

}

// Callback function for display the cell in the front end.
function reference_cell_content_callback($cell_settings) {
	ob_start();
	?>

	<?php // Display a single field ?>
	<p>
		<?php the_ddl_field('text_data'); // Get the value of the text field using the 'text_data' key. ?>
	</p>

	<?php // Display repeatable fields ?>
	<?php if ( has_ddl_repeater('gallery') ) : ?>

		<ul class="thumbnails">

			<?php // It looks very similar to the native WordPress Loop, which you're already familiar with ?>
			<?php while ( has_ddl_repeater('gallery') ) : the_ddl_repeater('gallery');?>

				<li>
					<a href="#" class="thumbnail">
						<?php // use the_ddl_sub_field('field-name') to display the field value or get_ddl_sub_field('field-name') to asign it to a variable ?>
						<img src="<?php the_ddl_sub_field('gallery_item'); ?>" alt="<?php _e('Image:','theme-context'); ?> <?php the_ddl_repeater_index(); // the_ddl_repeater_index() displays the loop index ?>">
					</a>
				</li>

			<?php endwhile; ?>
		</ul>

	<?php endif; ?>

	<?php
	return ob_get_clean();
}