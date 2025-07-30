<?php

namespace ToolsetBlocks\Block;

/**
 * StickyLinks class.
 *
 * StickyLinks breaks Button block when URL is pointing to an existing domain link. A quick workaround is to replace the link by a shortcode to avoid it.
 * It is also a way to replace content from blocks in the URL
 *
 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/views-3344
 *
 * @package toolset-blocks
 */
class StickyLinks {
	/**
	 * Init functions
	 * - Replace Sticky Links mess
	 */
	public function initialize() {
		if ( defined( 'WPML_STICKY_LINKS_VERSION' ) ) {
			add_filter( 'wp_insert_post', array( $this, 'fix_sticky_links_mess' ), 130, 2 );
			// add_filter( 'the_content', array( $this, 'replace_sticky_links_from_content' ), 1, 1 );
		}
	}

	/**
	 * Replaces urls modified by Sticky Links by the url stored in the attributes
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post.
	 */
	public function fix_sticky_links_mess( $post_id, $post ) {
		if ( has_block( 'toolset-blocks/button', $post ) ) {
			global $wpdb;
			// Can't use get_post because is cached and Sticky Links updates it using $wpdb :(
			$saved_post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $post_id ) );
			$content = $this->replace_sticky_links_from_content( $saved_post->post_content );
			if ( $content !== $post->post_content ) {
				// Sticky Links replace the links using any WP update post function :(
				$wpdb->update(
					$wpdb->posts,
					array( 'post_content' => $content ),
					array( 'ID' => $post_id )
				);
			}
		}
	}

	/**
	 * Replace button block href links in the content
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function replace_sticky_links_from_content( $content ) {
		$content = preg_replace_callback(
			'#<!-- wp:toolset-blocks/button[^>]*-->.*<!-- /wp:toolset-blocks/button -->#Usi',
			function( $match ) {
				// Uses dynamic sources.
				if ( preg_match( '#href="\[tb-dynamic#', $match[0] ) ) {
					return $match[0];
				}
				preg_match( '#"url":"([^"]+)"#', $match[0], $url );
				preg_match( '#href="([^"]+)"#', $match[0], $href );
				if ( $url && $href && $url[1] !== $href[1] ) {
					return str_replace( $url[0], '"url":"' . $href[1] . '"', $match[0] );
				}

				return $match[0];
			},
			$content
		);
		return $content;
	}
}
