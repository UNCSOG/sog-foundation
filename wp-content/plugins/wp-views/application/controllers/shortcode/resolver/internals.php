<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

/**
 * Shortcode resolver controller: internal shortcodes.
 *
 * @since 3.3.0
 */
class Internals extends HtmlAttributes implements IResolver {

	const SLUG = 'internals';

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
	 * Resolve internal shortcodes.
	 *
	 * @param string $content
	 * @return string
	 * @since 3.3.0
	 */
	private function resolve_shortcodes( $content ) {
		// See \ToolsetBlocks\Block\Style\Block\FieldsAndText::filter_block_content().
		// Temporarily restore quotes after closing shortcodes, to manage shortcodes as attributes.
		// Note that ###SPACE### placeholder:
		// it will help us with retoring back only the "]'" groups that Blocks altered.
		$content = str_replace( ']&#8217;', ']###SPACE###\'', $content );
		// Search for outer shortcodes, to process their inner expressions.
		$matches = array();
		$counts = $this->find_outer_brackets( $content, $matches );

		// Iterate 0-level shortcode elements and resolve their internals, one by one.
		if ( $counts > 0 ) {
			$inner_expressions = $this->get_inner_expressions();

			foreach ( $matches as $match ) {
				foreach ( $inner_expressions as $inner_expression ) {
					$inner_counts = preg_match_all( $inner_expression, $match, $inner_matches );
					// Replace all 1-level inner shortcode matches.
					if ( $inner_counts > 0 ) {
						foreach ( $inner_matches[0] as &$inner_match ) {
							// Execute shortcode content and replace.

							// -----------------------------------
							// Not sure why we run here the HtmlAttributes resolver,
							// since we are matching just a shortcode,
							// and it should not include any HTML string, but maybe it does?
							// Let's keep this for the sake of backwards compatibility only!
							$resolved_match = parent::apply_resolver( $inner_match );
							// -----------------------------------

							$filter_state = new \WPV_WP_filter_state( 'the_content' );
							// Not sure whether we need to run again do_shortcode as it is run in wpv_preprocess_shortcodes_in_html_elements already?
							$resolved_match = do_shortcode( $resolved_match );
							// Escape quote characters as they should be wrapping those shortcodes too.
							$resolved_match = str_replace( '"', '&quot;', $resolved_match );
							$resolved_match = str_replace( "'", '&#039;', $resolved_match );
							$filter_state->restore();
							$content = str_replace( $inner_match, $resolved_match, $content );
							$match = str_replace( $inner_match, $resolved_match, $match );
						}
					}
				}
			}
		}

		// Restore quote entities.
		$content = str_replace( ']###SPACE###\'', ']&#8217;', $content );

		return $content;
	}

	/**
	 * Get a list of regex compatible expressions to catch.
	 *
	 * @return array
	 * @since 3.3.0
	 */
	private function get_inner_expressions() {
		$inner_expressions = array();

		// It is extremely important that Types shortcodes are registered before Views inner shortcodes.
		// Otherwise, Types shortcodes wil not be parsed properly.
		$inner_expressions[] = '/\\[types.*?\\].*?\\[\\/types\\]/i';

		$views_shortcodes_regex = $this->get_inner_shortcodes_regex();
		$inner_expressions[] = '/\\[(' . $views_shortcodes_regex . ').*?\\]/i';

		$custom_inner_shortcodes = $this->get_custom_inner_shortcodes();
		if ( count( $custom_inner_shortcodes ) > 0 ) {
			foreach ( $custom_inner_shortcodes as $custom_inner_shortcode ) {
				$inner_expressions[] = '/\\[' . $custom_inner_shortcode . '.*?\\].*?\\[\\/' . $custom_inner_shortcode . '\\]/i';
			}
			$inner_expressions[] = '/\\[(' . implode( '|', $custom_inner_shortcodes ) . ').*?\\]/i';
		}

		return $inner_expressions;
	}

	/**
	 * Find top-level shortcodes that contain other shortcodes as attribute values,
	 * and populate a list of their opening tag, to process those internal shortcodes.
	 *
	 * @param string $content The content to check.
	 * @param array $matches List of top-level shortcodes: full shortcode without brackets.
	 * @return int Number of top level shortcodes found.
	 * @since 3.3.0
	 */
	private function find_outer_brackets( $content, &$matches ) {
		$count = 0;

		$first = strpos( $content, '[' );
		if ( false !== $first ) {
			$length = strlen( $content );
			$brace_count = 0;
			$brace_start = -1;
			for ( $i = $first; $i < $length; $i++ ) {
				if ( '[' === $content[ $i ] ) {
					if ( 0 === $brace_count ) {
						$brace_start = $i + 1;
					}
					$brace_count++;
				}
				if ( ']' === $content[ $i ] ) {
					if ( $brace_count > 0 ) {
						$brace_count--;
						if ( 0 === $brace_count ) {
							$inner_content = substr( $content, $brace_start, $i - $brace_start );
							if (
								! empty( $inner_content )
								&& $this->has_shortcode_as_attribute_value( $inner_content )
								&& $this->is_unbracketed_shortcode( $inner_content )
							) {
								$matches[] = $inner_content;
								$count++;
							}
						}
					}
				}
			}
		}

		return $count;
	}

	/**
	 * Make sure that a given content is indeed a shortcode without brackets:
	 * - Can not start with a closing tag delimiter.
	 * - Can not start with a bracket for another inner shortcode.
	 * - Must start with a valid shortcode tag.
	 *
	 * @param string $unbracketed_shortcode
	 * @return bool
	 */
	private function is_unbracketed_shortcode( $unbracketed_shortcode ) {
		// Is this a closing shortcode perhaps?
		if ( 0 === strpos( $unbracketed_shortcode, '/' ) ) {
			return false;
		}

		// Is this a doube brackets structure perhaps?
		if ( 0 === strpos( $unbracketed_shortcode, '[' ) ) {
			return false;
		}

		$shortcode_pieces = explode( ' ', $unbracketed_shortcode );

		// Does this start with a valid shortcode tag?
		return (
			isset( $shortcode_pieces[0] )
			&& ! empty( $shortcode_pieces[0] )
			&& shortcode_exists( $shortcode_pieces[0] )
		);
	}

	/**
	 * Make sure that a content which presumes to be a shortcode without brackets
	 * does contain an inner shortcode.
	 *
	 * @param string $unbracketed_shortcode
	 * @return bool
	 */
	private function has_shortcode_as_attribute_value( $unbracketed_shortcode ) {
		$has_inner_shortcode = strpos( $unbracketed_shortcode, '[' );

		return ( false !== $has_inner_shortcode );
	}

}
