<?php

/**
 * Handler for the wpv_get_available_content_templates filter API.
 *
 * @since m2m
 */
class WPV_Api_Handler_Get_Available_Content_Templates implements WPV_Api_Handler_Interface {
	
	const TRANSIENT_KEY = 'wpv_transient_published_cts';

	public function __construct() { }

	/**
	 * @return array
	 *
	 * @since m2m
	 */
	function process_call( $arguments ) {
		
		$cached = get_transient( self::TRANSIENT_KEY );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		return $this->generate_transient();
	}
	
	/**
	 * Generate the transient.
	 *
	 * @since m2m
	 */
	private function generate_transient() {
		global $wpdb;
		$values_to_prepare = array( 'view-template' );
		$view_template_available = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_name FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_status in ('publish')",
				$values_to_prepare
			)
		);
		set_transient( self::TRANSIENT_KEY, $view_template_available, WEEK_IN_SECONDS );
		return $view_template_available;
	}
	
}