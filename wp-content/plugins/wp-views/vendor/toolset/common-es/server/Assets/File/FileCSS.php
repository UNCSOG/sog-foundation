<?php

namespace ToolsetCommonEs\Assets\File;

/**
 * Class FileCSS
 *
 * @package ToolsetCommonEs\Assets\File
 */
class FileCSS extends AFile {

	/**
	 * @return string[]
	 */
	protected function allowed_file_extensions() {
		return [ 'css' ];
	}
}
