<?php

namespace ToolsetCommonEs\Utils;

use ToolsetCommonEs\Library\ImageUpscale\Upscale;
use ToolsetCommonEs\Library\WordPress\Image as WPImage;

class ImageResize {
	/** @var WPImage */
	private $wp_image;

	/**
	 * It's not directly called as it hooks by itself to the 'image_resize_dimensions' filter and more is not needed.
	 * @var Upscale
	 */
	private $upscale;

	/**
	 * ImageResize constructor.
	 *
	 * @param WPImage $wp_image
	 * @param Upscale $upscale
	 */
	public function __construct( WPImage $wp_image, Upscale $upscale ) {
		$this->wp_image = $wp_image;
		$this->upscale = $upscale;
	}

	/**
	 * Get a resized image by the original url.
	 *
	 * @param string $original_url
	 * @param string|int $width
	 * @param string|int $height
	 * @param bool $crop
	 *
	 * @return array|false|\WP_Error
	 */
	public function get_resized_image_by_original_url( $original_url, $width, $height, $crop = false ) {
		$uploads = $this->wp_image->wp_upload_dir();

		// An image, which should be cropped to the size $attr[width] x $attr[height]
		$width = intval( $width );
		$height = intval( $height );
		$image_file = str_replace( $uploads['baseurl'], $uploads['basedir'], $original_url );
		$image_editor = $this->wp_image->wp_get_image_editor( $image_file );

		if( $this->wp_image->is_wp_error( $image_editor ) ) {
			// No image found.
			return $image_editor;
		}

		// Suffix (this is not by choice, it needs to match the way WP does the suffix).
		$original_image_sizes = $image_editor->get_size();

		// Add filter to allow upscaling.
		add_filter( 'image_resize_dimensions', array( $this->upscale, 'image_resize_dimensions' ), 10, 6 );

		$image_dimensions = $this->wp_image->image_resize_dimensions(
			$original_image_sizes['width'],
			$original_image_sizes['height'],
			$width,
			$height,
			$crop
		);

		$suffix_width = is_array( $image_dimensions ) && isset( $image_dimensions[4] ) ? $image_dimensions[4] : $width;
		$suffix_height = is_array( $image_dimensions ) && isset( $image_dimensions[5] ) ? $image_dimensions[5] : $height;

		$image_size_suffix = $suffix_width . 'x' . $suffix_height;

		// Get name with suffix.
		$image_file_sized = $image_editor->generate_filename( $image_size_suffix );

		// Check if the file already exists.
		if( ! file_exists( $image_file_sized ) ) {
			// Not existing, try to create resized image.
			$image_editor->resize( $width, $height, $crop );
			$image_editor->save( $image_file_sized );

			if( $this->wp_image->is_wp_error( $image_editor ) ) {
				// Some error on resizing the image.
				return $image_editor;
			}

			// Resizing done. Check AGAIN if the file now exists.
			if( ! file_exists( $image_file_sized ) ) {
				// Something went wrong with the resizing mechanism.
				return false;
			}
		}

		// Remove filter to allow upscaling (as the price for upscaling are other limitations).
		remove_filter( 'image_resize_dimensions', array( $this->upscale, 'image_resize_dimensions' ) );

		// Resized image already existed OR was successfully created. Return URL of sized image.
		$url = str_replace( $uploads['basedir'], $uploads['baseurl'], $image_file_sized );

		return [
			'url' => $url,
			'width' => $suffix_width,
			'height' => $suffix_height
		];
	}
}
