<?php

class CRED_Ajax_Handler_Delete_Post extends Toolset_Ajax_Handler_Abstract{

	private $post_to_delete;

	function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => CRED_Ajax::CALLBACK_DELETE_POST,
				'is_public' => true,
			)
		);

		$post_id = toolset_getpost( 'credPostId' );
		$action = toolset_getpost( 'credAction', 'trash', array( 'trash', 'delete' ) );
		$on_success = toolset_getpost( 'credOnSuccess' );

		if ( empty( $post_id ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing post.', 'wp-cred' ) ), false );
			return;
		}

		$this->post_to_delete = get_post( $post_id );

		if ( null === $this->post_to_delete ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing post.', 'wp-cred' ) ), false );
			return;
		}

		if ( ! $this->current_user_can() ) {
			$this->ajax_finish( array( 'message' => __( 'You are not allowed to perform this action.', 'wp-cred' ) ), false );
			return;
		}

		$results = array(
			'onsuccess' => is_numeric( $on_success )
				/**
				 * Filter the redirect URL after deleting a post.
				 *
				 * @param string URL to redirect to.
				 * @param int $post_id ID of the post to delete. Note that the post is not deleted yet by the time this filter is applied.
				 * @return string
				 * @since unknown
				 */
				? (string) apply_filters( 'cred_redirect_after_delete_action', get_permalink( $on_success ), $post_id )
				: $on_success
		);

		switch ( $action ) {
			case 'delete':
				wp_delete_post( $post_id, true );
				break;
			case 'trash':
				wp_trash_post( $post_id );
				break;
		}

		$this->ajax_finish( $results, true );
	}

	private function current_user_can() {
		global $current_user;

		if ( $current_user->ID == $this->post_to_delete->post_author ) {
			return current_user_can( 'delete_own_posts_with_cred' );
		}

		return current_user_can( 'delete_other_posts_with_cred' );
	}

}
