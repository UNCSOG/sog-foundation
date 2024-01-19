<?php

namespace OTGS\Toolset\Views\Controllers\V1;

use OTGS\Toolset\Views\Services\ViewParsingService;
use OTGS\Toolset\Views\Services\ViewService;

/**
 * Class ViewFields
 * @package OTGS\Toolset\Views\Controllers\V1
 */
class Views extends Base {
	protected $service;

	public function __construct() {
		$this->service = new ViewService();
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/views/duplicate/(?P<id>\d+).*', array(
			array(
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'duplicate_item' ),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		) );
		register_rest_route( $this->namespace, '/views', array(
			array(
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		) );
		register_rest_route( $this->namespace, '/views/(?P<id>\d+)', array(
			array(
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		) );
		register_rest_route( $this->namespace, '/views/update_data/(?P<id>\d+)', array(
				array(
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => array( $this, 'update_data' ),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		) );
		register_rest_route( $this->namespace, '/views/(?P<id>\d+).*', array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'show_item' ),
				'permission_callback' => '__return_true',
			)
		) );
		register_rest_route( $this->namespace, '/views/(?P<id>\d+)', array(
			array (
				'methods' => \WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		) );
	}

	public function delete_item( $request ) {
		wp_delete_post( $request[ 'id' ] );
		return new \WP_REST_Response( array(), 200 );
	}

	public function show_item( $request ) {
		$view_id = $this->get_real_view_id(
			$request->get_param( 'id' ),
			$request->get_param( 'slug' )
		);

		$data = get_post_meta( $view_id, '_wpv_view_data', true );

		if ( empty( $data ) ) {
			return new \WP_REST_Response( array(
				'view_not_found' => true,
			), 200 );
		}

		if ( intval( $request->get_param( 'id' ) ) !== intval( $view_id ) ) {
			// if new view ID was fetched, we have to create a new draft post for preview
			$view_data = $data;
			$view_data['create_draft'] = true;
			$view_data['general']['name'] .= '-preview-' . time();
			// create preview post for view duplicate and fix values in output of view_data
			$result = $this->service->create( $view_data  );
			$data['general']['preview_id'] = $result['id'];
			$data['id'] = $result['id'];
			// save view data with updated IDs
			update_post_meta( $view_id, '_wpv_view_data', $data );
		}

		/**
		 * Filters the View block data when retrieved from the DB
		 *
		 * @param array      $data
		 * @param int|string $view_id
		 *
		 * @returns array
		 */
		$data = apply_filters( 'wpv_filter_view_block_data_from_db', $data, $view_id );

		$service = new ViewParsingService();
		$markup = $service->get_view_markup( $data['general']['parent_post_id'], $view_id );
		$data['viewMarkup'] = $markup;

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Create a new View using request data
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function create_item($request) {
		// get request data and convert it to the same structure as we have now
		$view_data = $request->get_params();
		// create the main view first
		$view_data['parent_id'] = null;
		$response = $this->service->create( $view_data );
		if ( $response['success'] ) {
			$response['view_data'] = $view_data;
			// than let's create a second view to use as a preview
			$preview_post_response = $this->create_preview_item( $view_data );
			if ( $preview_post_response['success'] ) {
				$response['preview_id'] = $preview_post_response['id'];
			}
		}
		return new \WP_REST_Response($response, 200);
	}

	public function duplicate_item( $request ) {
		$view_id = $this->get_real_view_id(
			$request->get_param( 'id' ),
			$request->get_param( 'slug' )
		);
		$data = get_post_meta( $view_id, '_wpv_view_data' );
		$settings = get_post_meta( $view_id, '_wpv_settings' );
		$result = array(
			'success' => false
		);
		if ( ! empty( $data ) ) {
			$view_data = $data[0];
			$suffix = '-copy-' . time();
			$view_data['general']['name'] .= $suffix;
			// Create the copy: we only need the original name.
			$result = $this->service->create( $view_data );
			// Adjust the slug to store and use in the editor, to match the one of the copy.
			$result['viewSlug'] = $result['slug'];
			$view_data['general']['slug'] = $result['slug'];
			if ( $result['success'] ) {
				$result['view_data'] = $view_data;
				$result['view_data']['id'] = $result['id'];
				$result['view_data']['general']['id'] = $result['id'];
				$view_data2 = $view_data;
				$view_data2['create_draft'] = true;
				$view_data2['general']['name'] .= '-preview-' . time();
				//create preview post for view duplicate and fix values in output of view_data
				$result2 = $this->service->create( $view_data2 );
				$result['preview_id'] = $result2['id'];
				$result['view_data']['general']['preview_id'] = $result['id'];
				update_post_meta( $result['id'], '_wpv_view_data', $result['view_data'] );

				if ( isset( $settings[0] ) ) {
					update_post_meta( $result2['id'], '_wpv_settings', $settings[0] );
				}
			}
		}
		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Creates the preview post of a View/WPA.
	 *
	 * @param $view_data
	 * @param string $view_query_mode Represents the `view-query-mode` settings (normal or archive).
	 *
	 * @return int
	 *
	 * @since 3.0
	 */
	public function create_preview_item( $view_data, $view_query_mode = null ) {
		$view_title = $view_data['general']['name'];
		$view_slug = $view_data['general']['slug'];

		// than let's create a second view to use as a preview
		$view_data['create_draft'] = true;
		$view_data['general']['name'] .= '-preview-' . time();
		$view_data['general']['slug'] .= '-preview-' . time();

		$response = $this->service->create( $view_data );

		if ( $response['success'] ) {
			$view_data['general']['name'] = $view_title;
			$view_data['general']['slug'] = $view_slug;
			update_post_meta( $response['id'], '_wpv_view_data', $view_data );
		}

		if ( $view_query_mode ) {
			$settings = get_post_meta( $response['id'], '_wpv_settings', true );
			$settings['view-query-mode'] = $view_query_mode;
			update_post_meta( $response['id'], '_wpv_settings', $settings );
		}

		return $response;
	}

	/**
	 * Update a View using request data
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item($request)
	{
		// When this is defined, image placeholders will not be rendered on the backend.
		define( 'WPV_BLOCK_UPDATE_ITEM', true );

		$ct_preview_post_id = (int) toolset_getarr( $request, 'ctPreviewPost' );
		if ( ! empty( $ct_preview_post_id ) ) {
			// For the case of a View inside a Content Template, the "ctPreviewPost" JSON parameter should be set, that
			// represents the Content Template preview post ID. This parameter is sometimes relevant with the preview of
			// the View (for example if the View uses a post relationship query filter), so this post needs to be set as the
			// top current post for the View.
			add_filter(
				'wpv_filter_wpv_get_top_current_post',
				function( $top_current_post ) use ( $ct_preview_post_id ) {
					$ct_preview_post_id = absint( $ct_preview_post_id );
					if ( \WPV_Content_Template_Embedded::POST_TYPE !== get_post_type( $top_current_post ) ) {
						return $top_current_post;
					}

					return get_post( $ct_preview_post_id );
				},
				99
			);
		}

		$view_data = $request->get_params();
		$id = $request['id'];

		// Check if the action was initiated from a Block backend preview call.
		// And set WPV_BLOCK_PREVIEW_RENDER constant, used as a part of the fix for.
		// https://onthegosystems.myjetbrains.com/youtrack/issue/views-3983
		if ( isset( $view_data['block_preview'] ) ) {
			define( 'WPV_BLOCK_PREVIEW_RENDER', true );
		}

		// Let's retrieve the current parent post id
		// and check if it's a translated version of other post
		// if so, we'll need to disable view editing on the backend
		$wpml_active_and_configured = apply_filters( 'wpml_setting', false, 'setup_complete' );
		$is_translated = false;
		if ( $wpml_active_and_configured ) {
			$post_id = $view_data['general']['parent_post_id'];
			$opid = intval( apply_filters( 'wpml_original_element_id', 0, $post_id ) );
			$is_translated = ( $opid !== $post_id && 0 !== $opid );
		}
		// If post is translated copy (using translation manger only) of another post
		// we don't save anything but return the preview.
		if ( ! ( $is_translated && $view_data['general']['translatedWithTM'] ) ) {
			$response = $this->service->save( $id, $view_data );
		} else {
			$response = $this->service->preview( $id, $view_data );
		}
		$response['is_translated'] = $is_translated;

		return new \WP_REST_Response($response, 200);
	}

	/**
	 * Update a View _wpv_view_data using request data
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_data($request)
	{
		$view_data = $request->get_params();
		update_post_meta( $request['id'], '_wpv_view_data', $view_data );
		return new \WP_REST_Response( array(), 200 );
	}
}
