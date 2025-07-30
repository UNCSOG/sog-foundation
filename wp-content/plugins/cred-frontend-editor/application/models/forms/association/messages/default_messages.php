<?php

namespace OTGS\Toolset\CRED\Model\Forms\Association\Messages;

class DefaultMessages {

	/**
	 * @return array
	 */
	public function getDefaultMessages() {
		return array(
			'cred_message_post_saved'              => array(
				'message'     => __( 'Relationship Saved', 'wp-cred' ),
				'description' => __( 'Relationship saved Message', 'wp-cred' )
			),
			'cred_message_post_not_saved_singular' => array(
				'message'     => __( 'The relationship was not saved because of the following problem:', 'wp-cred' ),
				'description' => __( 'Relationship not saved message (one problem)', 'wp-cred' )
			),
			'cred_message_post_not_saved_plural'   => array(
				'message'     => __( 'The relationship was not saved because of the following %NN problems:', 'wp-cred' ),
				'description' => __( 'Relationship not saved message (several problems)', 'wp-cred' )
			),
			'cred_message_invalid_form_submission' => array(
				'message'     => __( 'Invalid Form Submission (nonce failure)', 'wp-cred' ),
				'description' => __( 'Invalid submission message', 'wp-cred' )
			),
			'cred_message_field_required'          => array(
				'message'     => __( 'This field is required', 'wp-cred' ),
				'description' => __( 'Required field message', 'wp-cred' )
			),
			'cred_message_values_do_not_match'     => array(
				'message'     => __( 'Field values do not match', 'wp-cred' ),
				'description' => __( 'Invalid hidden field value message', 'wp-cred' )
			),
			'cred_message_enter_valid_email'       => array(
				'message'     => __( 'Please enter a valid email address', 'wp-cred' ),
				'description' => __( 'Invalid email message', 'wp-cred' )
			),
			'cred_message_enter_valid_number'      => array(
				'message'     => __( 'Please enter numeric data', 'wp-cred' ),
				'description' => __( 'Invalid numeric field message', 'wp-cred' )
			),
			'cred_message_enter_valid_url'         => array(
				'message'     => __( 'Please enter a valid URL address', 'wp-cred' ),
				'description' => __( 'Invalid URL message', 'wp-cred' )
			)
		);
	}

}
