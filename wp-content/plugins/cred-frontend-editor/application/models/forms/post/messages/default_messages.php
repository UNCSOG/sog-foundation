<?php

namespace OTGS\Toolset\CRED\Model\Forms\Post\Messages;

class DefaultMessages {

	/**
	 * @return array
	 */
	public function getDefaultMessages() {
		return array(
			'cred_message_post_saved' => array(
				'message'     => 'Post ' . __( 'Saved', 'wp-cred' ),
				'description' => __( 'Post saved Message', 'wp-cred' ),
			),
			'cred_message_post_not_saved_singular' => array(
				'message'     => __( 'The post was not saved because of the following problem:', 'wp-cred' ),
				'description' => __( 'Post not saved message (one problem)', 'wp-cred' )
			),
			'cred_message_post_not_saved_plural'   => array(
				'message'     => __( 'The post was not saved because of the following %NN problems:', 'wp-cred' ),
				'description' => __( 'Post not saved message (several problems)', 'wp-cred' )
			),
			'cred_message_invalid_form_submission' => array(
				'message'     => __( 'Invalid Form Submission (nonce failure)', 'wp-cred' ),
				'description' => __( 'Invalid submission message', 'wp-cred' )
			),
			'cred_message_no_data_submitted' => array(
				'message'     => __( 'Invalid Form Submission (maybe a file has a size greater than allowed)', 'wp-cred' ),
				'description' => __( 'Invalid Form Submission (maybe a file has a size greater than allowed)', 'wp-cred' ),
			),
			'cred_message_upload_failed' => array(
				'message'     => __( 'Upload Failed', 'wp-cred' ),
				'description' => __( 'Upload failed message', 'wp-cred' ),
			),
			'cred_message_field_required' => array(
				'message'     => __( 'This field is required', 'wp-cred' ),
				'description' => __( 'Required field message', 'wp-cred' )
			),
			'cred_message_enter_valid_date' => array(
				'message'     => __( 'Please enter a valid date', 'wp-cred' ),
				'description' => __( 'Invalid date message', 'wp-cred' ),
			),
			'cred_message_values_do_not_match' => array(
				'message'     => __( 'Field values do not match', 'wp-cred' ),
				'description' => __( 'Invalid hidden field value message', 'wp-cred' )
			),
			'cred_message_enter_valid_email' => array(
				'message'     => __( 'Please enter a valid email address', 'wp-cred' ),
				'description' => __( 'Invalid email message', 'wp-cred' )
			),
			'cred_message_enter_valid_colorpicker' => array(
				'message'     => __( 'Please use a valid hexadecimal value', 'wp-cred' ),
				'description' => __( 'Invalid color picker message', 'wp-cred' )
			),
			'cred_message_enter_valid_number' => array(
				'message'     => __( 'Please enter numeric data', 'wp-cred' ),
				'description' => __( 'Invalid numeric field message', 'wp-cred' )
			),
			'cred_message_enter_valid_url' => array(
				'message'     => __( 'Please enter a valid URL address', 'wp-cred' ),
				'description' => __( 'Invalid URL message', 'wp-cred' )
			),
			'cred_message_enter_valid_captcha' => array(
				'message'     => __( 'Wrong CAPTCHA', 'wp-cred' ),
				'description' => __( 'Invalid captcha message', 'wp-cred' ),
			),
			'cred_message_missing_captcha' => array(
				'message'     => __( 'Missing CAPTCHA', 'wp-cred' ),
				'description' => __( 'Missing captcha message', 'wp-cred' ),
			),
			'cred_message_show_captcha' => array(
				'message'     => __( 'Show CAPTCHA', 'wp-cred' ),
				'description' => __( 'Show captcha button', 'wp-cred' ),
			),
			'cred_message_edit_skype_button' => array(
				'message'     => __( 'Edit Skype Button', 'wp-cred' ),
				'description' => __( 'Edit skype button', 'wp-cred' ),
			),
			'cred_message_not_valid_image' => array(
				'message'     => __( 'Not Valid Image', 'wp-cred' ),
				'description' => __( 'Invalid image message', 'wp-cred' ),
			),
			'cred_message_file_type_not_allowed' => array(
				'message'     => __( 'File type not allowed', 'wp-cred' ),
				'description' => __( 'Invalid file type message', 'wp-cred' ),
			),
			'cred_message_image_width_larger' => array(
				'message'     => __( 'Image width larger than %dpx', 'wp-cred' ),
				'description' => __( 'Invalid image width message', 'wp-cred' ),
			),
			'cred_message_image_height_larger' => array(
				'message'     => __( 'Image height larger than %dpx', 'wp-cred' ),
				'description' => __( 'Invalid image height message', 'wp-cred' ),
			),
			'cred_message_show_popular' => array(
				'message'     => __( 'Show Popular', 'wp-cred' ),
				'description' => __( 'Taxonomy show popular message', 'wp-cred' ),
			),
			'cred_message_hide_popular' => array(
				'message'     => __( 'Hide Popular', 'wp-cred' ),
				'description' => __( 'Taxonomy hide popular message', 'wp-cred' ),
			),
			'cred_message_add_taxonomy' => array(
				'message'     => __( 'Add', 'wp-cred' ),
				'description' => __( 'Add taxonomy term', 'wp-cred' ),
			),
			'cred_message_remove_taxonomy' => array(
				'message'     => __( 'Remove', 'wp-cred' ),
				'description' => __( 'Remove taxonomy term', 'wp-cred' ),
			),
			'cred_message_add_new_taxonomy' => array(
				'message'     => __( 'Add New', 'wp-cred' ),
				'description' => __( 'Add new taxonomy message', 'wp-cred' ),
			),
			'cred_message_access_error_can_not_use_form' => array(
				'message'     => '',
				/* translators: Label for the setting to show when the current visitor can not use the current form */
				'description' => __( 'Optional message to show when the current visitor is not allowed to use this form', 'wp-cred' ),
			),
		);
	}

}
