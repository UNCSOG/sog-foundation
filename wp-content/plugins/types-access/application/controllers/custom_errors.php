<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Controllers\PermissionsRead as PermissionsReads;
use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings as Settings;
use OTGS\Toolset\Access\Models\UserRoles as UserRoles;
use OTGS\Toolset\Access\Controllers\Actions\FrontendActions as FrontendActions;
use OTGS\Toolset\Access\Controllers\Filters\FrontendFilters as FrontendFilters;
use OTGS\Toolset\Access\Viewmodels\PermissionsGui;

/**
 * Manage custom read errors for single posts and archives
 *
 * Class CustomErrors
 *
 * @package OTGS\Toolset\Access\Models
 * @since 2.7
 */
class CustomErrors {

	private static $instance;

	/**
	 * @var array
	 */
	private $custom_read_permissions;

	/**
	 * @var boolean
	 */
	private $read_permissions_set;

	/**
	 * @var string
	 */
	private $current_post_language;

	/**
	 * @var Settings
	 */
	private $access_settings;

	/**
	 * @var UserRoles
	 */
	private $access_roles;

	/**
	 * @var Capabilities
	 */
	private $capabilities;


	/**
	 * @return CustomErrors
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
	 * CustomErrors constructor.
	 */
	public function __construct( $settings = null, $user_roles = null, $capabilities = null ) {
		if ( empty( $this->access_settings ) ) {
			$this->access_settings = $settings ?: Settings::get_instance();
			$this->access_roles = $user_roles ?: UserRoles::get_instance();
			$this->capabilities = $capabilities ?: Capabilities::get_instance();
		}
	}


	/**
	 * @param string $post_type
	 * @param string $post_id
	 */
	public function set_archive_custom_read_errors( $post_type, $post_id ) {
		global $wp_post_types;
		$permissions_read = PermissionsReads::get_instance();
		if ( $post_type !== 'attachment' ) {

			$custom_archive_error = \Access_Cacher::get( 'wpcf-access-archive-permissions-' . $post_type );
			if ( false === $custom_archive_error ) {
				$custom_archive_error = $this->get_archive_custom_errors( $post_type );
				\Access_Cacher::set( 'wpcf-access-archive-permissions-' . $post_type, $custom_archive_error );
			}

			if ( isset( $_GET['toolset_access_preview'] ) && $_GET['toolset_access_preview'] == 1 ) {
				if ( isset( $_GET['post_type'] ) && $_GET['post_type'] !== $post_type ) {
					return;
				}
				if ( '404' === $custom_archive_error[1] ) {
					$custom_archive_error[0] = 'hide';
				}
			}

			if ( class_exists( 'WPDD_Layouts' ) && apply_filters( 'ddl-is_integrated_theme', false ) ) {
				$wp_post_types[ $post_type ]->public = true;
			}

			if ( is_array( $custom_archive_error )
				&& ( empty( $post_id )
					|| ( isset( $_GET['toolset_access_preview'] )
						&& $_GET['toolset_access_preview'] == 1 ) ) ) {

				list( $action, $source, $item_id ) = $custom_archive_error;

				if ( $action == 'unhide' ) {

					$permissions_read->hidden_post_types = array_diff( $permissions_read->hidden_post_types, array( $post_type ) );

					$frontend_actions = FrontendActions::get_instance();

					if ( $source == 'view' ) {
						if ( function_exists( 'wpv_force_wordpress_archive' ) ) {
							add_filter( 'wpv_filter_force_wordpress_archive', array(
								$frontend_actions,
								'toolset_access_replace_archive_view',
							) );
						}
					}
					if ( $source == 'layout' && ! empty( $item_id ) ) {
						add_filter( 'ddl-is_ddlayout_assigned', array(
							$frontend_actions,
							'toolset_access_load_layout_archive_is_assigned',
						) );
						add_action( 'wp_head', array(
							$frontend_actions,
							'toolset_access_error_template_archive_layout',
						) );
					}
					if ( $source == 'php' ) {
						add_action( 'template_redirect', array(
							$frontend_actions,
							'toolset_access_replace_archive_php_template',
						) );
					}
				}
			}
		}
	}


	/**
	 * @param string $post_type
	 *
	 * @return array|void
	 */
	private function get_archive_custom_errors( $post_type ) {

		$role = $this->access_roles->get_main_role();
		if ( $role == 'administrator' ) {
			return;
		}

		$settings_access = $this->access_settings->get_types_settings();

		$error_types = array(
			'error_ct' => 'view',
			'error_layouts' => 'layout',
			'error_php' => 'php',
			'default_error' => '404',
			'default' => '404',
		);

		if ( ! isset( $settings_access[ PermissionsGui::CUSTOM_ERROR_ARCHIVE_TYPE ][ $post_type ]['permissions']['read'] ) ) {
			return;
		}

		$custom_error_archive_types = toolset_getnest( $settings_access, array( PermissionsGui::CUSTOM_ERROR_ARCHIVE_TYPE, $post_type, 'permissions', 'read' ), '' );
		$custom_error_archive_values = toolset_getnest( $settings_access, array( PermissionsGui::CUSTOM_ERROR_ARCHIVE_VALUE, $post_type, 'permissions', 'read' ), '' );

		if ( isset( $custom_error_archive_types[ $role ] ) ) {

			$error_type = $custom_error_archive_types[ $role ];

			if ( is_array( $custom_error_archive_values  ) && array_key_exists( $role, $custom_error_archive_values ) ) {
				$error_value = $custom_error_archive_values[ $role ];
				if ( ! array_key_exists( $error_type, $error_types ) ){
					return;
				}
				\Access_Cacher::set( 'wpcf_archive_error_value_' . $post_type, $error_value );

				return array( 'unhide', $error_types[ $error_type ], $error_value );
			} else {
				return;
			}
		}

		if ( isset( $custom_error_archive_types['everyone'] ) && ! empty( $custom_error_archive_types['everyone'] ) ) {
			$error_type = $custom_error_archive_types['everyone'];

			if ( ! empty( $custom_error_archive_values ) ) {
				$error_value = $custom_error_archive_values['everyone'];

				\Access_Cacher::set( 'wpcf_archive_error_value_' . $post_type, $error_value );

				return array( 'unhide', $error_types[ $error_type ], $error_value );
			} else {
				return;
			}
		}
	}

	/**
	 * @param string $post_type
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function set_custom_errors( $post_type, $post_id ) {
		$role = $this->access_roles->get_main_role();
		if ( 'administrator' === $role ) {
			return array( 1, 'unhide' );
		}
		$return = 0;
		$do = '';

		$template = \Access_Cacher::get( 'wpcf-access-post-permissions-' . $post_id );
		if ( false === $template ) {
			$template = $this->get_custom_error( $post_id );
			\Access_Cacher::set( 'wpcf-access-post-permissions-' . $post_id, $template );
		}

		$custom_error = toolset_getarr( $template, 0, '' );
		$custom_error_value = toolset_getarr( $template, 1, '' );

		if ( 'error_ct' === $custom_error ) {
			$this->disable_the_content_hooks();
		}

		$disable_comments = false;
		$frontend_filters = FrontendFilters::get_instance();
		$frontend_actions = FrontendActions::get_instance();

		if ( ! empty( $custom_error_value ) && $custom_error == 'error_ct' ) {
			$do = 'unhide';
			$return = 1;
			$disable_comments = true;
			add_filter( 'wpv_filter_force_template', array(
				$frontend_filters,
				'toolset_access_error_content_template',
			), 20, 3 );
		}
		if ( ! empty( $custom_error_value ) && $custom_error == 'error_php' && ! $template[2] ) {
			$do = 'unhide';
			$return = 1;
			add_action( 'template_redirect', array(
				$frontend_actions,
				'toolset_access_error_php_template',
			), $custom_error_value );
		}
		if ( ! empty( $custom_error_value ) && $custom_error == 'error_layouts' ) {
			$do = 'unhide';
			$return = 1;
			add_action( 'wp', array( $frontend_actions, 'toolset_access_error_template_layout' ) );
		}
		if ( $custom_error == 'error_404' && ! $template[2] ) {
			$do = 'hide';
			add_action( 'pre_get_posts', array(
				$frontend_actions,
				'toolset_access_exclude_selected_post_from_single',
			), 0 );
			$return = 1;
		}
		if ( $template[2] ) {
			$do = 'unhide';
			$return = 1;
		}
		if ( ! $template[2] && empty( $custom_error ) ) {
			$do = 'hide';
			$return = 1;
		}


		return array( $return, $do, $disable_comments );
	}


	/**
	 * @param int $post_id
	 * @return array
	 */
	public function get_custom_error( $post_id ) {
		global $current_user;
		$role = $this->access_roles->get_main_role();

		$settings_access = $this->access_settings->get_types_settings();
		$capabilities = $this->capabilities;

		$post_type = get_post_type( $post_id );
		$post_status = get_post_status( $post_id );

		$template_id = $show = '';
		$group = get_post_meta( $post_id, '_wpcf_access_group', true );

		// If current post not assigned to Post Group, try to load Post Group for the original post
		if ( empty( $group ) && $this->access_settings->is_wpml_installed() ) {
			$default_langauge = apply_filters( 'wpml_default_language', null );
			$post_id_original = apply_filters( 'wpml_object_id', $post_id, $post_type, true, $default_langauge );
			$group = get_post_meta( $post_id_original, '_wpcf_access_group', true );
		}

		$go = true;
		$read = false;


		if ( isset( $settings_access[ $post_type ]['permissions']['read'] )
			&& $settings_access[ $post_type ]['mode']
			== 'permissions' ) {
			$check_cap = $settings_access[ $post_type ]['permissions']['read'];
		} else {
			$check_cap = isset( $settings_access['post']['permissions']['read'] )
				? $settings_access['post']['permissions']['read'] : null;
			$post_type = 'post';
		}

		if ( ! isset( $check_cap['roles'] )
			|| ! isset( $settings_access[ $post_type ] )
			|| $settings_access[ $post_type ]['mode'] === 'not_managed' ) {
			return array( $show, '', true );
		}

		//Read permissions by Language
		if ( $this->access_settings->is_wpml_installed() ) {
			$wpml_settings = $this->access_settings->get_language_permissions();
			if ( ! $this->current_post_language ) {
				$this->current_post_language = apply_filters( 'wpml_current_language', null );
			}
			if ( isset( $wpml_settings[ $post_type ][ $this->current_post_language ] ) ) {
				$check_cap = $wpml_settings[ $post_type ][ $this->current_post_language ];
				if ( isset( $check_cap['group'] ) ) {
					$group = $check_cap['group'];
				} else {
					$check_cap = $check_cap['read'];
				}
			}
		}

		//If group assigned to this post
		if ( isset( $group ) && ! empty( $group ) && isset( $settings_access[ $group ] )
			&& $post_status
			== 'publish' ) {
			$show = '';
			$group_permissions = $settings_access[ $group ]['permissions']['read'];
			if ( isset( $current_user->ID ) ) {
				if ( isset( $group_permissions['users'] )
					&& in_array( $current_user->ID, $group_permissions['users'] )
					!== false ) {
					return array( $show, '', true );
				}
			}

			if ( $capabilities->user_has_permission( $group_permissions['roles'] ) ) {
				return array( $show, '', true );
			} else {
				$read = false;
			}


			//Check if current post and role have specific error.
			if ( isset( $settings_access['_custom_read_errors'][ $group ]['permissions']['read'][ $role ] ) && $go ) {
				$error_type = $settings_access['_custom_read_errors'][ $group ]['permissions']['read'][ $role ];
				$custom_error = isset( $settings_access['_custom_read_errors_value'][ $group ]['permissions']['read'][ $role ] )
					? $settings_access['_custom_read_errors_value'][ $group ]['permissions']['read'][ $role ] : '';
				if ( $error_type == 'error_404' ) {
					$show = $error_type;
					$go = false;
				}
				if ( ( $error_type == 'error_ct' || $error_type == 'error_layouts' ) && ! empty( $custom_error ) ) {
					$show = $error_type;
					$template_id = $custom_error;
					$go = false;
					$read = true;
				}
				if ( $error_type == 'error_php' && ! empty( $custom_error ) ) {
					$show = $error_type;
					$template_id = $custom_error;
					$go = false;
				}
			}

			//Check if current group have specific error
			if ( isset( $settings_access['_custom_read_errors'][ $group ]['permissions']['read']['everyone'] )
				&& $go ) {
				$error_type = $settings_access['_custom_read_errors'][ $group ]['permissions']['read']['everyone'];
				$custom_error = isset( $settings_access['_custom_read_errors_value'][ $group ]['permissions']['read']['everyone'] )
					? $settings_access['_custom_read_errors_value'][ $group ]['permissions']['read']['everyone'] : '';
				if ( $error_type == 'error_404' ) {
					$show = $error_type;
				}
				if ( ( $error_type == 'error_ct' || $error_type == 'error_layouts' ) && ! empty( $custom_error ) ) {
					$show = $error_type;
					$template_id = $custom_error;
				}
				if ( $error_type == 'error_php' && ! empty( $custom_error ) ) {
					$show = $error_type;
					$template_id = $custom_error;
				}
			}

			return array( $show, $template_id, $read );

		}

		// Check post type permissions
		if ( isset( $check_cap['roles'] )
			&& $capabilities->user_has_permission( $check_cap['roles'] )
			|| ( array_key_exists( 'users', $check_cap ) && is_array( $check_cap['users'] )
				&& in_array( $current_user->ID, $check_cap['users'] ) !== false )
		) {
			return array( $show, '', true );
		}


		if ( $go ) {

			//Check if current post and role have specific error.
			if ( isset( $settings_access['_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ] )
				&& $go ) {

				$error_type = $settings_access['_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ];
				$custom_error = isset( $settings_access['_custom_read_errors_value'][ $post_type ]['permissions']['read'][ $role ] )
					? $settings_access['_custom_read_errors_value'][ $post_type ]['permissions']['read'][ $role ] : '';
				if ( $error_type == 'error_404' ) {
					$show = $error_type;
					$go = false;
					$read = false;
				}
				if ( ( $error_type == 'error_ct' || $error_type == 'error_layouts' ) && ! empty( $custom_error ) ) {
					$show = $error_type;
					$template_id = $custom_error;
					$go = false;
					$read = true;
				}
				if ( $error_type == 'error_php' && ! empty( $custom_error ) ) {
					$show = $error_type;
					$template_id = $custom_error;
					$go = false;
				}
			}

			//Check if current group have specific error
			if ( isset( $settings_access['_custom_read_errors'][ $post_type ]['permissions']['read']['everyone'] )
				&& $go ) {
				$error_type = $settings_access['_custom_read_errors'][ $post_type ]['permissions']['read']['everyone'];
				$custom_error = isset( $settings_access['_custom_read_errors_value'][ $post_type ]['permissions']['read']['everyone'] )
					? $settings_access['_custom_read_errors_value'][ $post_type ]['permissions']['read']['everyone']
					: '';
				if ( $error_type == 'error_404' ) {
					$show = $error_type;
					$go = false;
					$read = false;
				}
				if ( ( $error_type == 'error_ct' || $error_type == 'error_layouts' ) && ! empty( $custom_error ) ) {
					$show = $error_type;
					$template_id = $custom_error;
				}
				if ( $error_type == 'error_php' && ! empty( $custom_error ) ) {
					$show = $error_type;
					$template_id = $custom_error;
				}
			}

		}

		return array( $show, $template_id, $read );
	}


	/**
	 * Remove the_content filters from Elementor when render Content Template custom error
	 */
	public function disable_the_content_hooks() {
		global $wp_filter;
		$filters = $wp_filter['the_content'];
		foreach ( $filters as $priority => $filters_array ) {
			foreach ( $filters_array as $filter_index => $filter ) {
				if ( ! is_object( $filter['function'] ) // Skip checking Closure Object
				     && isset( $filter['function'][1] )
				     && is_object( $filter['function'][0] )
					 && 'Elementor\Frontend' == get_class( $filter['function'][0] )
					 && 'apply_builder_in_content' === $filter['function'][1]
				) {
					remove_filter( 'the_content', array(
						$filter['function'][0],
						$filter['function'][1],
					), $priority );
				}
			}
		}
	}


	/**
	 * @param string $text
	 * @return string
	 */
	public function wpcf_esc_like( $text ) {
		global $wpdb;
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			return $wpdb->esc_like( $text );
		} else {
			return like_escape( esc_sql( $text ) );
		}
	}


	/**
	 * @return array
	 */
	public function get_hidden_post_types() {
		$permissions_read = PermissionsRead::get_instance();

		return $permissions_read->hidden_post_types;
	}


	/**
	 * Set read permissions
	 */
	public function set_frontend_read_permissions_action() {

		if ( $this->read_permissions_set ) {
			return;
		}
		if ( ! empty( $this->custom_read_permissions ) ) {
			for ( $i = 0; $i < count( $this->custom_read_permissions ); $i ++ ) {
				$this->set_frontend_read_permissions( $this->custom_read_permissions[ $i ][1] );
			}
			$this->read_permissions_set = true;
		}
	}
}
