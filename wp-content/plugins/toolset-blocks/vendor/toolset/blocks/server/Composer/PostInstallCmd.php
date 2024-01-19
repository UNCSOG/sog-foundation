<?php

namespace ToolsetBlocks\Composer;

use Composer\Installer\PackageEvent;

/**
 * Class PostInstallCmd
 * @package ToolsetCommonEs\Composer
 *
 * @since 1.0.0
 */
class PostInstallCmd {

	/**
	 * On Post Install Cmd install this package's dependencies and create the build files.
	 *
	 * @param $event
	 */
	public static function run( $event ) {
		$event->getIO()->write("-------------------------------------------------");
		$event->getIO()->write("-- TOOLSET BLOCKS: Installing NPM Packages.    --");
		$event->getIO()->write("-------------------------------------------------");
		Blocks::runNpmCommand( 'ci --only=prod' );

		$event->getIO()->write("-------------------------------------------------");
		$event->getIO()->write("-- TOOLSET BLOCKS: Generating build files.     --");
		$event->getIO()->write("-------------------------------------------------");
		Blocks::runNpmCommand( 'run build' );

		$event->getIO()->write("Completed.");
	}
}
