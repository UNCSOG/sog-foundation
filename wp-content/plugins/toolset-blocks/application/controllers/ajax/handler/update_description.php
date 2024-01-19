<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Handle saving a View or WPA description.
 *
 * @since 2.7
 */
class WPV_Ajax_Handler_Update_Description extends Toolset_Ajax_Handler_Abstract {

	/**
	 * @var WPV_View_Embedded
	 */
	private $view = null;

	/**
	 * Construcor for the AJAX handler that updates a View or WPA description.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param WPV_View_Embedded $view
	 */
	public function __construct(
		Toolset_Ajax $ajax_manager,
		WPV_View_Embedded $view = null
	) {
		parent::__construct( $ajax_manager );

		$this->view = $view;
	}

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 */
	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_UPDATE_DESCRIPTION,
			'capability_needed' => EDIT_VIEWS,
		) );

		$view_id = intval( toolset_getpost( 'id', 0 ) );

		if ( $view_id < 1 ) {
			$data = array(
				'type' => 'id',
				/* translators: Error message when failing to save a View or WordPress Archive description because we miss the object ID */
				'message' => __( 'Wrong or missing ID.', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		// This is full Views, so we will always get WPV_View, WPV_WordPress_Archive or null.
		$view = $this->view
			? $this->view
			: WPV_View_Base::get_instance( $view_id );

		// Fail if the View/WPA doesn't exist.
		if (
			null === $view
			|| (int) $view->id <= 0
		) {
			$data = array(
				'type' => 'id',
				/* translators: Error message when failing to save a View or WordPress Archive description because we miss the object ID */
				'message' => __( 'Wrong or missing object.', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		$transaction_result = $view->update_transaction( array(
			'description' => toolset_getpost( 'description' ),
		) );

		// On failure, return the first available error message (there should be only one anyway).
		if ( ! $transaction_result['success'] ) {
			/* translators: Error message when failing to save a View or WordPress Archive description because of an unknown problem */
			$error_message = toolset_getarr( $transaction_result, 'first_error_message', __( 'An unexpected error happened.', 'wpv-views' ) );
			$data = array(
				'type' => 'update',
				'message' => $error_message,
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		// Success.
		$data = array(
			'id' => $view_id,
		);
		$ajax_manager->ajax_finish(
			$data,
			true
		);
	}

}
