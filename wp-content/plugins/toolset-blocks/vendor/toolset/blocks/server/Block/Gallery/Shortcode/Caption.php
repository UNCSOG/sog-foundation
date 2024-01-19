<?php

namespace ToolsetBlocks\Block\Gallery\Shortcode;

use ToolsetBlocks\Block\Image\MediaLibrary;

/**
 * Handles [tb-caption] shortcode
 *
 * Shortcode: tb-caption
 * Description: gets the caption from an url
 * Attributes:
 *   - url: URL of the image
 *
 * @link https://toolset.com/forums/topic/displaying-caption-from-media-post/
 * @since 1.2
 */
class Caption {

	/** @var MediaLibrary */
	private $media_library;

	/**
	 * Caption constructor.
	 *
	 * @param MediaLibrary $media_library
	 */
	public function __construct( MediaLibrary $media_library ) {
		$this->media_library = $media_library;
	}

	/**
	 * Initializes the class
	 */
	public function initialize() {
		$this->add_shortcode();
	}

	/**
	 * Adds the shortcode
	 */
	private function add_shortcode() {
		add_shortcode( 'tb-caption', array( $this, 'caption_shortcode_render' ) );
	}


	/**
	 * Renders the shortcode.
	 *
	 * @param mixed $attributes Should be an array, but can't be trusted.
	 *
	 * @return string Resolves the shortcode to the actual caption.
	 */
	public function caption_shortcode_render( $attributes ) {
		if ( ! is_array( $attributes ) || ! array_key_exists( 'url', $attributes ) ) {
			return '';
		}

		return $this->media_library->caption_by_guid( $attributes['url'] );
	}
}
