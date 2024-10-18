<?php

namespace OTGS\Toolset\Views\Controllers\V1;

/**
 * Class ViewOrderingFields
 * @package OTGS\Toolset\Views\Controllers\V1
 */
class ViewOrderingFields extends Base {
	public function register_routes() {
		register_rest_route($this->namespace, '/views_ordering_fields', array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array($this, 'get_items'),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		));
	}

	public function get_items($request) {
		$data = array(
			array(
				'value' => 'post_date',
				'label' => __( 'Post date', 'wpv-views' ),
			),
			array(
				'value' => 'post_title',
				'label' => __( 'Post title', 'wpv-views' ),
			),
			array(
				'value' => 'ID',
				'label' => __( 'Post ID', 'wpv-views' ),
			),
			array(
				'value' => 'post_author',
				'label' => __( 'Post author', 'wpv-views' ),
			),
			array(
				'value' => 'post_type',
				'label' => __( 'Post type', 'wpv-views' ),
			),
			array(
				'value' => 'modified',
				'label' => __( 'Last modified', 'wpv-views' ),
			),
			array(
				'value' => 'menu_order',
				'label' => __( 'Menu order', 'wpv-views' ),
			),
			array(
				'value' => 'rand',
				'label' => __( 'Random order', 'wpv-views' ),
			),
			array(
				'value' => 'price',
				'label' => __( 'Price', 'wpv-views' ),
			),
		);
		$all_types_fields = get_option( 'wpcf-fields', array() );
		$cf_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );

		foreach ( $cf_keys as $key ) {
			$option_text = "";
			$field_type = '';

			if ( stripos( $key, 'wpcf-' ) === 0 )  {
				if (
					isset( $all_types_fields[substr( $key, 5 )] )
					&& isset( $all_types_fields[substr( $key, 5 )]['name'] )
				) {
					$option_text = sprintf(__('Field - %s', 'wpv-views'), $all_types_fields[substr( $key, 5 )]['name']);
					$field_type = $all_types_fields[substr( $key, 5 )]['type'];
				} else {
					$option_text = sprintf(__('Field - %s', 'wpv-views'), $key);
				}
			} else {
				$option_text = sprintf(__('Field - %s', 'wpv-views'), $key);
			}

			if ( ! in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
				$data[] = array(
					'value' => 'field-' . $key,
					'field_type' => $field_type,
					'label' => $option_text
				);
			}
		}
		return new \WP_REST_Response($data, 200);
	}
}
