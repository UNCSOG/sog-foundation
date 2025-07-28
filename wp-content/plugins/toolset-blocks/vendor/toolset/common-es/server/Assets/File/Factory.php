<?php

namespace ToolsetCommonEs\Assets\File;

/**
 * Class Factory
 *
 * @package ToolsetCommonEs\Assets\File
 */
class Factory {

	/** @var FileCSS[] Storage for already requested files. */
	private $css_files = [];

	/** @var FileJS[] Storage for already requested files. */
	private $js_files = [];

	/**
	 * @param string $file Path to file.
	 *
	 * @return FileCSS
	 * @throws \Exception No valid css file.
	 */
	public function css( $file ) {
		if ( ! array_key_exists( $file, $this->css_files ) ) {
			// First time the file is requested.
			$this->css_files[ $file ] = new FileCSS( $file );
		}

		return $this->css_files[ $file ];

	}

	/**
	 * @param string $file Path to file.
	 *
	 * @return FileJS
	 * @throws \Exception No valid js file.
	 */
	public function js( $file ) {
		if ( ! array_key_exists( $file, $this->js_files ) ) {
			// First time the file is requested.
			$this->js_files[ $file ] = new FileJS( $file );
		}

		return $this->js_files[ $file ];

	}
}
