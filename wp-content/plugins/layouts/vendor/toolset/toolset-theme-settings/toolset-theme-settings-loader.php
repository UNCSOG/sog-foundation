<?php
if( !defined('TOOLSET_THEME_SETTINGS_VERSION') ){
	define('TOOLSET_THEME_SETTINGS_VERSION', '1.5.4');
}

if ( ! defined('TOOLSET_THEME_SETTINGS_PATH' ) ) {
	define( 'TOOLSET_THEME_SETTINGS_PATH', dirname( __FILE__ ) );
}

if ( ! defined('TOOLSET_THEME_SETTINGS_ELEMENT_TEMPLATES_PATH' ) ) {
	define( 'TOOLSET_THEME_SETTINGS_ELEMENT_TEMPLATES_PATH', TOOLSET_THEME_SETTINGS_PATH . '/compatibility-modules/templates/gui-form-elements/' );
}

if ( ! defined('TOOLSET_THEME_SETTINGS_BUNDLED_PATH' ) ) {
	define( 'TOOLSET_THEME_SETTINGS_BUNDLED_PATH', TOOLSET_THEME_SETTINGS_PATH. '/compatibility-modules/bundles' );
}

if ( ! defined('TOOLSET_THEME_SETTINGS_DIR' ) ) {
	define( 'TOOLSET_THEME_SETTINGS_DIR', basename( TOOLSET_THEME_SETTINGS_PATH ) );
}

if ( ! defined('TOOLSET_THEME_SETTINGS_CACHE_OPTION' ) ) {
	define( 'TOOLSET_THEME_SETTINGS_CACHE_OPTION', 'toolset_config_cache' );
}

if ( ! defined('TOOLSET_THEME_SETTINGS_SOURCES_OPTION' ) ) {
	define( 'TOOLSET_THEME_SETTINGS_SOURCES_OPTION', 'toolset_config_sources' );
}

if ( ! defined('TOOLSET_THEME_SETTINGS_CONFIG_FILE' ) ) {
	// TODO this is dulicated in this codebase. Let's have a CONSTANTS class instead.
	define( 'TOOLSET_THEME_SETTINGS_CONFIG_FILE', 'toolset-config.json' );
}

if ( ! defined('TOOLSET_THEME_SETTINGS_DATA_KEY' ) ) {
	define( 'TOOLSET_THEME_SETTINGS_DATA_KEY', 'toolset_theme_settings' );
}

require_once( TOOLSET_THEME_SETTINGS_PATH . '/bootstrap.php' );


if ( ! function_exists( 'toolset_run_theme_settings' ) ) {
	function toolset_run_theme_settings() {
		global $toolset_theme_settings_bootstrap;
		$toolset_theme_settings_bootstrap = Toolset_Theme_Settings_Bootstrap::get_instance();
		$toolset_theme_settings_bootstrap->initialize();
	}

	function toolset_theme_settings_uri_and_start( $path ) {
		if ( ! defined( 'TOOLSET_THEME_SETTINGS_REL_PATH' ) ) {
			define( 'TOOLSET_THEME_SETTINGS_REL_PATH', $path );
		}

		$url = untrailingslashit( $path );

		if (
			is_ssl()
			|| (
				defined( 'FORCE_SSL_ADMIN' )
				&& FORCE_SSL_ADMIN
			)
		) {
			define( 'TOOLSET_THEME_SETTINGS_URL', str_replace( 'http://', 'https://', $url ) );
		} else {
			define( 'TOOLSET_THEME_SETTINGS_URL', $url );
		}

	}

	add_action( 'after_setup_theme', 'toolset_run_theme_settings', 2 );
}
