<?php
/**
 * Class Access_Ajax_Handler_Remove_Custom_Cap
 * Remove custom capability
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Remove_Custom_Cap extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Remove_Custom_Cap constructor.
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

		global $wp_roles;
		$custom_caps = get_option( 'wpcf_access_custom_caps' );

		if ( ! is_array( $custom_caps ) ) {
			$custom_caps = array();
		}

		$edit_role = sanitize_text_field( $_POST['edit_role'] );
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$access_roles = $access_settings->getAccessRoles();
		$cap = sanitize_text_field( $_POST['cap_name'] );
		$remove = sanitize_text_field( $_POST['remove'] );
		$roles = array();
		if ( $remove == 0 ) {
			foreach ( $access_roles as $role => $role_info ) {
				if ( isset( $role_info['caps'][ $cap ] ) && $role != $edit_role ) {
					$roles[] = ( array_key_exists( 'name', $role_info ) ? $role_info['name'] : ucwords( $role ) );
				}
			}

			if ( count( $roles ) > 0 ) {
				$roles = implode( ", ", $roles );
				$output = '<div class="js-wpcf-removediv js-removediv_' . $cap . '">'
					. '<p>' . __( 'The following role(s) have this capability:', 'wpcf-access' ) . '</p>' . $roles;
				$output .= '<p><button class="js-wpcf-remove-cap-cancel button" data-cap="'
					. $cap
					. '">'
					. __( 'Cancel', 'wpcf-access' )
					. '</button> '
					. '<button class="js-wpcf-remove-cap-anyway button-primary button" data-remove="1" data-object="'
					. sanitize_text_field( $_POST['remove_div'] )
					. '" data-cap="'
					. $cap
					. '">'
					. __( 'Delete anyway', 'wpcf-access' )
					. '</button> '
					. '<span class="ajax-loading spinner"></span>'
					. '</p></div>';
			} else {
				foreach ( $wp_roles->roles as $role => $role_info ) {
					if ( isset( $role_info['capabilities'][ $cap ] ) ) {
						if ( isset( $access_roles[ $role ]['caps'][ $cap ] ) ) {
							unset( $access_roles[ $role ]['caps'][ $cap ] );
						}
						$wp_roles->remove_cap( $role, $cap );
					}
				}
				$access_settings->updateAccessRoles( $access_roles );
				unset( $custom_caps[ $cap ] );
				update_option( 'wpcf_access_custom_caps', $custom_caps );
				$output = 1;
			}
		} else {
			foreach ( $wp_roles->roles as $role => $role_info ) {
				if ( isset( $role_info['capabilities'][ $cap ] ) ) {
					if ( isset( $access_roles[ $role ]['caps'][ $cap ] ) ) {
						unset( $access_roles[ $role ]['caps'][ $cap ] );
					}
					$wp_roles->remove_cap( $role, $cap );
				}
			}
			$access_settings->updateAccessRoles( $access_roles );
			unset( $custom_caps[ $cap ] );
			update_option( 'wpcf_access_custom_caps', $custom_caps );
			$output = 1;
		}
		wp_send_json_success( $output );

	}
}
