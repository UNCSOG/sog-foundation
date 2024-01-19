<?php

namespace OTGS\Toolset\Views\Controllers\V1;

/**
 * Class ViewPostTypes
 * @package OTGS\Toolset\Views\Controllers\V1
 * There is a built-in /wp/v2/types endpoint, however it doesn't return all post types (at least
 * it doesn't returned all my custom post types in my test installation)
 */
class ViewPostTypes extends Base {
	public function register_routes() {
		register_rest_route($this->namespace, '/post_types', array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array($this, 'get_items'),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		));
	}

	public function get_items($request) {
		$post_types = array();
		$post_types_arr = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types_arr as $post_type_object ) {
			$post_types[] = array(
				'value' => $post_type_object->name,
				'label' => $post_type_object->labels->name
			);
		}
		$rfg_post_types = get_post_types( array( \Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP => true ), 'objects' );
		$rfgs = array();
		if ( ! empty( $rfg_post_types ) ) {
			foreach ( $rfg_post_types as $post_type_object ) {
				$rfgs[] = array(
					'value' => $post_type_object->name,
					'label' => $post_type_object->labels->name,
				);
			}
		}
		$result = array(
			'post_types' => $post_types,
			'rfgs' => $rfgs,
		);
		return new \WP_REST_Response($result, 200);
	}
}
