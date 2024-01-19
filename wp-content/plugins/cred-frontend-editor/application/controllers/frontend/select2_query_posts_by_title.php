<?php

/**
 * Class used by CRED_Field_Utils in order to retrieve query posts by post_title only
 *
 * @since 1.9.4
 */
class CRED_Frontend_Select2_Query_Posts_By_Title {

	private static $instance;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Retrieves WP_Post[] and if it is a query search tries to use the posts_where filter in order to
	 * search by post_title only
	 *
	 * @return WP_Post[]
	 */
	public function get_posts( $args ) {
		$has_search_query = ( isset( $args['s'] ) && ! empty( $args['s'] ) );
		if ( $has_search_query ) {
			add_filter( 'posts_where', array( $this, 'search_by_title_only' ), 10, 2 );
		}

		$wp_query = new WP_Query( $args );
		$posts = $wp_query->get_posts();
		if ( $has_search_query ) {
			remove_filter( 'posts_where', array( $this, 'search_by_title_only' ), 10, 2 );
		}

		return $posts;
	}

	/**
	 * Callback related to posts_where filter in order to force search to post_title only
	 *
	 * @param $search
	 * @param $wp_query
	 *
	 * @return array|string
	 */
	public function search_by_title_only( $search, $wp_query ) {
		if ( ! empty( $search ) ) {
			global $wpdb;

			$search = array();

			if ( ! empty( $wp_query->query_vars['search_terms'] ) ) {
				$query = $wp_query->query_vars;
				$char = ! empty( $query['exact'] ) ? '' : '%';

				foreach ( ( array ) $query['search_terms'] as $term ) {
					$search[] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $char . cred_wrap_esc_like( $term ) . $char );
				}

				if ( ! is_user_logged_in() ) {
					$search[] = "{$wpdb->posts}.post_password = ''";
				}
			}

			if ( ! empty( $wp_query->query_vars['post_status'] ) ) {
				$search[] = "{$wpdb->posts}.post_status IN ( " . Toolset_Utils::prepare_mysql_in( $wp_query->query_vars['post_status'], '%s' ) . " )";
			}

			if ( ! empty( $wp_query->query_vars['post_type'] ) ) {
				$search[] = $wpdb->prepare( "{$wpdb->posts}.post_type = %s", $wp_query->query_vars['post_type'] );
			}

			// Do not override filters by author!
			if ( ! empty( $wp_query->query_vars['author'] ) ) {
				$search[] = $wpdb->prepare( "{$wpdb->posts}.post_author IN (%d)", $wp_query->query_vars['author'] );
			}

			$search = ' AND ' . implode( ' AND ', $search );
		}

		return $search;
	}
}
