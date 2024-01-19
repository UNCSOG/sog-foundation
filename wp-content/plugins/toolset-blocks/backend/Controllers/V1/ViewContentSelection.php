<?php

namespace OTGS\Toolset\Views\Controllers\V1;

use OTGS\Toolset\Views\Services\ContentSelectionService;

/**
 * Handles the REST API requests to fetch Content Selection related information for a View that exists inside a Content
 * Template designed in the block editor.
 */
class ViewContentSelection extends Base {
	const CODE_INVALID_ARGUMENTS = 'invalid_arguments';

	const MESSAGE_INVALID_ARGUMENTS = 'Invalid arguments';

	const CODE_INVALID_POST_TYPE = 'invalid_post_type';

	const MESSAGE_INVALID_POST_TYPE = 'Invalid post type';

	/** @var ContentSelectionService */
	private $content_selection_service;

	/**
	 * ContentTemplate constructor.
	 *
	 * @param ContentSelectionService $content_selection_service
	 */
	public function __construct( ContentSelectionService $content_selection_service  ) {
		$this->content_selection_service = $content_selection_service;
	}

	/**
	 * Registers the REST API routes that will serve all the Content Selection related information that are post type
	 * relevant (related post types and RFGs).
	 *
	 * Mostly needed for fetching Content Selection information for Views inside Content Templates.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/content-selection/related-post-types',
			array(
				array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_related_post_types' ),
					'args' => array(
						'post-type' => array(
							'required' => true,
							'sanitize_callback' => 'sanitize_text_field',
						),

					),
					'permission_callback' => array( $this, 'can_edit_view' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/content-selection/related-rfgs',
			array(
				array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_related_rfgs' ),
					'args' => array(
						'post-type' => array(
							'required' => true,
							'sanitize_callback' => 'sanitize_text_field',
						),

					),
					'permission_callback' => array( $this, 'can_edit_view' ),
				),
			)
		);
	}

	/**
	 * REST API callback that fetches the related post types for a given post type.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return void|\WP_Error|\WP_REST_Response
	 */
	public function get_related_post_types( $request ) {
		$params = $request->get_params();

		if ( ! isset( $params['post-type'] ) ) {
			return new \WP_Error( self::CODE_INVALID_ARGUMENTS, self::MESSAGE_INVALID_ARGUMENTS, array( 'status' => 404 ) );
		}

		$post_type = $params['post-type'];

		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( self::CODE_INVALID_POST_TYPE, self::MESSAGE_INVALID_POST_TYPE, array( 'status' => 404 ) );
		}

		return new \WP_REST_Response( $this->content_selection_service->get_related_post_type_options( $post_type ), 200 );
	}

	/**
	 * REST API callback that fetches the related RFGs for a given post type.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return void|\WP_Error|\WP_REST_Response
	 */
	public function get_related_rfgs( $request ) {
		$params = $request->get_params();

		if ( ! isset( $params['post-type'] ) ) {
			return new \WP_Error( self::CODE_INVALID_ARGUMENTS, self::MESSAGE_INVALID_ARGUMENTS, array( 'status' => 404 ) );
		}

		$post_type = $params['post-type'];

		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( self::CODE_INVALID_POST_TYPE, self::MESSAGE_INVALID_POST_TYPE, array( 'status' => 404 ) );
		}

		return new \WP_REST_Response( $this->content_selection_service->get_rfg_options( $post_type ), 200 );
	}
}
