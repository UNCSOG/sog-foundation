<?php

namespace OTGS\Toolset\Access\Controllers\Filters;

use OTGS\Toolset\Access\Models\Settings as Settings;
use OTGS\Toolset\Access\Models\UserRoles as UserRoles;
use OTGS\Toolset\Access\Models\Capabilities;

/**
 * Class FeedPermissions
 *
 * @package OTGS\Toolset\Access\Controllers\Filters
 * @since 2.7
 */
class FeedPermissions {

	private static $instance;

	private $posts;


	/**
	 * @return FeedPermissions
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
	 * Set post type feed permissions
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	public function set_feed_permissions( $query ) {
		global $current_user;
		$access_settings = Settings::get_instance();
		$settings_access = $access_settings->get_types_settings();

		$user_roles = UserRoles::get_instance();
		$role = $user_roles->get_main_role();
		if ( $role == 'administrator' ) {
			return $query;
		}

		$exclude_ids = array();
		foreach ( $settings_access as $group_slug => $group_data ) {
			if ( strpos( $group_slug, 'wpcf-custom-group-' ) === 0 ) {
				if ( isset( $settings_access[ $group_slug ]['permissions']['read']['users'] )
					&& in_array( $current_user->ID, $settings_access[ $group_slug ]['permissions']['read']['users'] )
				) {
					continue;
				}
				if ( Capabilities::get_instance()->user_has_permission( $settings_access[ $group_slug ]['permissions']['read']['roles'] ) ) {
					$exclude_posts = get_posts( array(
						'meta_key' => '_wpcf_access_group',
						'meta_value' => $group_slug,
						'post_type' => get_post_types(),
					) );
					$temp_posts = wp_list_pluck( $exclude_posts, 'ID' );
					$exclude_ids = array_merge( $exclude_ids, $temp_posts );
				}
			}
		}
		$query['post__not_in'] = $exclude_ids;

		return $query;
	}
}
