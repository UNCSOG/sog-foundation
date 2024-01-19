<?php

namespace ToolsetCommonEs\Utils;


/**
 * Factory for WP_Query, to be used in dependency injection.
 */
class WpQueryFactory {

	/**
	 * @param string|array $args Arguments for the WP_Query.
	 *
	 * @return \WP_Query
	 */
	public function create( $args = '') {
		return new \WP_Query( $args );
	}

}
