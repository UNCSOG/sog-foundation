<?php

/**
 * Handler for the wpv_get_archives_and_templates_assignments filter API.
 */
class WPV_Api_Handler_Get_Archives_And_Templates_Assignments implements WPV_Api_Handler_Interface {

	const TRANSIENT_KEY = 'wpv_archives_and_templates_assignments_array';

	public function __construct() { }

	/**
	 * @param array $arguments
	 *
	 * @return array
	 */
	public function process_call( $arguments ) {

		$cached = get_transient( self::TRANSIENT_KEY );

		if ( false !== $cached ) {
			return $cached;
		}

		return $this->generate_transient();
	}

	/**
	 * Returns all assignments to the generic filter from CT & WPA.
	 *
	 * @return array
	 */
	private function generate_transient() {
		global $WPV_view_archive_loop;
		return array_merge(
			$WPV_view_archive_loop->get_archive_loops( 'native', true, true, true ),
			$WPV_view_archive_loop->get_archive_loops( 'post_type', true, true, true ),
			$WPV_view_archive_loop->get_archive_loops( 'taxonomy', true, true, true )
		);
	}

}
