<?php
/**
 * Class Access_Ajax_Handler_Remove_Post_Group_Form
 * Remove Post Group
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Remove_Post_Group_Form extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Remove_Post_Group_Form constructor.
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
		$out = '<form method="">
		<p>' . __( 'Are you sure want to remove this group?', 'wpcf-access' ) . '</p></form>';
		$out = '<div class="toolset-access-alarm-wrap-left"><i class="fa fa-exclamation-triangle fa-5x"></i></div>
					<div class="toolset-access-alarm-wrap-right">' . $out . '</div>';

		wp_send_json_success( $out );
	}
}
