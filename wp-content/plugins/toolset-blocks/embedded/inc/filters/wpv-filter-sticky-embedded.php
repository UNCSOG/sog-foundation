<?php

/**
* Post Sticky frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_Post_Sticky_Frontend_Filter::on_load();

/**
* WPV_Post_Sticky_Frontend_Filter
*
* Views Post Sticky Filter Frontend Class
*
* @since 2.1
*/

class WPV_Post_Sticky_Frontend_Filter {
	
	static function on_load() {
		// Apply frontend filter by post stickyness
        add_filter( 'wpv_filter_query',											array( 'WPV_Post_Sticky_Frontend_Filter', 'filter_post_sticky' ), 900, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',					array( 'WPV_Post_Sticky_Frontend_Filter', 'archive_filter_post_sticky' ), 900, 3 );
    }
	
	/**
	* filter_post_sticky
	*
	* Apply the filter by stickyness on Views.
	*
	* @since 2.1	Moved to a statc method
	*/
	
	static function filter_post_sticky( $query, $view_settings, $view_id ) {
		if ( isset( $view_settings['post_sticky'] ) ) {
			$sticky = get_option( 'sticky_posts' ) ? get_option( 'sticky_posts' ) : array();
			switch ( $view_settings['post_sticky'] ) {
				case 'include':
					$query['post__in'] = isset( $query['post__in'] ) ? array_intersect( (array) $query['post__in'], $sticky ) : $sticky;
					$query['post__in'] = array_values( $query['post__in'] );
					if ( empty( $query['post__in'] ) ) {
						$query['post__in'] = array( '0' );
					}
					break;
				case 'exclude':
					$query['post__not_in'] = isset( $query['post__not_in'] ) ? array_merge( (array) $query['post__not_in'], $sticky ) : $sticky;
					break;
			}
		}
		return $query;
	}
	
	/**
	* archive_filter_post_sticky
	*
	* Apply the filter by stickyness on WPAs.
	*
	* @since 2.1
	*/
	
	static function archive_filter_post_sticky( $query, $archive_settings, $archive_id ) {
		if ( isset( $archive_settings['post_sticky'] ) ) {
			$sticky = get_option( 'sticky_posts' ) ? get_option( 'sticky_posts' ) : array();
			$post__in = $query->get( 'post__in' );
			$post__in = isset( $post__in ) ? $post__in : array();
			$post__not_in = $query->get( 'post__not_in' );
			$post__not_in = isset( $post__not_in ) ? $post__not_in : array();
			switch ( $archive_settings['post_sticky'] ) {
				case 'include':
					$post__in = ( count( $post__in ) > 0 ) ? array_intersect( $post__in, $sticky ) : $sticky;
					$post__in = array_values( $post__in );
					if ( empty( $post__in ) ) {
						$post__in = array( '0' );
					}
					$query->set( 'post__in', $post__in );
					break;
				case 'exclude':
					$post__not_in = array_merge( $post__not_in, $sticky );
					if ( ! empty( $post__not_in ) ) {
						$query->set( 'post__not_in', $post__not_in );
					}
					break;
			}
		}
	}
	
}