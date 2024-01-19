<?php

namespace OTGS\Toolset\Views\Controllers\V1;

use OTGS\Toolset\Views\Controllers\RestControllerInterface;
use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Abstract class for REST controllers
 */
abstract class Base extends \WP_REST_Controller implements RestControllerInterface {
	protected $namespace = 'toolset-views/v1';

	/**
	 * Get the namepsace of the REST request handled by this controller.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	public function can_edit_view() {
		return current_user_can( EDIT_VIEWS );
	}

	/**
	 * Returns correct view ID according to its post name (slug)
	 *
	 * @param int $id Supposed view ID
	 * @param string $slug View Slug
	 *
	 * @return int
	 */
	protected function get_real_view_id( $id, $slug ) {
		$view = \WPV_View::get_instance( $id );

		if ( $view->post_name === $slug ) {
			return $id;
		}

		$posts = get_posts(
			array(
				'name' => $slug,
				'post_type' => 'view',
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);

		if ( count( $posts ) > 0 ) {
			return $posts[0]->ID;
		}

		return $this->maybe_get_view_id_from_view_draft_post( $slug );
	}

	/**
	 * When the slug of the View draft post is used for some reason, use it to find the View id of the actual View connected
	 * to this draft.
	 *
	 * @param  string $slug The slug of the draft View post.
	 *
	 * @return int    The id of the actual View or zero when it could not be determined.
	 */
	private function maybe_get_view_id_from_view_draft_post( $slug ) {
		$maybe_draft_view = get_posts(
			array(
				'name' => $slug,
				'post_type' => 'view',
				'post_status' => 'draft',
				'numberposts' => 1,
			)
		);

		if ( count( $maybe_draft_view ) > 0 ) {
			$view_draft = \WPV_View::get_instance( $maybe_draft_view[0]->ID );
			$view_draft_data = $view_draft->get_postmeta( '_wpv_view_data' );
			return (int) toolset_getnest( $view_draft_data, array( 'general', 'id' ), null );
		}

		return 0;
	}
}
