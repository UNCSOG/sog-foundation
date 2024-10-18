<?php

/**
* Post Status frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_Post_Status_Frontend_Filter::on_load();

/**
* WPV_Post_Status_Frontend_Filter
*
* Views Post Status Filter Frontend Class
*
* @since 2.1
*/

class WPV_Post_Status_Frontend_Filter {
	
	static function on_load() {
		// Apply frontend filter by post status
        add_filter( 'wpv_filter_query',											array( 'WPV_Post_Status_Frontend_Filter', 'filter_post_status' ), 10, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',					array( 'WPV_Post_Status_Frontend_Filter', 'archive_filter_post_status' ), 40, 3 );
		// Auxiliar filter
		add_filter( 'wpv_filter_wpv_filter_auxiliar_post_relationship_query',	array( 'WPV_Post_Status_Frontend_Filter', 'filter_post_status' ), 10, 3 );
    }
	
	/**
	* filter_post_status
	*
	* Apply the post status filter for Views.
	*
	* @sinde unknown
	* @since 2.1	Moved to a statis method
	*/
	
	static function filter_post_status( $query, $view_settings, $view_id ) {
		if ( isset( $view_settings['post_status'] ) ) {
			$query['post_status'] = $view_settings['post_status'];
		} else {
			$status = array( 'publish' );
			if ( in_array( 'attachment', $query['post_type'] ) ) {
				$status[] = 'inherit';
			}
			if ( current_user_can( 'read_private_posts' ) ) {
				$status[] = 'private';
			}
			$query['post_status'] = $status;
		}
		return $query;
	}
	
	/**
	* archive_filter_post_status
	*
	* Apply the post status filter for WPAs.
	*
	* @since 2.1
	*/
	
	static function archive_filter_post_status( $query, $archive_settings, $archive_id ) {
		if ( isset( $archive_settings['post_status'] ) ) {
			$query->set( 'post_status', $archive_settings['post_status'] );
		} else {
			$status = array( 'publish' );
			$post_type = $query->get( 'post_type' );
			$post_type = isset( $post_type ) ? $post_type : array();
			$post_type = is_array( $post_type ) ? $post_type : array( $post_type );
			if ( in_array( 'attachment', $post_type ) ) {
				$status[] = 'inherit';
			}
			if ( current_user_can( 'read_private_posts' ) ) {
				$status[] = 'private';
			}
			$query->set( 'post_status', $status );
		}
	}
	
}