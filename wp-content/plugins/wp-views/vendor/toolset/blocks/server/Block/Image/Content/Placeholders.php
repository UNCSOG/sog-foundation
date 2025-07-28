<?php

namespace ToolsetBlocks\Block\Image\Content;

use ToolsetBlocks\Block\Image\MediaLibrary;
use ToolsetCommonEs\Library\WordPress\Actions;
use ToolsetCommonEs\Library\WordPress\Image;

/**
 * @package ToolsetBlocks\Block\Image\Shortcode
 */
class Placeholders {
	const PLACEHOLDER_ALT_TEXT = '%%tb-image-alt-text%%';
	const PLACEHOLDER_ID = '%%tb-image-id%%';
	const PLACEHOLDER_URL = '%%tb-image-url%%';
	const PLACEHOLDER_FILENAME = '%%tb-image-filename%%';
	const PLACEHOLDER_ATTACHMENT_URL = '%%tb-image-attachment-url%%';
	const PLACEHOLDER_WP_IMAGE_CLASS = '%%tb-image-wp-image-class%%';
	const PLACEHOLDER_CAPTION = '%%tb-image-caption%%';

	/** @var Image */
	private $wp_image;

	/** @var MediaLibrary */
	private $media_library;

	/**
	 * AltText constructor.
	 *
	 * @param Actions $wp_actions
	 * @param Image $wp_image
	 * @param MediaLibrary $media_library
	 */
	public function __construct( Actions $wp_actions, Image $wp_image, MediaLibrary $media_library ) {
		$this->wp_image = $wp_image;
		$this->media_library = $media_library;

		// Register callback to replace placeholders on the_content filter. Priority 9 is because
		// %%tb-image-wp-image-class%% needs to be replaced with wp-image- class which will on priority 10 trigger
		// srcset adding to images.
		$wp_actions->add_filter( 'the_content', [ $this, 'replace_placeholders' ], 9 );
		// Views / WPA content
		$wp_actions->add_filter( 'wpv_filter_wpv_view_shortcode_output', [ $this, 'replace_placeholders' ] );
		$wp_actions->add_filter( 'toolset_the_content_wpa', [ $this, 'replace_placeholders' ], PHP_INT_MAX - 1 );
		$wp_actions->add_filter( 'wpv_filter_wpv_widget_output', [ $this, 'replace_placeholders' ], PHP_INT_MAX - 1 );
		$wp_actions->add_filter( 'wpv_filter_view_output', [ $this, 'replace_placeholders' ], 9 );

		// Remove pointless (and potentially harmful) srcset for SVG.
		$wp_actions->add_filter( 'wp_calculate_image_sizes', [ $this, 'remove_svg_srcset' ], 10, 3 );
	}

	/**
	 * @param string $sizes
	 * @param array $size
	 * @param string $image_src
	 *
	 * @return string
	 */
	public function remove_svg_srcset( $sizes, $size, $image_src = null ) {
		$explode = explode( '.', $image_src );
		$image_type = end( $explode );

		if ( 'svg' === $image_type ) {
			$sizes = '';
		}

		return $sizes;
	}

	/**
	 * This replaces the defined placeholders inside an image tag.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function replace_placeholders( $content ) {
		if (
			strpos( $content, '%%' ) === false // No placeholder at all.
			|| (
				defined( 'WPV_BLOCK_UPDATE_ITEM' ) // Updating View in backend
				&& WPV_BLOCK_UPDATE_ITEM
				&& strpos( $content, self::PLACEHOLDER_CAPTION ) === false
			)
		) {
			// Abort when there's no placeholder at all
			// OR when applying on backend, except when caption is used.
			return $content;
		}

		$replaced_content = preg_replace_callback(
			'/\<figure.*?class=".*?tb-image.*?".*?src="(.*?)".*?>.*?<\/figure>/',
			function ( $matches ) {
				$img_html = $matches[0];

				if ( count( $matches ) === 1 || empty( $matches[1] ) ) {
					// No image src. Remove the complete figure.
					return '';
				}

				if ( strpos( $img_html, '%%' ) === false ) {
					// No placeholders.
					return $img_html;
				}

				$src = preg_replace( '/(\-[0-9]{1,10}x[0-9]{1,10})(\.[A-Za-z]{1,5}$)/', '$2', $matches[1] );

				// Replace image url.
				$img_html = str_replace( self::PLACEHOLDER_URL, $src, $img_html );

				// Replace filename.
				$img_html = str_replace( self::PLACEHOLDER_FILENAME, basename( $src ), $img_html );

				if ( strpos( $img_html, '%%' ) === false ) {
					// No further "heavy" placeholders, which require to call the database.
					return $img_html;
				}

				$caption = '';
				$alt_text = '';
				$id = $this->wp_image->attachment_id_by_guid( $src );
				$wp_image_class = '';
				$attachment_url = '';

				if ( $id ) {
					$wp_image_class = 'wp-image-' . $id;
					if ( strpos( $img_html, self::PLACEHOLDER_CAPTION ) !== false ) {
						$caption = $this->media_library->caption_by_id( $id );
					}

					if ( strpos( $img_html, self::PLACEHOLDER_ALT_TEXT ) !== false ) {
						$alt_text = $this->media_library->alt_text_by_id( $id );
					}

					if ( strpos( $img_html, self::PLACEHOLDER_ATTACHMENT_URL ) !== false ) {
						$attachment_url = $this->wp_image->get_attachment_link( $id );
					}
				}

				// Replace caption placeholder with actual caption.
				$img_html = str_replace( self::PLACEHOLDER_CAPTION, $caption, $img_html );

				// Replace alt text placeholder with actual alt text.
				$img_html = str_replace( self::PLACEHOLDER_ALT_TEXT, $alt_text, $img_html );

				// Replace id placeholder with actual id.
				$img_html = str_replace( self::PLACEHOLDER_ID, $id ? $id : '', $img_html );

				// Replace wp image class.
				$img_html = str_replace( self::PLACEHOLDER_WP_IMAGE_CLASS, $wp_image_class, $img_html );

				// Replace image url.
				$img_html = str_replace( self::PLACEHOLDER_ATTACHMENT_URL, $attachment_url, $img_html );

				return $img_html;
			},
			$content
		);

		// In case preg_replace_callback failed and returned null, just return unchanged content. (Fixes the problem
		// with WP Gallery block having lots of images.)
		if ( null === $replaced_content ) {
			return $content;
		}

		return $replaced_content;
	}
}
