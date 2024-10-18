<?php


/**
 * As Conditionals can be packaged with multiple plugins, this number makes sure that the latest
 * version is loaded. Raise the number by 1 right before you merge it to develop.
 *
 * 100000 = Version 1.0.0
 */
$toolset_conditionals_version = 116000;

require_once( __DIR__ . '/psr4-autoload.php' );

/* Bootstrap Toolset Common ES */
require plugin_dir_path( __FILE__ ) . '/../common-es/loader.php';
require_once plugin_dir_path( __FILE__ ) . '/../dynamic-sources/server/ds-instance.php';

use Toolset\DynamicSources\DynamicSources;

/**
 * Register Script and Style. This will always be called very very early on init as it uses a negative priority.
 * Priority is: 100000 - $toolset_conditionals_version <= 0
 *
 * This makes sure that the highest version number is called first.
 */
add_action( 'init', function() use ( $toolset_conditionals_version ) {
	if( defined( 'TOOLSET_CONDITIONALS_LOADED' ) ) {
		// A more recent version of Toolset Conditionals is already active.
		return;
	}

	// Define TOOLSET_CONDITIONALS_LOADED so any older instance of Conditionals is not loaded.
	define( 'TOOLSET_CONDITIONALS_LOADED', $toolset_conditionals_version );

	define( 'TOOLSET_CONDITIONALS_URL', plugin_dir_url( __FILE__ ) );

	define( 'TOOLSET_CONDITIONALS_SCRIPT_HANDLER', 'toolset-conditionals' );

	// Apply a new init callback, which is called on priority 1.
	// Reasons:
	// - It's good to have a defined priorty and not a dynamic.
	// - Having a negative priority to register scripts causes problems with core scripts (lodash conflict).
	add_action( 'init', function() use ( $toolset_conditionals_version ) {
		new Toolset\DynamicSources\DynamicSources();
		do_action( 'toolset/dynamic_sources/actions/toolset_dynamic_sources_initialize' );

		// Bundled Javascript
		if( ! wp_script_is( TOOLSET_CONDITIONALS_SCRIPT_HANDLER, 'registered' ) ) {
			wp_register_script(
				TOOLSET_CONDITIONALS_SCRIPT_HANDLER,
				plugin_dir_url( __FILE__ ) . 'public/toolset-conditionals.js',
				array( 'toolset-common-es', 'toolset_dynamic_sources_script' ),
				$toolset_conditionals_version
			);
		}

		// CSS
		if( ! wp_style_is( TOOLSET_CONDITIONALS_SCRIPT_HANDLER, 'registered' ) ) {
			wp_register_style(
				TOOLSET_CONDITIONALS_SCRIPT_HANDLER,
				plugin_dir_url( __FILE__ ) . 'public/toolset-conditionals.css',
				array( 'toolset-common-es' ),
				$toolset_conditionals_version
			);
		}
	}, 2 ); // Loads after DS and Common-ES
}, 100000 - $toolset_conditionals_version );
