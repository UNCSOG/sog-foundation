<?php
/**
 * Class Access_Ajax_Handler_Hide_Max_Fields_Message
 * Remove warning: Maximum fields on Access edit permissions page
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Hide_Max_Fields_Message extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Hide_Max_Fields_Message constructor.
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

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-edit' ) );
		update_option( 'wpcf_hide_max_fields_message', 1 );
		wp_send_json_success();

	}
}
