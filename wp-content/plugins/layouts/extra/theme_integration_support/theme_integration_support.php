<?php
/**
 * This file is loaded by WDDL_ExtraModulesLoader::load_modules(). It further loads other resources needed for
 * layouts theme integration to work.
 *
 * We assume it's being loaded at some point during the 'init' action.
 *
 * @since 1.5
 */


if( ! defined('WPDDL_THEME_INTEGRATION_ABS') ) define('WPDDL_THEME_INTEGRATION_ABS', WPDDL_LAYOUTS_EXTRA_MODULES . DIRECTORY_SEPARATOR .  basename( dirname(__FILE__) ) );
if( ! defined('WPDDL_THEME_INTEGRATION_REL') ) define('WPDDL_THEME_INTEGRATION_REL', WPDDL_LAYOUTS_EXTRA_MODULES_REL. '/' .  basename( dirname(__FILE__) )  );
if( ! defined('WPDDL_INTEGRATION_STATIC_REL') ) define('WPDDL_INTEGRATION_STATIC_REL', WPDDL_THEME_INTEGRATION_REL . '/' . 'resources');

require_once 'theme_integration_autoloader.php';

/**
 * Prepare the autoloader for theme integration classes.
 *
 * @since 1.5
 */
function wpddl_load_layouts_integration_autoloader() {
	$autoloader = WPDDL_Theme_Integration_Autoloader::getInstance();
	$autoloader->addPaths( array(
		dirname( __FILE__ ) . '/autoloaded',
	) );

	$autoloader->addPrefix( 'WPDDL' );
	$autoloader->addPrefix( 'WPDDL_Integration' );
}


function wpddl_init_layouts_integration_support() {

	// This MUST be incremented whenever any API changes in this "extra" module.
	$integration_support_version = 1;

	wpddl_load_layouts_integration_autoloader();

	/**
	 * Indicate that the theme integration support is ready.
	 *
	 * It must be set up before WPDDL_Layouts::wpddl_init() is called.
	 *
	 * @param string $version Layouts version
	 * @param int $integration_support_version Version of the Theme integration API
	 * @since 1.5
	 */
	do_action( 'wpddl_theme_integration_support_ready', WPDDL_VERSION, $integration_support_version );

}


// Now the magic happens
wpddl_init_layouts_integration_support();


/**
 * PHP 5.2 support.
 *
 * get_called_class() is only in PHP >= 5.3, this is a workaround.
 * This function is needed by WPDDL_Theme_Integration_Abstract.
 */
if ( !function_exists( 'get_called_class' ) ) {
	function get_called_class() {
		$bt = debug_backtrace();
		$l = 0;
		do {
			$l++;
			$lines = file( $bt[ $l ]['file'] );
			$callerLine = $lines[ $bt[ $l ]['line'] - 1 ];
			preg_match( '/([a-zA-Z0-9\_]+)::' . $bt[ $l ]['function'] . '/', $callerLine, $matches );
		} while( $matches[1] === 'parent' && $matches[1] );

		return $matches[1];
	}
}


// Compatibility fix for Layouts 1.15. Replace by toolset_* equivalents when it's safe.

function wpddl_getpost( $key, $default = '', $valid = null ) {
	return wpddl_getarr( array_map('sanitize_text_field',$_POST), $key, $default, $valid );
}


function wpddl_getget( $key, $default = '', $valid = null ) {
	return wpddl_getarr( array_map('sanitize_text_field', $_GET ), $key, $default, $valid );
}


function wpddl_getarr( &$source, $key, $default = '', $valid = null ) {
	if( isset( $source[ $key ] ) ) {
		$val = $source[ $key ];
		if( is_array( $valid ) && !in_array( $val, $valid ) ) {
			return $default;
		}

		return $val;
	} else {
		return $default;
	}
}