<?php
/**
 * Class Access_Ajax_Handler_Select_Post_Group_For_Post_Process
 * Select Post Group for a post process
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Select_Post_Group_For_Post_Process extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Select_Post_Group_For_Post_Process constructor.
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

		$requeste_capability = ( current_user_can( 'manage_options') ? 'manage_options' : 'access_change_post_group' );

		$this->ajax_begin( array(
			'nonce' => 'wpcf-access-error-pages',
			'capability_needed' => $requeste_capability,
		) );

		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( __( 'Post ID not found', 'wpcf-access' ) );
		}

		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$settings_access = $access_settings->get_types_settings( true, true );

		if ( $_POST['methodtype'] == 'existing_group' ) {

			update_post_meta( sanitize_text_field( $_POST['id'] ), '_wpcf_access_group', sanitize_text_field( $_POST['group'] ) );
			if ( $_POST['group'] != '' ) {
				$message = sprintf(
						__( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf-access' ), esc_attr( $settings_access[ $_POST['group'] ]['title'] ) )
					. '</p>';
				if ( current_user_can( 'manage_options' ) ) {
					$message .= '<p><a href="admin.php?page=types_access&tab=custom-group">'
						.
						sprintf( __( 'Edit %s group privileges', 'wpcf-access' ), $settings_access[ sanitize_text_field( $_POST['group'] ) ]['title'] )
						. '</a></p>';
				}
			} else {
				$message = __( 'No group selected.', 'wpcf-access' );
			}
		} else {
			if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'access_create_new_group' ) ) {
				wp_send_json_error( __( 'There are security problems. You do not have permissions.', 'wpcf-access' ) );
			}
			$nice = 'wpcf-custom-group-' . md5( sanitize_title( $_POST['new_group'] ) );
			$access_roles = \OTGS\Toolset\Access\Models\UserRoles::get_instance();
			$groups[ $nice ] = array(
				'title' => sanitize_text_field( $_POST['new_group'] ),
				'mode' => 'permissions',
				'permissions' => array( 'read' => array( 'roles' => $access_roles->get_roles_by_role( 'guest' ) ) ),
			);

			if( isset( $settings_access[ $nice ] ) ) {
				wp_send_json_error( 'error' );
			}

			update_post_meta( sanitize_text_field( $_POST['id'] ), '_wpcf_access_group', $nice );
			$settings_access = array_merge( $settings_access, $groups );
			$access_settings->updateAccessTypes( $settings_access );
			$message = sprintf(
					__( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf-access' ), esc_attr( $_POST['new_group'] ) )
				. '</p>';
			if ( current_user_can( 'manage_options' ) ) {
				$message .= '<p><a href="admin.php?page=types_access&tab=custom-group">'
					. sprintf( __( 'Edit %s group privileges', 'wpcf-access' ), esc_attr( $_POST['new_group'] ) )
					. '</a></p>';
			}
		}
		wp_send_json_success( $message );
	}
}
