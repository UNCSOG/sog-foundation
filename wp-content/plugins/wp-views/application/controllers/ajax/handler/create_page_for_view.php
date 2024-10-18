<?php

/**
 * Duplicate a View
 *
 * @since 1.3
 * @since 2.8 Moved the callback here.
 * @see WPV_View::duplicate
 */
class WPV_Ajax_Handler_Create_Page_For_View extends Toolset_Ajax_Handler_Abstract {

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_CREATE_PAGE_FOR_VIEW,
			)
		);

		// Check if view has been created
		$view_id = (int) toolset_getpost( 'id', 0 );

		if ( 0 == $view_id ) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or Missing View ID.', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		// Check for rest of the attributes
		$view_title = (string) toolset_getpost( 'title', '' );
		$view_title = sanitize_text_field( $view_title );
		if ( empty( $view_title ) ) {
			$data = array(
				'type' => 'title',
				'message' => __( 'Missing View Title.', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		$view_slug = (string) toolset_getpost( 'slug', '' );
		if ( empty( $view_slug ) ) {
			$data = array(
				'type' => 'slug',
				'message' => __( 'Missing View Slug.', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		// If all set, continue to create the page

		// Create page object and save in the database
		$wpv_page = array(
			'post_title' => 'View: '. $view_title,
			'post_content' => '[wpv-view name="' . $view_slug . '"]',
			'post_status' => 'draft',
			'post_type' => 'page'
		);
		$wpv_page_id = wp_insert_post( $wpv_page );

		// Return success
		$data = array(
			'id' => $view_id,
			'page_id' => $wpv_page_id,
			'edit_url' => get_edit_post_link( $wpv_page_id, '' ),
			'message' => __( 'Page created.', 'wpv-views' )
		);
		$ajax_manager->ajax_finish(
			$data,
			true
		);

	}

}
