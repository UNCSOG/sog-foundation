<?php

/**
 * Create a target page for the results of a View form.
 *
 * @since 2.8 Moved the callback here.
 */
class WPV_Ajax_Handler_Create_Form_Target_Page extends Toolset_Ajax_Handler_Abstract {

	const NONCE = 'wpv_create_form_target_page_nonce';
	const CAPABILITY = 'publish_pages';

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => self::NONCE,
				'capability_needed' => self::CAPABILITY,
			)
		);

		$target_page = array(
			'post_title' => wp_strip_all_tags( toolset_getpost('post_title') ),
			'post_status' => 'publish',
			'post_type' => 'page',
		);

		$target_page_id = wp_insert_post( $target_page );

		$target_page_title = get_the_title( $target_page_id );

		$response = array(
			'page_title' => $target_page_title,
			'page_id' => $target_page_id,
		);

		$ajax_manager->ajax_finish(
			$response,
			true
		);

	}

}
