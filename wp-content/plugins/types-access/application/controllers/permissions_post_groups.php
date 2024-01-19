<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Controllers\PermissionsPostTypes;

/**
 * Post group edit and delete permissions
 * Set edit, delete and publish permissions for single posts
 *
 *
 * @since 2.8
 */
class PermissionsPostGroups {

	/** @var PermissionsPostGroups */
	private static $instance;

	/**
	 * All post groups settings
	 *
	 * @var array
	 */
	public $post_groups_settings = array();

	/**
	 * Array of all post ids assigned to post groups
	 *
	 * @var array
	 */
	public $post_groups_ids = array();

	/**
	 * an array of post types where can edit
	 *
	 * @var array
	 */
	public $user_can_edit_post_types_by_groups = array();

	/**
	 * Post Groups permissions already loaded
	 * @var bool
	 */
	public $post_group_permissions_loaded = false;

	/**
	 * Use has Post Groups edit permissions for at least one post
	 *
	 * @var bool
	 */
	public $post_groups_exists = false;

	/**
	 * PermissionsPostTypes class instance
	 *
	 * @var PermissionsPostTypes
	 */
	public $post_types_permissions = null;

	/**
	 * @var \OTGS\Toolset\Access\Models\Capabilities
	 */
	public $access_capabilities = null;

	/**
	 * @var Settings
	 */
	public $access_settings = null;

	/**
	 * @return PermissionsPostGroups
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

	public function __construct( PermissionsPostTypes $permissions_post_types = null, Capabilities $access_capabilities = null, Settings $access_settings = null ) {
		$this->post_types_permissions = $permissions_post_types ?: PermissionsPostTypes::get_instance();
		$this->access_capabilities    = $access_capabilities ?: Capabilities::get_instance();
		$this->access_settings = $access_settings ?: Settings::get_instance();
	}


	/**
	 * @param array $allcaps
	 * @param object $user
	 * @param array $post_type
	 *
	 * @return array
	 */
	public function set_post_group_permissions_to_defaults( $allcaps, $user, $post_type ) {

		$default_user_caps = array(
			'edit'             => false,
			'edit_published'   => false,
			'edit_others'      => false,
			'delete'           => false,
			'delete_published' => false,
			'delete_others'    => false,
		);

		$allcaps = $this->access_capabilities->bulk_allcaps_update( $default_user_caps, $post_type['post_type'], $user, $allcaps, $post_type['plural'] );

		return $allcaps;
	}

	/**
	 * Set edit permissions for specific post
	 *
	 * @param array $allcaps
	 * @param string $group
	 * @param object $user
	 * @param array $post_type
	 * @param array $roles
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function set_permissions_post_groups( $allcaps, $group, $user, $post_type, $roles, $post_id ) {
		$access_cache_posttype_caps_key_single = md5(
			'access::postype_language_cap__single_'
			. $post_type['post_type_slug']
			. '_' . $post_id
		);

		$cached_post_type_caps = \Access_Cacher::get(
			$access_cache_posttype_caps_key_single, 'access_cache_posttype_languages_caps_single'
		);

		//Load cached capabilities
		if ( false !== $cached_post_type_caps ) {
			$this->access_capabilities->bulk_allcaps_update( $cached_post_type_caps, $post_type['post_type'], $user, $allcaps, $post_type['plural'] );

			return $allcaps;
		}

		$settings = $this->post_groups_settings[ $group ]['permissions'];

		$requested_capabilties = array(
			'edit_any'   => true,
			'delete_any' => true,
			'edit_own'   => true,
			'delete_own' => true,
			'publish'    => true,
		);

		$user_caps                 = array(
			'edit_published'   => false,
			'edit_others'      => false,
			'delete_published' => false,
			'delete_others'    => false,
		);
		$parsed_caps               = $this->post_types_permissions->parse_post_type_caps( $settings, $requested_capabilties, $roles );
		$parsed_caps['edit_own']   = $parsed_caps['publish'] = $parsed_caps['edit_any'];
		$parsed_caps['delete_own'] = $parsed_caps['delete_any'];
		$user_caps                 = $this->post_types_permissions->generate_user_caps( $parsed_caps, $user_caps );

		$post_type_cap = $post_type['post_type'];

		$allcaps = $this->access_capabilities->bulk_allcaps_update( $user_caps, $post_type_cap, $user, $allcaps, $post_type['plural'] );

		\Access_Cacher::set( $access_cache_posttype_caps_key_single, $user_caps, 'access_cache_posttype_languages_caps_single' );

		return $allcaps;
	}

	/**
	 * Is user can edit at least one post with post groups permissions for requested post type
	 *
	 * @param array $post_type_array
	 * @param object $user
	 * @param array $roles
	 *
	 * @return bool
	 */
	public function user_can_edit_single_post( $post_type_array, $user, $roles ) {
		$post_type = $post_type_array['post_type'];
		if ( isset( $this->user_can_edit_post_types_by_groups[ $post_type ] ) ) {
			return $this->user_can_edit_post_types_by_groups[ $post_type ];
		}

		foreach ( $this->post_groups_settings as $group_name => $group_settings ) {
			$edit_own = $this->access_settings->toolset_getnest( $group_settings, array( 'permissions', 'edit_any', 'roles' ), array() );
			$role_check = array_intersect( $roles, $edit_own );
			if ( ! empty( $role_check ) && isset( $this->post_groups_ids[ $group_name ] ) ) {
				foreach ( $this->post_groups_ids[ $group_name ] as $post_id => $post_type ) {
					if ( $post_type_array['singular'] === $post_type ) {
						$this->user_can_edit_post_types_by_groups[ $post_type ] = true;

						return true;
					}
				}
			}
		}

		$this->user_can_edit_post_types_by_groups[ $post_type ] = false;

		return false;
	}

	/**
	 * Load Post Groups edit permissions
	 */
	public function load_post_group_permissions() {
		global $wpcf_access, $wpdb;
		if ( $this->post_group_permissions_loaded ) {
			return;
		}
		$types = $wpcf_access->settings->types;

		foreach ( $types as $type => $permissions ) {
			if ( strpos( $type, 'wpcf-custom-group-' ) === 0 ) {
				$this->post_groups_settings[ $type ] = $permissions;
			}
		}

		$sql = $wpdb->prepare( "SELECT p.ID,p.post_type,m.meta_value from " . $wpdb->posts . " as p, " .
		                       $wpdb->postmeta . " as m where p.ID=m.post_id AND m.meta_key=%s ", '_wpcf_access_group' );

		$post_groups = $wpdb->get_results( $sql );

		foreach ( $post_groups as $group ) {
			$this->post_groups_ids[ $group->meta_value ][ $group->ID ] = $group->post_type;
		}

		$this->post_group_permissions_loaded = true;
		if ( ! empty( $this->post_groups_ids ) ) {
			$this->post_groups_exists = true;
		}
	}

}
