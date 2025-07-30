<?php

/**
 * Handler for the wpv_get_available_views filter API.
 *
 * @since m2m
 */
class WPV_Api_Handler_Get_Available_Views implements WPV_Api_Handler_Interface {

	const TRANSIENT_KEY = 'wpv_transient_published_views';

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
		$view_available = $wpdb->get_results(
			"SELECT ID, post_title, post_name, {$wpdb->postmeta}.meta_value as is_gutenberg_view FROM {$wpdb->posts}
			LEFT JOIN {$wpdb->postmeta}
			ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '_wpv_is_gutenberg_view'
			WHERE post_type='view'
			AND post_status in ('publish')
			ORDER BY post_title ASC"
		);
		$views_objects_transient_to_update = array(
			'archive'	=> array(),
			'posts'		=> array(),
			'taxonomy'	=> array(),
			'users'		=> array()
		);
		$wpv_filter_wpv_get_view_settings_args = array(
			'override_view_settings'	=> false,
			'extend_view_settings'		=> false,
			'public_view_settings'		=> false
		);
		foreach ( $view_available as $view ) {
			if ( WPV_View_Base::is_archive_view( $view->ID ) ) {
				// Archive Views - add only to cache
				$views_objects_transient_to_update['archive'][] = $view;
			} else {
				$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view->ID, $wpv_filter_wpv_get_view_settings_args );
				$current_view_type = 'posts';
				if ( isset( $view_settings['query_type'][0] ) ) {
					$current_view_type = $view_settings['query_type'][0];
				}
				$views_objects_transient_to_update[ $current_view_type ][] = $view;
			}
		}
		set_transient( self::TRANSIENT_KEY, $views_objects_transient_to_update, WEEK_IN_SECONDS );
		return $views_objects_transient_to_update;
	}

}
