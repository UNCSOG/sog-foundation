<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Save Views and WPA title and description section.
 *
 * Expects following $_POST variables:
 * - wpnonce
 * - id
 * - title
 * - slug
 * - description
 *
 * @since unknown
 * @since 2.1   Moved to a static method.
 * @since 2.7.0 Moved it in an independent handler. Removed the need for the "is_title_escaped" $_POST variable.
 */
class WPV_Ajax_Handler_Update_View_Title extends Toolset_Ajax_Handler_Abstract {
	private $view;

	public function __construct(
		Toolset_Ajax $ajax_manager,
		$view = null
	) {
		parent::__construct( $ajax_manager );

		$this->view = $view;
	}

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce'  => WPV_Ajax::CALLBACK_UPDATE_VIEW_TITLE,
				'public' => false,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		$view_id = intval( toolset_getpost( 'id', 0 ) );

		// Fail if the View/WPA doesn't exist.
		if ( 0 === $view_id ) {
			$ajax_manager->ajax_finish(
				array(
					'type'    => 'id',
					'message' => __( 'Missing View ID.', 'wpv-views' ),
				),
				false
			);

			return;
		}

		// This is full Views, so we will always get WPV_View, WPV_WordPress_Archive or null.
		$view = $this->view ?
			$this->view :
			WPV_View_Base::get_instance( $view_id );

		if (
			null === $view ||
			(int) $view->id <= 0
		) {
			$ajax_manager->ajax_finish(
				$data = array(
					'type'    => 'view_not_found',
					'message' => __( 'A View with the given ID was not found.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		// Try to update all three properties at once.
		$transaction_result = $view->update_transaction(
			array(
				'title' => toolset_getpost( 'title' ),
			)
		);

		// On failure, return the first available error message (there should be only one anyway).
		if ( ! $transaction_result['success'] ) {
			$ajax_manager->ajax_finish(
				array(
					'type'    => 'update',
					'message' => toolset_getarr( $transaction_result, 'first_error_message', __( 'An unexpected error happened.', 'wpv-views' ) ),
				),
				false
			);

			return;
		}

		$ajax_manager->ajax_finish(
			array( 'id' => $view_id ),
			true
		);
	}
}
