<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Class WPV_Ajax_Handler_Generate_View_Loop_Output
 *
 * Handles the Loop Output generation.
 *
 * @since 2.6.4
 */
class WPV_Ajax_Handler_Generate_View_Loop_Output extends Toolset_Ajax_Handler_Abstract {

	const NONCE = 'layout_wizard_nonce';

	/**
	 * The loop output generator instance.
	 *
	 * @var OTGS\Toolset\Views\View\LoopOutputGenerator
	 */
	private $loop_generator;

	/**
	 * WPV_Ajax_Handler_Generate_View_Loop_Output constructor.
	 *
	 * @param Toolset_Ajax                                      $ajax_manager          The Toolset_Ajax class instance.
	 * @param \OTGS\Toolset\Views\View\LoopOutputGenerator|null $loop_output_generator The OTGS\Toolset\Views\View\LoopOutputGenerator class instance.
	 */
	public function __construct( Toolset_Ajax $ajax_manager, OTGS\Toolset\Views\View\LoopOutputGenerator $loop_output_generator = null ) {
		parent::__construct( $ajax_manager );

		if ( null === $loop_output_generator ) {
			$loop_output_generator = new OTGS\Toolset\Views\View\LoopOutputGenerator();
		}
		$this->loop_generator = $loop_output_generator;
	}

	/**
	 * Generate layout settings for a View.
	 *
	 * This is basically just a wrapper for the WPV_View_Base::generate_loop_output() method that handles AJAX stuff.
	 *
	 * Expects following POST arguments:
	 * - wpnonce: A valid layout_wizard_nonce.
	 * - view_id: ID of a View. Used to retrieve current View "_wpv_layout_settings". If ID is invalid or the View doesn't
	 *       have these settings, an empty array is used instead.
	 * - style: One of the valid Loop styles. @see WPV_View_Base::generate_loop_output().
	 * - fields: Array of arrays of field attributes (= the fields whose shortcodes should be inserted into loop).
	 *       For historical reason, each field is represented by a non-associative array whose elements have this meaning:
	 *       0 - prefix, text before [shortcode]
	 *       1 - [shortcode]
	 *       2 - suffix, text after [shortcode]
	 *       3 - field name
	 *       4 - header name
	 *       5 - row title <TH>
	 *       Note: 0,2 maybe not used since v1.3
	 * - args: An array of arguments for WPV_View_Base::generate_loop_output(), encoded as a JSON string.
	 *
	 * Outputs a JSON-encoded array with following elements:
	 * - success: Boolean. If false, the AJAX call has failed and this is the only element present (or making sense).
	 * - loop_output_settings: An array with loop settings (old values merged with new ones). Keys stored in database
	 *       and not updated by wpv_generate_view_loop_output() will be preserved.
	 * - ct_content: Content of the Content Template to be used in Loop, if such exists, or an empty string.
	 *
	 * @see WPV_View_Base::generate_loop_output() for detailed information.
	 *
	 * @since 1.8.0
	 * @since 2.6.4 Moved to an independent class inheriting from "Toolset_Ajax_Handler_Abstract".
	 * @since 2.6.4 Added the option to style the loop output as list with separators.
	 */
	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_GENERATE_VIEW_LOOP_OUTPUT,
			'public' => false,
		) );

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' ),
			);

			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		$view_id = toolset_getpost( 'view_id', false );

		if (
			! $view_id ||
			! is_numeric( $view_id ) ||
			intval( $view_id ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' ),
			);

			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		$style = toolset_getpost( 'style', 'unformatted' );
		$fields = json_decode( stripslashes( toolset_getpost( 'fields', '[]' ) ), true );
		$args = json_decode( stripslashes( toolset_getpost( 'args', '' ) ), true );
		if ( is_array( $args ) ) {
			// We can skip sanitization for specific fields for two reasons:
			//    * Even if we sanitize it, the user will be able to change it back to the unsanitized version in the
			//      Loop editor content.
			//    * WordPress will sanitize the contents of the Loop editor, which will include the skipped fields below,
			//      as the fields are saved in the database as part of a serialized array.
			$skip_sanitization_for_fields = array( 'list_separator' );
			foreach ( $args as $key => $arg ) {
				$args[ $key ] = ! in_array( $key, $skip_sanitization_for_fields, true ) ? sanitize_text_field( (string) $arg ) : $arg;
			}
		} else {
			$args = array();
		}

		// Translate field data from non-associative arrays into something that WPV_View_Base::generate_loop_output() understands.
		$fields_normalized = array();
		foreach ( $fields as $field ) {
			$field = array_map( 'sanitize_text_field', $field );
			$fields_normalized[] = array(
				'prefix' => $field[0],
				'shortcode' => $field[1],
				'suffix' => $field[2],
				'field_name' => $field[3],
				'header_name' => $field[4],
				'row_title' => $field[5],
			);
		}

		$loop_output_generator = $this->loop_generator;

		$loop_output = $loop_output_generator->generate( $style, $fields_normalized, $args );

		// Forward the fail when loop couldn't have been generated.
		if ( null === $loop_output ) {
			$data = array(
				'type' => 'error',
				'message' => __( 'Could not generate the Loop. Please reload and try again.', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		// Merge new settings to existing ones (overwrite keys from $layout_settings but keep the rest).
		$loop_output_settings = toolset_getarr( $loop_output, 'loop_output_settings', array() );
		$prev_settings = get_post_meta( $view_id, '_wpv_layout_settings', true );
		if ( ! is_array( $prev_settings ) ) {
			// Handle missing _wpv_layout_settings for given View.
			$prev_settings = array();
		}
		$loop_output_settings = array_merge( $prev_settings, $loop_output_settings );

		if (
			isset( $loop_output_settings['fields'] )
			&& is_array( $loop_output_settings['fields'] )
		) {
			$loop_output_settings['fields'] = array_values( $loop_output_settings['fields'] );
		}

		// Return the results.
		$data = array(
			'loop_output_settings' => $loop_output_settings,
			'ct_content' => toolset_getarr( $loop_output, 'ct_content', '' ),
		);

		$ajax_manager->ajax_finish(
			$data,
			true
		);
	}
}
