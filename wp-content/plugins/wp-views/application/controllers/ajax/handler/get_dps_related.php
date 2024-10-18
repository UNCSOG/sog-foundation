<?php

/**
 * Deprecated AJAX callback.
 *
 * @since 2.8 Moved the callback here.
 */
class WPV_Ajax_Handler_Get_Dps_Related extends Toolset_Ajax_Handler_Abstract {

	public function process_call( $arguments ) {

		_deprecated_hook( 'wp_ajax_wpv_get_dps_related', 'Toolset Views 2.8' );

		wp_send_json_error();
	}

}
