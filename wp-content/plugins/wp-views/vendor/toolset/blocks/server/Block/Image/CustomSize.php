<?php

namespace ToolsetBlocks\Block\Image;

use ToolsetBlocks\Block\Image\Shortcode\IShortcode;
use ToolsetCommonEs\Utils\ImageResize;

/**
 * Class CustomSize
 *
 * @package ToolsetBlocks\Block\Image
 */
class CustomSize {

	/** @var ImageResize */
	private $image_resize;

	/** @var IShortcode[] */
	private $shortcodes = [];

	/**
	 * Crop constructor.
	 *
	 * @param ImageResize $image_resize
	 */
	public function __construct( ImageResize $image_resize ) {
		$this->image_resize = $image_resize;
	}

	/**
	 * Add a supported shortcode for cropping.
	 *
	 * @param IShortcode $shortcode
	 */
	public function add_shortcode( IShortcode $shortcode ) {
		$this->shortcodes[] = $shortcode;
	}

	/**
	 * Will check all added shortcodes for cropping images.
	 *
	 * @filter do_shortcode_tag (see ./server/routes.php)
	 * @param string $output
	 * @param string $tag
	 * @param mixed[] $attr
	 *
	 * @return mixed
	 */
	public function resize_by_shortcodes( $output, $tag, $attr ) {
		foreach ( $this->shortcodes as $shortcode ) {
			$output_resized = $this->resize_by_shortcode( $shortcode, $output, $tag, $attr );

			if ( $output_resized !== $output ) {
				// Return the resized image.
				return $output_resized;
			}
		}

		// No image found, which should be resized.
		return $output;
	}

	/**
	 * @param IShortcode $shortcode
	 * @param string $output
	 * @param string $tag
	 * @param mixed[] $attr
	 *
	 * @return mixed
	 */
	private function resize_by_shortcode( IShortcode $shortcode, $output, $tag, $attr ) {
		if ( $tag !== $shortcode->get_tag_name() ) {
			// The tag does not match the registered $shortcodes tag name. Return original output.
			return $output;
		}

		if (
			! array_key_exists( $shortcode->get_width_attribute_key(), $attr ) ||
			! array_key_exists( $shortcode->get_height_attribute_key(), $attr )
		) {
			// Missing required attribute or crop is not wanted. Return original output.
			return $output;
		}

		$do_crop = array_key_exists( $shortcode->get_crop_attribute_key(), $attr ) ?
			$attr[ $shortcode->get_crop_attribute_key() ] :
			false;

		// Get resized url. getResizedImageUrlByOriginalUrl() will create the resized image if it does not exist.
		$image_resized = $this->image_resize->get_resized_image_by_original_url(
			$output,
			$attr[ $shortcode->get_width_attribute_key() ],
			$attr[ $shortcode->get_height_attribute_key() ],
			$do_crop
		);

		if ( ! $image_resized || $image_resized instanceof \WP_Error ) {
			// For some reason the image could not be resized. Return original output.
			return $output;
		}

		// Return the url of the resized image.
		return $image_resized['url'];
	}
}
