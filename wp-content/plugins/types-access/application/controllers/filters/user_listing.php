<?php

namespace OTGS\Toolset\Access\Controllers\Filters;

use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;

/**
 * Filter the results when listing users and exclude users and roles higher than the role(s) of the current user.
 * Class UserListing
 *
 * @package OTGS\Toolset\Access\Controllers\Filters
 */
class UserListing {
	/**
	 * @var array
	 */
	public $excluded_roles = array();

	/**
	 * @var Settings
	 */
	private $access_settings;

	/**
	 * @var UserRoles
	 */
	private $access_roles;

	/**
	 * UserListing constructor.
	 *
	 * @param object $access_settings
	 * @param object $access_roles
	 */
	public function __construct( $access_settings, $access_roles ) {
		$this->access_settings = $access_settings;
		$this->access_roles = $access_roles;
		$is_roles_protected = get_option( 'toolset-access-is-roles-protected', true );
		if ( $this->access_roles->is_administrator() || ! $is_roles_protected ) {
			return;
		}
		$this->add_hooks();
	}

	/**
	 * Add filters
	 */
	public function add_hooks() {
		add_filter( 'pre_get_users', array( $this, 'filter_high_level_users' ) );
		add_filter( 'views_users', array( $this, 'filter_high_level_users_links' ) );
		add_filter( 'editable_roles', array( $this, 'filter_editable_roles' ) );
	}

	/**
	 * Filter users query
	 *
	 * @param \WP_Query $query
	 *
	 * @return \WP_Query
	 */
	public function filter_high_level_users( $query ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['action'] ) && 'wpv_get_view_query_results' === $_POST['action'] ) {
			return;
		}
		$excluded_roles = $this->get_excluded_roles();
		$query->query_vars['role__not_in'] = $excluded_roles;

		return $query;
	}

	/**
	 * Filter roles links on users listing page
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function filter_high_level_users_links( $links ) {
		$excluded_roles = $this->get_excluded_roles();
		foreach ( $excluded_roles as $role ) {
			if ( isset( $links[ $role ] ) ) {
				unset( $links[ $role ] );
			}
		}

		return $links;
	}

	/**
	 * Filter editable roles array to exclude roles from roles dropdown
	 *
	 * @param array $roles
	 *
	 * @return mixed
	 */
	public function filter_editable_roles( $roles ) {
		$excluded_roles = $this->get_excluded_roles();

		foreach ( $excluded_roles as $role ) {
			if ( isset( $roles[ $role ] ) ) {
				unset( $roles[ $role ] );
			}
		}

		return $roles;
	}

	/**
	 * Get a list of roles to exclude from a user listing
	 *
	 * @return array|string
	 */
	private function get_excluded_roles() {
		if ( ! empty( $this->excluded_roles ) ) {
			return $this->excluded_roles;
		}

		$ordered_roles = $this->access_settings->get_ordered_wp_roles( false, true );
		$user_roles = $this->access_roles->get_current_user_roles();

		$this->excluded_roles = array();
		foreach ( $ordered_roles as $group_roles ) {
			$group_roles = array_keys( $group_roles );

			if ( ! array_intersect( $user_roles, $group_roles ) ) {
				$this->excluded_roles = array_merge( $this->excluded_roles, $group_roles );
			} else {
				break;
			}
		}

		return $this->excluded_roles;
	}
}
