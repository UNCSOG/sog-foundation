<?php

/**
 * Kill all the dissident posts for a Content Template single post type usage.
 *
 * @since 2.8
 */
class WPV_Ajax_Handler_Ct_Kill_Dissident_Posts extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Processes the AJAX call.
	 *
	 * @param array $arguments Original action arguments.
	 * @since 2.8
	 */
	public function process_call( $arguments ) {

		$this->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CT_KILL_DISSIDENT_POSTS
			)
		);

		$ct_id = (int) toolset_getpost( 'ctId' );
		$post_type = toolset_getpost( 'postType' );

		if ( ! post_type_exists( $post_type ) ) {
			$this->ajax_finish( array( 'message' => __( 'Missing post type', 'wpv-views' ) ), false );
		}

		$template_object = WPV_Content_Template::get_instance( $ct_id );

		if ( null === $template_object ) {
			$this->ajax_finish( array( 'message' => __( 'Missing Content Template', 'wpv-views' ) ), false );
		}

		$template_object->kill_dissident_posts( array( $post_type ) );

		wp_send_json_success();

	}

}
