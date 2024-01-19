<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Duplicate a View
 *
 * @since 1.3
 * @since 2.8 Moved the callback here.
 * @see WPV_View::duplicate
 */
class WPV_Ajax_Handler_Duplicate_View extends Toolset_Ajax_Handler_Abstract {
	/** @var callable|null */
	private $wpv_view_instance;

	/** @var callable|null */
	private $wpv_view_is_name_used;

	/**
	 * Constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param callable|null $wpv_view
	 * @param callable|null $is_view_name_used
	 */
	public function __construct(
		Toolset_Ajax $ajax_manager,
		callable $wpv_view = null,
		callable $is_view_name_used = null
	) {
		parent::__construct( $ajax_manager );

		$this->wpv_view_instance = $wpv_view ?: array( \WPV_View::class, 'get_instance' );
		$this->wpv_view_is_name_used = $is_view_name_used ?: array( \WPV_View::class, 'is_name_used' );
	}

	/**
	 * Process the AJAX call.
	 *
	 * @param mixed $arguments
	 */
	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_DUPLICATE_VIEW,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		$post_id = (int) toolset_getpost( 'id', 0 );
		$post_name = sanitize_text_field( toolset_getpost( 'name', '' ) );

		if (
			0 === $post_id ||
			empty( $post_name )
		) {
			$data = array(
				'message' => __( 'Wrong data', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		if ( call_user_func( $this->wpv_view_is_name_used, $post_name ) ) {
			$data = array(
				'message' => __( 'A View with that name already exists. Please use another name.', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		// Get the original View.
		$original_view = call_user_func( $this->wpv_view_instance, $post_id );

		if ( null === $original_view ) {
			$data = array(
				'message' => __( 'Wrong data', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}

		$duplicate_view_id = $original_view->duplicate( $post_name );

		if ( $duplicate_view_id ) {
			// New View id
			$ajax_manager->ajax_finish(
				array(),
				true
			);
			return;
		} else {
			$data = array(
				'message' => __( 'Unexpected error', 'wpv-views' ),
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
			return;
		}
	}
}
