<?php

/**
 * Toolset Divi can be installed as a standalone glue plugin,
 * but it also comes packaged with other Toolset plugins.
 *
 * To include it on a Toolset plugin, do as follows:
 * - Include this repository as a Composer dependency.
 * - Wait until after_setup_theme to include this loader.php file.
 *
 * This will ensure that the glue plugin can be used if avaiilable;
 * otherwise, this will ensure that the Toolset plugin packing the newest version will push it.
 *
 * $toolset_divi_version must be increased on every new version of the glue plugin.
 * Note that this must always be greater than 1, since Divi registers its extensions on init:0.
 * Also, having a negative priority ensures that the highest version number gets called first.
 */

 /**
  * WARNING: INCREASE THIS LOADER VERSION ON EVERY NEW RELEASE.
  */
$toolset_divi_version = 11000;

add_action( 'init', function() use ( $toolset_divi_version ) {
	if ( defined( 'TOOLSET_DIVI_LOADED' ) ) {
		// A more recent version of Toolset Divi is already active.
		return;
	}

	// Define TOOLSET_DIVI_LOADED so any older instance of Toolset Divi is not loaded.
	define( 'TOOLSET_DIVI_LOADED', $toolset_divi_version );

	// This will have to reference toolset-divi.php once the MR form Pierre is merged.
	require_once plugin_dir_path( __FILE__ ) . '/toolset-divi.php';
}, 1 - $toolset_divi_version );
