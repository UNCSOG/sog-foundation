<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

/**
 * Shotcode resolver controller: formatting shortcodes.
 *
 * @since 3.3.0
 */
class Formatting implements IResolver {

	const SLUG = 'formatting';

	/**
	 * Apply resolver.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	public function apply_resolver( $content ) {
		if ( false === strpos( $content, '[wpv-noautop' ) ) {
			return $content;
		}

		global $shortcode_tags;
		// Back up current registered shortcodes and clear them all out.
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		add_shortcode( 'wpv-noautop', array( 'WPV_Formatting_Embedded', 'wpv_shortcode_wpv_noautop' ) );

		$expression = '/\\[wpv-noautop((?!\\[wpv-noautop).)*\\[\\/wpv-noautop\\]/isU';
		$counts = preg_match_all( $expression, $content, $matches );

		while ( $counts ) {
			foreach ( $matches[0] as $match ) {
				$shortcode = do_shortcode( $match );
				$content = str_replace( $match, $shortcode, $content );
			}
			$counts = preg_match_all( $expression, $content, $matches );
		}

		// @codingStandardsIgnoreLine
		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}

}
