<?php
/**
 * Plugin initial API.
 *
 * @package Toolset Views
 * @since 3.0
 */

/**
 * Filter to check whether Views is installed.
 *
 * @note Can not be moved to a controlled API call because of the prefix.
 * @since 1.9
 */
add_filter( 'toolset_is_views_available', '__return_true' );

/**
 * Return the current Views version installed.
 *
 * @note Can not be moved to a controlled API call because of the prefix.
 * @since 2.1
 */
add_filter( 'toolset_views_version_installed', 'wpv_return_installed_version' );

/**
 * Return the currently installed Views version number.
 *
 * @param string $version Dummy variable from a filter.
 * @return string
 * @since 2.1
 * @see toolset_views_version_installed
 */
function wpv_return_installed_version( $version = '' ) {
	return WPV_VERSION;
};

/**
 * Check if Views Lite version active
 *
 * @return bool
 * @since unknown
 * @see wpv_is_views_lite
 */
function wpv_is_views_lite() {
	if ( defined( 'WPV_LITE' ) ) {
		return WPV_LITE;
	}
	return false;
}

/**
 * Return the flavour of the current Views version installed.
 *
 * @since 3.0
 */
add_filter( 'toolset_views_flavour_installed', 'wpv_get_views_flavour' );

/**
 * Check the Views flavour active.
 *
 * @param string $flavour Dummy variable from a filter.
 * @return string 'classic'|'blocks'
 * @since 3.0
 */
function wpv_get_views_flavour( $flavour = 'classic' ) {
	if ( defined( 'WPV_FLAVOUR' ) ) {
		return WPV_FLAVOUR;
	}
	return 'classic';
}

/**
 * Return the flavour variation of the current Views version installed.
 *
 * @note Can not be moved to a controlled API call because of the prefix.
 * @since 3.0
 */
add_filter( 'toolset_views_editing_experience', 'wpv_get_views_editing_experience' );

/**
 * Check the Views editing experience
 *
 * @param string $variation Dummy variable from a filter.
 * @return string 'classic'|'blocks'|'mixed'
 * @since 3.0
 */
function wpv_get_views_editing_experience( $variation = '' ) {
	$settings = WPV_Settings::get_instance();
	return $settings->editing_experience;
}
