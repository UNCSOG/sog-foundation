<?php

/**
 * Class Access_Ajax_Handler_Update_Settings
 *
 * @since 2.8
 */
class Access_Ajax_Handler_Update_Settings extends Toolset_Ajax_Handler_Abstract {


	/**
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin( array( 'nonce' => 'toolset_access_user_settings' ) );
		$status = toolset_getpost( 'status', 'true', array( 'true', 'false' ) );
		$status = ( 'true' === $status ? 1 : 0 );
		update_option( 'toolset-access-is-roles-protected', $status );
		$this->ajax_finish( '', true );
		wp_send_json_success();
	}


}
