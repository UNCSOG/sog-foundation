<?php
/**
 * Class Access_Ajax_Handler_Delete_Role_Form
 * Show delete custom role form
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Delete_Role_Form extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Delete_Role_Form constructor.
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
		$output = '';

		if ( ! isset( $_POST['role'] ) || empty( $_POST['role'] ) ) {
			die();
		}
		$role = $_POST['role'];

		$output .= '<div class="wpcf-access-reassign-role-popup">';
		$users = get_users( 'role=' . $role . '&number=5' );
		$users_txt = array();
		foreach ( $users as $user ) {
			$users_txt[] = $user->display_name;
		}
		if ( ! empty( $users ) ) {
			$users_txt = implode( '</li><li> ', $users_txt );
			$output .= sprintf( __( 'Assign current %s users to another role: ',
				'wpcf-access' ), '<ul><li>' . $users_txt . '</li></ul>' );
			$output .= \OTGS\Toolset\Access\Viewmodels\PermissionsGui::get_instance()->admin_roles_dropdown( $access_settings->wpcf_get_editable_roles(),
				'wpcf_reassign', array(),
				__( '-- Select role --', 'wpcf-access' ), true, array( $role ) );
		} else {
			$output .= '<input type="hidden" name="wpcf_reassign" class="js-wpcf-reassign-role" value="ignore" />';
			$output .= '<strong>' . __( 'Do you really want to remove this role?', 'wpcf-access' ) . '</strong>';
		}
		$output .= '</div> <!-- .wpcf-access-reassign-role-popup -->';

		$output = '<div class="toolset-access-alarm-wrap-left"><i class="fa fa-exclamation-triangle fa-5x"></i></div>
					<div class="toolset-access-alarm-wrap-right">' . $output . '</div>';

		wp_send_json_success( $output );

	}
}
