<?php

namespace OTGS\Toolset\Access\Controllers\Actions;

use OTGS\Toolset\Access\Models\Settings as Settings;
use OTGS\Toolset\Access\Models\UserRoles as UserRoles;
use OTGS\Toolset\Access\Viewmodels\PermissionsGui;

/**
 * Preview Access Custom error class
 *
 * Class ErrorPreview
 *
 * @package OTGS\Toolset\Access\Controllers\Actions
 * @since 2.7
 */
class ErrorPreview {

	private static $instance;

	/** @var Settings */
	private $access_settings;

	/** @var UserRoles */
	private $access_roles;

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
	 * Change current user main role and temporary update custom error
	 */
	function preview_error() {
		if ( isset( $_GET['toolset_access_preview'] ) && $_GET['toolset_access_preview'] == 1 ) {
			global $current_user, $wp_roles, $wpcf_access;

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$role = sanitize_text_field( $_GET['role'] );
			$error_type = sanitize_text_field( $_GET['error_type'] );
			$error_id = sanitize_text_field( $_GET['id'] );
			$post_type = sanitize_text_field( $_GET['access_preview_post_type'] );
			$access_preview = sanitize_text_field( $_GET['access_preview'] );
			$update_user = false;


			if ( ! array_key_exists( $role, $wp_roles->roles ) && $role != 'guest' ) {
				return;
			}

			if ( empty( $this->access_settings ) ) {
				$this->access_settings = Settings::get_instance();
				$this->access_roles = UserRoles::get_instance();
			}

			if ( $access_preview == 'single' ) {
				if ( ( $error_type == 'error_layouts' || $error_type == 'error_ct' ) && empty( $error_id ) ) {
					return;
				}

				$wpcf_access->wpml_installed = apply_filters( 'wpml_setting', false, 'setup_complete' );

				if ( isset( $wpcf_access->settings->types[ $post_type ]['permissions']['read']['roles'] ) ) {
					$role_index = array_search( $role, $wpcf_access->settings->types[ $post_type ]['permissions']['read']['roles'] );
					unset( $wpcf_access->settings->types[ $post_type ]['permissions']['read']['roles'][ $role_index ] );
				}

				$wpcf_access->settings->types[ $post_type ]['permissions']['read']['users'] = array();
				$wpcf_access->settings->types[ PermissionsGui::CUSTOM_ERROR_SINGLE_POST_TYPE ][ $post_type ]['permissions']['read'][ $role ] = $error_type;
				$wpcf_access->settings->types[ PermissionsGui::CUSTOM_ERROR_SINGLE_POST_VALUE ][ $post_type ]['permissions']['read'][ $role ] = $error_id;

				$update_user = true;
			}

			if ( $access_preview == 'archive' ) {
				if ( ( $error_type == 'error_layouts' || $error_type == 'error_ct' || $error_type == 'error_php' )
					&& empty( $error_id ) ) {
					return;
				}

				$wpcf_access->settings->types[ PermissionsGui::CUSTOM_ERROR_ARCHIVE_TYPE ][ $post_type ]['permissions']['read'][ $role ] = $error_type;
				$wpcf_access->settings->types[ PermissionsGui::CUSTOM_ERROR_ARCHIVE_VALUE ][ $post_type ]['permissions']['read'][ $role ] = $error_id;
				$update_user = true;
			}

			if ( $update_user ) {
				if ( $role != 'guest' ) {
					$current_user->roles = array( $role );
					$current_user->caps = $wp_roles->roles[ $role ]['capabilities'];
					$current_user->allcaps = $wp_roles->roles[ $role ]['capabilities'];
				} else {
					$current_user->roles = array();
					$current_user->caps = array( 'read' );
					$current_user->allcaps = array( 'read' );
				}
			}
		}
	}
}
