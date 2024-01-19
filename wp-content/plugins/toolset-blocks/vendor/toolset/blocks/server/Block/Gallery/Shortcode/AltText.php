<?php

namespace ToolsetBlocks\Block\Gallery\Shortcode;

use ToolsetBlocks\Block\Image\MediaLibrary;
use ToolsetCommonEs\Library\WordPress\Shortcode;

/**
 * Handles [tb-alttext] shortcode
 *
 * Shortcode: tb-alttext
 * Description: change the alt text, from an url to the real alt
 *
 * Example:
 * [tb-alttext][types field='r-image' size='full' alt='[types field="r-image" output="raw"][/types]' output="normal'][/types][/tb-alttext]
 * Why? because it was a mess of quotes, so it is the best solution
 *
 * @todo Consider using alt text placeholder like on the image block /server/Block/Image/Content/Placeholder.
 *
 * @since 1.2
 */
class AltText {
	/** @var MediaLibrary */
	private $media_library;

	/** @var Shortcode */
	private $wp;

	/**
	 * AltText constructor.
	 *
	 * @param MediaLibrary $media_library
	 * @param Shortcode $wp
	 */
	public function __construct( MediaLibrary $media_library, Shortcode $wp ) {
		$this->media_library = $media_library;
		$this->wp = $wp;
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
		add_shortcode( 'tb-alttext', array( $this, 'alttext_shortcode_render' ) );
	}


	/**
	 * Returns the image tag with the resolved alt tag.
	 *
	 * @param mixed $attributes The shortcode attributes. Should be an array, but can't be trusted.
	 * @param mixed $content The complete image. Should be a string, but can't be trusted.
	 *
	 * @return string The img tag with the actual alt text.
	 */
	public function alttext_shortcode_render( $attributes, $content = '' ) {
		if ( ! is_string( $content ) || empty( $content ) ) {
			return '';
		}
		$image = $this->wp->do_shortcode( $content );

		preg_match( '#alt="([^"]+)"#', $image, $url );
		if ( ! isset( $url[1] ) ) {
			return '';
		}

		$alt_text = $this->media_library->alt_text_by_guid( $url[1] );
		return preg_replace( '#alt="([^"]+)"#', 'alt="' . $alt_text . '"', $image );
	}
}
