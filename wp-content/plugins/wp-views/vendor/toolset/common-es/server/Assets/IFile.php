<?php

namespace ToolsetCommonEs\Assets;

/**
 * Interface IFile
 *
 * @package ToolsetCommonEs\Assets
 */
interface IFile {
	/**
	 * @return string
	 */
	public function get_content();

	/**
	 * @return string
	 */
	public function get_path();
}
