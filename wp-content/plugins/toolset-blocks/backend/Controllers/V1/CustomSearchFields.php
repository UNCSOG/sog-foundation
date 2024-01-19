<?php

namespace OTGS\Toolset\Views\Controllers\V1;

class CustomSearchFields extends Base {
	public function register_routes() {
		register_rest_route($this->namespace, '/views_custom_search_fields', array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array($this, 'get_items'),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		));
	}

	public function get_items($request) {
		$params = $request->get_params();
		$view_id = $params['id'];
		$view_settings	= get_post_meta( $view_id, '_wpv_settings', true );
		$view_query_type = apply_filters( 'wpv_filter_wpv_get_query_type', 'posts', $view_id );

		$custom_search_shortcodes = apply_filters( 'wpv_filter_wpv_get_form_filters_shortcodes', array() );
		$custom_search_filters = array();

		// Search filter
		$custom_search_filters[ __( 'Text Search', 'wpv-views' ) ] = [
			[
				'shortcode' => 'wpv-filter-search-box',
				'name' => __( 'Text search (title and content)', 'wpv-views' ),
				'params' => [
					'attributes' => [
						'field' => 'search',
						'type' => 'search',
					],
				],
			],
		];

		foreach ( $custom_search_shortcodes as $search_shortcode_key => $search_shortcode_data ) {
			if ( $search_shortcode_data['query_type_target'] != $view_query_type ) {
				return;
			}
			if ( isset( $search_shortcode_data['custom_search_filter_subgroups'] ) ) {
				foreach( $search_shortcode_data['custom_search_filter_subgroups'] as $search_shortcode_data_subgroup ) {
					if (
						isset( $search_shortcode_data_subgroup['custom_search_filter_group'] )
						&& isset( $search_shortcode_data_subgroup['custom_search_filter_items'] )
					) {
						if ( ! isset( $custom_search_filters[ $search_shortcode_data_subgroup['custom_search_filter_group'] ] ) ) {
							$custom_search_filters[ $search_shortcode_data_subgroup['custom_search_filter_group'] ] = array();
						}
						foreach ( $search_shortcode_data_subgroup['custom_search_filter_items'] as $search_shortcode_data_item ) {
							$custom_search_filters[ $search_shortcode_data_subgroup['custom_search_filter_group'] ][] = array(
								'shortcode'		=> $search_shortcode_key,
								'name'			=> $search_shortcode_data_item['name'],
								'params'		=> $search_shortcode_data_item['params'],
								'present'		=> $search_shortcode_data_item['present']
							);
						}
					}
				}
			} else if (
				isset( $search_shortcode_data['custom_search_filter_group'] )
				&& isset( $search_shortcode_data['custom_search_filter_items'] )
			) {
				if ( ! isset( $custom_search_filters[ $search_shortcode_data['custom_search_filter_group'] ] ) ) {
					$custom_search_filters[ $search_shortcode_data['custom_search_filter_group'] ] = array();
				}
				foreach ( $search_shortcode_data['custom_search_filter_items'] as $search_shortcode_data_item ) {
					$custom_search_filters[ $search_shortcode_data['custom_search_filter_group'] ][] = array(
						'shortcode'		=> $search_shortcode_key,
						'name'			=> $search_shortcode_data_item['name'],
						'params'		=> $search_shortcode_data_item['params'],
						'present'		=> $search_shortcode_data_item['present']

					);
				}
			}
		}

		// Non Types fields filter
		$fields_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
		$native_fields = array();
		foreach ( $fields_keys as $key ) {
			if ( ! wpv_is_types_custom_field( $key ) ) {
				$native_fields[] = [
					'shortcode' => 'wpv-control-postmeta',
					'name' => $key,
					'params' => [
						'attributes' => [
							'field' => $key,
							'type' => 'native',
						],
					],
				];
			}
		}
		$custom_search_filters[ __( 'Non Types fields', 'wpv-views') ] = $native_fields;

		return new \WP_REST_Response($custom_search_filters, 200);
	}
}
