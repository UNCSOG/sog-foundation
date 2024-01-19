<?php

/**
 * As Common ES can be packaged with multiple plugins, this number makes sure that the latest
 * version is loaded. Raise the number by 1 right before you merge it to develop.
 *
 * 100000 = Version 1.0.0
 * 101000 = Version 1.0.1
 * 101001 = The  1 merge to develop while working on 1.0.2.
 * ...
 * 101019 = The 19 merge to develop while working on 1.0.2.
 * 102000 = Version 1.0.2
 *
 * ...and so on...
 */
$toolset_common_es_version = 166000;

/**
 * Register Script and Style. This will always be called very very early on init as it uses a negative priority.
 * Priority is: 100000 - $toolset_common_es_version <= 0
 *
 * This makes sure that the highest version number is called first.
 */
add_action( 'init', function() use ( $toolset_common_es_version ) {
	if ( defined( 'TOOLSET_COMMON_ES_LOADED' ) ) {
		// A more recent version of Toolset Common ES is already active.
		return;
	}

	// Define TOOLSET_COMMON_ES_LOADED so any older instance of Common ES is not loaded.
	define( 'TOOLSET_COMMON_ES_LOADED', $toolset_common_es_version );

	define( 'TOOLSET_COMMON_ES_URL', plugin_dir_url( __FILE__ ) );

	define( 'TOOLSET_COMMON_ES_DIR', __DIR__ );
	define( 'TOOLSET_COMMON_ES_DIR_JS_FRONTEND', __DIR__ . '/public/frontend' );

	// Apply a new init callback, which is called on priority 1.
	// Reasons:
	// - It's good to have a defined priorty and not a dynamic.
	// - Having a negative priority to register scripts causes problems with core scripts (lodash conflict).
	add_action( 'init', function() use ( $toolset_common_es_version ) {
		// Bundled Javascript
		if ( ! wp_script_is( 'toolset-common-es', 'registered' ) ) {
			wp_register_script(
				'toolset-common-es',
				plugin_dir_url( __FILE__ ) . 'public/toolset-common-es.js',
				array(),
				$toolset_common_es_version,
				false
			);
		}

		// CSS
		if ( ! wp_style_is( 'toolset-common-es', 'registered' ) ) {
			wp_register_style(
				'toolset-common-es',
				plugin_dir_url( __FILE__ ) . 'public/toolset-common-es.css',
				array(),
				$toolset_common_es_version
			);

			if( array_key_exists( 'page', $_GET ) && $_GET['page'] === 'toolset-settings' ) {
				// Always load on Toolset Settings page.
				wp_enqueue_style( 'toolset-common-es' );
			}
		}

		if ( ! is_admin() ) {
			// Bundled Javascript
			if ( ! wp_script_is( 'toolset-common-es-frontend', 'registered' ) ) {
				wp_register_script(
					'toolset-common-es-frontend',
					plugin_dir_url( __FILE__ ) . 'public/toolset-common-es-frontend.js',
					array(),
					$toolset_common_es_version,
					false
				);
			}

			wp_enqueue_script( 'toolset-common-es-frontend' );
		}
	}, 1 );

	// Register autoloader.
	require_once __DIR__ . '/psr4-autoload.php';

	// Bootstrap PHP part
	require_once __DIR__ . '/server/bootstrap.php';
}, 100000 - $toolset_common_es_version );
