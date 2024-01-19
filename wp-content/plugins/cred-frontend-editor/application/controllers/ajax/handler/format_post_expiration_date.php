<?php

/**
 * Delete a post/user form
 *
 * @since 2.3
 */
class CRED_Ajax_Handler_Format_Post_Expiration_Date extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Process the AJAX callback.
	 *
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => CRED_Ajax::CALLBACK_FORMAT_POST_EXPIRATION_DATE,
				'is_public' => true,
			)
		);

		$date = toolset_getpost( 'date' );

		if ( strlen( $date ) < 8 ) {
			$results = array(
				'message'=> __( 'Wrong date data.', 'wp-cred' ),
			);

			$this->ajax_finish( $results, false );
			return;
		}

		$timestamp = adodb_mktime( 0, 0, 0, substr( $date, 2, 2 ), substr( $date, 0, 2 ), substr( $date, 4, 4 ) );

		$results = array(
			'timestamp'=> $timestamp,
		);

		$this->ajax_finish( $results, true );
	}

}
