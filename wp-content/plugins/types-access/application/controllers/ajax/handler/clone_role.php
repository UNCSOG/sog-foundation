<?php
/**
 * Class Access_Ajax_Handler_Clone_Role
 * Clone a role capabilities
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Clone_Role extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Clone_Role constructor.
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

		$this->ajax_begin( array( 'nonce' => 'otg_access_general_nonce' ) );
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();

		if ( ! empty( $_POST['roles'] ) ) {
			$access_roles = $access_settings->getAccessRoles();

			foreach ( $_POST['roles'] as $role => $level ) {
				$role = sanitize_text_field( $role );
				$level = sanitize_text_field( $level );
				$clone_from = 'subscriber';
				if ( $level == 1 ) {
					$clone_from = 'contributor';
				}
				if ( $level >= 2 && $level < 7 ) {
					$clone_from = 'author';
				}
				if ( $level >= 7 && $level < 10 ) {
					$clone_from = 'editor';
				}
				if ( $level == 10 ) {
					$clone_from = 'administrator';
				}
				$temp_role_data = get_role( $clone_from );

				$role_data = get_role( $role );
				foreach ( $role_data->capabilities as $role_cap => $role_status ) {
					$role_data->remove_cap( $role_cap );
				}

				foreach ( $temp_role_data->capabilities as $role_cap => $role_status ) {
					$role_data->add_cap( $role_cap );
				}
				$role_data->add_cap( 'wpcf_access_role' );


				$role_data = get_role( $role );

				if ( ! empty( $role_data ) ) {
					$level = intval( $level );
					for ( $index = 0; $index < 11; $index ++ ) {
						if ( $index <= $level ) {
							$role_data->add_cap( 'level_' . $index, 1 );
						} else {
							$role_data->remove_cap( 'level_' . $index );
						}
						if ( isset( $access_roles[ $role ] ) ) {
							if ( isset( $access_roles[ $role ]['caps'] ) ) {
								if ( $index <= $level ) {
									$access_roles[ $role ]['caps'][ 'level_' . $index ] = true;
								} else {
									unset( $access_roles[ $role ]['caps'][ 'level_' . $index ] );
								}
							}
						}
					}

				}
			}
			$access_settings->updateAccessRoles( $access_roles );
		}
		$data = array(
			'message' => \OTGS\Toolset\Access\Viewmodels\PermissionsTablesCustomRoles::get_instance()->get_permission_table_for_custom_roles(),
		);
		wp_send_json_success( $data );
	}
}
