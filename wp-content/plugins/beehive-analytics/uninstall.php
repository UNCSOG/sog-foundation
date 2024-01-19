<?php
/**
 * Uninstall process for the plugin.
 *
 * @link    http://wpmudev.com
 * @since   3.3.5
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Uninstall
 */

use Beehive\Core\Controllers\Cleanup;
use Beehive\Core\Controllers\Settings;

// If uninstall not called from WordPress exit.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit();

// Include autoloader.
require_once plugin_dir_path( __FILE__ ) . '/core/utils/autoloader.php';

// Check if it's a multisite.
$multisite = is_multisite();

// Get cleanup flag.
$keep = Settings::instance()->get( 'settings', 'data', $multisite );

// No need to clean anything.
if ( $keep ) {
	return;
}

// Delete all custom options.
Cleanup::clean_settings( $multisite );

// Delete all transients.
Cleanup::clean_transients( $multisite );

// Additional cleanup for subsites.
if ( $multisite ) {
	$offset = 0;
	$limit  = 100;

	global $wpdb;

	// Get all blog ids and do cleanup.
	// phpcs:ignore
	while ( $blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs} LIMIT $offset, $limit" ) ) {
		if ( $blogs ) {
			foreach ( $blogs as $blog ) {
				// Switch to blog.
				switch_to_blog( $blog->blog_id );

				// Delete all custom options.
				Cleanup::clean_settings();

				// Delete all transients.
				Cleanup::clean_transients();
			}

			// Restore old site.
			restore_current_blog();
		}

		// Update the offset.
		$offset += $limit;
	}
}
