<?php

namespace ToolsetCommonEs\Assets\File;

use ToolsetCommonEs\Assets\IFile;

/**
 * Class AFile
 *
 * @package ToolsetCommonEs\Assets\File
 */
abstract class AFile implements IFile {
	/** @var string The path to the file. */
	protected $file;

	/** @var string|false The content or false if file_get_contents( $this->file ) failed. */
	private $content;

	/**
	 * AFile constructor.
	 *
	 * @param string $file Path of the file.
	 * @throws \RuntimeException The file could not be loaded.
	 * @throws \InvalidArgumentException Given file type not supported.
	 */
	public function __construct( $file ) {
		$allowed_file_extensions = $this->allowed_file_extensions();

		if ( ! is_array( $allowed_file_extensions ) ) {
			throw new \RuntimeException( 'allowed_file_extensions() must return an array.' );
		}

		$file_info = pathinfo( $file );

		if ( ! in_array( $file_info['extension'], $allowed_file_extensions, true ) ) {
			throw new \InvalidArgumentException(
				'File must have one of the following extensions: ' .
				implode( ', ', $allowed_file_extensions )
			);
		}

		if ( ! file_exists( $file ) ) {
			throw new \RuntimeException( "File not found: $file" );
		}

		if ( ! is_readable( $file ) ) {
			throw new \RuntimeException( "File not readable: $file" );
		}

		$this->file = $file;
	}


	/**
	 * @return array Array of allowed extensions without the dot.
	 */
	abstract protected function allowed_file_extensions();

	/**
	 * @return string The path of the file.
	 */
	public function get_path() {
		return $this->file;
	}

	/**
	 * @return string The content of the file.
	 */
	public function get_content() {
		if ( $this->content === null ) {
			// @codingStandardsIgnoreStart WP wants to use wp_remote_get(). Disagree.
			$this->content = file_get_contents( $this->file );
			// @codingStandardsIgnoreEnd
		}

		return $this->content ? trim( $this->content ) : '';
	}
}
