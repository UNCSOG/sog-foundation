<?php

/**
*
* This file is responsible for loading the latest version of the Toolset Theme Settings lbraries.
*
* To use it in a plugin or theme you should include this file early in the
* plugin loader and then call the toolset_theme_settings_initialize function.
* The toolset_theme_settings_initialize should be passed the file path to the directory
* where this file is located and also the url to this directory.
* Note that both the path and URL will be normalized with untrailingslashit
* so they do not pack any trailing slash.
*
*
*
* -----------------------------------------------------------------------
*
* This version number should always be incremented by 1 whenever a change
* is made to the toolset-theme-settings code.
* The version number will then be used to work out which plugin has the latest
* version of the code.
*
* The version number will have a format of XXXYYY
* where XXX is the future target Toolset Theme Settings version number, built upon the stable released one stated in changelog.txt plus 1
* and YYY is incremented by 1 on each change to the Toolset Theme Settings repo
* so we allow up to 1000 changes per dev cycle.
*
*/
$toolset_theme_settings_version = 154000;


// ----------------------------------------------------------------------//
// WARNING * WARNING *WARNING
// ----------------------------------------------------------------------//

// Don't modify or add to this code.
// This is only responsible for making sure the latest version of the resources
// is loaded.

global $toolset_theme_settings_paths;

if ( ! isset( $toolset_theme_settings_paths ) ) {
	$toolset_theme_settings_paths = array();
}

if ( ! isset( $toolset_theme_settings_paths[ $toolset_theme_settings_version ] ) ) {
	// Save the path to this version.
	$toolset_theme_settings_paths[ $toolset_theme_settings_version ]['path'] = str_replace( '\\', '/', dirname( __FILE__ ) );
}

if ( ! function_exists( 'toolset_theme_settings_plugins_loaded' ) ) {
	function toolset_theme_settings_plugins_loaded() {
		global $toolset_theme_settings_paths;

		// find the latest version
		$latest = 0;
		foreach ( $toolset_theme_settings_paths as $key => $data ) {
			if ( $key > $latest ) {
				$latest = $key;
			}
		}
		if ( $latest > 0 ) {
			require_once $toolset_theme_settings_paths[ $latest ]['path'] . '/toolset-theme-settings-loader.php';
			toolset_theme_settings_uri_and_start( $toolset_theme_settings_paths[ $latest ]['url'] );
		}
	}

	add_action( 'after_setup_theme', 'toolset_theme_settings_plugins_loaded', -1 );
}

if ( ! function_exists( 'toolset_theme_settings_initialize' ) ) {
	function toolset_theme_settings_initialize( $path, $url ) {
		global $toolset_theme_settings_paths;

		$path = str_replace( '\\', '/', $path );

		if ( substr( $path, strlen( $path ) - 1 ) === '/' ) {
			$path = substr( $path, 0, strlen( $path ) - 1 );
		}

		// Save the url in the matching path
		foreach ( $toolset_theme_settings_paths as $key => $data ) {
			if ( $toolset_theme_settings_paths[ $key ]['path'] === $path ) {
				$toolset_theme_settings_paths[ $key ]['url'] = $url;
				break;
			}
		}
	}
}

