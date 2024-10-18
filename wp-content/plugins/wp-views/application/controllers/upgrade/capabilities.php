<?php
/**
 * Upgrade capabilities on Toolset Views 3.0
 */

namespace OTGS\Toolset\Views\Controller\Upgrade;

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Grant editing capabilities now that we have a custom cap.
 *
 * @since 3.0
 */
class Capabilities implements IRoutine {

	const COMPLETE_3000000_BETA_LEFTOVER_FLAG = 'toolset_edit_views_granted';

	/**
	 * Execute safety car
	 *
	 * @param array $args
	 * @since 3.0
	 */
	public function execute_routine( $args = array() ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Grant the capability to all roles that include manage_options
		global $wp_roles;
		foreach ( $wp_roles->roles as $key => $role ) {
			if ( isset( $role['capabilities']['manage_options'] ) && $role['capabilities']['manage_options'] ) {
				$wp_roles->add_cap( $key, EDIT_VIEWS );
			}
		}
		// Grant the capability to admins if it wasn't granted above
		$wp_roles->add_cap( 'administrator', EDIT_VIEWS );

		// Clean leftover from 3.0 betas
		delete_option( self::COMPLETE_3000000_BETA_LEFTOVER_FLAG );
	}

}
