<?php

namespace OTGS\Toolset\Access\Models;

use OTGS\Toolset\Access\Models\UserRoles as UserRoles;
use OTGS\Toolset\Access\Models\WPMLSettings as WPMLSettings;
use OTGS\Toolset\Access\Viewmodels\PermissionsGui;

/**
 * Class Settings
 *
 * @package OTGS\Toolset\Access\Models
 * @since 2.7
 */
class Settings {

	const CACHE_GROUP = __CLASS__;

	private static $instance;

	private $post_type_settings;

	private $roles;

	private $access_roles;

	private $current_user_roles;

	private $main_role;

	/**
	 * @return Settings
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
	 * Settings constructor.
	 */
	function __construct() {
		
		$user_roles = UserRoles::get_instance();
		$this->current_user_roles = $user_roles->get_current_user_roles();

		$this->main_role = $user_roles->get_main_role();
		$this->load_settings( true );
		add_filter( 'toolset_access_check_if_post_type_managed', array(
			$this,
			'is_post_type_managed_api_callback',
		), null, 2 );
		add_filter( 'toolset_access_get_allowed_post_groups', array( $this, 'get_allowed_post_groups' ), null, 2 );
		$this->roles = $this->wpcf_get_editable_roles();
	}


	/**
	 * Return true if WPML installed and configured
	 *
	 * @return mixed
	 */
	public function is_wpml_installed() {
		global $wpcf_access;

		return $wpcf_access->wpml_installed;
	}

	/**
	 * Callback for the filter toolset_access_check_if_post_type_managed.
	 *
	 * @param bool $status
	 * @param string $post_type
	 * @return bool
	 */
	public function is_post_type_managed_api_callback( $status, $post_type ) {
		$status = $this->is_post_type_managed( $post_type );
		return $status;
	}


	/**
	 * @param string $post_type
	 *
	 * @return bool
	 */
	function is_post_type_managed( $post_type ) {
		$types = $this->get_types_settings();

		if ( isset( $types[ $post_type ] ) && 'permissions' === $types[$post_type]['mode'] ) {
			return true;
		}

		return false;
	}


	/**
	 * @param array $groups
	 * @param bool $user_can_read
	 *
	 * @return array
	 */
	function get_allowed_post_groups( $groups, $user_can_read ) {
		$types = $this->get_types_settings();

		$this->access_roles = UserRoles::get_instance();

		$current_role = $this->access_roles->get_main_role();
		foreach ( $types as $group_slug => $group_data ) {
			if ( strpos( $group_slug, 'wpcf-custom-group-' ) === 0 ) {
				$read = $group_data['permissions']['read']['roles'];
				if ( in_array( $current_role, $read ) && $user_can_read ) {
					$groups[] = $group_slug;
				} elseif ( ! in_array( $current_role, $read ) && ! $user_can_read ) {
					$groups[] = $group_slug;
				}
			}
		}

		return $groups;
	}


	/**
	 * @return mixed
	 */
	function get_post_types_settings() {
		$skip_post_types = array( '_custom_read_errors', '_custom_read_errors_value' );
		if ( ! empty( $this->post_type_settings ) ) {
			return $this->post_type_settings;
		} else {
			$post_type_permissions = $this->get_types_settings();
			foreach ( $post_type_permissions as $post_type => $permissions ) {
				if ( in_array( $post_type, $skip_post_types ) ) {
					continue;
				}
				if ( isset( $permissions['mode'] ) ) {
					$this->post_type_settings[ $post_type ] = array( 'mode' => $permissions['mode'] );
				} else {
					$this->post_type_settings[ $post_type ] = array( 'mode' => 'not_managed' );
				}
			}
		}

		return $this->post_type_settings;
	}


	/**
	 * @return mixed
	 */
	function get_access_settings( $force = false, $full = false ) {
		$options = $this->get_access_options( $force, $full );

		return $options->settings;
	}


	/**
	 * @param bool $force Force settings reload
	 * @param bool $full Get full array of settings
	 *
	 * @return array
	 */
	function get_types_settings( $force = false, $full = false ) {
		$settings = $this->get_access_settings( $force, $full );

		return isset( $settings->types ) ? $settings->types : array();
	}


	/**
	 * @param bool $force Force settings reload
	 * @param bool $full Get full array of settings
	 *
	 * @return mixed
	 */
	function get_tax_settings( $force = false, $full = false ) {
		$settings = $this->get_access_settings( $force, $full );

		return isset( $settings->tax ) ? $settings->tax : array();
	}


	/**
	 * @param bool $force Force settings reload
	 * @param bool $full Get full array of settings
	 *
	 * @return mixed
	 */
	function get_third_party_asettings( $force = false, $full = false ) {
		$settings = $this->get_access_settings( $force, $full );

		return isset( $settings->third_party ) ? $settings->third_party : array();
	}


	/**
	 * @return mixed
	 */
	function get_access_options( $force = false, $full = false ) {
		global $wpcf_access;
		if ( empty( $wpcf_access ) || ( ! empty( $wpcf_access ) && '' == $wpcf_access->settings ) || $force ) {
			$wpcf_access = $this->load_settings( true, $full );
		}

		return $wpcf_access;
	}


	/**
	 * @return mixed
	 */
	function get_language_permissions() {
		$options = $this->get_access_options();

		return $options->language_permissions;
	}


	/**
	 * @param $user_roles
	 * @param $roles
	 *
	 * @return bool
	 */
	function roles_in_array( $user_roles, $roles ) {
		foreach ( $user_roles as $index => $role ) {
			if ( in_array( $role, $roles ) !== false ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Generate empty access settings object
	 *
	 * @param bool $force
	 * @param bool $full
	 *
	 * @return \stdClass
	 */
	public function load_settings( $force = false, $full = false ) {
		global $wpcf_access;
		if ( ! empty( $wpcf_access ) && false === $force ) {
			return $wpcf_access;
		}

		$wpcf_access = new \stdClass();

		// WPML related properties
		$wpcf_access->wpml_installed = false;
		$wpcf_access->wpml_installed_groups = false;
		$wpcf_access->active_languages = array();
		$wpcf_access->current_language = null;
		$wpcf_access->language_permissions = array();


		if ( ! isset( $wpcf_access->settings ) ) {
			$wpcf_access->settings = new \stdClass;
		}

		if ( ! isset( $wpcf_access->settings->types ) ) {
			$wpcf_access->settings->types = array();
		}

		if ( ! isset( $wpcf_access->settings->tax ) ) {
			$wpcf_access->settings->tax = array();
		}

		if ( ! isset( $wpcf_access->settings->third_party ) ) {
			$wpcf_access->settings->third_party = array();
		}

		if ( ! isset( $wpcf_access->rules ) ) {
			$wpcf_access->rules = new \stdClass;
		}

		$access_settings = get_option( 'toolset-access-options' );

		if ( empty( $access_settings ) ) {
			$access_types = get_option( 'wpcf-access-types', array() );
			$access_taxs = get_option( 'wpcf-access-taxonomies', array() );
			$access_third_party = get_option( 'wpcf-access-3rd-party', array() );
			$access_settings = $this->convert_options_format( $access_types, $access_taxs, $access_third_party );
			$this->updateAccessSettings( $access_settings );
		}
		$wpcf_access->settings = $access_settings;
		if ( ! $full ) {
			$wpcf_access->settings = $this->optimize_settings( $wpcf_access->settings );
		}

		if ( ! class_exists( 'WPMLSettings' ) ) {
			require_once( TACCESS_PLUGIN_PATH . '/application/models/wpml_settings.php' );
		}
		$wpml_settings = WPMLSettings::get_instance();
		$wpcf_access->wpml_installed = apply_filters( 'wpml_setting', false, 'setup_complete' );

		add_filter( 'toolset_access_filter_is_wpml_installed', array( $wpml_settings, 'is_wpml_installed' ) );
		add_action( 'wpml_loaded', array( $wpml_settings, 'toolset_access_wpml_loaded' ) );
		add_action( 'plugins_loaded', array( $wpml_settings, 'toolset_load_wpml_groups_caps' ), 999 );

		return $wpcf_access;
	}


	/**
	 * @param $update_settings
	 *
	 * @return mixed
	 */
	public function updateAccessTypes( $update_settings ) {
		$settings = $this->load_settings( true, true );
		$settings = $settings->settings;
		$settings->types = $update_settings;
		$settings = $this->fix_settings_array( $settings );

		return update_option( 'toolset-access-options', $settings );
	}


	/**
	 * @param $update_settings
	 *
	 * @return mixed
	 */
	public function updateAccessTaxonomies( $update_settings ) {
		$settings = $this->load_settings( true, true );
		$settings = $settings->settings;
		$settings->tax = $update_settings;
		$settings = $this->fix_settings_array( $settings );

		return update_option( 'toolset-access-options', $settings );
	}


	/**
	 * @param $update_settings
	 *
	 * @return mixed
	 */
	public function updateAccessThirdParty( $update_settings ) {
		$settings = $this->load_settings( true, true );
		$settings = $settings->settings;
		$settings->third_party = $update_settings;
		$settings = $this->fix_settings_array( $settings );

		return update_option( 'toolset-access-options', $settings );
	}


	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function fix_settings_array( $settings ) {

		if ( ! isset( $settings->third_party ) ) {
			$settings->third_party = array();
		}

		if ( ! isset( $settings->types ) ) {
			$settings->types = array();
		}

		if ( ! isset( $settings->tax ) ) {
			$settings->tax = array();
		}

		return $settings;
	}


	/**
	 * Removes all roles from permissions array excerpt roles assigned to the current user
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	function optimize_settings( $settings ) {
		foreach ( $settings as $area_type => $area_type_settings ) {
			foreach ( $area_type_settings as $area => $area_settings ) {
				//Remove permissions array when an arrea not managed by Access
				if ( isset( $area_settings['permissions'] ) && isset( $area_settings['mode'] )
					&& 'permissions'
					!= $area_settings['mode'] ) {
					if ( strpos( $area, 'wpcf-wpml-group-' ) !== 0 && strpos( $area, 'wpcf-custom-group' ) !== 0 ) {
						unset( $settings->{$area_type}[ $area ]['permissions'] );
					}
				}

				if ( isset( $area_settings['permissions'] ) ) {
					foreach ( $area_settings['permissions'] as $permission => $roles ) {
						if ( isset( $roles['roles'] ) ) {
							$area_roles = array_intersect( $roles['roles'], $this->current_user_roles );
							$settings->{$area_type}[ $area ]['permissions'][ $permission ]['roles'] = $area_roles;
						}
					}
				}
			}
		}

		return $settings;
	}


	/**
	 * sanitise and save custom error and custom archive errors
	 *
	 * @since 2.2.3
	 *
	 * @param string $key
	 * @param array $types
	 * @param array $settings_access_types_previous
	 *
	 * @return array
	 */
	public function update_custom_error( $key, $types, $settings_access_types_previous ) {
		if ( ! empty( $types ) ) {
			foreach ( $types as $type => $data ) {
				$type = str_replace( '%', '(-37;-)', $type );
				$type = sanitize_text_field( $type );
				$type = str_replace( '(-37;-)', '%', $type );
				$settings_access_types_previous = $this->clean_empty_custom_error( $settings_access_types_previous, $data, $key, $type );
			}
			$this->updateAccessTypes( $settings_access_types_previous );
		}

		return $settings_access_types_previous;
	}


	/**
	 * Remove role index from an array if custom error is empty
	 *
	 * @param array $settings_access_types_previous
	 * @param array $data
	 * @param string $key
	 * @param string $type
	 *
	 * @return array
	 */
	private function clean_empty_custom_error( $settings_access_types_previous, $data, $key, $type ) {
		foreach ( $data['permissions']['read'] as $role => $value ) {
			if ( empty( $value ) ) {
				unset( $data['permissions']['read'][ $role ] );
			}
		}
		if ( empty( $data['permissions']['read'] ) ) {
			unset( $settings_access_types_previous[ $key ][ $type ] );
		} else {
			$settings_access_types_previous[ $key ][ $type ] = $data;
		}

		return $settings_access_types_previous;
	}


	/**
	 * @param $settings array
	 *
	 * @return boolean
	 */
	public function updateAccessSettings( $settings ) {
		return update_option( 'toolset-access-options', $settings );
	}


	/**
	 * Maps role to level.
	 *
	 * Returns an array of roles => levels
	 * As this is used a lot of times, we added caching
	 *
	 * @return array $map
	 */
	public function wpcf_access_role_to_level_map() {
		$access_cache_map_group = 'access_cache_map_group';
		$access_cache_map_key = md5( 'access::role_to_level_map' );
		$map = \Access_Cacher::get( $access_cache_map_key, $access_cache_map_group );

		if ( false === $map ) {
			$default_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
			$map = array(
				'administrator' => 'level_10',
				'editor' => 'level_7',
				'author' => 'level_2',
				'contributor' => 'level_1',
				'subscriber' => 'level_0',
			);

			require_once ABSPATH . '/wp-admin/includes/user.php';

			$roles = $this->wpcf_get_editable_roles();

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
	 * Track fetching editable roles.
	 *
	 * Sometimes WP includes get_editable_role func too late.
	 * Especially if user is not logged.
	 *
	 * @return array
	 */
	public function wpcf_get_editable_roles() {
		if ( ! is_null( $this->roles ) ) {
			return $this->roles;
		}
		global $wpcf_access;
		if ( ! function_exists( 'get_editable_roles' ) ) {
			if ( ! function_exists( 'wp_roles' ) ) {
				if ( file_exists( '/wp-admin/includes/user.php' ) ) {
					include_once ABSPATH . '/wp-admin/includes/user.php';
				}
			} else {
				$wp_roles = wp_roles();

				return $wp_roles->roles;
			}

		}

		if ( ! function_exists( 'get_editable_roles' ) ) {
			$wpcf_access->errors['get_editable_roles-missing_func'] = debug_backtrace();

			return array();
		}

		return get_editable_roles();
	}


	/**
	 * @param array $group_permissions
	 *
	 * @return array
	 * @since 2.2
	 * format minimal role to list of roles
	 */
	public function format_permissions_array( $group_permissions = array() ) {

		$ordered_roles = $this->order_wp_roles();
		$level_map = $this->wpcf_access_role_to_level_map();
		if ( isset( $group_permissions['permissions'] ) ) {
			foreach ( $group_permissions['permissions'] as $permission_name => $permissions ) {
				$minimal_role = $permissions['role'];
				if ( $minimal_role != 'guest' ) {
					$minimal_role_level = $level_map[ $minimal_role ];
					$minimal_level = str_replace( 'level_', '', $minimal_role_level );
				} else {
					$minimal_role_level = 'level_0';
					$minimal_level = 0;
				}
				$permissions['roles'] = array();
				foreach ( $ordered_roles as $role_name => $role_array ) {
					if ( $minimal_role == 'guest' ) {
						$permissions['roles'][] = $role_name;
					} else {
						if ( $role_name == 'guest' ) {
							continue;
						}
						if ( isset( $role_array['capabilities'][ $minimal_role_level ] ) ) {
							$permissions['roles'][] = $role_name;
						}
						//Set Roles with no level to level 0 permissions
						if ( $minimal_level == 0 && ! isset( $role_array['capabilities'][ $minimal_role_level ] ) ) {
							$permissions['roles'][] = $role_name;
						}
					}
				}
				if ( $minimal_role == 'guest' ) {
					$permissions['roles'][] = 'guest';
				}
				$group_permissions['permissions'][ $permission_name ] = $permissions;
			}
		}

		return $group_permissions;
	}


	/**
	 * Order wp_roles array
	 * order: administrator, super users, admins, editors, all other users
	 *
	 * @param bool $invalidate
	 *
	 * @return array
	 * @since 2.2
	 */
	public function order_wp_roles( $invalidate = false ) {
		return $this->get_ordered_wp_roles( $invalidate );
	}


	/**
	 * @since 2.2
	 *
	 * @param array $access_types
	 * @param array $access_tax
	 * @param array $access_third_party
	 *
	 * @return stdClass
	 * convert Access settings to new format
	 */
	public function convert_options_format( $access_types = array(), $access_tax = array(), $access_third_party = array() ) {

		$access_settings = new \stdClass;


		$access_settings->types = array();
		$access_settings->tax = array();
		$access_settings->third_party = array();

		//Parse post types
		if ( is_array( $access_types ) ) {
			foreach ( $access_types as $group => $group_permissions ) {
				$group_permissions = $this->format_permissions_array( $group_permissions );
				$access_settings->types[ $group ] = $group_permissions;
			}
		}

		//Parse taxonomies
		if ( is_array( $access_tax ) ) {
			foreach ( $access_tax as $group => $group_permissions ) {
				$group_permissions = $this->format_permissions_array( $group_permissions );
				$access_settings->tax[ $group ] = $group_permissions;
			}
		}

		//Parse third party
		if ( is_array( $access_third_party ) ) {
			foreach ( $access_third_party as $group => $group_permissions ) {
				foreach ( $group_permissions as $sub_group => $sub_group_permissions ) {
					$sub_group_permissions = $this->format_permissions_array( $sub_group_permissions );
					$access_settings->third_party[ $group ][ $sub_group ] = $sub_group_permissions;
				}
			}
		}

		return $access_settings;
	}


	private function order_roles( $roles ) {
		$group1 = $group2 = $group3 = $group4 = $group5 = $group6 = $group7 = array();
		foreach ( $roles as $role => $role_info ) {
			if ( $role == 'administrator' ) {
				$group1[ $role ] = $role_info;
				$group1[ $role ]['permissions_group'] = 1;
			} elseif ( isset( $role_info['capabilities']['manage_network'] )
				|| isset( $role_info['capabilities']['manage_sites'] )
				|| isset( $role_info['capabilities']['manage_network_users'] )
				|| isset( $role_info['capabilities']['manage_network_plugins'] )
				|| isset( $role_info['capabilities']['manage_network_themes'] )
				|| isset( $role_info['capabilities']['manage_network_options'] ) ) {
				$group2[ $role ] = $role_info;
				$group2[ $role ]['permissions_group'] = 2;
			} elseif ( isset( $role_info['capabilities']['delete_users'] ) ) {
				$group3[ $role ] = $role_info;
				$group3[ $role ]['permissions_group'] = 3;
			} elseif ( isset( $role_info['capabilities']['edit_others_pages'] )
				|| isset( $role_info['capabilities']['edit_others_posts'] ) ) {
				$group4[ $role ] = $role_info;
				$group4[ $role ]['permissions_group'] = 4;
			} elseif ( isset( $role_info['capabilities']['edit_published_pages'] )
				|| isset( $role_info['capabilities']['edit_published_posts'] ) ) {
				$group5[ $role ] = $role_info;
				$group5[ $role ]['permissions_group'] = 5;
			} elseif ( isset( $role_info['capabilities']['edit_pages'] )
				|| isset( $role_info['capabilities']['edit_posts'] ) ) {
				$group6[ $role ] = $role_info;
				$group6[ $role ]['permissions_group'] = 6;
			} else {
				$group7[ $role ] = $role_info;
				$group7[ $role ]['permissions_group'] = 7;
			}
		}

		return array( $group1, $group2, $group3, $group4, $group5, $group6, $group7 );
	}

	/**
	 * @since 2.2
	 * Order wp_roles array
	 * order: administrator, super users, admins, editors, all other users
	 */
	public function get_ordered_wp_roles( $invalidate = false, $groups_array = false ) {

		$ordered_roles = \Access_Cacher::get( 'toolset_access_ordered_roles_' . $groups_array );
		if ( false === $ordered_roles || $invalidate = true ) {
			global $wp_roles;
			if ( ! isset( $wp_roles ) || empty( $wp_roles ) ) {
				$wp_roles = new \WP_Roles();
			}

			$roles  = $wp_roles->roles;

			ksort( $roles );
			list( $group1, $group2, $group3, $group4, $group5, $group6, $group7 ) = $this->order_roles( $roles );
			if ( $groups_array ) {
				$ordered_roles = array( $group1, $group2, $group3, $group4, $group5, $group6, $group7 );
			} else {
				$ordered_roles          = array_merge( $group1, $group2, $group3, $group4, $group5, $group6, $group7 );
				$ordered_roles['guest'] = array(
					'name'              => __( 'Guest', 'wpcf-access' ),
					'permissions_group' => 6,
					'capabilities'      => array( 'read' => 1 ),
				);
			}
			\Access_Cacher::set( 'toolset_access_ordered_roles_' . $groups_array, $ordered_roles );
		}

		return $ordered_roles;
	}


	/**
	 * @param bool $args
	 *
	 * @return mixed
	 */
	public function get_post_types( $args = false ) {
		if ( false === $args ) {
			$args = class_exists( 'bbPress' ) ? array() : array( 'show_ui' => true );
		}

		return get_post_types( $args, 'objects' );
	}


	/**
	 * getPostTypesNames
	 *
	 * @since 2.1
	 */
	public function get_post_types_names( $args = false ) {
		if ( false === $args ) {
			$args = array( 'show_ui' => true );
		}

		return get_post_types( $args, 'names' );
	}


	/**
	 * @param bool $args
	 *
	 * @return mixed
	 */
	public function get_taxonomies( $args = false ) {
		if ( false === $args ) {
			$args = array( 'show_ui' => true );
		}

		return get_taxonomies( $args, 'objects' );
	}


	/**
	 * @param bool $args
	 *
	 * @return mixed
	 * @since 2.1
	 */
	public function get_taxonomies_names( $args = false ) {
		if ( false === $args ) {
			$args = array( 'show_ui' => true );
		}

		return get_taxonomies( $args, 'names' );
	}


	/**
	 * Auxiliary function
	 * Convert data to array
	 *
	 * @param mixed $data  Arbitrary data to convert
	 * @param int   $depth Recursion depth
	 *
	 * @return array|bool|mixed
	 */
	public function object_to_array( $data, $depth = 1 ) {
		if ( empty( $data ) ) {
			return $data;
		}

		$cache_key = '';

		if ( 1 === $depth ) {
			$cache_key = md5( json_encode( $data ) );
			$cache     = wp_cache_get( $cache_key, self::CACHE_GROUP );
			if ( $cache ) {
				return $cache;
			}
		}

		if ( $depth > 4 ) {
			return array();
		}

		if ( is_array( $data ) || is_object( $data ) ) {
			$result = array();
			foreach ( $data as $key => $value ) {
				if (
					$value instanceof \Closure
				) {
					continue;
				}
				if ( is_string( $value ) ) {
					$result[ $key ] = $value;
				}
				$result[ $key ] = $this->object_to_array( $value, $depth + 1 );
			}

			if ( 1 === $depth ) {
				wp_cache_set( $cache_key, $result, self::CACHE_GROUP );
			}

			return $result;
		}

		if ( 1 === $depth ) {
			wp_cache_set( $cache_key, $data, self::CACHE_GROUP );
		}

		return $data;
	}

	/**
	 * @return mixed
	 */
	public function get_post_types_with_custom_groups() {

		global $wpdb;
		$values_to_prepare = array();
		$values_to_prepare[] = '_wpcf_access_group';
		$values_to_prepare[] = 'publish';

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT posts.post_type
				FROM {$wpdb->posts} AS posts
				LEFT JOIN {$wpdb->postmeta} AS meta
				ON (
					posts.ID = meta.post_id
					AND meta.meta_key = %s
				)
				WHERE posts.post_status = %s
				AND meta.meta_value IS NOT NULL",
				$values_to_prepare
			)
		);

		return $results;
	}


	/**
	 * Parses submitted data.
	 *
	 * @param $data
	 * @param $caps
	 * @param bool $custom
	 * @param array $saved_data
	 *
	 * @return array
	 */
	public function parse_permissions( $data, $caps, $custom = false, $saved_data = array() ) {
		$permissions = array();
		$access_roles = UserRoles::get_instance();
		if ( empty( $data['permissions'] ) ) {
			return $permissions;
		}

		foreach ( $data['permissions'] as $cap => $data_cap ) {
			$cap = esc_attr( $cap );
			$users = isset( $saved_data['permissions'][ $cap ]['users'] ) ? $saved_data['permissions'][ $cap ]['users']
				: array();
			// Check if submitted
			if ( isset( $data['permissions'][ $cap ] ) ) {
				$permissions[ $cap ] = $data['permissions'][ $cap ];
			} else {
				$permissions[ $cap ] = $data_cap;
			}

			if ( ! isset( $permissions[ $cap ]['roles'] ) || empty( $permissions[ $cap ]['roles'] ) ) {
				$permissions[ $cap ] = array_merge( $permissions[ $cap ], array( 'roles' => $access_roles->get_roles_by_role( 'administrator' ) ) );
			}

			// Make sure only pre-defined are used on ours, third-party rules
			// can have anything they want.
			if ( ! $custom && ! isset( $caps[ $cap ] ) ) {
				unset( $permissions[ $cap ] );
				continue;
			}

			if ( ! empty( $users ) ) {
				$permissions[ $cap ]['users'] = $users;
			}
		}

		return $permissions;
	}


	public function getAccessRoles() {
		return get_option( 'wpcf-access-roles', array() );
	}


	public function updateAccessRoles( $settings ) {
		$updated = update_option( 'wpcf-access-roles', $settings );
		do_action( 'otg_access_action_access_roles_updated', $settings, $updated );

		return $updated;
	}


	/*
	 * @since 2.2
	 * Add new access role to access settings
	 * $role - role slug
	 * $copy_of_role - copy permissions from that role
	 * If $copy_of_role is empty, create permissions depend of role capabilities
	 */
	public function add_role_to_settings( $role, $copy_of_role = '' ) {

		if ( empty( $role ) ) {
			return;
		}

		if ( ! empty( $copy_of_role ) ) {
			$settings = $this->get_access_settings( true, true );
			$new_settings = $settings;

			foreach ( $new_settings->types as $group => $group_permissions ) {
				$group_permissions = $this->permissions_array_add_role( $group_permissions, $role, $copy_of_role );
				$new_settings->types[ $group ] = $group_permissions;
			}

			//Parse taxonomies
			foreach ( $new_settings->tax as $group => $group_permissions ) {
				$group_permissions = $this->permissions_array_add_role( $group_permissions, $role, $copy_of_role );
				$new_settings->tax[ $group ] = $group_permissions;
			}

			//Parse third party
			foreach ( $new_settings->third_party as $group => $group_permissions ) {
				foreach ( $group_permissions as $sub_group => $sub_group_permissions ) {
					$sub_group_permissions = $this->permissions_array_add_role( $sub_group_permissions, $role, $copy_of_role );
					$new_settings->third_party[ $group ][ $sub_group ] = $sub_group_permissions;
				}

			}

			$this->updateAccessSettings( $new_settings );

		} else {
			/* TODO Gen: every page load check if new roles were added
			 If yes, scan capabilites and add to access settings
			 -manage_categories = Add/edit taxonomies
			 -edit_posts - add posts and cpt (_pages)
			 -delete_posts (_pages)
			 -edit_others_posts (_pages)
			 -delete_others_posts (_pages)
			 - publish_posts, publish_pages
			 WPML, posts groups, Types groups, Cred forms will set to subscriber before will be changed by admin
			*/
		}


	}


	/*
     * @since 2.2
     * Add $role to permissions and default permissions array
     */
	public function permissions_array_add_role( $group_permissions, $role, $copy_of_role ) {

		if ( isset( $group_permissions['permissions'] ) ) {
			foreach ( $group_permissions['permissions'] as $permission_name => $permissions ) {
				if ( isset( $permissions['roles'] )
					&& is_array( $permissions['roles'] )
					&& in_array( $copy_of_role, $permissions['roles'] ) !== false ) {
					$permissions['roles'][] = $role;
				}
				$group_permissions['permissions'][ $permission_name ] = $permissions;
			}

		}

		return $group_permissions;
	}


	/**
	 * @param $text
	 *
	 * @return mixed
	 */
	public function esc_like( $text ) {
		global $wpdb;
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			return $wpdb->esc_like( $text );
		} else {
			return like_escape( esc_sql( $text ) );
		}
	}


	/**
	 * Gets attachment parent post type.
	 *
	 * @return boolean
	 */
	public function attachment_parent_type() {
		if ( isset( $_POST['attachment_id'] ) ) {
			$post_id = intval( $_POST['attachment_id'] );
		} elseif ( isset( $_GET['attachment_id'] ) ) {
			$post_id = intval( $_GET['attachment_id'] );
		} else {
			return false;
		}
		$post = get_post( $post_id );
		if ( ! empty( $post->post_parent ) ) {
			$post_parent = get_post( $post->post_parent );
			if ( ! empty( $post_parent->post_type ) ) {
				return $post_parent->post_type;
			}
		}

		return false;
	}


	/**
	 * Determines post ID.
	 *
	 * @global type $post
	 * @global type $pagenow
	 * @return bool|int
	 */

	public static function determine_post_id() {
		global $post;
		if ( ! empty( $post ) ) {
			return $post->ID;
		} elseif ( isset( $_GET['post'] ) ) {
			return intval( $_GET['post'] );
		} elseif ( isset( $_POST['post'] ) ) {
			return intval( $_POST['post'] );
		} elseif ( isset( $_GET['post_id'] ) ) {
			return intval( $_GET['post_id'] );
		} elseif ( isset( $_POST['post_id'] ) ) {
			return intval( $_POST['post_id'] );
		} elseif ( defined( 'DOING_AJAX' ) && isset( $_SERVER['HTTP_REFERER'] ) ) {
			$split = explode( '?', $_SERVER['HTTP_REFERER'] );
			if ( isset( $split[1] ) ) {
				parse_str( $split[1], $vars );
				if ( isset( $vars['post'] ) ) {
					return intval( $vars['post'] );
				} elseif ( isset( $vars['post_id'] ) ) {
					return intval( $vars['post_id'] );
				}
			}
		}

		return false;
	}


	/**
	 * Determines post type.
	 *
	 * @param int $post_id
	 *
	 * @global type $post
	 * @global type $pagenow
	 * @return string
	 */
	public function determine_post_type( $post_id = '' ) {
		global $post;
		$post_type = false;
		if ( empty( $post_id ) ) {
			$post_id = $this->determine_post_id();
		}
		if ( ! empty( $post ) || ! empty( $post_id ) ) {
			if ( get_post( $post_id ) ) {
				return get_post_type( $post_id );
			}
			$post_type = get_post_type( $post );
		}
		elseif ( isset( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
			$post_type = get_post_type( $post_id );
		} elseif ( isset( $_GET['post_id'] ) ) {
			$post_id = intval( $_GET['post_id'] );
			$post_type = get_post_type( $post_id );
		} elseif ( isset( $_POST['post_id'] ) ) {
			$post_id = intval( $_POST['post_id'] );
			$post_type = get_post_type( $post_id );
		} elseif ( isset( $_POST['post'] ) ) {
			$post_id = intval( $_POST['post'] );
			$post_type = get_post_type( $post_id );
		} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$split = explode( '?', $_SERVER['HTTP_REFERER'] );
			if ( isset( $split[1] ) ) {
				parse_str( $split[1], $vars );
				if ( isset( $vars['post_type'] ) ) {
					$post_type = $vars['post_type'];
				} elseif ( isset( $vars['post'] ) ) {
					$post_type = get_post_type( $vars['post'] );
				} elseif ( strpos( $split[1], 'post-new.php' ) !== false ) {
					$post_type = 'post';
				}
			} elseif ( strpos( $_SERVER['HTTP_REFERER'], 'post-new.php' ) !== false
				|| strpos( $_SERVER['HTTP_REFERER'], 'edit-tags.php' ) !== false
				|| strpos( $_SERVER['HTTP_REFERER'], 'edit.php' ) !== false ) {
				$post_type = 'post';
			}
		}

		return $post_type;
	}


	/**
	 * Remove deprecated settings from existing array
	 */
	public function remove_depricated_settings() {
		$access_settings = get_option( 'toolset-access-options' );

		if ( ! is_array( $access_settings ) && ! is_object( $access_settings ) ) {
			return;
		}
		if ( ! class_exists('PermissionsGui')){
			require_once( TACCESS_PLUGIN_PATH . '/application/viewmodels/permissions_gui.php' );
		}
		$custom_errors_keys = array(
			PermissionsGui::CUSTOM_ERROR_SINGLE_POST_TYPE,
			PermissionsGui::CUSTOM_ERROR_SINGLE_POST_VALUE,
			PermissionsGui::CUSTOM_ERROR_ARCHIVE_TYPE,
			PermissionsGui::CUSTOM_ERROR_ARCHIVE_VALUE,
		);


		foreach ( $access_settings as $group => $types ) {
			foreach ( $types as $access_type => $settings ) {
				//empty custom errors
				if ( in_array( $access_type, $custom_errors_keys ) ) {
					foreach ( $settings as $custom_error_cpt => $custom_array_array ) {
						foreach ( $custom_array_array['permissions']['read'] as $role => $value ) {
							if ( empty( $value ) ) {
								unset( $access_settings->{$group}[ $access_type ][ $custom_error_cpt ]['permissions']['read'][ $role ] );
							}
						}
						if ( empty( $custom_array_array['permissions']['read'] ) ) {
							unset( $access_settings->{$group}[ $access_type ][ $custom_error_cpt ] );
						}
					}
				}
				//Remove predefined permissions array
				if ( array_key_exists( '__permissions', $settings ) ) {
					unset( $access_settings->{$group}[ $access_type ]['__permissions'] );
				}

				if ( $group === 'third_party' ) {
					foreach ( $settings as $area => $area_settings ) {
						if ( array_key_exists( '__permissions', $area_settings ) ) {
							unset( $access_settings->{$group}[ $access_type ][ $area ]['__permissions'] );
						}

					}
				}
			}
		}
		update_option( 'toolset-access-options', $access_settings );
	}


	/**
	 * @param bool $overwrite
	 *
	 * @return array
	 */
	public function wpcf_access_get_areas( $overwrite = false ) {
		static $areas = null;

		if ( is_null( $areas ) || $overwrite ) {
			$areas = apply_filters( 'types-access-show-ui-area', array() );
		}

		return $areas;
	}


	/**
	 * @return array|null
	 */
	public function get_taxonomies_shared() {
		global $wpcf_access;
		static $cache = null;
		static $failed = array();

		if ( is_null( $cache ) ) {
			$found = array();
			$taxonomies = $this->get_taxonomies();
			foreach ( $taxonomies as $slug => $data ) {
				if ( count( $data->object_type ) > 1 ) {
					$found[ $slug ] = $data->object_type;
				}
			}
			$cache = $wpcf_access->shared_taxonomies = $found;
		}

		return $cache;
	}


	/**
	 * Checks if taxonomy is shared.
	 *
	 * @param string $taxonomy
	 * @return bool
	 */
	public function is_taxonomy_shared( $taxonomy ) {
		$shared = $this->get_taxonomies_shared();

		return ! empty( $shared[ $taxonomy ] );
	}


	/**
	 * Sets taxonomy mode.
	 *
	 * @param string $taxonomy
	 * @param string $mode
	 * @return string
	 */
	public function get_taxonomy_mode( $taxonomy, $mode = 'follow' ) {
		// default to 'not_managed' if shared to have uniform handling of imported caps
		return $this->is_taxonomy_shared( $taxonomy ) ? /*'permissions'*/
			'not_managed' : $mode;
	}


	/**
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function getAccessMeta( $post_id ) {
		return get_post_meta( $post_id, '_types_access', true );
	}


	/**
	 * @param $post_id
	 * @param $data
	 *
	 * @return mixed
	 */
	public function updateAccessMeta( $post_id, $data ) {
		return update_post_meta( $post_id, '_types_access', $data );
	}


	/**
	 * @param $post_id int
	 *
	 * @return mixed
	 */
	public function deleteAccessMeta( $post_id ) {
		return delete_post_meta( $post_id, '_types_access' );
	}


	/**
	 * @return mixed
	 */
	public function getWpcfTypes() {
		return get_option( 'wpcf-custom-types', array() );
	}


	/**
	 * @param $settings object
	 *
	 * @return mixed
	 */
	public function updateWpcfTypes( $settings ) {
		return update_option( 'wpcf-custom-types', $settings );
	}


	/**
	 * @return mixed
	 */
	public function getWpcfTaxonomies() {
		return get_option( 'wpcf-custom-taxonomies', array() );
	}


	/**
	 * @param $settings object
	 *
	 * @return mixed
	 */
	public function updateWpcfTaxonomies( $settings ) {
		return update_option( 'wpcf-custom-taxonomies', $settings );
	}


	/**
	 * @return mixed
	 */
	public function getAccessVersion() {
		return get_option( 'wpcf-access-version-check', false );
	}


	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function updateAccessVersion( $data ) {
		return update_option( 'wpcf-access-version-check', $data );
	}

	/**
	 * Get a value from nested associative array.
	 * This method is a clone of Toolset common toolset_getnest but available for Access before autoloader starts
	 *
	 * This method will try to traverse a nested associative array by the set of keys provided.
	 *
	 * E.g. if you have $source = array( 'a' => array( 'b' => array( 'c' => 'my_value' ) ) ) and want to reach 'my_value',
	 * you need to write: $my_value = wpcf_getnest( $source, array( 'a', 'b', 'c' ) );
	 *
	 * @param mixed|array $source The source array.
	 * @param string[] $keys Keys which will be used to access the final value.
	 * @param null|mixed $default Default value to return when the keys cannot be followed.
	 *
	 * @return mixed|null Value in the nested structure defined by keys or default value.
	 *
	 * @since 2.8.1.2
	 */
	public function toolset_getnest( &$source, $keys = array(), $default = null ) {

		$current_value = $source;

		// For detecting if a value is missing in a sub-array, we'll use this temporary object.
		// We cannot just use $default on every level of the nesting, because if $default is an
		// (possibly nested) array itself, it might mess with the value retrieval in an unexpected way.
		$missing_value = new \stdClass();

		while ( ! empty( $keys ) ) {
			$current_key = array_shift( $keys );
			$is_last_key = empty( $keys );

			$current_value = $this->toolset_getarr( $current_value, $current_key, $missing_value );

			if ( $is_last_key ) {
				// Apply given default value.
				if ( $missing_value === $current_value ) {
					return $default;
				} else {
					return $current_value;
				}
			} elseif ( ! is_array( $current_value ) ) {
				return $default;
			}
		}

		return $default;
	}

	/**
	 * Safely retrieve a key from given array (meant for $_POST, $_GET, etc).
	 * This method is a clone of Toolset common toolset_getarr but available for Access before autoloader starts
	 *
	 * Checks if the key is set in the source array. If not, default value is returned. Optionally validates against array
	 * of allowed values and returns default value if the validation fails.
	 *
	 * @param array|\ArrayAccess $source The source array.
	 * @param string $key The key to be retrieved from the source array.
	 * @param mixed $default Default value to be returned if key is not set or the value is invalid. Optional.
	 *     Default is empty string.
	 * @param null|array $valid If an array is provided, the value will be validated against it's elements.
	 *
	 * @return mixed The value of the given key or $default.
	 *
	 * @since 1.8
	 */
	public function toolset_getarr( &$source, $key, $default = '', $valid = null ) {
		if ( isset( $source[ $key ] ) ) {
			$val = $source[ $key ];

			if ( is_callable( $valid ) && ! call_user_func( $valid, $val ) ) {
				return $default;
			} elseif ( is_array( $valid ) && ! in_array( $val, $valid ) ) {
				return $default;
			}

			return $val;
		} else {
			return $default;
		}
	}

}
