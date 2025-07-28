<?php

namespace Toolset\DynamicSources\InlineField;

/**
 * Server side part for Inline Field rich text format.
 */
class InlineField {
	/**
	 * Replace the special span from our Inline Field rich text format with an appropriate DS shortcode.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function replace_span_with_shortcode( $content ) {
		return preg_replace_callback(
			'/<span\s+((?:data-\w+=[\'"][^\'"]*[\'"]\s+)+)class=[\'"][^\'"]*tb-inline-field[^\'"]*[\'"]\s*>(.*?)<\/span>/is',
			function ( $matches ) {
				return '[tb-dynamic ' . str_replace( 'data-', '', $matches[1] ) . 'force-string="first"]';
			},
			$content
		);
	}
}
