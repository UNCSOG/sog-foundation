<?php
/**
Plugin Name: Toolset Access
Plugin URI: http://toolset.com/home/types-access/?utm_source=accessplugin&utm_campaign=access&utm_medium=release-notes-plugins-list&utm_term=Visit plugin site
Description: User access control and roles management
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 2.8.16
 */


if ( ! defined( 'TACCESS_VERSION' ) ) {
	define( 'TACCESS_VERSION', '2.8.16' );
}

if ( ! defined( 'TACCESS_PLUGIN_PATH' ) ) {
	define( 'TACCESS_PLUGIN_PATH', dirname( __FILE__ ) );
}

define( 'TACCESS_PLUGIN', plugin_basename( __FILE__ ) );
define( 'TACCESS_PLUGIN_FOLDER', basename( TACCESS_PLUGIN_PATH ) );
define( 'TACCESS_PLUGIN_NAME', TACCESS_PLUGIN_FOLDER . '/' . basename( __FILE__ ) );
define( 'TACCESS_PLUGIN_BASENAME', TACCESS_PLUGIN );
define( 'TACCESS_PLUGIN_URL', plugins_url() . '/' . TACCESS_PLUGIN_FOLDER );
define( 'WPCF_ACCESS_ABSPATH_', TACCESS_PLUGIN_PATH );
define( 'WPCF_ACCESS_RELPATH_', TACCESS_PLUGIN_URL );

global $wpcf_access;
// release notes
if ( ! defined( 'TACCESS_RELEASE_NOTES' ) ) {
	define(
		'TACCESS_RELEASE_NOTES',
		'https://toolset.com/version/access-'
		. str_replace( '.', '-', TACCESS_VERSION )
		. '/?utm_source=typesplugin&utm_campaign=types&utm_medium=release-notes-admin-notice&utm_term=Access%20'
		. TACCESS_VERSION
		. '%20release%20notes'
	);
}

if ( ! defined( 'ACCESS_TEMPLATES' ) ) {
	define( 'ACCESS_TEMPLATES', dirname( __FILE__ ) . '/application/views' );
}

add_filter( 'plugin_row_meta', 'otg_access_plugin_row_meta', 10, 4 );
/**
 * Add a link to the Access release notes on the plugin row.
 *
 * @param array $plugin_meta
 * @param string $plugin_file
 * @param array $plugin_data
 * @param string $status
 *
 * @return array
 *
 * @since 2.0
 */
function otg_access_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {//phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	$this_plugin = basename( TACCESS_PLUGIN_PATH ) . '/types-access.php';
	$release_text = sprintf( __( 'Access %s release notes', 'wpcf-access' ), TACCESS_VERSION ); //phpcs:ignore
	if ( $plugin_file === $this_plugin ) {
		$plugin_meta[] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			'https://toolset.com/version/access-'
			. str_replace( '.', '-', TACCESS_VERSION )
			.
			'/?utm_source=accessplugin&utm_campaign=access&utm_medium=release-notes-plugins-list&utm_term=Access '
			. TACCESS_VERSION
			. ' release notes',
			$release_text
		);
	}

	return $plugin_meta;
}


require_once( dirname( __FILE__ ) . '/application/bootstrap.php' );//phpcs:ignore

//
// Activation and deactivation hooks must be defined in the main file.
//
register_activation_hook( __FILE__, 'taccess_on_activate' );
add_action( 'admin_init', 'taccess_on_activate_action' );
