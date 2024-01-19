<?php

namespace ToolsetCommonEs\Assets;

use ToolsetCommonEs\Assets\File\AFile;
use ToolsetCommonEs\Assets\File\Factory;

/**
 * Class Loader
 *
 * @package ToolsetCommonEs\Assets
 */
class Loader {
	const JS = 'js';
	const CSS = 'css';

	/** @var Factory */
	private $file_factory;

	/** @var array[string]integer */
	private $fetched = [];

	/** @var array[string]string */
	private $errors = [];

	/**
	 * Loader constructor.
	 *
	 * @param Factory $file_factory
	 */
	public function __construct( Factory $file_factory ) {
		$this->file_factory = $file_factory;
	}


	/**
	 * @param string $file Path of the file.
	 * @param bool $print True will directly print the style. False will return it.
	 *
	 * @return string|void
	 */
	public function css_print( $file, $print = true ) {
		if ( ! $this->is_print_allowed() ) {
			return;
		}

		if ( $file = $this->get_file_once( $file, self::CSS ) ) {
			if ( $print ) {
				// @codingStandardsIgnoreStart
				// No need to escape our style files.
				echo '<style>' . $file->get_content() . '</style>';
				// @codingStandardsIgnoreEnd
			} else {
				return $file->get_content();
			}
		}
	}


	/**
	 * @param string $file The path of the file.
	 *
	 * @return string
	 */
	public function get_css_file_content( $file ) {
		try {
			$file = $this->get_file( $file, self::CSS );
		} catch ( \Exception $e ) {
			$this->error( $file, $e->getMessage() );

			// Continue without handling the broken file.
			return '';
		}

		return $file->get_content();
	}

	/**
	 * @param string $file Path of the file.
	 */
	public function js_print( $file ) {
		if ( strpos( $file, TOOLSET_COMMON_ES_DIR_JS_FRONTEND ) !== false ) {
			// Already loaded via static js file.
			return;
		}

		if ( ! $this->is_print_allowed() ) {
			return;
		}

		// @codingStandardsIgnoreStart
		// No need to escape our script files.
		if ( $file = $this->get_file_once( $file, self::JS ) ) {
			echo '<script>' . $file->get_content() . '</script>';
		}
		// @codingStandardsIgnoreEnd
	}

	/**
	 * @param string $file Path of the file.
	 * @param string $file_type Type of the file.
	 *
	 * @throws \InvalidArgumentException File type not supported.
	 *
	 * @return IFile|void
	 */
	private function get_file_once( $file, $file_type ) {
		if ( array_key_exists( $file, $this->fetched ) ) {
			// File was already fetched.
			$this->fetched[ $file ]++;
			return;
		}

		try {
			$file = $this->get_file( $file, $file_type );
		} catch ( \Exception $e ) {
			$this->error( $file, $e->getMessage() );

			// Continue without handling the broken file.
			return;
		}

		// File exists, is readable and wasn't fetched before.
		$this->fetched[ $file->get_path() ] = 1;
		return $file;
	}


	/**
	 * @param string $file The path of the file.
	 * @param string $file_type The type of the file (css / js).
	 *
	 * @return AFile
	 * @throws \InvalidArgumentException File type not supported.
	 */
	private function get_file( $file, $file_type ) {
		switch ( $file_type ) {
			case self::CSS:
				$file = $this->file_factory->css( $file );
				break;
			case self::JS:
				$file = $this->file_factory->js( $file );
				break;
			default:
				// Shouldn't happen as this method is private.
				throw new \InvalidArgumentException( "$file_type not supported." );
		}

		return $file;
	}

	/**
	 * To enable debugging add define( 'TC_ASSETS_DEBUG', true ).
	 *
	 * @param string $file Path of the file.
	 * @param string $msg Error message.
	 *
	 * @return void
	 */
	private function error( $file, $msg ) {
		if ( ! defined( 'TC_ASSETS_DEBUG' ) || ! TC_ASSETS_DEBUG ) {
			// No debugging enabled.
			return;
		}

		$this->errors[ $file ] = $msg;
	}


	/**
	 * Returns true if print() is allowed on the current site / request.
	 *
	 * @return bool
	 */
	private function is_print_allowed() {
		return ! is_admin() &&
			! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
			! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}
