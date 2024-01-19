<?php

namespace OTGS\Toolset\CRED\Controller;

/**
 * Comments manager controller.
 *
 * Apply the settings to hide comments on pages featuring a form.
 *
 * @since 2.4
 */
class CommentsManager {

	/**
	 * @var \OTGS\Toolset\CRED\Controller\CommentsManager\Repository
	 */
	private $repository = null;

	/**
	 * @var bool
	 */
	private $comments_were_disabled = false;

	/**
	 * @var bool
	 */
	private $current_page_can_support_comments = null;

	/**
	 * Class constructor
	 *
	 * @param \OTGS\Toolset\CRED\Controller\CommentsManager\Repository $repository
	 */
	public function __construct(
		\OTGS\Toolset\CRED\Controller\CommentsManager\Repository $repository
	) {
		$this->repository = $repository;
	}

	/**
	 * Initialize the public hooks for this controller.
	 *
	 * @since 2.4
	 */
	public function initialize() {
		add_action( 'toolset_forms_frontend_flow_form_started', array( $this, 'maybe_hide_comments' ) );
	}

	/**
	 * Maybe hide comments on a page featuring a form.
	 *
	 * @param \WP_Post $form
	 * @since 2.4
	 */
	public function maybe_hide_comments( \WP_Post $form ) {

		if ( $this->comments_were_disabled ) {
			return;
		}

		if ( ! $this->can_current_page_support_comments() ) {
			return;
		}

		$comments_manager = $this->repository->get_controller( $form );

		if ( null === $comments_manager ) {
			return;
		}

		if ( $comments_manager->maybe_disable_comments( $form->ID ) ) {
			$this->disable_comments();
		}
	}

	/**
	 * Check whether we are in a frontend singular page and the global post is set.
	 *
	 * @return bool
	 * @since 2.4
	 */
	private function can_current_page_support_comments() {
		if ( null !== $this->current_page_can_support_comments ) {
			return $this->current_page_can_support_comments;
		}

		global $post;
		$this->current_page_can_support_comments = (
			is_singular()
			&& isset( $post )
		);

		return $this->current_page_can_support_comments;
	}

	/**
	 * Do the actual comments disabling:
	 * - remove post type support for comments and trackbacks.
	 * - set the post properties to hold no comments and be able to get no comments.
	 * - clear the query object from comments.
	 *
	 * @since 2.4
	 */
	private function disable_comments() {
		global $post;
		global $wp_query;
		remove_post_type_support( $post->post_type, 'comments' );
		remove_post_type_support( $post->post_type, 'trackbacks' );
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';
		$post->comment_count = 0;
		$wp_query->comment_count = 0;
		$wp_query->comments = array();
		add_filter( 'comments_open', '__return_false', 1000 );
		add_filter( 'pings_open', '__return_false', 1000 );
		add_filter( 'comments_array', '__return_empty_array', 1000 );

		$this->comments_were_disabled = true;
	}

}
