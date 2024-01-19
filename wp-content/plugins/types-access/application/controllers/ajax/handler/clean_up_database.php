<?php

/**
 * Class Access_Ajax_Handler_Clean_Up_Database
 *
 * @since 2.7
 */
class Access_Ajax_Handler_Clean_Up_Database extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Clean_Up_Database constructor.
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
	function process_call( $arguments ){

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-edit' ) );

		global $wpdb;

		$cleanup_completed = true;

		$this->remove_access_settings( $wpdb );

		$access_roles_instance = \OTGS\Toolset\Access\Models\UserRoles::get_instance();
		$roles = $access_roles_instance->get_editable_roles();

		$cleanup_completed = $this->remove_access_roles_and_users( $wpdb, $cleanup_completed, $roles );

		$this->deactivate_plugin( $cleanup_completed );

		$output = $this->generate_output( $cleanup_completed );

		$this->ajax_finish( $output, true );

		wp_send_json_success( $output );
	}

	/**
	 * Remove Access settings from usermeta, postmeta, options tables
	 * @param \wpdb $wpdb
	 */
	public function remove_access_settings( $wpdb ) {

		if ( isset( $_POST['remove_settings'] ) && 'true' === $_POST['remove_settings'] ) {

			$query_usermeta = $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key IN ( %s, %s )",
					'toolset_access_conversion_ignore_notice', 'wpcf_access_section_status' );
			$wpdb->query( $query_usermeta );

			$query_postmeta = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", '_wpcf_access_group' );
			$wpdb->query( $query_postmeta );

			$access_options = array(
				'toolset-access-options',
				'wpcf-access-version-check',
				'wpcf-access-types',
				'wpcf-access-taxonomies',
				'wpcf-access-3rd-party',
				'wpcf_access_custom_caps',
				'wpcf-access-roles',
				'otg_access_advaced_mode'
				);
			$query_options = $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name IN ( %s, %s, %s, %s, %s, %s, %s, %s )", $access_options );
			$wpdb->query( $query_options );
		}
	}

	/**
	 * Remove Access roles and reasign existing users to selected default role
	 * @param \wpdb $wpdb
	 */
	public function remove_access_roles_and_users( $wpdb, $cleanup_completed, $roles ) {
		if ( isset( $_POST['remove_roles'] ) && 'true' === $_POST['remove_roles'] ){
			$maximum_users_to_reassign = 5;
			$total_processed_users = 0;
			$role_to_assign = ( isset( $_POST['role_to_assign'] ) ? $_POST['role_to_assign'] : '' );

			$access_roles = array( );
			foreach( $roles as $role => $role_data ){
				if ( isset( $role_data['capabilities']['wpcf_access_role'] ) ) {
					$access_roles[] = $role;
				}
			}

			if ( count( $access_roles ) > 0 && ! empty( $role_to_assign ) ) {
				$capabilities_array = serialize( array( $role_to_assign => true ) );
				$roles_to_update = array();
				$user_query = new WP_User_Query( array( 'role__in' => $access_roles, 'number' => ( $maximum_users_to_reassign + 1 ) ) );
				if ( ! empty( $user_query->results ) ) {
					foreach ( $user_query->results as $user ) {
						$total_processed_users++;
						$roles_to_update[] = $user->ID;
						if ( $total_processed_users >= $maximum_users_to_reassign ) {
							$cleanup_completed = false;
							break;
						}
					}
					$sql = "UPDATE $wpdb->usermeta SET meta_value='$capabilities_array' WHERE meta_key='wp_capabilities' AND user_id IN (". implode(',', $roles_to_update).")";
					$wpdb->query( $sql );
				}
			}

			if ( $cleanup_completed ){
				foreach( $roles as $role => $role_data ) {
					if ( isset( $role_data['capabilities']['wpcf_access_role'] ) ) {
						remove_role( $role );
					}
				}
			}
		}

		return $cleanup_completed;
	}

	/**
	 * @param bool $cleanup_completed
	 *
	 * @return array
	 */
	public function generate_output( $cleanup_completed ) {

		if ( $cleanup_completed ){
			if ( isset( $_POST['remove_roles'] ) && $_POST['remove_roles'] == 'true'
			        && isset( $_POST['remove_settings'] ) && $_POST['remove_settings'] != 'true' ) {
				$output = array( 'status' => 1, 'message' => __( 'All done! Access user roles are back to default now','wpcf-access' ) );
			}
			elseif ( isset( $_POST['remove_roles'] ) && $_POST['remove_roles'] != 'true'
			            && isset($_POST['remove_settings']) && $_POST['remove_settings'] == 'true' ) {
				$output = array( 'status' => 1, 'message' => __( 'All done! Access settings are back to default now','wpcf-access' ) );
			}else{
				$output = array( 'status' => 1, 'message' => __( 'All done! Access settings and user roles are back to default now','wpcf-access' ) );
			}
		}else{
			$output = array( 'status' => 2, 'message' => __( 'Reassigning users in progress, %n of %t','wpcf-access' ), 'assigned_users' => $total_processed_users );
		}

		return $output;
	}

	/**
	 * Deactivate Access plugin
	 * @param bool $cleanup_completed
	 */
	public function deactivate_plugin( $cleanup_completed ) {
		if ( isset( $_POST['disable_plugin'] ) && $_POST['disable_plugin'] == 'true' && $cleanup_completed ){
			$file = TACCESS_PLUGIN_PATH . '/types-access.php';
			deactivate_plugins( $file );
		}
	}


}
