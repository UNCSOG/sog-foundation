<?php

namespace OTGS\Toolset\Access\Controllers;

use OTGS\Toolset\Access\Models\Settings as Settings;

/**
 * Manage upload permissions
 *
 * @package OTGS\Toolset\Access\Controllers
 * @since 2.7
 */
class UploadPermissions {

	private static $instance;

	/**
	 * @return UploadPermissions
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function initialize() {
		self::get_instance();
		add_filter( 'toolset_access_additional_capabilities', array( $this, 'set_uploads_capabilities' ), 10, 3 );
	}


	/**
	 * @param array $allcaps
	 * @param array $caps
	 * @param array $args
	 * @param mixed $user
	 *
	 * @return array|mixed
	 */
	public function set_uploads_capabilities( $allcaps, $caps, $args, $user ) {
		$access_settings = Settings::get_instance();
		$post_type_permissions = PermissionsPostTypes::get_instance();

		$access_api = AccessApi::get_instance();

		$post_id = $access_settings->determine_post_id();
		$post_type = $access_settings->determine_post_type();

		if ( empty( $post_id ) && empty( $post_type ) ) {
			$post_type = 'attachment';
		}

		$settings_access = $access_settings->get_types_settings();
		if ( ! isset( $settings_access['attachment'] ) || $settings_access['attachment']['mode'] === 'not_managed' ) {
			$post_type = 'post';
		}
		$allow = false;
		if ( ! empty( $post_id ) ) {
			$allow = $access_api->get_post_permissions_process( false, $post_id, 'edit' );
		}
		if ( empty( $post_id ) ) {
			$allow = $access_api->get_post_type_permissions_process( false, $post_type, 'edit_own' );
		}

		if ( $allow ) {
			$allcaps['upload_files'] = true;
		}

		$allcaps = $post_type_permissions->get_post_type_caps( $allcaps, array( 'edit_media_s' ), array( 'edit_media_s' ), $user, 'edit' );

		return $allcaps;
	}

}
