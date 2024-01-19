<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;
use OTGS\Toolset\Access\Models\WPMLSettings;
use Toolset_Post_Type_Exclude_List;

/**
 * Main post types controller
 * Set edit, delete and publish permissions
 *
 * Class PermissionsPostTypes
 *
 * @package OTGS\Toolset\Access\Controllers
 * @since 2.7
 */
class PermissionsPostTypes {

	/**
	 * @var PermissionsPostTypes
	 */
	private static $instance;

	/**
	 * @var array
	 */
	private $translated_post_types = array();

	/**
	 * @var array
	 */
	private $post_type_slug_list = array();

	/**
	 * An array of post types to excluded from Access permissions
	 *
	 * @var array
	 */
	public $excluded_post_types = array();

	/**
	 * Array of default wordpress post types
	 */
	const INHERIT_POST_TYPES = array( 'post', 'page', 'attachment', 'media' );

	/**
	 * All post groups settings
	 *
	 * @var array
	 */
	private $post_groups_settings = array();

	/**
	 * Array of all post ids assigned to post groups
	 *
	 * @var array
	 */
	private $post_groups_ids = array();

	/**
	 * an array of post types where can edit
	 *
	 * @var array
	 */
	private $user_can_edit_post_types_by_groups = array();

	private $post_group_permissions_loaded = false;

	private $post_groups_exists = false;

	/**
	 * Default Access post type capabilities
	 *
	 * @var bool[]
	 */
	private $default_access_capabilities = array(
		'edit_any' => true,
		'edit_own' => true,
		'publish' => true,
		'delete_any' => true,
		'delete_own' => true,
	);

	/**
	 * Default user post type capabilities
	 *
	 * @var bool[]
	 *
	 */
	private $default_user_capabilities = array(
		'edit' => false,
		'edit_published' => false,
		'edit_others' => false,
		'delete' => false,
		'delete_others' => false,
		'delete_published' => false,
	);


	/**
	 * @return PermissionsPostTypes
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
	 * PermissionsPostTypes constructor.
	 */
	public function __construct() {
		add_action( 'registered_post_type', array( $this, 'registered_post_type_hook' ), 10, 2 );
	}


	/**
	 * Generate a clone of post type object.
	 *
	 * @param string $post_type
	 * @param string $singular
	 * @param string $plural
	 *
	 * @return \WP_Post_Type
	 */
	private function generate_temp_post_type_object( $post_type, $singular, $plural ) {
		global $wp_post_types;
		$tmp_post_type_object = clone $wp_post_types[ $post_type ];
		$tmp_post_type_object->capability_type = array( $singular, $plural );
		$tmp_post_type_object->map_meta_cap = true;
		$tmp_post_type_object->capabilities = array();
		$tmp_post_type_object->cap = get_post_type_capabilities( $tmp_post_type_object );

		return $tmp_post_type_object;
	}


	/**
	 * Fix menu position when a child post type has different permissions with parent post type
	 *
	 * @param bool $is_post_managed
	 * @param string $post_type
	 */
	private function fix_menu_position_for_child_post_types( $is_post_managed, $post_type ) {
		/**
		 * TODO: change this when WordPress will fix capabilities for child menu elements
		 * This code fix the issue with CPT capability when CPT added as child to other (parent) CPT menu
		 * and current user has no edit permissions for parent CPT
		 * The issue is: Wordpress use parent edit capability for all elements in parent menu.
		 */
		global $wp_post_types;
		if ( $is_post_managed ) {
			$post_type_data = $wp_post_types[ $post_type ];
			if ( isset( $post_type_data->show_in_menu_page )
				&& strpos( $post_type_data->show_in_menu_page, 'edit.php?post_type=' ) !== false ) {
				$parent_post_type = trim( str_replace( 'edit.php?post_type=', '', $post_type_data->show_in_menu_page ) );
				if ( isset( $wp_post_types[ $parent_post_type ] ) ) {
					$user_can_edit_parent = apply_filters( 'toolset_access_api_get_post_type_permissions', false, 'post', 'edit_own' );
					if ( ! $user_can_edit_parent ) {
						$post_type_data->show_in_menu_page = true;
						$post_type_data->show_in_menu = true;
					}
				}
			}
		}
	}


	/**
	 * Detect and mark inherit post types
	 *
	 * @param string $post_type
	 */
	private function detect_inherits_post_types( $post_type ) {
		global $wp_post_types;

		if (
			! in_array( $post_type, self::INHERIT_POST_TYPES )
			&& ( empty( $wp_post_types[ $post_type ]->capability_type )
				|| $wp_post_types[ $post_type ]->capability_type == 'post' )
		) {
			$wp_post_types[ $post_type ]->_wpcf_access_inherits_post_cap = 1;
		}
	}


	/**
	 * Maps rules and settings for post types registered outside of Types.
	 *
	 * Wordpress allows using the 'registered_post_type' action very early (before 'init'). Make sure that all methods and classes used in this method exist and can be accessed from it.
	 *
	 * @param string $post_type
	 * @param array $args
	 * @param Capabilities $access_settings
	 * @param Settings $access_capabilities
	 * @return void
	 */
	public function registered_post_type_hook( $post_type, $args, Settings $access_settings = null, Capabilities $access_capabilities = null, $toolset_post_type_exclude_list = null ) {
		global $wpcf_access, $wp_post_types;
		if ( empty( $this->excluded_post_types ) && class_exists( 'Toolset_Post_Type_Exclude_List' ) ) {
			$post_type_exclude_list_object = $toolset_post_type_exclude_list ?: new Toolset_Post_Type_Exclude_List();
			$this->excluded_post_types  = apply_filters( 'toolset-access-excluded-post-types', $post_type_exclude_list_object->get() );
		}

		if ( in_array( $post_type, $this->excluded_post_types ) ) {
			return false;
		}

		list( $plural, $singular ) = toolset_access_get_post_type_names( $post_type );
		if ( empty( $plural ) ) {
			return false;
		}

		$access_capabilities = $access_capabilities ? $access_capabilities : Capabilities::get_instance();
		$access_settings = $access_settings ? $access_settings : Settings::get_instance();
		$settings_access = $access_settings->get_types_settings();

		$tmp_post_type_object = $this->generate_temp_post_type_object( $post_type, $singular, $plural );

		$is_post_managed = ( isset( $settings_access['post'] ) && $settings_access['post']['mode'] == 'permissions' );
		$this->fix_menu_position_for_child_post_types( $is_post_managed, $post_type );

		if ( ! isset( $settings_access[ $post_type ]['mode'] ) ) {
			// This post type has not been tracked/saved yet!
			// Save settings for it.
			$settings_access_full = $access_settings->get_types_settings( true, true );
			$settings_access_full[ $post_type ]['mode'] = 'not_managed';
			$access_settings->updateAccessTypes( $settings_access_full );
			// Set settings for it in the logic below.
			$settings_access[ $post_type ]['mode'] = 'not_managed';
		}

		$wp_post_types[ $post_type ]->__accessIsCapValid = ! $access_capabilities->check_cap_conflict( array_values( (array) $tmp_post_type_object->cap ) );
		$wp_post_types[ $post_type ]->__accessIsNameValid = isset( $tmp_post_type_object->labels );
		$wp_post_types[ $post_type ]->__accessNewCaps = $tmp_post_type_object->cap;
		$this->detect_inherits_post_types( $post_type );

		$custom_post_mode = $settings_access[ $post_type ]['mode'];

		if (
			'not_managed' === $custom_post_mode
			|| ! $wp_post_types[ $post_type ]->__accessIsCapValid
			|| ! $wp_post_types[ $post_type ]->__accessIsNameValid
		) {
			if ( $wpcf_access->wpml_installed ) {
				WPMLSettings::get_instance()->load_wpml_languages_permissions( $access_settings, $post_type );
			}
			return false;
		}

		if ( 'follow' !== $custom_post_mode ) {
			$wp_post_types[ $post_type ]->capability_type = array( $singular, $plural );
			$wp_post_types[ $post_type ]->map_meta_cap = true;
			$wp_post_types[ $post_type ]->capabilities = array();
			$wp_post_types[ $post_type ]->cap = get_post_type_capabilities( $wp_post_types[ $post_type ] );
			unset( $wp_post_types[ $post_type ]->capabilities );
		}

		if ( $wpcf_access->wpml_installed ) {
			WPMLSettings::get_instance()->load_wpml_languages_permissions( $access_settings, $post_type );
		}
	}


	/**
	 * @param array $allcaps
	 * @param array $caps
	 * @param array $args
	 * @param mixed $user
	 * @param string $type
	 *
	 * @return array|mixed
	 */
	public function get_post_type_caps( $allcaps, $caps, $args, $user, $type ) {
		global $wpcf_access;
		$settings = Settings::get_instance();
		$access_roles = UserRoles::get_instance();
		$requested_capability = $args[0];
		$is_edit_comment = ( 'edit_comment' == $args[0] );
		if ( ! $is_edit_comment ) {
			if ( 'delete' === $type ) {
				$requested_capability = $caps[0];

				$post_type = str_replace( array(
					'delete_others_',
					'delete_private_',
					'delete_published_',
					'delete_',
				), '', $requested_capability );
			} else {
				$post_type = str_replace( array( $type . '_', 'others_' ), '', $requested_capability );
			}

			if ( 'edit_comment' == $args[0] ) {

			}

			if ( isset( $args[2] ) && ! empty( $args[2] ) ) {
				$new_post_type = $settings->determine_post_type( $args[2] );
				$post_type = ( empty( $new_post_type ) ? $post_type : $new_post_type );
			}
		} else {
			if ( isset( $args[2] ) ) {
				$comments_permissions = CommentsPermissions::get_instance();
				$post = $comments_permissions->get_comment_post( $args[2] );
				if ( empty( $post ) ) {
					return $allcaps;
				}
				$post_type = $post->post_type;
			} else {
				return $allcaps;
			}
		}

		list( $plural, $singular ) = $this->get_post_type_names( $caps, $args );
		$post_type_slug = $this->get_post_type_slug_by_name( $post_type, $singular );

		if ( class_exists( 'bbPress' ) ) {
			if ( $singular === 'topic' ) {
				$post_type_slug = 'topic';
			}
			if ( $singular === 'forum' ) {
				$post_type_slug = 'forum';
			}
		}

		if ( empty( $singular ) || empty( $post_type_slug ) ) {
			return $allcaps;
		}

		if ( $singular == 'media' ) {
			$post_type_slug = 'attachment';
		}

		$roles = $access_roles->get_current_user_roles();
		$types_settings = $settings->get_types_settings();
		if (
			! isset( $types_settings[ $post_type_slug ] )
			|| (
				isset( $types_settings[ $post_type_slug ]['mode'] )
				&& 'not_managed' == $types_settings[ $post_type_slug ]['mode']
			)
		) {
			return $allcaps;
		}

		$post_type_array = array(
			'post_type' => $post_type,
			'plural' => $plural,
			'singular' => $singular,
			'post_type_slug' => $post_type_slug,
		);

		// Includes PermissionsPostGroups class file if a has_cap_filter started before autoloader
		if ( ! class_exists( ' \OTGS\Toolset\Access\Controllers\PermissionsPostGroups' ) ) {
			require_once( TACCESS_PLUGIN_PATH . '/application/controllers/permissions_post_groups.php' );
		}

		$post_group_permissions = PermissionsPostGroups::get_instance();
		$post_group_permissions->load_post_group_permissions();

		// Post group edit permissions for single post
		// Has highest priority
		if ( $post_group_permissions->post_groups_exists ) {
			if ( isset( $args[2] ) || ! empty( $args[2] ) ) {
				$post_id = intval( $args[2] );
				foreach ( $post_group_permissions->post_groups_ids as $group_name => $group_info ) {
					if ( isset( $group_info[ $post_id ] )
						&& isset( $post_group_permissions->post_groups_settings[ $group_name ] ) ) {
						return $post_group_permissions->set_permissions_post_groups( $allcaps, $group_name, $user, $post_type_array, $roles, $post_id );
					}
				}
				$allcaps = $post_group_permissions->set_post_group_permissions_to_defaults( $allcaps, $user, $post_type_array );
			}
		}

		if ( $wpcf_access->wpml_installed ) {
			if ( ! isset( $wpcf_access->post_types_info[ $plural ][2] ) ) {

				if ( ! array_key_exists( $post_type_array['post_type_slug'], $this->translated_post_types ) ) {
					$this->translated_post_types[ $post_type_array['post_type_slug'] ] = apply_filters( 'wpml_is_translated_post_type', null, $post_type_array['post_type_slug'] );
				}
				$is_translatable = $this->translated_post_types[ $post_type_array['post_type_slug'] ];
				$wpcf_access->post_types_info[ $plural ][2] = $is_translatable;
			} else {
				$is_translatable = $wpcf_access->post_types_info[ $plural ][2];
			}

			if ( $is_translatable ) {
				$wpml_settings = WPMLSettings::get_instance();
				$types_settings = $wpml_settings->get_wpml_permissions( $settings );
				$allcaps = $wpml_settings->set_post_type_permissions_wpml( $allcaps, $args, $caps, $user, $types_settings, $post_type_array, $roles );
			} else {
				$allcaps = $this->set_post_type_permissions( $allcaps, $user, $types_settings, $post_type_array, $roles, $args );
			}
		} else {
			$allcaps = $this->set_post_type_permissions( $allcaps, $user, $types_settings, $post_type_array, $roles, $args );
		}

		// Make post type menu visible if a user has edit permissions for at least one post
		if ( $post_group_permissions->post_groups_exists ) {
			if ( ! isset( $args[2] ) || empty( $args[2] ) ) {
				if ( $post_group_permissions->user_can_edit_single_post( $post_type_array, $user, $roles ) ) {
					$requested_post_type = $post_type_array['post_type'];
					if ( ! isset( $allcaps[ 'edit_' . $requested_post_type ] )
						|| ! $allcaps[ 'edit_'
						. $requested_post_type ] ) {
						$post_types_object = get_post_types( array(), 'objects' );
						$this->disable_add_new_button_for_post_type( $post_type_array['singular'], $post_types_object[ $post_type_array['singular'] ] );
					}
					$allcaps[ 'edit_' . $requested_post_type ] = true;
					$allcaps[ 'edit_others_' . $requested_post_type ] = true;
				}
			}
		}

		return $allcaps;
	}


	/**
	 * @param array $allcaps
	 * @param object $user
	 * @param array $types_settings
	 * @param array $post_type
	 * @param array $roles
	 * @param array $args
	 * @param Capabilities $access_capabilities
	 *
	 * @return array
	 */
	public function set_post_type_permissions( $allcaps, $user, $types_settings, $post_type, $roles, $args, $access_capabilities = null ) {
		$access_capabilities = $access_capabilities ?: Capabilities::get_instance();

		$additional_key = '';
		if ( isset( $args[2] ) && ! empty( $args[2] ) ) {
			$additional_key = 'edit_own' . $args[2];
		}
		$access_cache_post_type_caps_key_single = md5( 'access::post_ype_language_cap__single_'
			. $post_type['post_type_slug']
			. $additional_key );
		$cached_post_type_caps = \Access_Cacher::get( $access_cache_post_type_caps_key_single, 'access_cache_post_type_languages_caps_single' );
		//Load cached capabilities
		if ( false !== $cached_post_type_caps ) {
			$allcaps = $access_capabilities->bulk_allcaps_update( $cached_post_type_caps, $post_type['post_type'], $user, $allcaps, $post_type['plural'] );
			return $allcaps;
		}

		$user_caps = $this->default_user_capabilities;

		if ( isset( $types_settings[ $post_type['post_type_slug'] ] ) ) {
			$post_type_permissions = array();
			if ( 'follow' === $types_settings[ $post_type['post_type_slug'] ]['mode'] ) {
				$post_type_permissions = $types_settings['post']['permissions'];
			} else if ( isset( $types_settings[ $post_type['post_type_slug'] ]['permissions'] ) ) {
				$post_type_permissions = $types_settings[ $post_type['post_type_slug'] ]['permissions'];
			} else {
				return $allcaps;
			}
			$parsed_caps = $this->parse_post_type_caps( $post_type_permissions, $this->default_access_capabilities, $roles, $user );
			$user_caps = $this->generate_user_caps( $parsed_caps, $user_caps );
		}

		$allcaps = $access_capabilities->bulk_allcaps_update( $user_caps, $post_type['post_type'], $user, $allcaps, $post_type['plural'] );
		\Access_Cacher::set( $access_cache_post_type_caps_key_single, $user_caps, 'access_cache_post_type_languages_caps_single' );

		return $allcaps;
	}


	/**
	 * @param $types_settings
	 * @param $requested_capabilities
	 * @param $roles
	 * @param null|WP_User  $user
	 *
	 * @return mixed
	 */
	public function parse_post_type_caps( $types_settings, $requested_capabilities, $roles, $user = null ) {
		global $current_user;
		$user = $user ?: $current_user;
		$user_id = $user->ID;
		$output = $requested_capabilities;

		foreach ( $requested_capabilities as $cap => $status ) {
			if ( ! isset( $types_settings[ $cap ] ) ) {
				$output[ $cap ] = false;
				continue;
			}

			${$cap} = $types_settings[ $cap ]['roles'];

			if ( isset( $types_settings[ $cap ]['users'] ) ) {
				${$cap . '_users'} = $types_settings[ $cap ]['users'];
			}

			$output[ $cap ] = false;

			if ( isset( ${$cap . '_users'} ) && in_array( $user_id, ${$cap . '_users'} ) ) {
				$output[ $cap ] = true;
				continue;
			}
			$roles_check = array_intersect( $roles, ${$cap} );
			if ( ! empty( $roles_check ) ) {
				$output[ $cap ] = true;
				continue;
			}

		}

		return $output;
	}


	/**
	 * @param $parsed_caps
	 * @param $user_caps
	 *
	 * @return mixed
	 */
	public function generate_user_caps( $parsed_caps, $user_caps ) {
		if ( $parsed_caps['publish'] ) {
			$user_caps['publish'] = true;
		} elseif ( ! $parsed_caps['publish'] ) {
			$user_caps['publish'] = false;
		}

		if ( $parsed_caps['edit_any'] ) {
			$user_caps['edit'] = true;
			$user_caps['edit_others'] = true;
			if ( $parsed_caps['publish'] ) {
				$user_caps['edit_published'] = true;
			}
		} elseif ( ! $parsed_caps['edit_any'] && $parsed_caps['edit_own'] ) {
			$user_caps['edit'] = true;
			if ( $parsed_caps['publish'] ) {
				$user_caps['edit_published'] = true;
			}
		}

		if ( $parsed_caps['delete_any'] ) {
			$user_caps['delete'] = true;
			$user_caps['delete_others'] = true;
			if ( $parsed_caps['publish'] ) {
				$user_caps['delete_published'] = true;
			}
		} elseif ( ! $parsed_caps['delete_any'] && $parsed_caps['delete_own'] ) {
			$user_caps['delete'] = true;
			if ( $parsed_caps['publish'] ) {
				$user_caps['delete_published'] = true;
			}
		}

		return $user_caps;
	}


	/**
	 * Proccess disable add new button
	 *
	 * @param string $post_type_slug
	 * @param object $post_type_object
	 */
	public function disable_add_new_button_for_post_type( $post_type_slug, $post_type_object ) {
		$cap = "create_" . $post_type_slug;
		$post_type_object->cap->create_posts = $cap;
		map_meta_cap( $cap, 0 );
	}


	public function get_post_type_slug_by_name( $post_type, $singular ) {
		if ( array_key_exists( $post_type, $this->post_type_slug_list ) ) {
			return $this->post_type_slug_list[ $post_type ];
		}

		if ( in_array( $post_type, array( 'posts', 'pages' ) ) ) {
			switch ( $post_type ) {
				case 'pages':
					$this->post_type_slug_list[ $post_type ] = 'page';

					return 'page';
					break;
				case 'posts':
				default:
					$this->post_type_slug_list[ $post_type ] = 'post';

					return 'post';
					break;
			}
		}

		$_post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
		foreach ( $_post_types as $post_type_name => $post_type_info ) {
			if(
				sanitize_title( strtolower( $post_type_info->name ) ) === $singular
				|| (
					isset( $post_type_info->label )
					&& (
						sanitize_title( strtolower( $post_type_info->label ) ) === $post_type
						|| sanitize_title( strtolower( $post_type_info->label ) ) === $singular
					)
				)
			) {
				$this->post_type_slug_list[ $post_type ] = $post_type_name;

				return $post_type_name;
			}
		}

		// Return empty if there is no private registered post type matching the passed slug:
		// See https://onthegosystems.myjetbrains.com/youtrack/issue/access-750
		$_post_type_slugs = get_post_types( '', 'names' );
		if (!in_array($post_type, $_post_type_slugs)) {
			return '';
		}

		return $post_type;
	}


	/**
	 * @param $post_type
	 *
	 * @return array
	 */
	public function get_post_type_names( $cap, $args = array() ) {
		global $wpcf_access;
		if ( ! isset( $cap[0] ) ) {
			return array( '', '' );
		}

		$post_type_plural = $post_type = str_replace( array(
			'edit_others_',
			'edit_published_',
			'delete_others_',
			'delete_published_',
			'edit_',
			'delete_',
			'publish_',
		), '', $cap[0] );
		if ( in_array( $post_type, array( 'posts', 'pages' ) ) ) {
			switch ( $post_type ) {
				case 'pages':
					return array( 'pages', 'page' );
					break;
				case 'posts':
				default:
					return array( 'posts', 'post' );
					break;
			}
		}
		if ( ! isset( $wpcf_access->post_types_info[ $post_type_plural ] ) ) {
			$settings = Settings::get_instance();
			$_post_types = $settings->get_post_types();
			$post_type_object = null;
			if ( in_array( $post_type_plural, $_post_types ) ) {
				$post_type_object = get_post_type_object( $post_type_plural );
				$post_type_cap = sanitize_title_with_dashes( strtolower( $post_type_object->labels->name ) );
			} else {
				$post_type_cap = $post_type_plural;
				$post_type = $this->get_post_type_singular_by_plural( $post_type_cap );
			}

			$wpcf_access->post_types_info[ $post_type_plural ] = array( $post_type_cap, $post_type );
		} else {
			$post_type_cap = $wpcf_access->post_types_info[ $post_type_plural ][0];
			$post_type = $wpcf_access->post_types_info[ $post_type_plural ][1];
		}

		return array( $post_type_cap, $post_type );
	}


	/**
	 * @param $post_type_name
	 *
	 * @return int|string
	 * Get post type name by plural name
	 */
	public function get_post_type_singular_by_plural( $post_type_name ) {
		$settings = Settings::get_instance();
		$_post_types = $settings->get_post_types();
		foreach ( $_post_types as $post_type => $post_type_data ) {
			if ( isset( $post_type_data->__accessNewCaps )
				&& $post_type_data->__accessNewCaps->edit_posts == 'edit_'
				. $post_type_name ) {
				$cap = $post_type_data->__accessNewCaps->edit_post;
				$post_type = str_replace( 'edit_', '', $cap );

				return $post_type;
			}
		}

		return '';
	}


	/**
	 * Defines capabilities.
	 *
	 * @return array
	 */
	public function get_types_caps_array() {
		$access_roles = UserRoles::get_instance();
		$caps = array(
			//
			// READ
			//
			'read_post' => array(
				'title' => __( 'Read post', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'read' ),
				'predefined' => 'read',
			),
			'read_private_posts' => array(
				'title' => __( 'Read private posts', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			//
			// EDIT OWN
			//
			'create_post' => array(
				'title' => __( 'Create post', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'create_posts' => array(
				'title' => __( 'Create post', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'edit_post' => array(
				'title' => __( 'Edit post', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'edit_posts' => array(
				'title' => __( 'Edit post', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',
			),
			'edit_comment' => array(
				'title' => __( 'Moderate comments', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_own',//'edit_own_comments',
				'fallback' => array( 'edit_published_posts', 'edit_others_posts' ),
			),
			//
			// DELETE OWN
			//
			'delete_post' => array(
				'title' => __( 'Delete post', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'delete_posts' ),
				'predefined' => 'delete_own',
			),
			'delete_posts' => array(
				'title' => __( 'Delete post', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'delete_posts' ),
				'predefined' => 'delete_own',
			),
			'delete_private_posts' => array(
				'title' => __( 'Delete private posts', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'delete_private_posts' ),
				'predefined' => 'delete_own',
			),
			//
			// EDIT ANY
			//
			'edit_others_posts' => array(
				'title' => __( 'Edit others posts', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_others_posts' ),
				'predefined' => 'edit_any',
				'fallback' => array( 'moderate_comments' ),
			),
			'edit_published_posts' => array(
				'title' => __( 'Edit published posts', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_published_posts' ),
				'predefined' => 'publish',
			),
			'edit_private_posts' => array(
				'title' => __( 'Edit private posts', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_private_posts' ),
				'predefined' => 'edit_any',
			),
			'moderate_comments' => array(
				'title' => __( 'Moderate comments', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'edit_posts' ),
				'predefined' => 'edit_any_comments',
				'fallback' => array( 'edit_others_posts', 'moderate_comments' ),
			),
			//
			// DELETE ANY
			//
			'delete_others_posts' => array(
				'title' => __( 'Delete others posts', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'delete_others_posts' ),
				'predefined' => 'delete_any',
			),
			'delete_published_posts' => array(
				'title' => __( 'Delete published posts', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'delete_published_posts' ),
				'predefined' => 'publish',
			),
			//
			// PUBLISH
			//
			'publish_posts' => array(
				'title' => __( 'Publish post', 'wpcf-access' ),
				'roles' => $access_roles->get_roles_by_role( '', 'publish_posts' ),
				'predefined' => 'publish',
			),
		);

		return apply_filters( 'wpcf_access_types_caps', $caps );
	}


}
