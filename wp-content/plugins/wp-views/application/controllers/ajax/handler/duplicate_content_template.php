<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Duplicate a Content Template.
 *
 * Currently only used in the Content Template Listing page.
 *
 * @since unknown
 * @since 2.7.0 Moved the callback here.
 */
class WPV_Ajax_Handler_Duplicate_Content_Template extends Toolset_Ajax_Handler_Abstract {
	private $ct;

	public function __construct(
		Toolset_Ajax $ajax_manager,
		$ct = null
	) {
		parent::__construct( $ajax_manager );

		$this->ct = $ct;
	}


	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_DUPLICATE_CONTENT_TEMPLATE,
				'public' => false,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		$title = '';
		if ( isset( $_POST['title'] ) ) {
			$title = sanitize_text_field( $_POST['title'] );
		}

		if ( ! isset( $_POST['id'] ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'title',
					'message' => __( 'You can not duplicate a Content Template without a source Content Template ID.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		if ( empty( $title ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'title',
					'message' => __( 'You can not create a Content Template with an empty name.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		// Load the original CT.
		$original_ct_id = intval( $_POST['id'] );

		try {
			$original_ct = $this->ct ?
				$this->ct :
				new WPV_Content_Template( $original_ct_id );

			if ( ! $original_ct instanceof WPV_Content_Template ) {
				throw new Exception();
			}
		} catch ( Exception $e ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'error',
					'message' => __( 'An unexpected error happened.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		// Clone and report the result.
		$cloned_ct = $original_ct->duplicate( $title, false );

		if ( ! $cloned_ct instanceof WPV_Content_Template ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'error',
					'message' => isset( $cloned_ct['error'] ) ? $cloned_ct['error'] : __( 'An error occurred while duplicating the Content Template.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		$ajax_manager->ajax_finish(
			array(),
			true
		);
	}
}
