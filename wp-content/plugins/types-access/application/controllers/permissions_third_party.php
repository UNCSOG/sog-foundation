<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;

/**
 *
 * Main third party controller
 *
 * Class PermissionsThirdParty
 *
 * @package OTGS\Toolset\Access\Controllers
 * @since 2.7
 */
class PermissionsThirdParty {

	private static $instance;


	/**
	 * @return PermissionsThirdParty
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Class initialization
	 */
	public static function initialize() {
		self::get_instance();
	}


	/**
	 * @param array $allcaps
	 * @param array $caps
	 * @param array $args
	 * @param mixed $user
	 *
	 * @return array
	 */
	public function get_third_party_caps( $allcaps, $caps, $args, $user ) {
		$settings = Settings::get_instance();
		$access_roles = UserRoles::get_instance();
		$access_capabilities = Capabilities::get_instance();
		$roles = $access_roles->get_current_user_roles( false, $user );

		$requested_capability = $args[0];

		$cached_caps = \Access_Cacher::get( 'access_third_party_caps_cached', 'access_cache_user_has_cap' );
		if ( false === $cached_caps ) {
			return $allcaps;
		}
		if ( ! isset( $cached_caps[ $requested_capability ] ) ) {
			return $allcaps;
		}
		$third_party_settings = $settings->get_third_party_asettings();
		$cap_info = $cached_caps[ $requested_capability ];
		$capability_users = array();
		if ( isset( $third_party_settings[ $cap_info['area'] ][ $cap_info['group'] ]['permissions'][ $requested_capability ]['roles'] ) ) {
			$cap_array = $third_party_settings[ $cap_info['area'] ][ $cap_info['group'] ]['permissions'][ $requested_capability ];
			$capability_roles = $cap_array['roles'];
			$capability_users = ( isset( $cap_array['users'] ) ? $cap_array['users'] : array() );
		} else {
			$default_role = $cap_info['default_role'];
			$capability_roles = $access_roles->get_roles_by_role( $default_role );
		}

		$roles_check = ( array_intersect( $roles, $capability_roles ) || in_array( $user->ID, $capability_users, true ) ? true : false );
		$allcaps = $access_capabilities->add_or_remove_cap( $allcaps, $requested_capability, $roles_check, $user );

		return $allcaps;
	}
}
