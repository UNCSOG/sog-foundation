<?php

namespace ToolsetCommonEs\Assets\File;

/**
 * Class Factory
 *
 * @package ToolsetCommonEs\Assets\File
 */
class Factory {

	/**
	 * @param string $file Path to file.
	 *
	 * @return FileCSS
	 * @throws \Exception No valid css file.
	 */
	public function css( $file ) {
		return new FileCSS( $file );
	}

	/**
	 * @param string $file Path to file.
	 *
	 * @return FileJS
	 * @throws \Exception No valid js file.
	 */
	public function js( $file ) {
		return new FileJS( $file );
	}
}
