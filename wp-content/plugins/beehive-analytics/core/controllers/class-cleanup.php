<?php
/**
 * The data cleanup class for Beehive.
 *
 * @link    http://wpmudev.com
 * @since   3.3.5
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Controllers
 */

namespace Beehive\Core\Controllers;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class Cleanup
 *
 * @package Beehive\Core\Controllers
 */
class Cleanup {

	/**
	 * Cleanup all settings options added by Beehive.
	 *
	 * This will clean settings on current site or on network site.
	 *
	 * @param bool $network Network flag.
	 *
	 * @since 3.3.5
	 *
	 * @return void
	 */
	public static function clean_settings( $network = false ) {
		// Setting names to delete.
		$options = array(
			'beehive_settings',
			'beehive_version',
			'beehive_owner_user',
		);

		// Delete on main site and network.
		foreach ( $options as $option ) {
			$network ? delete_site_option( $option ) : delete_option( $option );
		}
	}

	/**
	 * Cleanup the transient data added by Beehive for cache.
	 *
	 * This is will clear site transient if it's network admin or
	 * clear the site transients on single or subsite.
	 *
	 * @param bool $network Network flag.
	 *
	 * @since 3.3.5
	 *
	 * @return void
	 */
	public static function clean_transients( $network = false ) {
		global $wpdb;

		if ( $network ) {
			// Delete all transients.
			// phpcs:ignore
			$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_beehive_%' OR meta_key LIKE '_site_transient_timeout_beehive_%'" );
		} else {
			// Delete all transients.
			// phpcs:ignore
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_beehive_%' OR option_name LIKE '_transient_timeout_beehive_%'" );
		}
	}
}
