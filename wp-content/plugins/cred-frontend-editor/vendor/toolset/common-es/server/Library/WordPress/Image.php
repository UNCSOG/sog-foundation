<?php


namespace ToolsetCommonEs\Library\WordPress;

use OTGS\Toolset\Common\Utils\Attachments;

/**
 * Class Image
 *
 * No need to comment wp 1:1 aliases:
 * phpcs:disable Squiz.Commenting.FunctionComment.Missing
 */
class Image {

	/** @var Attachments */
	private $tc_attachments;

	/**
	 * @param Attachments $tc_attachments
	 */
	public function __construct( Attachments $tc_attachments ) {
		$this->tc_attachments = $tc_attachments;
	}

	/**
	 * Alias wp_get_image_editor( $image )
	 *
	 * @param string $image
	 *
	 * @return \WP_Error|\WP_Image_Editor
	 */
	public function wp_get_image_editor( $image ) {
		return wp_get_image_editor( $image );
	}

	public function wp_upload_dir( $time = null, $create_dir = true, $refresh_cache = false ) {
		return wp_upload_dir( $time, $create_dir, $refresh_cache );
	}

	public function wp_is_writable( $path ) {
		return wp_is_writable( $path );
	}

	/**
	 * Retrieves calculated resize dimensions for use in WP_Image_Editor.
	 *
	 * Calculates dimensions and coordinates for a resized image that fits
	 * within a specified width and height.
	 *
	 * Cropping behavior is dependent on the value of $crop:
	 * 1. If false (default), images will not be cropped.
	 * 2. If an array in the form of array( x_crop_position, y_crop_position ):
	 *    - x_crop_position accepts 'left' 'center', or 'right'.
	 *    - y_crop_position accepts 'top', 'center', or 'bottom'.
	 *    Images will be cropped to the specified dimensions within the defined crop area.
	 * 3. If true, images will be cropped to the specified dimensions using center positions.
	 *
	 * @since 2.5.0
	 *
	 * @param int        $orig_w Original width in pixels.
	 * @param int        $orig_h Original height in pixels.
	 * @param int        $dest_w New width in pixels.
	 * @param int        $dest_h New height in pixels.
	 * @param bool|array $crop   Optional. Whether to crop image to specified width and height or resize.
	 *                           An array can specify positioning of the crop area. Default false.
	 * @return false|array False on failure. Returned array matches parameters for `imagecopyresampled()`.
	 */
	public function image_resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, $crop = false ) {
		return image_resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, $crop );
	}

	/**
	 * Alias is_wp_error( $thing );
	 *
	 * Not really a image related, but prefer to have it duplicated than having to inject a class just for this.
	 *
	 * @param mixed $thing
	 *
	 * @return bool
	 */
	public function is_wp_error( $thing ) {
		return is_wp_error( $thing );
	}

	/**
	 * Custom function to get the alt text of an media library image.
	 *
	 * @param int $image_id
	 *
	 * @return string
	 */
	public function get_alt_text( $image_id ) {
		$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		if ( empty( $image_alt ) ) {
			return '';
		}

		return $image_alt;
	}

	public function attachment_url_to_postid( $url ) {
		return attachment_url_to_postid( $url );
	}

	public function wp_prepare_attachment_for_js( $attachment ) {
		return wp_prepare_attachment_for_js( $attachment );
	}

	public function wp_get_attachment_metadata( $id, $unfiltered = false ) {
		return wp_get_attachment_metadata( $id, $unfiltered );
	}

	public function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail', $icon = false ) {
		return wp_get_attachment_image_src( $attachment_id, $size, $icon );
	}

	public function wp_get_attachment_url( $id ) {
		return wp_get_attachment_url( $id );
	}

	public function get_attachment_link( $id ) {
		return get_attachment_link( $id );
	}

	public function wp_basename( $path, $suffix = '' ) {
		return wp_basename( $path, $suffix );
	}


	/**
	 * @param string $guid Attachments guid.
	 *
	 * @return ?int Id of the attachment. Null if no attachment was found.
	 */
	public function attachment_id_by_guid( $guid ) {
		if ( ! is_string( $guid ) || empty( $guid ) ) {
			return null;
		}

		$id = $this->tc_attachments->get_attachment_id_by_url( $guid );
		return $id ? (int) $id : null;
	}

	/**
	 * Returns the alt text of the given id.
	 *
	 * @param int $id Attachment id.
	 *
	 * @return string
	 */
	public function alt_text_by_id( $id ) {
		if ( ! is_numeric( $id ) || empty( $id ) ) {
			return '';
		}

		$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );

		return is_string( $alt ) ? $alt : '';
	}


	/**
	 * Returns the caption of the given id.
	 *
	 * @param int $id Attachment id.
	 *
	 * @return string
	 */
	public function caption_by_id( $id ) {
		if ( ! is_numeric( $id ) || empty( $id ) ) {
			return '';
		}

		// Get caption by using core getter (post_excerpt).
		if ( $caption = wp_get_attachment_caption( $id ) ) {
			return $caption;
		}

		// Get caption by attachment meta data.
		$attachment_meta = get_post_meta( $id, '_wp_attachment_metadata', true );
		if (
			is_array( $attachment_meta ) &&
			array_key_exists( 'image_meta', $attachment_meta ) &&
			is_array( $attachment_meta['image_meta'] ) &&
			array_key_exists( 'caption', $attachment_meta['image_meta'] ) &&
			is_string( $attachment_meta['image_meta']['caption'] )
		) {
			return $attachment_meta['image_meta']['caption'];
		}

		// No caption found.
		return '';
	}
}
