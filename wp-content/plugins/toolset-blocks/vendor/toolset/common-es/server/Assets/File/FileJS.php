<?php

namespace ToolsetCommonEs\Assets\File;

/**
 * Class FileJS
 *
 * @package ToolsetCommonEs\Assets\File
 */
class FileJS extends AFile {

	/**
	 * @return string[]
	 */
	protected function allowed_file_extensions() {
		return [ 'js' ];
	}
}
