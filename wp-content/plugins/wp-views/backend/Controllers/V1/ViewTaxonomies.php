<?php

namespace OTGS\Toolset\Views\Controllers\V1;

/**
 * Class ViewTaxonomies
 * @package OTGS\Toolset\Views\Controllers\V1
 * There is a built-in /wp/v2/taxonomies endpoint, however it doesn't have the blacklist settings
 */
class ViewTaxonomies extends Base {
	public function register_routes() {
		register_rest_route($this->namespace, '/taxonomies', array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array($this, 'get_items'),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		));
	}

	public function get_items($request) {
		$result = array();
		$taxonomies = get_taxonomies( '', 'objects' );
		$exclude_tax_slugs = array();
		$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
		foreach ( $taxonomies as $tax_slug => $tax ) {
			if ( in_array( $tax_slug, $exclude_tax_slugs ) ) {
				continue; // Take out taxonomies that are in our compatibility black list
			}
			if ( ! $tax->show_ui ) {
				continue; // Only show taxonomies with show_ui set to TRUE
			}
			$result[] = array(
				'value' => $tax->name,
				'label' => $tax->labels->name
			);
		}
		return new \WP_REST_Response($result, 200);
	}
}
