<?php
/**
 * The installer class of the plugin.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Controllers
 */

namespace Beehive\Core\Controllers;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Helpers\General;
use Beehive\Core\Utils\Abstracts\Base;

/**
 * Class Installer
 *
 * @package Beehive\Core\Controllers
 */
class Installer extends Base {

	/**
	 * Run plugin activation scripts.
	 *
	 * If plugin is activated for the first time, setup the
	 * version details, and other flags.
	 * If the Pro version is being activated, check if free version is
	 * active and then deactivate it.
	 *
	 * @since 3.2.0
	 */
	public function activate() {
		// Current plugin version.
		if ( $this->is_network() ) {
			$version = get_site_option( 'beehive_version', '1.0.0' );
		} else {
			$version = get_option( 'beehive_version', '1.0.0' );
		}

		// Set plugin owner.
		$this->set_plugin_owner();
		// Assign capabilities.
		$this->assign_caps();

		/**
		 * Action hook to execute after activation.
		 *
		 * @since 3.2.0
		 *
		 * @param int $version     Old version.
		 * @param int $new_version New version.
		 */
		do_action( 'beehive_after_activate', $version, BEEHIVE_VERSION );
	}

	/**
	 * Upgrade if we are updating from old version.
	 *
	 * This method will only update the version number if the
	 * installation is new.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function upgrade() {
		// Current plugin version.
		if ( $this->is_network() ) {
			$version = get_site_option( 'beehive_version' );
		} else {
			$version = get_option( 'beehive_version' );
		}

		// Not an upgrade.
		if ( empty( $version ) ) {
			return;
		}

		// Upgrade to 3.4.
		if ( version_compare( $version, '3.4.0', '<' ) ) {
			$this->upgrade_3_4();
		}

		// If new installation or older versions.
		if ( BEEHIVE_VERSION !== $version ) {
			// Mark the plugin version.
			if ( $this->is_network() ) {
				update_site_option( 'beehive_version', BEEHIVE_VERSION );
			} else {
				update_option( 'beehive_version', BEEHIVE_VERSION );
			}

			// Assign capabilities.
			$this->assign_caps();

			/**
			 * Action hook to execute after upgrade.
			 *
			 * @since 3.2.0
			 *
			 * @param int $old_version Old version.
			 * @param int $new_version New version.
			 */
			do_action( 'beehive_after_upgrade', $version, BEEHIVE_VERSION );
		}
	}

	/**
	 * Upgrade to 3.4.0 version.
	 *
	 * @since      3.3.8
	 * @deprecated 3.4.10
	 *
	 * @return void
	 */
	private function upgrade_3_4() {
		// Set statistics type to ua on existing sites.
		beehive_analytics()->settings->update( 'statistics_type', 'ua', 'google', $this->is_network() );
	}

	/**
	 * Set a user id identify who activated the plugin.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	private function set_plugin_owner() {
		// Get current user.
		$user = get_current_user_id();

		if ( ! empty( $user ) ) {
			// If network activated in multisite.
			if ( $this->is_network() ) {
				update_site_option( 'beehive_owner_user', $user );
			} else {
				// Single site.
				update_option( 'beehive_owner_user', $user );
			}
		}
	}

	/**
	 * Set our custom capability to admin user by default.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	private function assign_caps() {
		// Not needed in network admin.
		if ( ! $this->is_network() ) {
			global $wp_roles;

			// Get the role object.
			$role_object = $wp_roles->get_role( 'administrator' );

			// Assign settings and analytics caps.
			if ( ! empty( $role_object ) ) {
				$role_object->add_cap( Capability::SETTINGS_CAP );
				$role_object->add_cap( Capability::ANALYTICS_CAP );
			}
		}
	}

	/**
	 * On upgrade call this if you want to show the welcome modal.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	private function show_welcome() {
		// Set welcome modal.
		beehive_analytics()->settings->update(
			'show_welcome',
			true,
			'misc',
			General::is_networkwide()
		);
	}
}
