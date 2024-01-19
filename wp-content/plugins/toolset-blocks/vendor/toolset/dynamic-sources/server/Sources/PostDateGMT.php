<?php

namespace Toolset\DynamicSources\Sources;

/**
 * Source for offering the post's creation date in GMT as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class PostDateGMT extends DateSource {
	const NAME = 'post-date-gmt';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Date GMT', 'wpv-views' );
	}

	/**
	 * Gets the content of the Source.
	 *
	 * Note that drafts might get an empty GMT post date for unrelated reasons.
	 * @see https://core.trac.wordpress.org/ticket/38883
	 * @see https://core.trac.wordpress.org/changeset/40108
	 *
	 * @param null|string $field
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 * @return string The content of the Source.
	 */
	public function get_content( $field = null, $attributes = null ) {
		global $post;

		$post_date_gmt = ( '0000-00-00 00:00:00' === $post->post_date_gmt )
			? date( 'Y-m-d H:i:s', strtotime( $post->post_date ) - ( get_option( 'gmt_offset' ) * 3600 ) )
			: $post->post_date_gmt;

		return wp_kses_post( $this->maybe_formatted( $attributes, $post_date_gmt ) );
	}
}
