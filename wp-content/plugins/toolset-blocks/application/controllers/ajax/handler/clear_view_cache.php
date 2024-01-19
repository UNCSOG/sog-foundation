<?php
/**
 * Clear the View output stored cache.
 *
 * @package Toolset Views
 * @since 3.1
 */

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Clear the View output stored cache.
 *
 * @since 3.1
 */
class WPV_Ajax_Handler_Clear_View_Cache extends Toolset_Ajax_Handler_Abstract {

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Views\Invalidator
	 */
	private $invalidator;

	public function __construct(
		\Toolset_Ajax $ajax_manager,
		\OTGS\Toolset\Views\Controller\Cache\Views\Invalidator $invalidator
	) {
		parent::__construct( $ajax_manager );
		$this->invalidator = $invalidator;
	}

	/**
	 * Process the AJAX call.
	 *
	 * @param mixed $arguments
	 * @since 3.1
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_CLEAR_VIEW_CACHE,
				'public' => false,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		$object_id = (int) toolset_getpost( 'id', 0 );
		if ( 0 == $object_id ) {
			$data = array(
				'message' => __( 'Missing object ID', 'wpv-views' ),
			);
			$this->ajax_finish( $data, false );
			return;
		}

		$this->invalidator->invalidate_view_cache_action( $object_id );

		$this->ajax_finish( array(), true );
	}
}
