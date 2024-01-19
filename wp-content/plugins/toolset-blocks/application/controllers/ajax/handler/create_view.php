<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Create a View
 *
 * @since 1.3
 * @since 2.8 Moved the callback here.
 * @see wpv_create_view
 */
class WPV_Ajax_Handler_Create_View extends Toolset_Ajax_Handler_Abstract {

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_CREATE_VIEW,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		$view_title = toolset_getpost( 'title' );
		$view_title = empty( $view_title ) ? __('Unnamed View', 'wpv-views') : $view_title;

		$view_kind = toolset_getpost( 'kind' );
		$view_kind = empty( $view_kind ) ? 'normal' : $view_kind;

		$view_purpose = toolset_getpost( 'purpose' );
		$view_purpose = empty( $view_purpose ) ? 'full' : $view_purpose;

		$post_types_array = isset( $_POST['post_type'] ) ? wp_unslash( $_POST['post_type'] ) : array();
		$sanitized_post_types_array = array();
		if ( is_array( $post_types_array ) ) {
			foreach ( $post_types_array as $post_type ) {
				$sanitized_post_types_array[] = sanitize_text_field( $post_type );
			}
		}

		$args = array(
			'title' => $view_title,// This is sanitized in wpv_create_view, see WPV_View_Base::create_post()
			'settings' => array(
				'view-query-mode' => sanitize_text_field( $view_kind ),
				'view_purpose' => sanitize_text_field( $view_purpose ),
				'post_type' => $sanitized_post_types_array,
			),
		);

		$response = wpv_create_view( $args );

		if ( isset( $response['success'] ) ) {
			$data = array(
				'new_view_id' => $response['success']
			);
			$ajax_manager->ajax_finish(
				$data,
				true
			);
			return;
		} else {
			$data = array(
				'message' => toolset_getarr( $response, 'error', __( 'The View could not be created', 'wpv-views' ) )
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

	}

}
