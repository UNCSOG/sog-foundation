<?php
/**
 * Class Remove_Assignment_Post_Group
 * Remove assignment from a post
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Remove_Assignment_Post_Group extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Remove_Assignment_Post_Group constructor.
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
		delete_post_meta( sanitize_text_field( $_POST['id'] ), '_wpcf_access_group' );
		wp_send_json_success();
	}
}
