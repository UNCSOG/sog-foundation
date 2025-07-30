<?php

/**
 * Handler for the wpv_get_archives_and_templates_assignments filter API.
 */
class WPV_Api_Handler_Get_Archive_For_Taxonomy_Term implements WPV_Api_Handler_Interface {

	public function __construct() { }

	/**
	 * @param array $arguments
	 *
	 * @return array
	 */
	public function process_call( $arguments ) {
		$default_value = toolset_getarr( $arguments, 0 );
		$taxonomy_slug = toolset_getarr( $arguments, 1 );
		$term_slug = toolset_getarr( $arguments, 2 );

		global $WPV_view_archive_loop;
		$wpa_id = $WPV_view_archive_loop->get_archive_for_taxonomy_term( $taxonomy_slug, $term_slug );

		return 0 !== $wpa_id ? $wpa_id : $default_value;
	}

}
