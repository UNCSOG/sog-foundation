<?php

namespace OTGS\Toolset\Views\Model\Wordpress;

/**
 * Wrapper for WordPress WP_Image_Editor class interaction.
 *
 * @since 2.8.1
 */
class Media {

	const SUFFIX = 'wpv_';

	/**
	 * @var \Toolset_Files
	 */

	private $file_manager = null;

	/**
	 * @var \OTGS\Toolset\Views\Model\Wordpress\Error
	 */
	private $error_manager = null;

	/**
	 * Constructor
	 *
	 * @param \Toolset_Files $file_manager
	 * @since 2.8.1
	 */
	public function __construct(
		\Toolset_Files $file_manager = null,
		\OTGS\Toolset\Views\Model\Wordpress\Error $error_manager = null
	) {
		$this->file_manager = $file_manager;
		$this->error_manager = $error_manager;
	}

	/**
	 * Get the file manager instance, or generate a new one.
	 *
	 * @return \Toolset_Files
	 * @since 2.8.1
	 */
	private function get_file_manager() {
		$this->file_manager = ( null === $this->file_manager )
			? new \Toolset_Files()
			: $this->file_manager;

		return $this->file_manager;
	}

	/**
	 * Get the error manager instance, or generate a new one.
	 *
	 * @return \OTGS\Toolset\Views\Model\Wordpress\Error
	 * @since 2.8.1
	 */
	private function get_error_manager() {
		$this->error_manager = ( null === $this->error_manager )
			? new \OTGS\Toolset\Views\Model\Wordpress\Error()
			: $this->error_manager;

		return $this->error_manager;
	}

	/**
	 * Generate the proper suffix for Views resized images.
	 *
	 * @param int $width
	 * @param int $height
	 * @param array|bool $crop
	 * @return string
	 * @since 2.8.1
	 */
	private function generate_suffix( $width, $height, $crop = false ) {
		$suffix = self::SUFFIX . "{$width}x{$height}";

		if ( false !== $crop ) {
			$suffix .= '_' . implode( '_', $crop );
		}

		return $suffix;
	}

	/**
	 * Check whether an image i n the intended path already exists.
	 *
	 * @param string $file
	 * @return bool
	 * @since 2.8.1
	 */
	private function file_exists( $file ) {
		return $this->get_file_manager()->file_exists( $file );
	}

	/**
	 * Resize an image given its URL, a pair of width and height values,
	 * and an optional set of crop settings.
	 *
	 * @param string $file_url
	 * @param int $width
	 * @param int $height
	 * @param array|bool $crop
	 * @return string|\WP_Error
	 * @since 2.8.1
	 */
	public function resize_image( $file_url, $width, $height, $crop = false ) {
		$uploads = wp_upload_dir();

		if ( false !== toolset_getarr( $uploads, 'error', false ) ) {
			return $this->get_error_manager()->get_error( 'error_loading_upload_dir' );
		}

		$file = str_replace( $uploads['baseurl'], $uploads['basedir'], $file_url );
		$image_editor = wp_get_image_editor( $file );

		if ( is_wp_error( $image_editor ) ) {
			return $this->get_error_manager()->get_error( 'error_loading_image', $image_editor, $file );
		}

		$suffix = $this->generate_suffix( $width, $height, $crop );

		$new_file = $image_editor->generate_filename( $suffix );

		if ( ! $this->file_exists( $new_file ) ) {
			$image_editor->set_quality( 90 );
			$image_editor->resize( $width, $height, $crop );
			$image_editor->save( $new_file );
		}

		return str_replace( $uploads['basedir'], $uploads['baseurl'], $new_file );
	}

	/**
	 * Delete all Views resized images by attachment post id.
	 *
	 * @param $attachment_post_id
	 * @since 2.8.1
	 */
	public function delete_resized_images_by_attachment_id( $attachment_post_id ) {
		if ( ! $attachment_file = get_attached_file( $attachment_post_id ) ) {
			// no attachment file for $attachment_post_id
			return;
		}

		$attachment_file_path_parts = pathinfo( $attachment_file );

		$files_pattern = $attachment_file_path_parts['dirname']
			. DIRECTORY_SEPARATOR
			. $attachment_file_path_parts['filename']
			. '-' . self::SUFFIX . '*';

		foreach ( glob( $files_pattern ) as $filename ) {
			$this->get_file_manager()->unlink_silent( $filename );
		}
	}


}
