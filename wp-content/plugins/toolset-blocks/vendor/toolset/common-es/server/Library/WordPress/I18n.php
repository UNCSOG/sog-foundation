<?php

namespace ToolsetCommonEs\Library\WordPress;

/**
 * Class I18n
 * NOTE: every function here has a starting i as starting with __ is reserved for PHP core.
 *       So __() becomes i__() and for consistent also _e() becomes i_e().
 * @package ToolsetCommonEs\Library\WordPress
 */
class I18n {
	/**
	 * @param string $text
	 * @param string $domain
	 *
	 * @return string
	 * @see https://developer.wordpress.org/reference/functions/__/
	 */
	public function i__( $text, $domain = 'default' ) {
		return __( $text, $domain );
	}

	/**
	 * @param string $text
	 * @param string $domain
	 *
	 * @return string
	 * @see https://developer.wordpress.org/reference/functions/_e/
	 */
	public function i_e( $text, $domain = 'default' ) {
		return __( $text, $domain );
	}
}


