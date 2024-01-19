<?php
/**
 * Class Access_Ajax_Handler_Add_Role
 * Add a custom role
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Add_Role extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Add_Role constructor.
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
		global $wp_roles;

		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$access_roles = $access_settings->getAccessRoles();


		$copy_of = 'subscriber';
		if ( isset( $_POST['copy_of'] )
			&& ! empty( $_POST['copy_of'] )
			&& isset( $wp_roles->roles[ $_POST['copy_of'] ] ) ) {
			$copy_of = $_POST['copy_of'];
		}
		$capabilities['wpcf_access_role'] = true;
		foreach ( $wp_roles->roles[ $copy_of ]['capabilities'] as $cap => $data ) {
			$capabilities[ $cap ] = true;
		}
		if ( preg_match( "/[\,\&\"\']+/", $_POST['role'] ) ) {
			$data = array(
				'type' => 'error',
				'message' => __( 'The symbols <b>, & " \'</b> cannot be used in the role name.', 'wpcf-access' ),
			);
			wp_send_json_error( $data );
			die();
		}
		$role_slug = str_replace( '-', '_', sanitize_title( $_POST['role'] ) );
		$role_slug = str_replace( array( '%', ',', '&', "'", '"' ), '', $role_slug );
		$success = add_role( $role_slug, sanitize_text_field( $_POST['role'] ), $capabilities );

		if ( is_null( $success ) ) {
			$data = array(
				'type' => 'error',
				'message' => __( 'The new role could not be created because that role name is already being used.', 'wpcf-access' ),
			);
			wp_send_json_error( $data );
			die();
		}
		$access_roles[ $role_slug ] = array(
			'name' => sanitize_text_field( $_POST['role'] ),
			'caps' => $capabilities,
		);
		$access_settings->updateAccessRoles( $access_roles );
		$access_settings->add_role_to_settings( $role_slug, $copy_of );
		$data = array(
			'message' => \OTGS\Toolset\Access\Viewmodels\PermissionsTablesCustomRoles::get_instance()
				->get_permission_table_for_custom_roles(),
		);
		wp_send_json_success( $data );
	}
}
