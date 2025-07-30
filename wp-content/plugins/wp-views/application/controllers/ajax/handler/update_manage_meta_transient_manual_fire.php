<?php

/**
 * Handle invalidating the cacheod for meta keys.
 *
 * @since 2.8.1
 */
class WPV_Ajax_Handler_Update_Manage_Meta_Transient_Manual_Fire extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_UPDATE_MANAGE_META_TRANSIENT_MANUAL_FIRE,
		) );

		do_action( 'wpv_action_wpv_delete_meta_transients' );

		$this->ajax_finish();
	}

}
