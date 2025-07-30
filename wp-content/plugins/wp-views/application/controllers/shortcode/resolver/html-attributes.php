<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

/**
 * Shotcode resolver controller: generic parsing.
 *
 * @since 3.3.0
 */
class HtmlAttributes extends NestedBase implements IResolver {

	const SLUG = 'html_attributes';

	/**
	 * Apply resolver.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	public function apply_resolver( $content ) {
		$content = $this->resolve_shortcodes( $content );

		return $content;
	}

	/**
	 * Resolve generic shortcodes.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	private function resolve_shortcodes( $content ) {
		// Normalize entities.
		$trans = array(
			'&#91;' => '&#091;', // Encoded [.
			'&#93;' => '&#093;', // Encoded ].
		);
		$content = strtr( $content, $trans );

		$textarr = $this->html_split( $content );
		$inner_expressions = $this->get_inner_expressions();

		foreach ( $textarr as &$element ) {
			if ( $this->should_skip_html_node( $element ) ) {
				continue;
			}

			// Look for every valid shortcode inside the node, and expand it.
			foreach ( $inner_expressions as $shortcode ) {
				$counts = preg_match_all( $shortcode['regex'], $element, $matches );

				if ( $counts > 0 ) {
					foreach ( $matches[0] as $index => &$match ) {
						// We need to exclude wpv-post-body here.
						// Otherwise wpautop can be applied to it too soon.
						if ( strpos( $match, '[wpv-post-body' ) !== 0 ) {
							$string_to_replace = $match;

							// Execute shortcode content and replace.
							// @codeCoverageIgnoreStart
							if ( $shortcode['has_content'] ) {
								$inner_content = $matches[1][ $index ];
								if ( $inner_content ) {
									// Recursion.
									$new_inner_content = $this->resolve_shortcodes( $inner_content );
									$match = str_replace( $inner_content, $new_inner_content, $match );
								}
							}
							// @codeCoverageIgnoreEnd

							$filter_state = new \WPV_WP_filter_state( 'the_content' );
							$replacement = do_shortcode( $match );
							$filter_state->restore();
							$resolved_match = $replacement;
							$element = str_replace( $string_to_replace, $resolved_match, $element );
						}
					}
				}
			}
		}

		$content = implode( '', $textarr );

		return $content;
	}

	/**
	 * Get a regex-compatible list of shortcodes supported inside attributes.
	 *
	 * @return array
	 * @since 3.3.0
	 */
	private function get_inner_expressions() {
		$inner_expressions = array();

		// It is extremely important that Types shortcodes are registered before Views inner shortcodes.
		// Otherwise, Types shortcodes wil not be parsed properly.
		$inner_expressions[] = array(
			'regex' => '/\\[types.*?\\]\\[\\/types\\]/i',
			'has_content' => false,
		);
		$inner_expressions[] = array(
			'regex' => '/\\[types.*?\\](.*?)\\[\\/types\\]/i',
			'has_content' => true,
		);

		$views_shortcodes_regex = $this->get_inner_shortcodes_regex();
		$inner_expressions[] = array(
			'regex' => '/\\[(' . $views_shortcodes_regex . ').*?\\]/i',
			'has_content' => false,
		);

		$custom_inner_shortcodes = $this->get_custom_inner_shortcodes();
		if ( count( $custom_inner_shortcodes ) > 0 ) {
			foreach ( $custom_inner_shortcodes as $custom_inner_shortcode ) {
				$inner_expressions[] = array(
					'regex' => '/\\[' . $custom_inner_shortcode . '.*?\\](.*?)\\[\\/' . $custom_inner_shortcode . '\\]/is',
					'has_content' => true,
				);
			}
			$inner_expressions[] = array(
				'regex' => '/\\[(' . implode( '|', $custom_inner_shortcodes ) . ').*?\\]/i',
				'has_content' => false,
			);
		}

		return $inner_expressions;
	}

	/**
	 * Check whether a given HTML node needs to be processed for shortcodes.
	 *
	 * @param string $element
	 * @return bool
	 * @since 3.3.0
	 * @codeCoverageIgnore
	 */
	private function should_skip_html_node( $element ) {
		if (
			'' === $element
			|| '<' !== $element[0]
		) {
			// This element is not an HTML tag.
			return true;
		}

		$noopen = false === strpos( $element, '[' );
		$noclose = false === strpos( $element, ']' );
		if (
			$noopen
			|| $noclose
		) {
			// This element does not contain shortcodes.
			return true;
		}

		if (
			'<!--' === substr( $element, 0, 4 )
			|| '<![CDATA[' === substr( $element, 0, 9 )
		) {
			// This element is a comment or a CDATA piece.
			return true;
		}

		return false;
	}

	/**
	 * Separate HTML elements and comments from the text.
	 *
	 * Heavily inspired in wp_html_split.
	 *
	 * @param string $input The text which has to be formatted.
	 * @return array The formatted text.
	 * @since 3.3.0
	 */
	private function html_split( $input ) {
		$comments =
				'!'           // Start of comment, after the <.
			. '(?:'         // Unroll the loop: Consume everything until --> is found.
				. '-(?!->)' // Dash not followed by end of comment.
				. '[^\-]*+' // Consume non-dashes.
			. ')*+'         // Loop possessively.
			. '(?:-->)?';   // End of comment. If not found, match all input.

		$cdata =
				'!\[CDATA\['  // Start of comment, after the <.
			. '[^\]]*+'     // Consume non-].
			. '(?:'         // Unroll the loop: Consume everything until ]]> is found.
				. '](?!]>)' // One ] not followed by end of comment.
				. '[^\]]*+' // Consume non-].
			. ')*+'         // Loop possessively.
			. '(?:]]>)?';   // End of comment. If not found, match all input.

		$regex =
				'/('              // Capture the entire match.
				. '<'           // Find start of element.
				. '(?(?=!--)'   // Is this a comment?
					. $comments // Find end of comment.
				. '|'
					. '(?(?=!\[CDATA\[)' // Is this a comment?
						. $cdata // Find end of comment.
					. '|'
						. '[^>]*>?' // Find end of element. If not found, match all input.
					. ')'
				. ')'
			. ')/s';

		return preg_split( $regex, $input, -1, PREG_SPLIT_DELIM_CAPTURE );
	}

}
