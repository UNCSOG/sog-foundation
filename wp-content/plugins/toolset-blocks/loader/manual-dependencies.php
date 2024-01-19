<?php
/**
 * Plugin manual dependencies, not autoload-able.
 *
 * @package Toolset Views
 * @since 3.0
 */

// Most of the dependency libraries here follow the same loading mechanism:
// - require once the library loader.
// - register the current relatives path and URL within the loader.
// - the loader will decide which instance to load based on version numbers.

// Load OTGS/UI.
require_once WPV_PATH . '/vendor/otgs/ui/loader.php';
otgs_ui_initialize( WPV_PATH . '/vendor/otgs/ui', WPV_URL . '/vendor/otgs/ui' );

// Load OnTheGoResources.
require WPV_PATH . '/vendor/toolset/onthego-resources/loader.php';
onthego_initialize( WPV_PATH . '/vendor/toolset/onthego-resources/', WPV_URL . '/vendor/toolset/onthego-resources/' );

// Load Toolset Common.
require WPV_PATH . '/vendor/toolset/toolset-common/loader.php';
toolset_common_initialize( WPV_PATH . '/vendor/toolset/toolset-common/', WPV_URL . '/vendor/toolset/toolset-common/' );

// Load Toolset Theme Settings.
require_once WPV_PATH . '/vendor/toolset/toolset-theme-settings/loader.php';
toolset_theme_settings_initialize( WPV_PATH . '/vendor/toolset/toolset-theme-settings', WPV_URL . '/vendor/toolset/toolset-theme-settings' );

/**
 * Bootstrap Toolset Common ES.
 */
require WPV_PATH . '/vendor/toolset/common-es/loader.php';

/**
 * Bootstrap Dynamic Sources.
 */
require_once WPV_PATH . '/vendor/toolset/dynamic-sources/server/ds-instance.php';

/**
 * Bootstrap Conditionals.
 */
require_once WPV_PATH . '/vendor/toolset/conditionals/loader.php';

/**
 * Bootstrap Toolset Blocks.
 */
require WPV_PATH . '/vendor/toolset/blocks/toolset-blocks.php';

/**
 * Lazy load Toolset Divi.
 * This will get fired from Toolset Common on demand.
 * Nothing will be initialized if the standalone Glue plugin was installed already.
 */
add_action( 'toolset_divi_initialize_extension', function() {
	require WPV_PATH . '/vendor/toolset/divi/loader.php';
});
