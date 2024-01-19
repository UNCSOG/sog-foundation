<?php

namespace OTGS\Toolset\Views\Controllers\V1;

/**
 * Class ViewFields
 * @package OTGS\Toolset\Views\Controllers\V1
 */
class ViewFields extends Base {
	public function register_routes() {
		register_rest_route($this->namespace, '/view_fields', array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array($this, 'get_items'),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		));
	}

	/**
	 * Receive a list of available fields for specific view type
	 * For Views listing posts, also generate a group for all non-Types postmeta fields.
	 * @return \WP_REST_Response
	 */
	public function get_items($request) {
		$target = $request->get_param('target');
		do_action( 'wpv_action_collect_shortcode_groups' );
		$shortcode_groups_all = apply_filters( 'wpv_filter_wpv_get_shortcode_groups', array() );
		$shortcode_groups = array();
		foreach ( $shortcode_groups_all as $group_id => $group_data ) {
			if ( ! in_array( $target, $group_data['target'] ) ) {
				continue;
			}

			$shortcode_groups[ $group_id ] = $group_data;
		}
		if ( 'posts' == $target ) {
			// Adjust the Post felds native group to include all non-Types native fields
			// Remove the wpv-post-field entry from the Post data group
			unset( $shortcode_groups['post']['fields']['wpv-post-field'] );
			$shortcode_groups['non-types-post-fields']['fields'] = array();
			$postmeta_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
			foreach ( $postmeta_keys as $postmeta_field ) {
				if ( ! wpv_is_types_custom_field( $postmeta_field ) ) {
					$shortcode_groups['non-types-post-fields']['fields'][ $postmeta_field ] = array(
						'name'		=> $postmeta_field,
						'handle'	=> 'wpv-post-field',
						'shortcode'	=> '[wpv-post-field name="' . $postmeta_field . '"]',
						'callback'	=> "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpv-post-field', title: '" . esc_js( __( 'Post field', 'wpv-views' ) ) . "', overrides: {attributes:{name:'" . esc_js( $postmeta_field ) . "'}} })"
					);

				}
			}
			if ( count( $shortcode_groups['non-types-post-fields']['fields'] ) == 0 ) {
				unset( $shortcode_groups['non-types-post-fields'] );
			}

		}
		$data = $shortcode_groups;
		return new \WP_REST_Response($data, 200);
	}
}
