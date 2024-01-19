<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;
use OTGS\Toolset\Access\Models\WPMLSettings;

/**
 * Access API functions
 *
 * Class AccessApi
 *
 * @package OTGS\Toolset\Access\Controllers
 */
class AccessApi {

	private static $instance;


	/**
	 * @return AccessApi
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function initialize() {
		self::get_instance();
		/**
		 * Filter to check permission for post types
		 *
		 * @param $has_permission | required
		 * @param $post_type (slug)(string) | required
		 * @param $option_name (publish, edit_own, edit_any, delete_own, delete_any, read) | optional | default: read
		 * @param $user | optional, default: $current_user
		 * @param $language (code)| optional, default: default language, example: en
		 *
		 * @return (boolean)true|false
		 */
		add_filter( 'toolset_access_api_get_post_type_permissions', array(
			$this,
			'get_post_type_permissions_process',
		), 10, 5 );

		/**
		 * Filter to check permission for taxonomies
		 *
		 * @param $has_permission | required
		 * @param $taxonomy (slug) | required
		 * @param $option_name (assign_terms, delete_terms, edit_terms, manage_terms) | optional | default: manage_terms
		 * @param $user (object) | optional, default: $current_user
		 *
		 * @return (boolean)true|false
		 */
		add_filter( 'toolset_access_api_get_taxonomy_permissions', array(
			$this,
			'get_taxonomy_permissions_process',
		), 10, 4 );

		/**
		 * Filter to check permission for specific post
		 *
		 * @param $has_permission | required
		 * @param $post_id (string) | $post(object) | required
		 * @param $option_name (read, edit) | optional | default: read
		 * @param $user | optional, default: $current_user
		 * @param $language (code)| optional, default: default language, example: en
		 *
		 * @return (boolean)true|false
		 */
		add_filter( 'toolset_access_api_get_post_permissions', array(
			$this,
			'get_post_permissions_process',
		), 10, 5 );
	}


	public function __construct() {

	}


	/**
	 * @param boolean $has_permission
	 * @param string $post
	 * @param string $option_name
	 * @param string $user
	 * @param string $language
	 *
	 * @return bool
	 */
	public function get_post_permissions_process( $has_permission = false, $post = '', $option_name = 'read', $user = '', $language = '' ) {
		if ( empty( $post ) ) {
			return $has_permission;
		}

		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! isset( $post->ID ) ) {
			return $has_permission;
		}

		if ( in_array( $option_name, array( 'edit', 'read' ) ) === false ) {
			return $has_permission;
		}

		$access_settings = Settings::get_instance();
		$access_roles = UserRoles::get_instance();

		if ( $access_roles->is_administrator() ) {
			return true;
		}

		$converted_caps = array( 'edit_own' => 'edit_posts', 'edit_any' => 'edit_others_posts', 'read' => 'read' );

		if ( empty( $user ) ) {
			global $current_user;
			$user = $current_user;
		}

		if ( ! is_object( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}

		if ( ! is_object( $user ) ) {
			return $has_permission;
		}
		if ( $option_name == 'edit' ) {
			if ( $post->post_author == $user->ID ) {
				$option_name = 'edit_own';
			} else {
				$option_name = 'edit_any';
			}
		}

		$role = $access_roles->get_current_user_roles( true, $user );
		$roles = $access_roles->get_current_user_roles( false, $user );
		$post_type = $post->post_type;

		global $wpcf_access, $wp_roles;

		//If Access settings not set yet use capabilities from role
		if ( ! isset( $wpcf_access->settings->types ) ) {
			if ( $role == 'guest' ) {
				return ( $option_name == 'read' );
			}
			$role_caps = $wp_roles->roles;
			if ( isset( $role_caps[ $role ]['capabilities'][ $converted_caps[ $option_name ] ] ) ) {
				return true;
			} else {
				return false;
			}
		}

		$types_settings = $access_settings->get_types_settings();

		if ( $option_name == 'read' ) {
			$group = get_post_meta( $post->ID, '_wpcf_access_group', true );
			if ( ! empty( $group ) ) {
				if ( isset( $types_settings[ $group ] ) ) {
					$post_type_roles = $types_settings[ $group ]['permissions'][ $option_name ]['roles'];
					$is_role_managed_by_access = array_intersect( $roles, $post_type_roles );
					if (
						! empty( $is_role_managed_by_access )
						|| $this->is_specific_user_has_permissions( $post_type_roles )
					) {
						return true;
					} else {
						return false;
					}
				}
			}
		}

		// Check WPML permission
		if ( $wpcf_access->wpml_installed ) {
			$language_settings = $wpcf_access->language_permissions;
			if ( empty( $language ) ) {
				$wpml_settings = WPMLSettings::get_instance();
				$language = $wpml_settings->get_default_language();
			}

			if ( isset( $language_settings[ $post_type ][ $language ][ $option_name ] ) ) {
				$post_type_roles = $language_settings[ $post_type ][ $language ][ $option_name ]['roles'];
				$is_role_managed_by_access = array_intersect( $roles, $post_type_roles );
				if (
					! empty( $is_role_managed_by_access ) || $this->is_specific_user_has_permissions( $post_type_roles )
				) {
					return true;
				} else {
					return false;
				}
			}
		}

		// If settings set and post type managed by Access
		if ( isset( $types_settings[ $post_type ] )
			&& $types_settings[ $post_type ]['mode'] === 'permissions'
			&& isset( $types_settings[ $post_type ]['permissions'][ $option_name ]['roles'] ) ) {
			$post_type_roles = $types_settings[ $post_type ]['permissions'][ $option_name ]['roles'];
			$is_role_managed_by_access = array_intersect( $roles, $post_type_roles );
			if (
				! empty( $is_role_managed_by_access ) || $this->is_specific_user_has_permissions( $post_type_roles )
			) {
				return true;
			} else {
				return false;
			}
		} // If settings set and post not type managed by Access
		elseif ( ( isset( $types_settings[ $post_type ] )
				&& $types_settings[ $post_type ]['mode'] != 'permissions'
				&& isset( $types_settings['post'] )
				&& $types_settings[ $post_type ]['mode'] == 'permissions' )
			|| ( ! isset( $types_settings[ $post_type ] )
				&& isset( $types_settings['post'] )
				&& $types_settings['post']['mode'] == 'permissions' ) ) {

			$post_type_roles = isset( $types_settings['post']['permissions'][ $option_name ]['roles'] )
				? $types_settings['post']['permissions'][ $option_name ]['roles'] : array();
			$is_role_managed_by_access = array_intersect( $roles, $post_type_roles );
			if (
				! empty( $is_role_managed_by_access ) || $this->is_specific_user_has_permissions( $post_type_roles )
			) {
				return true;
			} else {
				return false;
			}
		} // Use role capabilities
		else {
			if ( $role == 'guest' ) {
				return ( $option_name == 'read' );
			}
			$role_caps = $wp_roles->roles;
			if ( isset( $role_caps[ $role ]['capabilities'][ $converted_caps[ $option_name ] ] ) ) {
				return true;
			} else {
				return false;
			}
		}
	}


	/**
	 * @param boolean $has_permission
	 * @param string $taxonomy
	 * @param string $option_name
	 * @param string $user
	 *
	 * @return bool
	 */
	public function get_taxonomy_permissions_process( $has_permission = false, $taxonomy = '', $option_name = 'manage_terms', $user = '' ) {

		if ( empty( $taxonomy ) ) {
			return $has_permission;
		}

		if ( in_array( $option_name, array(
				'assign_terms',
				'delete_terms',
				'edit_terms',
				'manage_terms',
			) ) === false ) {
			return $has_permission;
		}

		if ( empty( $user ) ) {
			global $current_user;
			$user = $current_user;
		}

		if ( ! is_object( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}

		if ( ! is_object( $user ) ) {
			return $has_permission;
		}

		$converted_caps = array(
			'assign_terms' => 'edit_posts',
			'delete_terms' => 'manage_categories',
			'edit_terms' => 'manage_categories',
			'manage_terms' => 'manage_categories',
		);

		$access_roles = UserRoles::get_instance();
		if ( $access_roles->is_administrator() ) {
			return true;
		}

		$role = $access_roles->get_current_user_roles( true, $user );
		$roles = $access_roles->get_current_user_roles( false, $user );

		global $wpcf_access, $wp_roles;
		$tax_settings = $wpcf_access->settings->tax;

		if ( ! isset( $wpcf_access->settings->tax ) ) {
			if ( $role == 'guest' ) {
				return false;
			}
			$role_caps = $wp_roles->roles;
			if ( isset( $role_caps[ $role ]['capabilities'][ $converted_caps[ $option_name ] ] ) ) {
				return true;
			} else {
				return false;
			}
		}

		if ( isset( $tax_settings[ $taxonomy ] ) && $tax_settings[ $taxonomy ]['mode'] == 'permissions' ) {
			$tax_roles = $tax_settings[ $taxonomy ]['permissions'][ $option_name ]['roles'];
			$is_role_managed_by_access = array_intersect( $roles, $tax_roles );
			if (
				! empty( $is_role_managed_by_access )
				|| $this->is_specific_user_has_permissions( $tax_settings[ $taxonomy ]['permissions'][ $option_name ] )
			) {
				return true;
			} else {
				return false;
			}
		} // If settings set and tax not type managed by Access
		elseif ( ( isset( $tax_settings[ $taxonomy ] )
				&& $tax_settings[ $taxonomy ]['mode'] != 'permissions'
				&& isset( $tax_settings['category'] )
				&& $tax_settings[ $taxonomy ]['mode'] == 'permissions' )
			|| ( ! isset( $tax_settings[ $taxonomy ] )
				&& isset( $tax_settings['category'] )
				&& $tax_settings[ $taxonomy ]['mode'] == 'permissions' ) ) {
			$tax_roles = $tax_settings['category']['permissions'][ $option_name ]['roles'];
			$is_role_managed_by_access = array_intersect( $roles, $tax_roles );
			if (
				! empty( $is_role_managed_by_access )
				|| $this->is_specific_user_has_permissions( $tax_settings['category']['permissions'][ $option_name ] )
			) {
				return true;
			} else {
				return false;
			}
		} // Use role capabilities
		else {
			if ( $role == 'guest' ) {
				return false;
			}
			global $wp_roles;
			$role_caps = $wp_roles->roles;
			if ( isset( $role_caps[ $role ]['capabilities'][ $converted_caps[ $option_name ] ] ) ) {
				return true;
			} else {
				return false;
			}
		}


	}


	/**
	 * @param boolean $has_permission
	 * @param string $post_type
	 * @param string $option_name
	 * @param string $user
	 * @param string $language
	 *
	 * @return bool
	 */
	public function get_post_type_permissions_process( $has_permission = false, $post_type = '', $option_name = 'read', $user = '', $language = '' ) {

		if ( empty( $post_type ) ) {
			return $has_permission;
		}

		$access_settings = Settings::get_instance();
		$access_roles = UserRoles::get_instance();
		if ( $access_roles->is_administrator() ) {
			return true;
		}

		$_post_types = $access_settings->get_post_types();
		if ( ! isset( $_post_types[ $post_type ] ) || $_post_types[ $post_type ]->public != 1 ) {
			return $has_permission;
		}
		if ( in_array( $option_name, array(
				'publish',
				'edit_own',
				'edit_any',
				'delete_own',
				'delete_any',
				'read',
			) ) === false ) {
			return $has_permission;
		}

		$converted_caps = array(
			'publish' => 'publish_posts',
			'edit_own' => 'edit_posts',
			'edit_any' => 'edit_others_posts',
			'delete_own' => 'delete_posts',
			'delete_any' => 'delete_others_posts',
			'read' => 'read',
		);

		if ( empty( $user ) ) {
			global $current_user;
			$user = $current_user;
		}

		if ( ! is_object( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}

		if ( ! is_object( $user ) ) {
			return $has_permission;
		}

		$roles = $access_roles->get_current_user_roles( false, $user );
		$role = $access_roles->get_current_user_roles( true, $user );

		global $wpcf_access, $wp_roles;

		// If Access settings not set yet use capabilities from role
		if ( ! isset( $wpcf_access->settings->types ) ) {
			if ( in_array( 'guest', $roles ) ) {
				return ( $option_name == 'read' );
			}
			$role_caps = $wp_roles->roles;

			return ( isset( $role_caps[ $role ]['capabilities'][ $converted_caps[ $option_name ] ] ) );
		}

		if ( $wpcf_access->wpml_installed ) {
			$language_settings = $wpcf_access->language_permissions;
			if ( empty( $language ) ) {
				$language = apply_filters( 'wpml_setting', '', 'default_language' );
			}

			if ( isset( $language_settings[ $post_type ][ $language ][ $option_name ] ) ) {
				$post_type_roles = $access_settings->toolset_getnest( $language_settings, array( $post_type, $language, $option_name, 'roles' ), array() );
				$is_role_managed_by_access = array_intersect( $roles, $post_type_roles );
				if (
					$is_role_managed_by_access
					|| self::is_specific_user_has_permissions( $language_settings[ $post_type ][ $language ][ $option_name ] )
				) {
					return true;
				} else {
					return false;
				}
			}
		}


		$types_settings = $wpcf_access->settings->types;
		// If settings set and post type managed by Access
		if ( isset( $types_settings[ $post_type ] )
			&& $types_settings[ $post_type ]['mode'] == 'permissions'
			&& isset( $types_settings[ $post_type ]['permissions'][ $option_name ]['roles'] ) ) {
			$post_type_roles = $types_settings[ $post_type ]['permissions'][ $option_name ]['roles'];
			$is_role_managed_by_access = array_intersect( $roles, $post_type_roles );
			if (
				! empty( $is_role_managed_by_access )
				|| $this->is_specific_user_has_permissions( $types_settings[ $post_type ]['permissions'][ $option_name ] )
			) {
				return true;
			} else {
				return false;
			}
		} // If settings set and post not type managed by Access
		elseif ( ( isset( $types_settings[ $post_type ] )
				&& $types_settings[ $post_type ]['mode'] != 'permissions'
				&& isset( $types_settings['post'] )
				&& $types_settings[ $post_type ]['mode'] == 'permissions' )
			|| ( ! isset( $types_settings[ $post_type ] )
				&& isset( $types_settings['post'] )
				&& $types_settings['post']['mode'] == 'permissions' ) ) {
			$post_type_roles = $access_settings->toolset_getnest( $types_settings, array( 'post', 'permissions', $option_name, 'roles' ), array() );
			$is_role_managed_by_access = array_intersect( $roles, $post_type_roles );
			if ( ! empty( $is_role_managed_by_access )
				|| $this->is_specific_user_has_permissions( $types_settings['post']['permissions'][ $option_name ] )
			) {
				return true;
			} else {
				return false;
			}
		}
		// Use role capabilities
		if ( $role == 'guest' ) {
			return ( $option_name == 'read' );
		}

		$role_caps = $wp_roles->roles;
		if ( isset( $role_caps[ $role ]['capabilities'][ $converted_caps[ $option_name ] ] ) ) {
			return true;
		} else {
			return false;
		}

	}


	/**
	 * Check if current user in specific users list
	 *
	 * @param array $setting
	 * @return bool
	 */
	private function is_specific_user_has_permissions( $setting ) {
		global $current_user;
		if ( isset( $setting['users'] ) && is_array( $setting['users'] ) ) {
			if ( in_array( $current_user->ID, $setting['users'] ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
