<?php
/**
 * Class Access_Ajax_Handler_Enable_Advanced_Mode
 * Enable/Disabled Access advanced mode
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Enable_Advanced_Mode extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Enable_Advanced_Mode constructor.
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

		$advanced_mode = get_option( 'otg_access_advaced_mode', 'false' );
		if ( $advanced_mode === 'false' ) {
			$new_mode = 'true';
		} else {
			$new_mode = 'false';
		}
		update_option( 'otg_access_advaced_mode', $new_mode );

		$data = array(
			'message' => \OTGS\Toolset\Access\Viewmodels\PermissionsTablesCustomRoles::get_instance()->get_permission_table_for_custom_roles(),
		);

		wp_send_json_success( $data );

	}
}
