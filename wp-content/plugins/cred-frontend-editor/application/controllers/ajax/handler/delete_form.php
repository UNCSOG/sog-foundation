<?php

/**
 * Delete a post/user form
 *
 * @since 2.2.1.1
 */
class CRED_Ajax_Handler_Delete_Form extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Process the AJAX callback.
	 *
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => CRED_Ajax::CALLBACK_DELETE_FORM,
			)
		);

		$form_id = toolset_getpost( 'formId', false );

		if ( ! $form_id ) {
			$this->ajax_finish(
				array(
					/* translators: Error message when trying to delete an unknown post or user form */
					'message' => __( 'Unknown form.', 'wp-cred' ),
				),
				false
			);
			return;
		}

		$deleted = wp_delete_post( $form_id );

		if ( ! $deleted ) {
			$this->ajax_finish(
				array(
					/* translators: Error message when failing to delete a post or user form */
					'message' => __( 'The form could not be deleted.', 'wp-cred' ),
				),
				false
			);
			return;
		}

		$this->ajax_finish(
			array(
				/* translators: Message after deleting a post or user form */
				'message' => __( 'The form was deleted.', 'wp-cred' ),
			),
			true
		);
	}

}
