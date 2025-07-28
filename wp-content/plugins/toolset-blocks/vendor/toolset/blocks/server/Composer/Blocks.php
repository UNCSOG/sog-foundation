<?php

namespace ToolsetBlocks\Composer;

use Composer\Installer\PackageEvent;

/**
 * Class Common
 *
 * @package ToolsetCommonEs\Composer
 *
 * @since 1.0.0
 */
class Blocks {
	const PACKAGE_NAME = 'toolset/blocks';

	/**
	 * Run NPM command
	 *
	 * @param string $command
	 */
	public static function runNpmCommand( $command ) {
		$blocks_path = 'vendor/' . self::PACKAGE_NAME;
		exec( "cd $blocks_path && npm $command" );
	}
}
