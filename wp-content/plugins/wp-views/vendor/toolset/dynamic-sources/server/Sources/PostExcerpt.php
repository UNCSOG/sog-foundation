<?php

namespace Toolset\DynamicSources\Sources;

use Toolset\DynamicSources\DynamicSources;

/**
 * Source for offering the post's excerpt as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class PostExcerpt extends AbstractSource {
	const NAME = 'post-excerpt';

	const DEFAULT_EXCERPT_LENGTH = 55;

	const EXCERPT_ELLIPSIS = '...';

	const EXCERPT_COUNT_BY_WORDS = 'word';

	const EXCERPT_COUNT_BY_CHARS = 'char';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Excerpt', 'wpv-views' );
	}

	/**
	 * Gets the Source group.
	 *
	 * @return string
	 */
	public function get_group() {
		return DynamicSources::POST_GROUP;
	}

	/**
	 * Gets the Source categories, i.e. the type of content this Source can offer.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( DynamicSources::TEXT_CATEGORY );
	}

	/**
	 * Gets the excerpt field, or post content as fallback.
	 *
	 * @param null|string $field
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 * @return string The content of the Source.
	 */
	public function get_content( $field = null, $attributes = null ) {
		$post = get_post();

		if ( ! $post ) {
			return '';
		}

		if ( $post->post_excerpt ) {
			$content = $post->post_excerpt;
		} else {
			$content = $post->post_content;
		}

		$processed_content = wp_strip_all_tags( $content );

		$excerpt_more = ! empty( $attributes['renderellipsis'] ) ? $attributes['ellipsistext'] : self::EXCERPT_ELLIPSIS;
		$count_by = ! empty( $attributes['countby'] ) ? $attributes['countby'] : self::EXCERPT_COUNT_BY_WORDS;
		$length = ! empty( $attributes['length'] ) ? $attributes['length'] : apply_filters( 'excerpt_length', self::DEFAULT_EXCERPT_LENGTH );

		if ( self::EXCERPT_COUNT_BY_WORDS === $count_by ) {
			$processed_content = wp_trim_words( $processed_content, $length, $excerpt_more );
		} elseif ( self::EXCERPT_COUNT_BY_CHARS === $count_by ) {
			$processed_content = wp_html_excerpt( $processed_content, $length, $excerpt_more );
		}

		return $processed_content;
	}
}
