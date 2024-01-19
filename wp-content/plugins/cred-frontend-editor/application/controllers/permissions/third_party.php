<?php

namespace OTGS\Toolset\CRED\Controller\Permissions;

/**
 * Toolset Forms permissions compatibility with third party plugins.
 *
 * @since 2.1.1
 */
class ThirdParty {

	/**
	 * @var \OTGS\Toolset\CRED\Controller\Permissions
	 */
	private $permissions_manager = null;

	public function __construct( \OTGS\Toolset\CRED\Controller\Permissions $permissions_manager ) {
		$this->permissions_manager = $permissions_manager;
	}

	/**
	 * Initialize the third party compatibility layer with Toolset Forms custom capabilities.
	 *
	 * As a general rule, grant all the custom capabilities to the administrator user role.
	 *
	 * @since 2.1.1
	 */
	public function initialize() {
		global $wp_roles;
		// Force our custom capabilities to the admin role
		// so third parties can manipulate them (eg User Role Editor or Members)
		if (
			! isset( $wp_roles )
			&& class_exists( '\WP_Roles' )
		) {
			$wp_roles = new \WP_Roles();
		}
		$wp_roles->use_db = true;
		if ( $wp_roles->is_role( 'administrator' ) ) {
			$administrator = $wp_roles->get_role( 'administrator' );
		} else {
			$administrator = false;
			trigger_error( __( 'Administrator Role not found! Toolset Forms capabilities will not work', 'wp-cred' ), E_USER_NOTICE );
			return;
		}
		if ( $administrator ) {
			$this->add_custom_capabilities_to_role( $administrator );
		}
	}

	private function maybe_remove_cabability_from_role( $role, $capability, $builtin_custom_cap_prefixes, $bultin_custom_capabilities ) {
		foreach ( $builtin_custom_cap_prefixes as $builtin_custom_cap_prefix ) {
			if (
				strpos( $capability, $builtin_custom_cap_prefix ) === 0
				&& ! in_array( $capability, $bultin_custom_capabilities )
			) {
				$role->remove_cap( $capability );
				return true;
			}
		}
		return false;
	}

	/**
	 * Grant all custom capabilities from Toolset Forms to a given role.
	 *
	 * Also, remove dynamic capabilities that belong to no longer existing forms.
	 *
	 * @param \WP_Role $role
	 * @since 2.1.1
	 */
	private function add_custom_capabilities_to_role( $role ) {
		$role_capabilities = ( isset( $role->capabilities ) ) ? $role->capabilities : array();
		$custom_capabilities_by_form = $this->permissions_manager->get_custom_capabilities_by_form();
		$custom_capabilities_by_post_form = $custom_capabilities_by_form[ \CRED_Form_Domain::POSTS ];
		$custom_capabilities_by_user_form = $custom_capabilities_by_form[ \CRED_Form_Domain::USERS ];
		$custom_capabilities = $this->permissions_manager->get_built_custom_capabilities();

		foreach ( array_keys( $role_capabilities ) as $role_cap ) {
			// Maybe remove obsolete post forms capabilities
			if ( $this->maybe_remove_cabability_from_role( $role, $role_cap, $custom_capabilities_by_post_form['new'], $custom_capabilities[ \CRED_Form_Domain::POSTS ] ) ) {
				continue;
			}
			if ( $this->maybe_remove_cabability_from_role( $role, $role_cap, $custom_capabilities_by_post_form['edit'], $custom_capabilities[ \CRED_Form_Domain::POSTS ] ) ) {
				continue;
			}
			// Maybe remove obsolete user forms capabilities
			if ( $this->maybe_remove_cabability_from_role( $role, $role_cap, $custom_capabilities_by_user_form['new'], $custom_capabilities[ \CRED_Form_Domain::USERS ] ) ) {
				continue;
			}
			if ( $this->maybe_remove_cabability_from_role( $role, $role_cap, $custom_capabilities_by_user_form['edit'], $custom_capabilities[ \CRED_Form_Domain::USERS ] ) ) {
				continue;
			}
		}
		// Grant refreshed post forms capabilities
		foreach ( $custom_capabilities[ \CRED_Form_Domain::POSTS ]  as $custom_cap ) {
			if ( ! $role->has_cap( $custom_cap ) ) {
				$role->add_cap( $custom_cap );
			}
		}
		// Grant refreshed user forms capabilities
		foreach ( $custom_capabilities[ \CRED_Form_Domain::USERS ]  as $custom_cap ) {
			if ( ! $role->has_cap( $custom_cap ) ) {
				$role->add_cap( $custom_cap );
			}
		}
		// Grant refreshed relationship forms capabilities
		foreach ( $custom_capabilities[ \CRED_Form_Domain::ASSOCIATIONS ]  as $custom_cap ) {
			if ( ! $role->has_cap( $custom_cap ) ) {
				$role->add_cap( $custom_cap );
			}
		}
	}

}
