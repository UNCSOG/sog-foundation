<?php

namespace ToolsetCommonEs\Library\WordPress;

class Rest {
	/**
	 * @param string $namespace
	 * @param string $route
	 * @param array $args
	 * @param bool $override
	 *
	 * @return bool
	 * @see https://developer.wordpress.org/reference/functions/register_rest_route/
	 */
	public function register_rest_route( $namespace, $route, $args = array(), $override = false ) {
		return register_rest_route( $namespace, $route, $args, $override );
	}

	/**
	 * @param string|int $action
	 *
	 * @return string
	 * @see https://developer.wordpress.org/reference/functions/wp_create_nonce/
	 */
	public function wp_create_nonce( $action = -1 ) {
		return wp_create_nonce( $action );
	}
}


