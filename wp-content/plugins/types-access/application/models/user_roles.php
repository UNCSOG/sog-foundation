<?php

namespace OTGS\Toolset\Access\Models;

use OTGS\Toolset\Access\Models\Settings as Settings;

/**
 * Wordpress Roles Helper class.
 *
 * @package OTGS\Toolset\Access
 * @since 2.7
 */
class UserRoles {

	private static $instance;

	public $wp_roles;


	/**
	 * @return UserRoles
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function initialize() {
		self::get_instance();
	}


	/**
	 * UserRoles constructor.
	 */
	function __construct() {
		$this->wp_roles = $this->get_editable_roles();
	}


	/**
	 * Get registered Wordpress roles
	 *
	 * @return object
	 */
	public function get_editable_roles() {
		if ( ! is_null( $this->wp_roles ) ) {
			return $this->wp_roles;
		}
		$this->wp_roles = array();
		if ( ! function_exists( 'get_editable_roles' ) ) {
			if ( ! function_exists( 'wp_roles' ) && file_exists( ABSPATH . '/wp-admin/includes/user.php' ) ) {
				include_once ABSPATH . '/wp-admin/includes/user.php';
				$this->wp_roles = get_editable_roles();
			} elseif ( function_exists( 'wp_roles' ) ) {
				$wp_roles_list = wp_roles();
				$this->wp_roles = $wp_roles_list->roles;
			}
		}

		return $this->wp_roles;
	}


	/**
	 * Reset editable roles
	 */
	public function reload_access_roles() {
		$this->wp_roles = null;
		$this->wp_roles = $this->get_editable_roles();
	}


	/**
	 * Get current user role/ or roles
	 *
	 * @param bool $single_role
	 * @param string $user
	 *
	 * @return array|string
	 */
	public function get_current_user_roles( $single_role = false, $user = '' ) {

		$cache_key = 'access_user_roles_' . $single_role . '_' . serialize( $user );
		$user_roles = \Access_Cacher::get( $cache_key, 'access_user_roles' );
		if ( $user_roles && ! isset( $_GET['toolset_access_preview'] ) ) {
			return $user_roles;
		}

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			wp_cookie_constants();
			if ( file_exists( ABSPATH . "wp-includes/pluggable.php" ) ) {
				include( ABSPATH . "wp-includes/pluggable.php" );
			}
		}
		if ( empty( $user ) ) {
			$current_user = wp_get_current_user();
		} else {
			$current_user = $user;
		}

		// For super-admins set temporary role to administrator when a user not assigned to the current site
		if ( is_multisite() ) {
			if ( isset( $current_user->roles ) && is_super_admin() && empty( $current_user->roles ) ){
				$current_user->roles[] = 'administrator';
			}
		}

		$roles = ( isset( $current_user->roles ) && ! empty( $current_user->roles ) ? $current_user->roles
			: array( 'guest' ) );
		if ( ! $single_role ) {
			$user_roles = $roles;
			if ( $user_roles == '' ) {
				$user_roles = 'guest';
			}
		} else {
			$roles = array_values( $roles );
			$user_roles = array_shift( $roles );
			if ( $user_roles == '' ) {
				$user_roles = array( 'guest' );
			}
		}
		\Access_Cacher::set( $cache_key, $user_roles, 'access_user_roles' );
		return $user_roles;
	}

	/**
	 * Check if current user has a role Administrator
	 * @param string $user
	 *
	 * @return bool|string
	 */
	public function is_administrator( $user = '' ){
		$cache_key = 'access_is_administrator_' . serialize( $user );
		$is_administrator = \Access_Cacher::get( $cache_key, 'access_is_administrator' );
		if ( false === $is_administrator ) {
			$roles = $this->get_current_user_roles( false, $user );
			$main_role = $this->get_current_user_roles( true, $user );
			if ( 'administrator' === $main_role || in_array( 'administrator', $roles ) ) {
				$is_administrator = 'true';
			} else {
				$is_administrator = 'false';
			}
			\Access_Cacher::set( $cache_key, $is_administrator, 'access_is_administrator' );
		}

		$is_administrator = ( $is_administrator == 'true') ? true : false ;

		return $is_administrator;
	}

	/**
	 * @return string
	 */
	public function get_main_role() {
		return $this->get_current_user_roles( true );
	}

	/**
	 * Turn a role into a meaningful capability that defines that role, if needed.
	 *
	 * @param string $role Role to convert; note that it can already be a capability!
	 * @return string
	 * @since 2.7.3
	 */
	public function maybe_translate_role_to_capability( $role ) {
		$default_capability = 'edit_posts';

		switch ( $role ) {
			case 'administrator':
				return 'delete_users';
			case 'editor':
				return 'edit_others_posts';
			case 'author':
				return 'edit_published_posts';
			case 'controbutor':
				return 'edit_posts';
			case 'subscriber':
			case 'guest':
				return 'read';
		}

		if ( wp_roles()->is_role( $role ) ) {
			return $default_capability;
		}

		// We passed an actual capability!!
		return $role;
	}


	/**
	 * Convert minimal role from Types/Cred to minimal caps and return list of roles
	 *
	 * @param string $role
	 * @param string $cap
	 * @return array
	 */
	public function get_roles_by_role( $role, $cap = '' ) {
		$settings = Settings::get_instance();
		$key = 'toolset_access_roles_list_' . md5( $role . $cap );
		$roles_list = \Access_Cacher::get( $key );
		if ( false === $roles_list ) {
			$ordered_roles = $settings->get_ordered_wp_roles();
			$required_cap = $cap;
			if ( empty( $cap ) ) {
				$required_cap = $this->maybe_translate_role_to_capability( $role );
			}

			$roles_list = array();
			foreach ( $ordered_roles as $role => $role_data ) {
				if ( isset( $role_data['capabilities'][ $required_cap ] ) ) {
					$roles_list[] = $role;
				}
			}
			\Access_Cacher::set( $key, $roles_list );
		}

		return $roles_list;
	}


	/**
	 * Get all capabilities array
	 *
	 * @param array $managed_caps
	 * @return array
	 */
	public function get_roles_capabilities_list( $managed_caps ) {
		global $wp_roles;
		$capabilities_list = array();
		foreach ( $wp_roles->roles as $role => $role_info ) {
			$role_caps = $role_info['capabilities'];
			foreach ( $role_caps as $cap => $cap_status ) {
				if ( $cap_status == 1
					&& in_array( $cap, $capabilities_list ) === false
					&& in_array( $cap, $managed_caps ) === false ) {
					$capabilities_list[] = $cap;
				}
			}
		}

		return $capabilities_list;
	}


	/**
	 * Maps role to level.
	 *
	 * @param string $role
	 * @return string
	 */
	public function role_to_level( $role ) {
		$map = $this->role_to_level_map();

		return isset( $map[ $role ] ) ? $map[ $role ] : '';
	}


	/**
	 * Maps role to level.
	 *
	 * Returns an array of roles => levels
	 * As this is used a lot of times, we added caching
	 *
	 * @return array $map
	 */
	public function role_to_level_map() {
		$access_cache_map_group = 'access_cache_map_group';
		$access_cache_map_key = md5( 'access::role_to_level_map' );
		$map = \Access_Cacher::get( $access_cache_map_key, $access_cache_map_group );
		if ( false === $map ) {
			$acess_capabilities = Capabilities::get_instance();
			$default_roles = $acess_capabilities->get_default_roles();

			$map = array(
				'administrator' => 'level_10',
				'editor' => 'level_7',
				'author' => 'level_2',
				'contributor' => 'level_1',
				'subscriber' => 'level_0',
			);
			require_once ABSPATH . '/wp-admin/includes/user.php';
			$roles = $this->get_editable_roles();
			foreach ( $roles as $role => $data ) {
				$role_data = get_role( $role );
				if ( ! empty( $role_data ) ) {
					for ( $index = 10; $index > - 1; $index -- ) {
						if ( isset( $data['capabilities'][ 'level_' . $index ] ) ) {
							$map[ $role ] = 'level_' . $index;
							break;
						}
					}
					// try to deduce the required level
					if ( ! isset( $map[ $role ] ) ) {
						foreach ( $default_roles as $r ) {
							if ( $role_data->has_cap( $r ) ) {
								$map[ $role ] = $map[ $r ];
								break;
							}
						}
					}
					// finally use a default here, level_0, subscriber
					if ( ! isset( $map[ $role ] ) ) {
						$map[ $role ] = 'level_0';
					}
				}
			}
			\Access_Cacher::set( $access_cache_map_key, $map, $access_cache_map_group );
		}

		return $map;
	}


	/**
	 * @since 2.2
	 * Return  array of roles with same or highest level
	 *
	 * @param string $role
	 * @return array
	 */
	public function get_roles_by_minimal_role( $role ) {

		$access_settings = Settings::get_instance();
		$ordered_roles = $access_settings->order_wp_roles();
		$level_map = $this->role_to_level_map();
		$output_roles = array();
		if ( $role == 'guest' && ! isset( $level_map[ $role ] ) ) {
			$role_level = 'level_0';
			$output_roles[] = 'guest';
		} else {
			if ( isset( $level_map[ $role ] ) ) {
				$role_level = $level_map[ $role ];
			} else {
				$role_level = 0;
			}
		}

		foreach ( $ordered_roles as $role => $role_data ) {
			if ( isset( $role_data['capabilities'][ $role_level ] ) ) {
				$output_roles[] = $role;
			}
		}

		return $output_roles;
	}


	/**
	 * Determines highest ranked role and it's level.
	 *
	 * @param int $user_id
	 * @param string $rank
	 * @return array
	 * @deprecated Not used anywhere
	 */
	public function rank_user( $user_id, $rank = 'high' ) {
		global $wpcf_access;
		$access_settings = Settings::get_instance();
		static $cache = array();
		$user = get_userdata( $user_id );
		if ( empty( $user ) ) {
			$wpcf_access->user_rank['not_found'][ $user_id ] = array( 'guest', false );

			return array( 'guest', false );
		}
		if ( ! empty( $cache[ $user_id ] ) ) {
			return $cache[ $user_id ];
		}
		$roles = $access_settings->wpcf_get_editable_roles();
		$levels = $this->order_roles_by_level( $roles );
		$role = false;
		$level = false;
		foreach ( $levels as $_levels => $_level ) {
			$current_level = $_levels;
			foreach ( $_level as $_role => $_role_data ) {
				if ( in_array( $_role, $user->roles ) ) {
					$role = $_role;
					$level = $current_level;
					if ( $rank != 'low' ) {
						$cache[ $user_id ] = array( $role, $level );
						$wpcf_access->user_rank[ $user_id ] = $cache[ $user_id ];

						return $cache[ $user_id ];
					}
				}
			}
		}
		if ( ! $role || ! $level ) {
			return array( 'guest', false );
		}
		$cache[ $user_id ] = array( $role, $level );

		$wpcf_access->user_rank[ $user_id ] = $cache[ $user_id ];

		return array( $role, $level );
	}


	/**
	 * Orders roles by level.
	 *
	 * @param array $roles
	 *
	 * @return array
	 */
	public static function order_roles_by_level( $roles ) {
		$ordered_roles = array();
		for ( $index = 10; $index > - 1; $index -- ) {
			foreach ( $roles as $role => $data ) {
				if ( isset( $data['capabilities'][ 'level_' . $index ] ) ) {
					$ordered_roles[ $index ][ $role ] = $data;
					unset( $roles[ $role ] );
				}
			}
		}
		$ordered_roles['not_set'] = ! empty( $roles ) ? $roles : array();

		return $ordered_roles;
	}

}
