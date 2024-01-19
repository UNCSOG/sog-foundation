<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Post\Action;

use OTGS\Toolset\CRED\Controller\FormAction\Message\Base as MessageBase;

/**
 * Post forms action as message base controller.
 * 
 * @since 2.1.2
 */
class Message extends MessageBase {

	/**
	 * Apply the right context to messages displayed after submitting post forms.
	 * 
	 * That context basically means:
	 * - set the current post to be the one created or edited, and restore afterwards.
	 * - apply basic formatting filters.
	 *
	 * @param string $message
	 * @return string
	 * @since 2.1.2
	 */
	protected function apply_content_to_action_message( $message ) {
		global $post;
		$old_post = null;
		
		$target_post_id = (int) toolset_getget( '_target', 0 );

		// Switch to the target post ID if:
		// - it is greater than 0, because get_post( 0 )
		//   will expensively regenerate the current post.
		// - it is indeed pointing to an existing post.
		// - it is different than the current post.
		if ( $target_post_id > 0 ) {
			$target_post = get_post( $target_post_id );

			if ( $target_post instanceof \WP_Post ) {
				if ( $post instanceof \WP_Post ) {
					$old_post_id = $post->ID;
					$old_post = clone $post;
				} else {
					$old_post_id = 0;
				}
				if ( $old_post_id != $target_post_id ) {
					$post = $target_post;
				}
			}
		}

		$message = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $message );

		if ( $old_post instanceof \WP_Post ) {
			$post = $old_post;
		}

		return $message;
	}

}