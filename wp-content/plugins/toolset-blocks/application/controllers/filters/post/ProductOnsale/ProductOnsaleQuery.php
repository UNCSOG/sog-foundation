<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;

use OTGS\Toolset\Views\Controller\Filters\AbstractQuery;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;

/**
 * Query component for the filter.
 */
class ProductOnsaleQuery extends AbstractQuery {

	// Value to pass as shortcode attribute or URL parameter to filter by this.
	// Note that this matches the legacy field stored value.
	const FILTER_VALUE = '1';

	/**
	 * Load query component hooks.
	 *
	 * Mind the priority as this is a post__in filter.
	 */
	public function load_hooks() {
		add_filter( 'wpv_filter_query', array( $this, 'filter_query' ), 15, 2 );
		add_action( 'wpv_action_apply_archive_query_settings', array( $this, 'filter_archive_query' ), 45, 2 );
		add_filter( 'wpv_filter_object_settings_for_fake_url_query_filters', array( $this, 'replace_legacy_query_filter' ) );
	}

	/**
	 * Decide whether the filter should be applied.
	 *
	 * @param string[] $view_settings
	 * @return bool
	 */
	private function should_apply_filter( $view_settings ) {
		$filter_settings = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG ), false );
		if ( false === $filter_settings ) {
			return false;
		}

		$filter_settings = array(
			'mode' => toolset_getarr( $filter_settings, 'mode', 'query_filter' ),
			'shortcode_attribute' => toolset_getarr( $filter_settings, 'shortcode_attribute', 'onsale' ),
			'url_parameter' => toolset_getarr( $filter_settings, 'url_parameter', 'wpv-on-sale' ),
		);

		switch ( $filter_settings[ 'mode' ] ) {
			case 'query_filter':
				return true;
			case 'shortcode_attribute':
				$view_attributes = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', array() );
				return ( self::FILTER_VALUE === strval( toolset_getarr( $view_attributes, $filter_settings[ 'shortcode_attribute' ] ) ) );
			case 'url_parameter':
				$url_parameter_value = toolset_getget( $filter_settings[ 'url_parameter' ], false );
				if ( false === $url_parameter_value ) {
					return false;
				}
				if ( is_array( $url_parameter_value ) ) {
					$url_parameter_value = array_map( 'strval', $url_parameter_value );
					return in_array( self::FILTER_VALUE, $url_parameter_value, true );
				}
				return ( self::FILTER_VALUE === strval( $url_parameter_value ) );
		}

		return false;
	}

	/**
	 * Apply the filter to the View query.
	 *
	 * @param mixed[] $query
	 * @param mixed[] $view_settings
	 * @return mixed[]
	 */
	public function filter_query( $query, $view_settings ) {
		if ( $this->is_empty_post__in_query_arg( $query ) ) {
			return $query;
		}

		if ( false === $this->should_apply_filter( $view_settings ) ) {
			return $query;
		}

		// Only for loops including products.
		$post_type_in_loop = toolset_getarr( $view_settings, 'post_type', array());
		if ( false === $this->is_listing_post_type( $post_type_in_loop, 'product' ) ) {
			$query['post__in'] = array( '0' );
			return $query;
		}

		$on_sale_ids = \wc_get_product_ids_on_sale();

		if ( 0 === count( $on_sale_ids ) ) {
			$query['post__in'] = array( '0' );
			return $query;
		}

		if ( isset( $query['post__in'] ) ) {
			$query['post__in'] = array_intersect( (array) $query['post__in'], $on_sale_ids );
			$query['post__in'] = array_values( $query['post__in'] );
			if ( empty( $query['post__in'] ) ) {
				$query['post__in'] = array( '0' );
			}
		} else {
			$query['post__in'] = $on_sale_ids;
		}

		return $query;
	}

	/**
	 * Apply the filter to the WPA query.
	 *
	 * @param \WP_Query $query
	 * @param mixed[] $archive_settings
	 * @return void
	 */
	public function filter_archive_query( $query, $archive_settings ) {
		if ( $this->is_empty_post__in_query_var( $query ) ) {
			return;
		}

		if ( false === $this->should_apply_filter( $archive_settings ) ) {
			return;
		}

		if ( false === $this->is_post_type_archive_query( $query, 'product', true ) ) {
			$query->set( 'post__in', array( '0' ) );
			return;
		}

		$on_sale_ids = \wc_get_product_ids_on_sale();

		if ( 0 === count( $on_sale_ids ) ) {
			$query->set( 'post__in', array( '0' ) );
			return;
		}

		$post__in = $query->get( 'post__in' );
		$post__in = isset( $post__in ) ? $post__in : array();
		if ( count( $post__in ) > 0 ) {
			$post__in = array_intersect( (array) $post__in, $on_sale_ids );
			$post__in = array_values( $post__in );
			if ( empty( $post__in ) ) {
				$post__in = array( '0' );
			}
			$query->set( 'post__in', $post__in );
		} else {
			$query->set( 'post__in', $on_sale_ids );
		}

		return;
	}

	/**
	 * Override and replace the legacy filter by the custom postmeta field crafted by Toolset WooCommerce Blocks.
	 *
	 * @param mixed[] $view_settings
	 * @return mixed[]
	 */
	public function replace_legacy_query_filter( $view_settings ) {
		$field_filter_value = toolset_getarr( $view_settings, 'custom-field-' . ProductOnsale::LEGACY_FIELD_SLUG . '_value', false );

		if ( false === $field_filter_value ) {
			return $view_settings;
		}

		// Create a new filter using the value from the existing one.
		$filters = toolset_getarr( $view_settings, \WPV_Filter_Manager::SETTING_KEY, array() );
		$filters[ ProductOnsale::SLUG ] = array(
			'mode' => toolset_getnest( $filters, array( ProductOnsale::SLUG, 'mode' ), 'query_filter' ),
			'shortcode_attribute' => toolset_getnest( $filters, array( ProductOnsale::SLUG, 'shortcode_attribute' ), 'onsale' ),
			'url_parameter' => toolset_getnest( $filters, array( ProductOnsale::SLUG, 'url_parameter' ), 'wpv-on-sale' ),
		);

		$url_pattern = '/URL_PARAM\(([^(]*?)\)/siU';
		if ( preg_match_all( $url_pattern, $field_filter_value, $url_matches, PREG_SET_ORDER ) ) {
			foreach ( $url_matches as $url_match ) {
				$filters[ ProductOnsale::SLUG ][ 'mode' ] = 'url_parameter';
				$filters[ ProductOnsale::SLUG ][ 'url_parameter' ] = $url_match[ 1 ];
			}
		}

		$attr_pattern = '/VIEW_PARAM\(([^(]*?)\)/siU';
		if( preg_match_all( $attr_pattern, $field_filter_value, $attr_matches, PREG_SET_ORDER ) ) {
			foreach ( $attr_matches as $attr_match ) {
				$filters[ ProductOnsale::SLUG ][ 'mode' ] = 'shortcode_attribute';
				$filters[ ProductOnsale::SLUG ][ 'shortcode_attribute' ] = $attr_match[ 1 ];
			}
		}

		$view_settings[ \WPV_Filter_Manager::SETTING_KEY ] = $filters;

		// Remove the old filter.
		$settings_to_remove = array(
			'custom-field-' . ProductOnsale::LEGACY_FIELD_SLUG . '_type',
			'custom-field-' . ProductOnsale::LEGACY_FIELD_SLUG . '_decimals',
			'custom-field-' . ProductOnsale::LEGACY_FIELD_SLUG . '_compare',
			'custom-field-' . ProductOnsale::LEGACY_FIELD_SLUG . '_value',
		);
		foreach ( $settings_to_remove as $removing ) {
			if ( isset( $view_settings[ $removing ] ) ) {
				unset( $view_settings[ $removing ] );
			}
		}

		return $view_settings;
	}

}
