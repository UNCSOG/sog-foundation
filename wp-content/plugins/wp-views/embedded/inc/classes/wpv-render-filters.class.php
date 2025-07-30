<?php

use OTGS\Toolset\Views\Models\Shortcode;

/**
* WPV_Frontend_Render_Filters
*
* Pre-process Views shortcodes in several scenarios.
*
* This helper class provides a single callback to parse Views shortcodes in a fixed order:
* 	- Resolve wpv-for-each shortcodes along.
* 	- Resolve wpv-if shortcodes (see wpv-condition.php).
* 	- Resolve shortcodes used as HTML attributes.
* Note that this same calbacks in the same order are applied in the the_content, in the wpv_filter_wpv_the_content_suppressed and in the wpv-pre-do-shortcode filters.
* Also note that they are executed early, priority 5, to keep compatibility with third parties doing the same at 7.
*
* @since 1.9.1
* @deprecated 3.3.0 See \OTGS\Toolset\Views\Controller\Shortcode\Resolution.
*/
class WPV_Frontend_Render_Filters {

	/**
	 * @deprecated 3.3.0
	 */
	static function on_load() {
	}

	/**
	 * Register the \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME filter callback
	 * to process inner, nested and all te other shortcodes goodies provided by this class.
	 *
	 * Note that this shared filter will become th replacement for wpv_filter_wpv_the_content_suppressed.
	 *
	 * @since 2.7
	 * @deprecated 3.3.0
	 */
	public static function register_shared_formatting_filter() {
	}

	/**
	 * Preprocess shortcodes performing the following actions:
	 * - adjust alternative syntax.
	 * - resolve formatting shortodes.
	 * - resolve foreach shortcodes.
	 * - resolve shortcodes in shortcodes.
	 * - resolve conditional shortcodes, including legacy.
	 * - resolve shortcodes as HTML attributes.
	 *
	 * Keep this as a static method, because it is used by some user editors in Toolset Common.
	 *
	 * @param string $content
	 * @return string
	 * @deprecated 3.3.0
	 */
	static function pre_process_shortcodes( $content ) {
		// Gutenberg, in particular, really loves calling this a lot with empty content, so this check saves a lot of
		// method calls that would do nothing.
		if ( empty( $content ) ) {
			return $content;
		}

		$dic = apply_filters( 'toolset_dic', false );

		if ( false === $dic ) {
			return $content;
		}

		$resolver = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolution' );
		return $resolver->pre_process_shortcodes( $content );
	}

	/**
	 * Preprocess shortcodes performing the following actions:
	 * - adjust alternative syntax.
	 * - resolve formatting shortodes.
	 * - resolve foreach shortcodes.
	 * - resolve shortcodes in shortcodes.
	 * - resolve conditional shortcodes, including legacy.
	 * - resolve shortcodes as HTML attributes.
	 *
	 * Keep this as a static method, because it might be used by some third party.
	 *
	 * @param string $content
	 * @return string
	 * @deprecated 3.3.0
	 */
	static function wpv_pre_do_shortcode( $content ) {
		// Gutenberg, in particular, really loves calling this a lot with empty content, so this check saves a lot of
		// method calls that would do nothing.
		if ( empty( $content ) ) {
			return $content;
		}

		$dic = apply_filters( 'toolset_dic', false );

		if ( false === $dic ) {
			return $content;
		}

		$resolver = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolution' );
		return $resolver->pre_process_shortcodes( $content );
	}
}

function wpv_shortcode_parse_condition_atts( $text ) {
	$atts = array();
	$pattern = '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
	$text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
	if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
		foreach ($match as $m) {
			if (!empty($m[1]))
				$atts[strtolower($m[1])] = stripcslashes($m[2]);
			elseif (!empty($m[3]))
				$atts[strtolower($m[3])] = stripcslashes($m[4]);
			elseif (!empty($m[5]))
				$atts[strtolower($m[5])] = stripcslashes($m[6]);
			elseif (isset($m[7]) && strlen($m[7]))
				$atts[] = stripcslashes($m[7]);
			elseif (isset($m[8]))
				$atts[] = stripcslashes($m[8]);
		}
		// Reject any unclosed HTML elements to help protect plugins.
		foreach( $atts as $key => $value ) {
			if (
				$key != 'evaluate'
				&& $key != 'if'
			) {
				if ( false !== strpos( $value, '<' ) ) {
					if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
						$value = '';
					}
				}

			}
			$atts[ $key ] = $value;
		}
	} else {
		$atts = ltrim($text);
	}
	return $atts;
}
