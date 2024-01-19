<?php
/**
 * Class Save_Section_Status
 * Save section status opened/closed
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Save_Section_Status extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Save_Section_Status constructor.
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
		global $current_user;
		$user_id = $current_user->ID;
		$sections_array = get_user_meta( $user_id, 'wpcf_access_section_status', true );
		if ( empty( $sections_array ) || is_array( $sections_array ) === false ) {
			$sections_array = array();
		}
		$target = sanitize_text_field( $_POST['target'] );
		$status = intval( $_POST['status'] );
		$sections_array[ $target ] = $status;
		update_user_meta( $user_id, 'wpcf_access_section_status', $sections_array );

		wp_send_json_success( true );
	}
}
