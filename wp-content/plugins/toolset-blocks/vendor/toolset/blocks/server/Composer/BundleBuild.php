<?php

namespace ToolsetBlocks\Composer;

use Composer\Installer\PackageEvent;

/**
 * Class BundleBuild
 * @package ToolsetCommonEs\Composer
 *
 * @since 1.0.0
 */
class BundleBuild {

	/**
	 * On BundleBuild command re-create the build files.
	 *
	 * @param $event
	 */
	public static function run( $event ) {
		$event->getIO()->write("-------------------------------------------------");
		$event->getIO()->write("-- TOOLSET BLOCKS: Generating build files.     --");
		$event->getIO()->write("-------------------------------------------------");
		Blocks::runNpmCommand( 'run build' );

		$event->getIO()->write("Completed.");
	}
}
