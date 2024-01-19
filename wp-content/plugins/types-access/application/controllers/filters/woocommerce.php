<?php

namespace OTGS\Toolset\Access\Controllers\Filters;

/**
 * Filter the results when listing users and exclude users and roles higher than the role(s) of the current user.
 * Class Woocommerce
 *
 * @package OTGS\Toolset\Access\Controllers\Filters
 */
class Woocommerce {

	/**
	 * @var \OTGS\Toolset\Access\Models\Settings
	 */
	private $access_settings_class = '';

	/**
	 * @var \OTGS\Toolset\Access\Models\UserRoles
	 */
	private $access_roles_class = '';

	/**
	 * Woocommerce constructor.
	 *
	 * @param \OTGS\Toolset\Access\Models\Settings $access_settings
	 * @param \OTGS\Toolset\Access\Models\UserRoles $access_roles
	 */
	public function __construct( $access_settings, $access_roles ) {
		$this->access_settings_class = $access_settings;
		$this->access_roles_class    = $access_roles;

		add_filter( 'woocommerce_prevent_admin_access', array( $this, 'wc_allow_admin_access_for_editors' ) );
	}

	/**
	 * Checks if a current user can edit at least one post type and allow login to admin when WooCommerce is enabled
	 *
	 * @param bool $prevent_access
	 *
	 * @return bool
	 */
	public function wc_allow_admin_access_for_editors( $prevent_access = true ) {
		if ( $this->access_roles_class->is_administrator() ) {
			return false;
		}
		$user_roles      = $this->access_roles_class->get_current_user_roles( false );
		$settings_access = $this->access_settings_class->get_types_settings();

		foreach ( $settings_access as $post_type => $permissions ) {
			$roles          = toolset_getnest( $permissions, array( 'permissions', 'edit_own', 'roles' ), array() );
			$roles_can_edit = array_intersect( $user_roles, $roles );
			if ( ! empty( $roles_can_edit ) ) {
				$prevent_access = false;
				break;
			}
		}

		return $prevent_access;
	}


}
