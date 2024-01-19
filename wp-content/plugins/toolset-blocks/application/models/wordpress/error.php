<?php

namespace OTGS\Toolset\Views\Model\Wordpress;

/**
 * Wrapper for WordPress WP_Error class interaction.
 *
 * @since 2.8.1
 */
class Error {

	/**
	 * Generate a \WP_Error instance
	 *
	 * @param string $code
	 * @param string $message
	 * @param string $data
	 * @return \WP_Error
	 */
	public function get_error( $code = '', $message = '', $data = '' ) {
		return new \WP_Error( $code, $message, $data );
	}

}
