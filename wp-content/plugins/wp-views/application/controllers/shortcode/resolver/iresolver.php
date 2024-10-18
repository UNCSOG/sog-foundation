<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

/**
 * Shotcode resolver interface.
 *
 * @since 3.3.0
 */
interface IResolver {

	/**
	 * Apply resolver.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	public function apply_resolver( $content );

}
