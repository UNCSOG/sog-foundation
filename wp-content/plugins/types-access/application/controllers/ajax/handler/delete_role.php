<?php
/**
 * Class Access_Ajax_Handler_Delete_Role
 * Clone a role capabilities
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Delete_Role extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Delete_Role constructor.
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
		$access_capabilities = \OTGS\Toolset\Access\Models\Capabilities::get_instance();

		if ( in_array( strtolower( trim( $_POST['wpcf_access_delete_role'] ) ), $access_capabilities->get_default_roles() ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'That role can not be deleted.', 'wpcf-access' ),
			);
			wp_send_json_error( $data );
		}

		$delete_role = sanitize_text_field( $_POST['wpcf_access_delete_role'] );

		$access_roles = $access_settings->getAccessRoles();
		if ( $_POST['wpcf_reassign'] != 'ignore' ) {
			$users = get_users( 'role=' . $delete_role );
			foreach ( $users as $user ) {
				$user = new WP_User( $user->ID );
				$user->add_role( sanitize_text_field( $_POST['wpcf_reassign'] ) );
				$user->remove_role( $delete_role );
			}
		}
		remove_role( $delete_role );
		if ( isset( $access_roles[ $delete_role ] ) ) {
			unset( $access_roles[ $delete_role ] );
		}
		$access_settings->updateAccessRoles( $access_roles );

		$data = array(
			'message' => \OTGS\Toolset\Access\Viewmodels\PermissionsTablesCustomRoles::get_instance()->get_permission_table_for_custom_roles()
		);
		wp_send_json_success( $data );

	}
}
