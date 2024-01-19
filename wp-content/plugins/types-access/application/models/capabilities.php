<?php

namespace OTGS\Toolset\Access\Models;

use OTGS\Toolset\Access\Controllers\CommentsPermissions;
use OTGS\Toolset\Access\Controllers\PermissionsPostTypes;
use OTGS\Toolset\Access\Controllers\PermissionsTaxonomies;
use OTGS\Toolset\Access\Controllers\PermissionsThirdParty;
use OTGS\Toolset\Access\Controllers\UploadPermissions;

/**
 * Collect and proccess user capabilities
 *
 *
 * @package OTGS\Toolset\Access\Models
 * @since 2.7
 */
class Capabilities {

	/**
	 * @var Capabilities
	 */
	private static $instance;

	/**
	 * @var array
	 */
	private $types_caps;

	/**
	 * @var array
	 */
	private $types_caps_predefined;

	/**
	 * @var array
	 */
	private $tax_caps;

	/**
	 * @var  array
	 */
	public $types_permissions_predefined;

	/**
	 * @var UserRoles|null
	 */
	private $access_roles;


	/**
	 * @return Capabilities
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
	 *
	 * @param UserRoles|null $access_roles
	 */
	function __construct( UserRoles $access_roles = null ) {
		$this->access_roles = $access_roles ?: UserRoles::get_instance();
		$this->types_caps = $this->types_caps_array();
		$this->types_caps_predefined = $this->types_caps_predefined_array();
		$this->tax_caps = $this->tax_caps_array();
	}


	/**
	 * Return post types caps
	 *
	 * @return array
	 */
	function get_types_caps() {
		return $this->types_caps;
	}


	/**
	 * Return taxonomies caps
	 *
	 * @return array
	 */
	function get_tax_caps() {
		return $this->tax_caps;
	}


	/**
	 * Return predefined post types caps
	 *
	 * @return array
	 */
	function get_types_predefined_caps() {
		return $this->types_caps_predefined;
	}


	/**
	 * Defines capabilities.
	 *
	 * @return array
	 */
	private function tax_caps_array() {
		$caps = array(
			'manage_terms' => array(
				'title' => __( 'Manage terms', 'wpcf-access' ),
				'roles' =>$this->access_roles->get_roles_by_role( '', 'manage_categories' ),
				'predefined' => 'manage',
				'match' => array(
					'manage_' => array(
						'match_access' => 'edit_any',
						'match' => 'edit_others_',
						'default' => 'manage_categories',
					),
				),
				'default' => 'manage_categories',
			),
			'edit_terms' => array(
				'title' => __( 'Edit terms', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'manage_categories' ),
				'predefined' => 'edit',
				'match' => array(
					'edit_' => array(
						'match_access' => 'edit_any',
						'match' => 'edit_others_',
						'default' => 'manage_categories',
					),
				),
				'default' => 'manage_categories',
			),
			'delete_terms' => array(
				'title' => __( 'Delete terms', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'manage_categories' ),
				'predefined' => 'edit',
				'match' => array(
					'delete_' => array(
						'match_access' => 'edit_any',
						'match' => 'edit_others_',
						'default' => 'manage_categories',
					),
				),
				'default' => 'manage_categories',
			),
			'assign_terms' => array(
				'title' => __( 'Assign terms', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit',
				'match' => array(
					'assign_' => array(
						'match_access' => 'edit_',
						'match' => 'edit_',
						'default' => 'edit_posts',
					),
				),
				'default' => 'edit_posts',
			),
		);

		return apply_filters( 'wpcf_access_tax_caps', $caps );
	}

	/**
	 * Defines predefined capabilities
	 *
	 * @return array
	 */
	private function types_caps_predefined_array() {
		$modes = array(
			'read' => array(
				'title' => __( 'Read', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'read' ),
				'predefined' => 'read',
			),
			'read_private' => array(
				'title' => __( 'Preview any', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'manage_options' ),
				'predefined' => 'read_private',
			),
			'edit_own' => array(
				'title' => __( 'Edit own', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'delete_own' => array(
				'title' => __( 'Delete own', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_posts' ),
				'predefined' => 'delete_own',
			),
			'edit_any' => array(
				'title' => __( 'Edit any', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_others_posts' ),
				'predefined' => 'edit_any',
			),
			'delete_any' => array(
				'title' => __( 'Delete any', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_others_posts' ),
				'predefined' => 'delete_any',
			),
			'publish' => array(
				'title' => __( 'Publish', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'publish_posts' ),
				'predefined' => 'publish',
			),
		);

		return $modes;
	}


	/**
	 * Defines post types capabilities.
	 *
	 * @return array
	 */
	private function types_caps_array() {
		$caps = array(
			//
			// READ
			//
			'read_post' => array(
				'title' => __( 'Read post', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'read' ),
				'predefined' => 'read',
			),
			'read_private_posts' => array(
				'title' => __( 'Read private posts', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			//
			// EDIT OWN
			//
			'create_post' => array(
				'title' => __( 'Create post', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'create_posts' => array(
				'title' => __( 'Create post', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'edit_post' => array(
				'title' => __( 'Edit post', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'edit_posts' => array(
				'title' => __( 'Edit post', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'edit_comment' => array(
				'title' => __( 'Moderate comments', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',//'edit_own_comments',
				'fallback' => array( 'edit_published_posts', 'edit_others_posts' ),
			),
			//
			// DELETE OWN
			//
			'delete_post' => array(
				'title' => __( 'Delete post', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_posts' ),
				'predefined' => 'delete_own',
			),
			'delete_posts' => array(
				'title' => __( 'Delete post', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_posts' ),
				'predefined' => 'delete_own',
			),
			'delete_private_posts' => array(
				'title' => __( 'Delete private posts', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_private_posts' ),
				'predefined' => 'delete_own',
			),
			//
			// EDIT ANY
			//
			'edit_others_posts' => array(
				'title' => __( 'Edit others posts', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_others_posts' ),
				'predefined' => 'edit_any',
				'fallback' => array( 'moderate_comments' ),
			),
			'edit_published_posts' => array(
				'title' => __( 'Edit published posts', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_published_posts' ),
				'predefined' => 'publish',
			),
			'edit_private_posts' => array(
				'title' => __( 'Edit private posts', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_private_posts' ),
				'predefined' => 'edit_any',
			),
			'moderate_comments' => array(
				'title' => __( 'Moderate comments', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_any_comments',
				'fallback' => array( 'edit_others_posts', 'moderate_comments' ),
			),
			//
			// DELETE ANY
			//
			'delete_others_posts' => array(
				'title' => __( 'Delete others posts', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_others_posts' ),
				'predefined' => 'delete_any',
			),
			'delete_published_posts' => array(
				'title' => __( 'Delete published posts', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_published_posts' ),
				'predefined' => 'publish',
			),
			//
			// PUBLISH
			//
			'publish_posts' => array(
				'title' => __( 'Publish post', 'wpcf-access' ),
				'roles' => $this->access_roles->get_roles_by_role( '', 'publish_posts' ),
				'predefined' => 'publish',
			),
		);

		return apply_filters( 'wpcf_access_types_caps', $caps );
	}


	/**
	 * Defines dependencies.
	 *
	 * @return array
	 */
	public function access_dependencies() {
		$deps = array(
			// post types
			'edit_own' => array(
				'true_allow' => array( 'read' ),
				'false_disallow' => array( 'edit_any', 'publish' ),
			),
			'edit_any' => array(
				'true_allow' => array( 'read', 'edit_own' ),
			),
			'publish' => array(
				'true_allow' => array( 'read', 'edit_own' ),
			),
			'delete_own' => array(
				'true_allow' => array( 'read' ),
				'false_disallow' => array( 'delete_any' ),
			),
			'delete_any' => array(
				'true_allow' => array( 'read', 'delete_own' ),
			),
			'read' => array(
				'false_disallow' => array(
					'edit_own',
					'delete_own',
					'edit_any',
					'delete_any',
					'publish',
					'read_private',
				),
			),
			'read_private' => array(
				'true_allow' => array( 'read' ),
			),
			// taxonomies
			'edit_terms' => array(
				'true_allow' => array( 'manage_terms' ),
				'false_disallow' => array( 'manage_terms', 'delete_terms' ),
			),
			'delete_terms' => array(
				'true_allow' => array( 'manage_terms', 'edit_terms' ),
			),
			'manage_terms' => array(
				'true_allow' => array( 'edit_terms', 'delete_terms' ),
				'false_disallow' => array( 'edit_terms', 'delete_terms' ),
			),
			'assign_terms' => array(),
		);

		return $deps;
	}


	/**
	 * Returns cap settings declared in embedded.php
	 *
	 * @param string $cap
	 * @return array
	 */
	public function get_cap_settings( $cap ) {

		$caps_types = $this->get_types_caps();
		if ( isset( $caps_types[ $cap ] ) ) {
			return $caps_types[ $cap ];
		}

		$caps_tax = $this->get_tax_caps();
		if ( isset( $caps_tax[ $cap ] ) ) {
			return $caps_tax[ $cap ];
		}

		return array(
			'title' => $cap,
			'roles' => $this->access_roles->get_roles_by_role( 'administrator' ),
			'predefined' => 'edit_any',
		);
	}


	/**
	 * Returns cap settings declared in embedded.php
	 *
	 * @param string $cap
	 * @return array
	 */
	public function get_cap_predefined_settings( $cap ) {
		$capabilities = \OTGS\Toolset\Access\Models\Capabilities::get_instance();
		$predefined = $capabilities->get_types_predefined_caps();
		if ( isset( $predefined[ $cap ] ) ) {
			return $predefined[ $cap ];
		}

		// If not found, try other caps
		return $this->get_cap_settings( $cap );
	}


	/**
	 * Check if capability requested in has_cap related to Access
	 *
	 * @return true or false
	 * @since 2.2
	 */
	public function is_managed_capability( $cap, $requested_cap = '' ) {
		$requested_cap = isset( $requested_cap[0] ) ? $requested_cap[0] : '';
		// we use long statement, with strpos because this is the fastest way to check if $arg contain part of text
		if ( strpos( $cap, 'edit_' ) !== false || strpos( $cap, 'wpcf_' ) !== false
			|| strpos( $cap, 'manage_' )
			!== false
			|| strpos( $cap, '_cred' ) !== false
			|| strpos( $cap, 'delete_' ) !== false
			|| strpos( $cap, 'publish_' ) !== false
			|| strpos( $cap, 'view_own_in_profile_' ) !== false
			|| strpos( $cap, 'modify_own_' ) !== false
			|| strpos( $cap, 'view_fields_in' ) !== false
			|| strpos( $cap, 'modify_fields_in_' ) !== false
			|| strpos( $cap, 'assign_' ) !== false
			|| strpos( $cap, 'read_private' ) !== false
		     || strpos( $requested_cap, 'read_private' ) !== false
			|| 'upload_files' == $cap
			|| 'moderate_comments' == $cap ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * @param array $allcaps
	 * @param $cap
	 * @param bool $add
	 * @param null $user
	 *
	 * @return mixed
	 */
	public function add_or_remove_cap( $allcaps, $cap, $add = true, $user = null ) {
		global $current_user;
		if ( is_null( $user ) ) {
			return $allcaps;
		}
		if ( $add ) {
			$allcaps[ $cap ] = 1;
			if ( isset( $user->allcaps ) ) {
				$user->allcaps[ $cap ] = true;
			}
			if ( isset( $user->ID ) && $user->ID == $current_user->ID ) {
				$current_user->allcaps[ $cap ] = true;
			}

		} else {
			unset( $allcaps[ $cap ] );
			if ( isset( $user->allcaps[ $cap ] ) ) {
				unset( $user->allcaps[ $cap ] );
			}
			if ( isset( $user->ID ) && $user->ID == $current_user->ID ) {
				unset( $current_user->allcaps[ $cap ] );
			}
		}

		return $allcaps;
	}


	private function get_taxonomy_caps() {
		$cached_caps = \Access_Cacher::get( 'access_taxonomy_capabilities', 'access_cache_user_has_cap' );
		if ( false === $cached_caps ) {
			$access_settings = Settings::get_instance();
			$taxonomies = $access_settings->get_tax_settings();
			$taxonomy_caps = array( 'manage_term', 'edit_term', 'assign_term', 'delete_term' );
			foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {

				$taxonomy_array = array(
					'manage_' . $taxonomy_slug,
					'edit_' . $taxonomy_slug,
					'assign_' . $taxonomy_slug,
					'delete_' . $taxonomy_slug,
				);
				$taxonomy_caps = array_merge( $taxonomy_caps, $taxonomy_array );

				$taxonomy = get_taxonomy( $taxonomy_slug );
				if ( isset( $taxonomy->label ) ) {
					$taxonomy_plural = strtolower( $taxonomy->label );
					$taxonomy_array = array(
						'manage_' . $taxonomy_plural,
						'edit_' . $taxonomy_plural,
						'assign_' . $taxonomy_plural,
						'delete_' . $taxonomy_plural,
					);
					$taxonomy_caps = array_merge( $taxonomy_caps, $taxonomy_array );
				}
			}
			\Access_Cacher::set( 'access_taxonomy_capabilities', $taxonomy_caps, 'access_cache_user_has_cap' );
		} else {
			$taxonomy_caps = $cached_caps;
		}

		return $taxonomy_caps;
	}


	private function get_third_party_caps() {
		$cached_caps = \Access_Cacher::get( 'access_third_party_capabilities', 'access_cache_user_has_cap' );
		if ( false === $cached_caps ) {
			$access_settings = Settings::get_instance();
			$third_party_caps = array();

			//$third_party = $access_settings->get_third_party_asettings();

			$areas_forms = apply_filters( 'types-access-area-for-cred-forms', array() );
			$areas_types = apply_filters( 'types-access-area-for-types-fields', array() );
			$areas = array_merge( $areas_forms, $areas_types );
			$cached_capabilities = array();
			if ( ! empty( $areas ) ) {
				foreach ( $areas as $area ) {
					$groups = apply_filters( 'types-access-group', array(), $area['id'] );
					foreach ( $groups as $group ) {
						$caps_filter = apply_filters( 'types-access-cap', array(), $area['id'], $group['id'] );
						foreach ( $caps_filter as $cap_id => $cap_data ) {
							$third_party_caps[] = $cap_id;
							$cached_capabilities[ $cap_id ] = $cap_data;
							$cached_capabilities[ $cap_id ]['area'] = $area['id'];
							$cached_capabilities[ $cap_id ]['group'] = $group['id'];
						}
					}
				}
				\Access_Cacher::set( 'access_third_party_capabilities', $third_party_caps, 'access_cache_user_has_cap' );
			}

			\Access_Cacher::set( 'access_third_party_caps_cached', $cached_capabilities, 'access_cache_user_has_cap' );
		} else {
			$third_party_caps = $cached_caps;
		}

		return $third_party_caps;
	}


	/**
	 * @param array $allcaps
	 * @param $caps
	 * @param $args
	 * @param $user
	 *
	 * @return mixed
	 */
	public function get_capabilities_by_user_permissions( $allcaps, $caps, $args, $user ) {
		$res = '';
		$tax_caps = $this->get_taxonomy_caps();
		$third_party_caps = $this->get_third_party_caps();

		/**
		 * Check permissions only for third party capabilities is a $user is empty ( gust )
		 */
		if ( $user->ID === 0 && ( ! isset( $caps[0] ) || ! in_array( $caps[0], $third_party_caps ) ) ) {
			return $allcaps;
		}

		if ( isset( $caps[0] ) && in_array( $caps[0], $tax_caps ) ) {
			$access_taxonomies = PermissionsTaxonomies::get_instance();
			$allcaps = $access_taxonomies->get_taxonomy_caps( $allcaps, $caps, $args, $user );
		} elseif ( isset( $caps[0] ) && in_array( $caps[0], $third_party_caps ) ) {
			$access_third_party = PermissionsThirdParty::get_instance();
			$allcaps = $access_third_party->get_third_party_caps( $allcaps, $caps, $args, $user );
		} elseif ( isset( $caps[0] ) && $caps[0] == 'upload_files' ) {
			$upload_permissions = UploadPermissions::get_instance();
			$allcaps = $upload_permissions->set_uploads_capabilities( $allcaps, $caps, $args, $user );
		} else {
			//Set post type edit permissions
			if ( strpos( $args[0], 'edit_' ) !== false && strpos( $args[0], 'edit_' ) === 0 ) {
				$res = 'edit';
			}
			if ( empty( $res ) && strpos( $args[0], 'delete_' ) !== false && strpos( $args[0], 'delete_' ) === 0 ) {
				$res = 'delete';
			}
			if ( empty( $res ) && strpos( $args[0], 'publish_' ) !== false && strpos( $args[0], 'publish_' ) === 0 ) {
				$res = 'publish';
			}
			if ( ! empty( $res ) ) {
				$access_post_types = PermissionsPostTypes::get_instance();
				$allcaps = $access_post_types->get_post_type_caps( $allcaps, $caps, $args, $user, $res );

				return $allcaps;
			}
		}

		return $allcaps;
	}


	/**
	 * @param $caps
	 * @param $post_type
	 * @param $user
	 * @param array $allcaps
	 *
	 * @return array|mixed
	 */
	public function bulk_allcaps_update( $caps, $post_type, $user, $allcaps = array(), $post_type_plural = '' ) {
		foreach ( $caps as $cap => $bool ) {
			$allcaps = $this->add_or_remove_cap( $allcaps, $cap . '_' . $post_type, $bool, $user );
			if ( ! empty( $post_type_plural ) ) {
				$allcaps = $this->add_or_remove_cap( $allcaps, $cap . '_' . $post_type_plural, $bool, $user );
			}
		}

		return $allcaps;
	}


	/**
	 * Check if capability conflict with default WordPress capabilities
	 *
	 * @param $caps
	 *
	 * @return bool
	 */
	public function check_cap_conflict( $caps ) {
		$wp_default_caps = array(
			'activate_plugins',
			'add_users',
			'create_users',
			'delete_plugins',
			'delete_themes',
			'delete_users',
			'edit_dashboard',
			'edit_files',
			'edit_plugins',
			'edit_theme_options',
			'edit_themes',
			'edit_users',
			'export',
			'import',
			'install_plugins',
			'install_themes',
			'list_users',
			'manage_options',
			'promote_users',
			'remove_users',
			'switch_themes',
			'unfiltered_html',
			'update_core',
			'update_plugins',
			'update_themes',
		);

		$cap_conflict = array_intersect( $wp_default_caps, (array) $caps );

		if ( ! empty( $cap_conflict ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Get post type permissions from predefined role capabilities stored in DB by default.
	 *
	 * @return array
	 */
	public function get_types_caps_default() {
		if ( ! empty( $this->types_permissions_predefined ) ) {
			return $this->types_permissions_predefined;
		}
		$this->types_permissions_predefined = array(
			'read' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'read' ),
			),
			'read_private' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'manage_options' ),
			),
			'edit_own' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
			),
			'delete_own' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_posts' ),
			),
			'edit_any' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_others_posts' ),
			),
			'delete_any' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'delete_others_posts' ),
			),
			'publish' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'publish_posts' ),
			),
		);

		return $this->types_permissions_predefined;
	}


	/**
	 * @return array
	 */
	public function get_taxs_caps_default() {

		return array(
			'manage_terms' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
			),
			'edit_terms' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
			),
			'delete_terms' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
			),
			'assign_terms' => array(
				'roles' => $this->access_roles->get_roles_by_role( '', 'edit_posts' ),
			),
		);
	}


	/**
	 * @return array
	 */
	public function get_default_roles() {
		return array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
	}


	/**
	 * @param $tax
	 * @param $taxdata
	 * @param $post_caps
	 *
	 * @return array
	 */
	public function types_to_tax_caps( $tax, $taxdata, $post_caps ) {
		$tax_caps_map = $this->get_tax_caps();
		$tax_caps = array();

		if ( ! isset( $post_caps['permissions'] ) ) {
			return $tax_caps;
		}

		foreach ( $tax_caps_map as $tcap => $mdata ) {
			$match_var = array_keys( $mdata['match'] );
			$match = array_shift( $match_var );
			$replace = $mdata['match'][ $match ];
			$tax_cap = $tcap;
			foreach ( $post_caps['permissions'] as $cap => $data ) {
				if ( 0 === strpos( $cap, $replace['match_access'] ) ) {
					$tax_caps[ $tax_cap ] = $data;
					break;
				}
			}
			// use a default here
			if ( ! isset( $tax_caps[ $tax_cap ] ) ) {
				$tax_caps[ $tax_cap ] = array( 'roles' => $this->access_roles->get_roles_by_role( 'administrator' ) );
			}
		}

		return $tax_caps;
	}

	/**
	 * Check if at least one user role in array of requested roles
	 *
	 * @param array $roles
	 * @param object|string $user
	 *
	 * @return bool
	 */
	public function user_has_permission( $roles, $user = '' ) {
		if ( ! is_array( $roles ) ) {
			return false;
		}
		if ( empty( $user ) ) {
			$user = wp_get_current_user();
		}

		$user_roles  = $this->access_roles->get_current_user_roles( false, $user );

		$roles_check = array_intersect( $roles, $user_roles );
		if ( ! empty( $roles_check ) ) {
			return true;
		}

		return false;
	}

}
