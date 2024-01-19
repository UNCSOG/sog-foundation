<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

use Toolset_Shortcode_Transformer;

/**
 * Shotcode resolver controller: alternative syntax.
 *
 * @since 3.3.0
 */
class AlternativeSyntax implements IResolver {

	const SLUG = 'alternative_syntax';

	/**
	 * Apply resolver.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	public function apply_resolver( $content ) {
		if ( false === Toolset_Shortcode_Transformer::has_non_standard_syntax( $content ) ) {
			return $content;
		}

		$content = apply_filters( 'toolset_transform_shortcode_format', $content );

		return $content;
	}

}
