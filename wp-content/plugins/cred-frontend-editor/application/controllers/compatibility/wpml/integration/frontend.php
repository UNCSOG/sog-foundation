<?php
/**
 * WPML integration for frontend actions.
 *
 * @package Toolset Forms
 * @since 2.5.5
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration;

/**
 * Compatibility layer for submitting frontend forms and WPML.
 *
 * @since 2.5.5
 */
class Frontend {

	public function initialize() {
		add_action( 'cred_save_data', array( $this, 'maybe_sync_field_values_on_frontend_submit' ), 10, 2 );
	}

	/**
	 * Maybe sync all sync-able felds for a post after it is submitted in a frontend form.
	 *
	 * @param int $post_id
	 * @param array $form_data
	 * @since 2.5.5
	 */
	public function maybe_sync_field_values_on_frontend_submit( $post_id, $form_data ) {
		$form_id = (int) toolset_getarr( $form_data, 'id', 0 );
		if ( 0 === $form_id ) {
			return;
		}

		if ( \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE !== get_post_type( $form_id ) ) {
			return;
		}

		// Sync all fields for this pos that are set to be syncronized on all languages.
		do_action( 'wpml_sync_all_custom_fields', $post_id );
	}

}
