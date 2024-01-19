<?php
/**
 * Handler for the wpv_is_views_lite filter API.
 *
 * @since Views Lite
 */
class WPV_Api_Handler_Is_Views_Lite implements WPV_Api_Handler_Interface {

	public function __construct() { }

	/**
	 * @return bool
	 *
	 * @since Views Lite
	 */
	function process_call( $args ) {

		if ( defined( 'WPV_LITE' ) ) {
			return WPV_LITE;
		}

		// if not defined return false (just for safety)
		return false;
	}

}