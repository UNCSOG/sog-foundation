<?php

namespace OTGS\Toolset\CRED\Model\Wordpress;

/**
 * Wrapper for WordPress media interaction
 *
 * @since 2.4
 */
class Media {

	/**
	 * @var string[]
	 */
	private $supported_file_types = array( 'audio', 'file', 'image', 'video' );

	/**
	 * @var string[]
	 */

	private $supported_mime_types = array();
	/**
	 * @var string[]
	 */
	private $supported_extensions = array();

	/**
	 * Get the valid mime types per file type.
	 *
	 * @since 2.4
	 */
	public function get_valid_mime_types( $file_type = 'file' ) {
		$valid_mime_types_per_file_type = toolset_getarr( $this->supported_mime_types, $file_type, null );

		if ( null != $valid_mime_types_per_file_type ) {
			return $valid_mime_types_per_file_type;
		}

		$this->set_valid_formats();

		return toolset_getarr( $this->supported_mime_types, $file_type, array() );
	}
	/**
	 * Get the valid extensions per file type.
	 *
	 * @since 2.4
	 */
	public function get_valid_extensions( $file_type = 'file' ) {
		$valid_extensions_per_file_type = toolset_getarr( $this->supported_extensions, $file_type, null );

		if ( null != $valid_extensions_per_file_type ) {
			return $valid_extensions_per_file_type;
		}

		$this->set_valid_formats();

		return toolset_getarr( $this->supported_extensions, $file_type, array() );
	}

	/**
	 * Set the supported extensions and mime types, pero file type.
	 *
	 * @since 2.4
	 */
	private function set_valid_formats() {
		$wp_allowed_mime_types = get_allowed_mime_types();

		// Add manual support for mp3
		if ( ! in_array( 'audio/mp3', $wp_allowed_mime_types ) ) {
			$wp_allowed_mime_types['mp3'] = 'audio/mp3';
		}

		// Set default empty values
		$this->supported_mime_types = array(
			'audio' => array(),
			'file' => array(),
			'image' => array(),
			'video' => array(),
		);
		$this->supported_extensions = array(
			'audio' => array(),
			'file' => array(),
			'image' => array(),
			'video' => array(),
		);

		// Set mime types and extensions support per file type.
		// Note that extensions can come in abc|xyz formats,
		// hence we explode and add individual extension values.
		foreach ( $wp_allowed_mime_types as $extension => $mime_type ) {
			$this->supported_mime_types['file'][] = $mime_type;
			$this->set_exploded_extensions( 'file', $extension );

			$this->maybe_set_valid_format_per_type( 'audio', $extension, $mime_type );
			$this->maybe_set_valid_format_per_type( 'image', $extension, $mime_type );
			$this->maybe_set_valid_format_per_type( 'video', $extension, $mime_type );
		}

		// Make unique, plus extend with filters.
		foreach ( $this->supported_file_types as $file_type ) {
			$this->supported_mime_types[ $file_type ] = array_unique( $this->supported_mime_types[ $file_type ] );
			$this->supported_mime_types[ $file_type ] = array_values( $this->supported_mime_types[ $file_type ] );

			$this->supported_extensions[ $file_type ] = array_unique( $this->supported_extensions[ $file_type ] );
			$this->supported_extensions[ $file_type ] = array_values( $this->supported_extensions[ $file_type ] );

			/**
			 * Despite its name, this filters mime types.
			 * Mind the hook name typo :-/
			 *
			 * @param string[]
			 * @since 1.9
			 * @deprecated 2.4
			 */
			$this->supported_mime_types[ $file_type ] = apply_filters( 'toolset_valid_' . $file_type . '_extentions', $this->supported_mime_types[ $file_type ] );

			/**
			 * Filter the supported mime types, per file type.
			 *
			 * @param string[]
			 * @since 2.4
			 */
			$this->supported_mime_types[ $file_type ] = apply_filters( 'toolset_forms_valid_' . $file_type . '_upload_mime_types', $this->supported_mime_types[ $file_type ] );

			/**
			 * Filter the supported extensions, per file type.
			 *
			 * @param string[]
			 * @since 2.4
			 */
			$this->supported_extensions[ $file_type ] = apply_filters( 'toolset_forms_valid_' . $file_type . '_upload_extensions', $this->supported_extensions[ $file_type ] );
		}
	}

	/**
	 * Maybe add a set of extensions and mime types to the list of supported, by a file type.
	 *
	 * @param string $file_type
	 * @param string $extension
	 * @param string $mime_type
	 * @since 2.4
	 */
	private function maybe_set_valid_format_per_type( $file_type, $extension, $mime_type ) {
		if ( false !== strpos( $mime_type, $file_type . '/' ) ) {
			$this->supported_mime_types[ $file_type ][] = $mime_type;
			$this->set_exploded_extensions( $file_type, $extension );
		}
	}

	/**
	 * Explode an abc|xyz list of extensions and add them individually to the
	 * list of supported extensions by a file type.
	 *
	 * @param string $file_type
	 * @param string $extension
	 * @since 2.4
	 */
	private function set_exploded_extensions( $file_type, $extension ) {
		$extensions = explode( '|', $extension );
		foreach ( $extensions as $exploded_extension ) {
			$this->supported_extensions[ $file_type ][] = $exploded_extension;
		}
	}

	/**
	 * Get the error message by file type.
	 *
	 * @return string
	 * @since 2.4
	*/
	public function get_upload_validation_error_message( $file_type = 'file' ) {
		switch ( $file_type ) {
			case 'audio':
				/* translators: Validation message when trying to upload a file of an unsupported type to an audio field */
				return __( 'You can only upload an audio file', 'wp-cred' );
			case 'image':
				/* translators: Validation message when trying to upload a file of an unsupported type to an image field */
				return __( 'You can only upload an image file', 'wp-cred' );
			case 'video':
				/* translators: Validation message when trying to upload a file of an unsupported type to a video field */
				return __( 'You can only upload a video file', 'wp-cred' );
			default:
				/* translators: Validation message when trying to upload a file of an unsupported format */
				return __( 'You cannot upload a file of this type', 'wp-cred' );
		}
	}

}
