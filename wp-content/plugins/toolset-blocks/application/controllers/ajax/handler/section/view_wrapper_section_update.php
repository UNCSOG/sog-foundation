<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Class WPV_Ajax_Handler_View_Wrapper_Section_Update
 *
 * Handle updating of the options under the View wrapper section in the View editor.
 *
 * @since 2.6.4
 */
class WPV_Ajax_Handler_View_Wrapper_Section_Update extends Toolset_Ajax_Handler_Abstract {
	/**
	 * View wrapper update process handler.
	 *
	 * @param array $arguments The arguments of the AJAX call.
	 */
	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_UPDATE_VIEW_WRAPPER_SECTION,
		) );

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				// phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' ),
			);

			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		$post_id = toolset_getpost( 'id', false );

		if (
			! $post_id ||
			! is_numeric( $post_id ) ||
			intval( $post_id ) < 1
		) {
			$data = array(
				// phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' ),
			);

			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		$changed = false;

		$view_array = get_post_meta( $post_id, '_wpv_settings', true );

		$options_to_save = array(
			'disable_view_wrapper' => 'bool',
		);

		foreach ( $options_to_save as $option => $type ) {
			$option_value = toolset_getpost( $option, false );
			if (
				(
					false !== $option_value ||
					'bool' === $type
				) &&
				(
					! isset( $view_array[ $option ] ) ||
					$option_value !== $view_array[ $option ]
				)
			) {
				if ( is_array( $option_value ) ) {
					$option_value = array_map( 'sanitize_text_field', $option_value );
				} else {
					$option_value = sanitize_text_field( $option_value );
				}

				$view_array[ $option ] = $option_value;

				$changed = true;
			}
		}

		if ( $changed ) {
			update_post_meta( $post_id, '_wpv_settings', $view_array );
			do_action( 'wpv_action_wpv_save_item', $post_id );
		}

		$data = array(
			// phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
			'id' => $post_id,
			'message' => __( 'View wrapper saved', 'wpv-views' ),
		);

		$ajax_manager->ajax_finish(
			$data,
			true
		);
	}
}
