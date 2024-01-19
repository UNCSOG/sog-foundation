<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Controllers\CustomErrors as CustomErrors;
use OTGS\Toolset\Access\Controllers\Filters\FrontendFilters as FrontendFilters;
use OTGS\Toolset\Access\Models\Settings as Settings;
use OTGS\Toolset\Access\Models\UserRoles as UserRoles;
use Toolset_Post_Type_Exclude_List;

/**
 * Manage front-end read permissions
 *
 * @package OTGS\Toolset\Access\Controllers
 * @since 2.7
 */
class PermissionsRead {

	/**
	 * @var PermissionsRead
	 */
	private static $instance;

	/**
	 * @var array
	 */
	private $custom_read_permissions;

	/**
	 * @var bool
	 */
	public $read_permissions_set;

	/**
	 * @var array
	 */
	public $hidden_post_types;

	/** @var int|false|string|null */
	private $current_page_id = null;

	/**
	 * @var string
	 */
	private $current_post_language;

	/**
	 * @var array
	 */
	private $post_type_permissions = array();

	/**
	 * @var \OTGS\Toolset\Access\Models\UserRoles
	 */
	private $access_roles;

	/**
	 * @var \OTGS\Toolset\Access\Models\Settings
	 */
	private $access_settings;

	/**
	 * @var string[]
	 */
	private $ignored_post_types = array();

	/**
	 * @var mixed[]
	 */
	private $post_types_to_proccess = array();

	/**
	 * @var mixed[]
	 */
	private $search_result_post_types_to_proccess = array();

	/**
	 * @return PermissionsRead
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
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
	 * PermissionsRead constructor.
	 */
	public function __construct() {

		add_action( 'registered_post_type', array( $this, 'register_post_types' ), 10, 2 );

		add_filter( 'wp_link_query', array( $this, 'filter_wp_link_query' ), null, 2 );

		$this->hidden_post_types = array();
	}


	/**
	 * @return array
	 */
	public function get_hidden_post_types() {
		return $this->hidden_post_types;
	}


	/**
	 * @param string $post_type
	 * @param array $args
	 */
	public function register_post_types( $post_type, $args ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		global $wp_post_types;
		$access_roles = UserRoles::get_instance();
		if ( $access_roles->is_administrator() && ! isset( $_GET['toolset_access_preview'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( empty( $this->access_settings ) ) {
			$this->access_settings = Settings::get_instance();
			$this->access_roles = UserRoles::get_instance();
		}

		if ( ! $wp_post_types[ $post_type ] ) {
			return;
		}

		$ignored_post_types = $this->get_ignored_post_types();
		if ( in_array( $post_type, $ignored_post_types, true ) ) {
			return;
		}

		$settings_access = $this->access_settings->get_post_types_settings();

		// Special case for pages
		if ( 'page' === $post_type
			&& ( ! isset( $settings_access[ $post_type ] )
				|| 'not_managed' === $settings_access[ $post_type ]['mode'] ) ) {
			return;
		}

		$data = $this->get_post_type_data( $settings_access, $post_type );

		$is_post_managed = $this->is_posts_managed( $settings_access );

		$is_allowed_ajax = $this->is_allowed_ajax();

		$set_permission = (
			'not_managed' !== $data['mode']
			|| (
				'post' !== $post_type
				&& 'follow' === $data['mode']
				&& $is_post_managed
			)
		);

		if ( $set_permission && ! is_admin() || $is_allowed_ajax ) {
			$this->custom_read_permissions[] = array(
				$data,
				$post_type,
			);
			add_action( 'init', array( $this, 'set_frontend_read_permissions_action' ), 999 );
		}
	}

	/**
	 * @see toolset_access_get_current_page_id
	 * @return int|false|string
	 */
	private function get_current_page_id() {
		if ( null !== $this->current_page_id ) {
			return $this->current_page_id;
		}

		$this->current_page_id = toolset_access_get_current_page_id();
		return $this->current_page_id;
	}


	/**
	 * @param string $post_type
	 */
	private function set_frontend_read_permissions( $post_type ) {
		global $wp_post_types;

		$post_id = $this->get_current_page_id();
		$this->search_result_post_types_to_proccess[] = array(
			'post_id' => $post_id,
			'post_type' => $post_type,
		);
		if ( isset( $wp_post_types[ $post_type ] )
			&& (
				$this->post_type_visibility( $post_id, $post_type )
				|| ( isset( $_GET['access_preview'] ) && 'archive' === $_GET['access_preview'] ) ) ) {//phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$wp_post_types[ $post_type ]->public = false;
			$wp_post_types[ $post_type ]->show_in_nav_menus = false;
			$wp_post_types[ $post_type ]->exclude_from_search = true;

			$this->hidden_post_types[] = $post_type;

			$this->post_types_to_proccess[] = array(
				'post_id' => $post_id,
				'post_type' => $post_type,
			);
			add_action( 'parse_query', array( $this, 'set_archive_errors' ) );

			// Register filters.
			$frontend_filters = FrontendFilters::get_instance();
			add_filter( 'posts_where', array( $frontend_filters, 'filter_posts' ) );
			add_filter( 'get_pages', array( $frontend_filters, 'exclude_pages' ) );
			add_filter( 'the_comments', array( $frontend_filters, 'filter_comments' ) );
		} elseif ( $wp_post_types[ $post_type ] ) {
			$wp_post_types[ $post_type ]->public = true;
		}

		// Exclude post type from search result when a user has no read permissions
		add_action( 'parse_query', array( $this, 'set_search_permissions' ) );
	}


	/**
	 * Exclude post type from search result when a user has no read permissions
	 *
	 * @param \WP_Query $wp_query
	 */
	public function set_search_permissions( $wp_query ) {
		global $wp_post_types;
		if ( ! $wp_query->is_search ) {
			return;
		}
		foreach ( $this->search_result_post_types_to_proccess as $post_types ) {
			$post_type = $post_types['post_type'];
			$temp_post_type_status = $wp_post_types[ $post_type ]->public;
			$wp_post_types[ $post_type ]->public = true;
			$can_read = apply_filters( 'toolset_access_api_get_post_type_permissions', false, $post_type, 'read' );
			if ( $can_read ) {
				$wp_post_types[ $post_type ]->exclude_from_search = false;
				$this->hidden_post_types = array_diff( $this->hidden_post_types, array( $post_type ) );
			} else {
				$wp_post_types[ $post_type ]->exclude_from_search = true;
				$wp_post_types[ $post_type ]->public = $temp_post_type_status;
			}
		}
	}


	/**
	 * @param object $wp_query
	 */
	public function set_archive_errors( $wp_query ) {
		if ( $wp_query->is_search ) {
			return;
		}

		if ( $wp_query->is_archive || $wp_query->is_home ) {
			foreach ( $this->post_types_to_proccess as $post_types ) {
				CustomErrors::get_instance()
					->set_archive_custom_read_errors( $post_types['post_type'], $post_types['post_id'] );
			}
		}
	}


	/**
	 * @param string $post_id
	 * @param string $post_type
	 *
	 * @return bool
	 */
	private function post_type_visibility( $post_id, $post_type ) {
		if ( empty( $post_id ) ) {
			return $this->get_post_type_permissions( $post_type );
		}

		$hide = true;
		$custom_error_info = CustomErrors::get_instance()->set_custom_errors( $post_type, $post_id );
		if ( isset( $custom_error_info[0] ) && 1 === (int) $custom_error_info[0] ) {
			if ( 'unhide' === $custom_error_info[1] ) {
				$hide = false;
			}
			if ( 'hide' === $custom_error_info[1] ) {
				$hide = true;
			}
			if ( isset( $custom_error_info[2] ) && $custom_error_info[2] ) {
				$frontend_filters = FrontendFilters::get_instance();
				add_filter( 'comments_open', array( $frontend_filters, 'toolset_access_disable_comments' ), 1 );
			}
		}

		return $hide;
	}


	/**
	 * @param string $post_type
	 *
	 * @return bool
	 */
	private function get_post_type_permissions( $post_type ) {

		if ( array_key_exists( $post_type, $this->post_type_permissions ) ) {
			return $this->post_type_permissions[ $post_type ];
		}

		global $current_user;
		$hide = true;
		$settings_access = $this->access_settings->get_types_settings();

		if ( ( ! isset( $settings_access[ $post_type ] ) || 'follow' === $settings_access[ $post_type ]['mode'] )
			&& isset( $settings_access['post'] ) ) {
			$data = $settings_access['post']['permissions']['read'];
		} else {
			if ( isset( $settings_access[ $post_type ]['permissions']['read'] ) ) {
				$data = $settings_access[ $post_type ]['permissions']['read'];
			} else {
				return false;
			}
		}

		$users = array();
		$roles = array();
		if ( $data ) {
			if ( isset( $data['users'] ) ) {
				$users = $data['users'];
			}
			if ( ! empty( $data['roles'] ) ) {
				$roles = $data['roles'];
			}
		}

		if ( $this->access_settings->is_wpml_installed() ) {
			$wpml_settings = $this->access_settings->get_language_permissions();
			if ( ! isset( $wpml_settings[ $post_type ] ) ) {
				return false;
			}
			if ( ! $this->current_post_language ) {
				$this->current_post_language = apply_filters( 'wpml_current_language', null );
			}
			$data_language = $wpml_settings[ $post_type ][ $this->current_post_language ]['read'];

			// Specific user
			if ( isset( $data_language['roles'] ) ) {
				$roles = $data_language['roles'];
			}
			if ( isset( $data_language['users'] ) ) {
				$users = $data_language['users'];
			}
		}

		// If user added as specific user
		if ( $current_user->ID && in_array( $current_user->ID, $users, true ) ) {
			$hide = false;
		}

		if ( $hide ) {
			$user_roles = $this->access_roles->get_current_user_roles();
			if ( $user_roles && $this->access_settings->roles_in_array( $user_roles, $roles ) ) {
				$hide = false;
			}
		}
		$this->post_type_permissions[ $post_type ] = $hide;

		return $hide;
	}


	/**
	 * @param string $text
	 *
	 * @return mixed
	 */
	public function wpcf_esc_like( $text ) {
		global $wpdb;
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			return $wpdb->esc_like( $text );
		} else {
			// Required for backward compatibility.
   			// phpcs:ignore WordPress.WP.DeprecatedFunctions.like_escapeFound
			return like_escape( esc_sql( $text ) );
		}
	}


	/**
	 * @return array|mixed
	 */
	private function get_allowed_ajax_actions() {
		$toolset_access_allowed_ajax_actions = array( 'wpv_get_archive_query_results' );
		$toolset_access_allowed_ajax_actions = apply_filters( 'toolset_access_allowed_ajax_actions', $toolset_access_allowed_ajax_actions );

		return $toolset_access_allowed_ajax_actions;
	}


	/**
	 * Set read permissions
	 */
	public function set_frontend_read_permissions_action() {

		if ( $this->read_permissions_set ) {
			return;
		}
		if ( ! empty( $this->custom_read_permissions ) ) {
			foreach ( $this->custom_read_permissions as $custom_read_permission ) {
				$this->set_frontend_read_permissions( $custom_read_permission[1] );
			}
			$this->read_permissions_set = true;
		}
	}


	/**
	 * Get allowed ajax actions
	 *
	 * @return bool
	 */
	private function is_allowed_ajax() {
		$allowed_ajax_actions = $this->get_allowed_ajax_actions();

		return ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] )//phpcs:ignore
			&& in_array( $_REQUEST['action'], $allowed_ajax_actions, true ) );//phpcs:ignore
	}


	/**
	 * @param array $settings_access
	 *
	 * @return bool
	 */
	private function is_posts_managed( $settings_access ) {
		return ( isset( $settings_access['post'] ) && 'permissions' === $settings_access['post']['mode'] );
	}


	/**
	 * Get list of ignored post types
	 *
	 * @return array
	 */
	private function get_ignored_post_types() {
		if ( empty( $this->ignored_post_types ) && class_exists( '\Toolset_Post_Type_Exclude_List' ) ) {
			$post_types_exclude_list = new Toolset_Post_Type_Exclude_List();
			$this->ignored_post_types = apply_filters( 'toolset-access-excluded-post-types', $post_types_exclude_list->get() );
		}

		return $this->ignored_post_types;
	}


	/**
	 * @param array $settings_access
	 * @param string $post_type
	 *
	 * @return array
	 */
	private function get_post_type_data( $settings_access, $post_type ) {
		if ( isset( $settings_access[ $post_type ] ) ) {
			if ( 'follow' === $settings_access[ $post_type ]['mode'] && isset( $settings_access['post'] ) ) {
				return $settings_access['post'];
			} else {
				return $settings_access[ $post_type ];
			}
		}

		return array(
			'mode' => 'not_managed',
			'permission' => 1,
		);

	}


	/**
	 * Filter wp_link_query result to exclude posts where a user has no read permissions
	 *
	 * @param array $result
	 *
	 * @return array
	 */
	public static function filter_wp_link_query( $result ) {

		foreach ( $result as $result_item_key => $result_item ) {
			if ( ! apply_filters( 'toolset_access_api_get_post_permissions', false, $result_item['ID'], 'read' ) ) {
				// no read permission
				unset( $result[ $result_item_key ] );
			}
		}

		return $result;
	}

}
