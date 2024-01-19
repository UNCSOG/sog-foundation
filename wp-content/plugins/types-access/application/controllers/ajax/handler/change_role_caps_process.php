<?php
/**
 * Class Change_Role_Caps_Process
 * Generate 'add new Post Group' process
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Change_Role_Caps_Process extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Change_Role_Caps_Process constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {
		parent::__construct( $access_ajax );
	}


	/**
	 * @param mixed $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$access_roles_class = \OTGS\Toolset\Access\Models\UserRoles::get_instance();
		$role = sanitize_text_field( $_POST['role'] );
		$caps = '';
		if ( isset( $_POST['caps'] ) ) {
			$caps = array_map( 'sanitize_text_field', $_POST['caps'] );
		}

		$access_roles = $access_settings->getAccessRoles();

		$all_capabilities = $access_roles_class->get_roles_capabilities_list( array() );
		$role_data = get_role( $role );

		for ( $i = 0, $caps_limit = count( $all_capabilities ); $i < $caps_limit; $i ++ ) {
			if ( isset( $access_roles[ $role ]['caps'][ $all_capabilities[ $i ] ] )
				&& $all_capabilities[ $i ]
				!== 'wpcf_access_role' ) {
				unset( $access_roles[ $role ]['caps'][ $all_capabilities[ $i ] ] );
				$role_data->remove_cap( $all_capabilities[ $i ] );
			}
		}

		if ( ! empty( $caps ) ) {
			for ( $i = 0, $caps_limit = count( $caps ); $i < $caps_limit; $i ++ ) {
				$cap = str_replace( 'Access:cap_', '', $caps[ $i ] );
				$access_roles[ $role ]['caps'][ $cap ] = true;
				$role_data->add_cap( $cap );
			}
		}
		$access_settings->updateAccessRoles( $access_roles );
		wp_send_json_success();
	}
}
