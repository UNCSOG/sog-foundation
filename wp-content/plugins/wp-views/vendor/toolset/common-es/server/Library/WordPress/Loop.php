<?php

namespace ToolsetCommonEs\Library\WordPress;


class Loop {
	/**
	 * Returns posts of the current page. This can be only one for a single post view or multiple for an archive.
	 *
	 * @return \WP_Post[]
	 */
	public function get_posts() {
		if( is_single() || is_page() || is_404() ) {
			global $post;
			return $post ? array( $post ) : array();
		}

		if( is_archive() || is_home() || is_search() ) {
			global $wp_query;
			return $wp_query->have_posts() ? $wp_query->get_posts() : array();
		}

		return array();
	}
}
