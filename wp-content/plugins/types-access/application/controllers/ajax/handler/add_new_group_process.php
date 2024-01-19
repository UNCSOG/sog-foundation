<?php
/**
 * Class Access_Ajax_Handler_Add_New_Group_Process
 * Process new Post Group
 *
 * @since 2.7
 */
class Access_Ajax_Handler_Add_New_Group_Process extends Toolset_Ajax_Handler_Abstract {

	/**
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$settings_access = $access_settings->get_types_settings( true, true );
		$group_name = ( isset( $_POST['title'] ) ? $_POST['title'] : '' ); // phpcs:ignore

		$group_name_generated = $this->generate_group_nice_name( $settings_access, $group_name );
		$is_group_name_valid = true;
		if ( ! empty( $settings_access ) ) {
			foreach ( $settings_access as $permission_slug => $permission_data ) {
				if ( isset( $permission_data['title'] ) && sanitize_text_field( $permission_data['title'] ) === $group_name ) {
					$is_group_name_valid = false;
					break;
				}
			}
		}
		if ( ! $is_group_name_valid ) {
			wp_send_json_error( 'error' );
		}
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posts = array_map( 'intval', isset( $_POST['posts'] ) ? $_POST['posts'] : array() );

		if ( isset( $settings_access['post']['permissions']['read']['roles'] ) ) {
			$roles = $settings_access['post']['permissions']['read']['roles'];
		} else {
			$ordered_roles = $access_settings->order_wp_roles();
			$roles = array_keys( $ordered_roles );
		}
		$groups = array();
		$groups[ $group_name_generated ] = array(
			'title' => sanitize_text_field( $group_name ),
			'mode' => 'permissions',
			'permissions' => array( 'read' => array( 'roles' => $roles ) ),
		);

		for ( $i = 0, $limit = count( $posts ); $i < $limit; $i ++ ) {
			update_post_meta( $posts[ $i ], '_wpcf_access_group', $group_name_generated );
		}
		$settings_access = array_merge( $settings_access, $groups );
		$access_settings->updateAccessTypes( $settings_access );

		wp_send_json_success( array( 'id' => $group_name_generated ) );
	}


	/**
	 * @param array $settings_access
	 * @param string $group_name
	 *
	 * @return string
	 */
	public function generate_group_nice_name( $settings_access, $group_name ) {
		$nice = 'wpcf-custom-group-' . md5( sanitize_title( $group_name ) );
		$i = 0;
		while ( array_key_exists( $nice, $settings_access ) ) {
			$i++;
			$nice = 'wpcf-custom-group-' . md5( sanitize_title( $group_name . $i ) );
		}
		return $nice;
	}
}
