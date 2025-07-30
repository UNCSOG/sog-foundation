<?php

namespace Toolset\DynamicSources\Sources;

use WP_Term;

/**
 * Source for offering the post's taxonomies as dynamic content.
 *
 * Note that this source is not usually offered on blocks:
 * it is mainly used only on the Single Field block as to offer taxonomy options.
 *
 * @package toolset-dynamic-sources
 */
class PostTaxonomiesRich extends PostTaxonomies {
	const NAME = 'post-taxonomies-rich';

	const HAS_FIELDS = true;

	const FORMAT_NAME = 'name';
	const FORMAT_LINK = 'link';
	const FORMAT_URL = 'url';
	const FORMAT_DESCRIPTION = 'description';
	const FORMAT_SLUG = 'slug';
	const FORMAT_COUNT = 'count';

	const SHOW_NAME = 'name';
	const SHOW_DESCRIPTION = 'description';
	const SHOW_SLUG = 'slug';
	const SHOW_COUNT = 'count';

	const ORDER_DESC = 'desc';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Taxonomies Rich', 'wpv-views' );
	}

	/**
	 * @param WP_Term $term
	 * @param string $text
	 *
	 * @return string
	 */
	private function make_tag_link( WP_Term $term, $text ) {
		return "<a href='" . esc_attr( get_tag_link( $term ) ) . "'>" . esc_html( $text ) . "</a>";
	}

	/**
	 * Gets the content of the Source.
	 *
	 * Note that the outcome of the source can be:
	 * - a separator-separated list of term data (name, slug, count, link, etc).
	 * - an array of terms.
	 *
	 * The array of terms is used by the editor to offer and preview options for the source;
	 * on frontend, attributes will be used to produce a proper string as outcome.
	 *
	 * @param null|string $taxonomy
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 * @return array|string The content of the Source.
	 */
	public function get_content( $taxonomy = null, $attributes = null ) {
		$result = [];
		$can_implode = true;

		$terms = get_the_terms( get_the_ID(), $taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $attributes && array_key_exists( 'format', $attributes ) ) {
					switch ( $attributes['format'] ) {
						case self::FORMAT_LINK:
							switch ( $attributes['show'] ) {
								case self::SHOW_NAME:
									$result[] = $this->make_tag_link( $term, $term->name );
								break;
								case self::SHOW_DESCRIPTION:
									$result[] = $this->make_tag_link( $term, $term->description );
									break;
								case self::SHOW_SLUG:
									$result[] = $this->make_tag_link( $term, $term->slug );
									break;
								case self::SHOW_COUNT:
									$result[] = $this->make_tag_link( $term, strval( $term->count ) );
									break;
							}
							break;
						case self::FORMAT_URL:
							$result[] = get_tag_link( $term );
							breaK;
						case self::FORMAT_COUNT:
							$result[] = $term->count;
							breaK;
						case self::FORMAT_SLUG:
							$result[] = $term->slug;
							break;
						case self::FORMAT_DESCRIPTION:
							$result[] = $term->description;
							break;
						case self::FORMAT_NAME:
						default:
							$result[] = $term->name;
					}
				} else {
					$result[] = $term;
					$can_implode = false;
				}
			}
		}

		if ( $attributes && array_key_exists( 'order', $attributes ) && self::ORDER_DESC === $attributes['order'] ) {
			$result = array_reverse( $result );
		}

		if ( isset( $attributes[ 'separator' ] ) && is_array( $result ) && $can_implode ) {
			$result = implode( $attributes[ 'separator' ], $result );
		}

		return $result;
	}
}
