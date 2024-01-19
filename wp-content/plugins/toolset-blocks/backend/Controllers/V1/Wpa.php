<?php

namespace OTGS\Toolset\Views\Controllers\V1;

use OTGS\Toolset\Views\Controller\Compatibility\BlockEditorWPA;
use OTGS\Toolset\Views\Services\ViewService;
use OTGS\Toolset\Views\Services\WpaService;

/**
 * Class Wpa
 *
 * @package OTGS\Toolset\Views\Controllers\V1
 *
 * @since 3.0
 */
class Wpa extends Base {
	/** Postmeta key where we store the slug of the WPA, a WPA preview post is assigned to*/
	const WPA_PREVIEW_OF_META_KEY = '_wpv_wpa_preview_of';

	/** @var Views */
	private $views_controller;

	/** @var BlockEditorWPA */
	private $block_wpa_editor;

	/**
	 * Constructor
	 *
	 * @param Views                   $views_controller View controller.
	 */
	public function __construct( Views $views_controller, BlockEditorWPA $block_wpa_editor ) {
		$this->views_controller = $views_controller ?: new Views();
		$this->block_wpa_editor = $block_wpa_editor;
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/wpa/preview-post',
			array(
				array(
					'methods' => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'create_wpa_preview_post' ),
					'permission_callback' => array( $this, 'can_edit_view' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/wpa/(?P<id>\d+).*',
			array(
				array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_wpa_info' ),
					'id' => array(
						'required' => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
						'sanitize_callback' => 'absint',
					),
					'permission_callback' => array( $this, 'can_edit_view' ),
				),
			)
		);
	}

	/**
	 * Gets information related to the WordPress Archive post (not block).
	 *
	 * @param $request
	 *
	 * @return array|\WP_Error
	 */
	public function get_wpa_info( $request ) {
		$wpa_id = $this->get_real_view_id(
			$request->get_param( 'id' ),
			$request->get_param( 'slug' )
		);

		if ( ! $wpa_id ) {
			return new \WP_Error( 'no_wpa_id_received', 'No WordPress archive ID was received.', array( 'status' => 404 ) );
		}

		$wpa = new \WPV_WordPress_Archive( $request->get_param( 'id' ) );

		if ( ! $wpa ) {
			return new \WP_Error( 'no_wpa_found', 'No WordPress archive with this ID.', array( 'status' => 404 ) );
		}

		$assigned_loops = array_map(
			function( $item ) {
				return isset( $item['post_type_name'] ) ? $item['post_type_name'] : $item['slug'];
			},
			$wpa->get_assigned_loops()
		);

		return array(
			'id' => $wpa->id,
			'title' => $wpa->title,
			'slug' => $wpa->slug,
			'assignedLoops' => $assigned_loops,
		);
	}

	/**
	 * Creates the preview post for the WordPress Archive block.
	 *
	 * @param $request
	 *
	 * @return int
	 */
	public function create_wpa_preview_post( $request ) {
		// get request data and convert it to the same structure as we have now
		$view_data = $request->get_param( 'viewData' );
		$parent_id = (int) $request->get_param( 'parent' );

		$this->block_wpa_editor->scan_and_delete_orphaned_preview_posts( $parent_id );

		$wpa_parent_instance = \WPV_WordPress_Archive::get_instance( $parent_id );

		$wpa_preview_post = $this->views_controller->create_preview_item( $view_data, 'archive' );

		// Add a post meta to the new WPA preview post to help for the identification as orphaned later.
		$wpa_preview_post_instance = \WPV_WordPress_Archive::get_instance( $wpa_preview_post['id'] );
		$wpa_preview_post_instance->update_postmeta( self::WPA_PREVIEW_OF_META_KEY, $wpa_parent_instance->slug );

		// The purpose is received from the parent post as by the time of the preview post creation, the view data of
		// it still don't have any info related to the parent WPA post because the linking will happen on the WPA Helper
		// post save.
		$wpa_preview_post[ \WPV_View_Embedded::VIEW_SETTINGS_PURPOSE ] = $wpa_parent_instance->view_settings[ \WPV_View_Embedded::VIEW_SETTINGS_PURPOSE ];

		return $wpa_preview_post;
	}
}
