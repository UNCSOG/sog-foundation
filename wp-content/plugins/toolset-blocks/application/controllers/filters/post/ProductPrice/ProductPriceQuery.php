<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;

use OTGS\Toolset\Views\Controller\Filters\AbstractQuery;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;
use OTGS\Toolset\Views\Model\WooCommerce\WcQuery;

/**
 * Query component for the filter.
 */
class ProductPriceQuery extends AbstractQuery {

	const SHORTCODE_ATTRIBUTE_MIN_PRICE = 'min-price';
	const SHORTCODE_ATTRIBUTE_MAX_PRICE = 'max-price';

	/**
	 * Load query component hooks.
	 */
	public function load_hooks() {
		add_filter( 'wpv_filter_query', array( $this, 'filter_query' ), 15, 2 );
		add_action( 'wpv_action_apply_archive_query_settings', array( $this, 'filter_archive_query' ), 45, 2 );
		// Faking query filters after :10 since at :10 we might get the legacy filter also faked.
		add_filter( 'wpv_filter_object_settings_for_fake_url_query_filters', array( $this, 'replace_legacy_query_filter' ), 15 );
	}

	private function get_legacy_proxy_filter_settings( $filter_settings ) {
		$field_filter_value = toolset_getnest( $filter_settings, [ 'legacy', 'value' ], false );
		$field_filter_compare = toolset_getnest( $filter_settings, [ 'legacy', 'compare' ], false );

		if (
			false === $field_filter_value
			|| false === $field_filter_compare
		) {
			return false;
		}

		$field_filter_value_array = explode( ',', $field_filter_value );
		$field_filter_value_array = array_map( function( $value ) {
			$value = trim( $value );
			$resolve_attr = array(
				'filters' => array( 'url_parameter', 'shortcode_attribute', 'framework_value' )
			);
			return apply_filters( 'wpv_resolve_variable_values', $value, $resolve_attr );;
		}, $field_filter_value_array );

		switch ( $field_filter_compare ) {
			case '=':
			case 'eq':
				// Filter products with an exact price.
				$value_to_compare = reset( $field_filter_value_array );
				if ( \WPV_Filter_Manager::NO_DYNAMIC_VALUE_FOUND === $value_to_compare ) {
					return $filter_settings;
				}
				$filter_settings[ 'mode' ] = 'query_filter';
				$filter_settings[ 'values' ][ 'min' ] = $value_to_compare;
				$filter_settings[ 'values' ][ 'max' ] = $value_to_compare;
				return $filter_settings;
			case '!=':
			case 'neq':
				// Not supported by this API.
				return false;
			case '>':
			case 'gt':
			case '>=':
			case 'get':
				$value_to_compare = reset( $field_filter_value_array );
				if ( \WPV_Filter_Manager::NO_DYNAMIC_VALUE_FOUND === $value_to_compare ) {
					return $filter_settings;
				}
				$filter_settings[ 'mode' ] = 'query_filter';
				$filter_settings[ 'values' ][ 'min' ] = $value_to_compare;
				$filter_settings[ 'values' ][ 'max' ] = PHP_INT_MAX;
				return $filter_settings;
			case '<':
			case 'lt':
			case '<=':
			case 'let':
				$value_to_compare = reset( $field_filter_value_array );
				if ( \WPV_Filter_Manager::NO_DYNAMIC_VALUE_FOUND === $value_to_compare ) {
					return $filter_settings;
				}
				$filter_settings[ 'mode' ] = 'query_filter';
				$filter_settings[ 'values' ][ 'min' ] = 0;
				$filter_settings[ 'values' ][ 'max' ] = $value_to_compare;
				return $filter_settings;
			case 'LIKE':
			case 'NOT LIKE':
				// Not supported by this API.
				return false;
			case 'IN':
			case 'NOT IN':
				// Not supported by this API.
				return false;
			case 'BETWEEN':
				if ( count( $field_filter_value_array ) < 2 ) {
					return false;
				}
				if (
					\WPV_Filter_Manager::NO_DYNAMIC_VALUE_FOUND === $field_filter_value_array[ 0 ]
					&& \WPV_Filter_Manager::NO_DYNAMIC_VALUE_FOUND === $field_filter_value_array[ 1 ]
				) {
					return $filter_settings;
				}
				$filter_settings[ 'mode' ] = 'query_filter';
				$filter_settings[ 'values' ][ 'min' ] = ( \WPV_Filter_Manager::NO_DYNAMIC_VALUE_FOUND === $field_filter_value_array[ 0 ] ) ? 0 : $field_filter_value_array[ 0 ];
				$filter_settings[ 'values' ][ 'max' ] = ( \WPV_Filter_Manager::NO_DYNAMIC_VALUE_FOUND === $field_filter_value_array[ 1 ] ) ? PHP_INT_MAX : $field_filter_value_array[ 1 ];
				return $filter_settings;
			case 'NOT BETWEEN':
				// Not supported by this API.
				return false;
		}

		return false;
	}

	private function get_filter_settings( $view_settings ) {
		$filter_settings = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG ), false );
		if ( false === $filter_settings ) {
			return false;
		}

		if ( false !== toolset_getarr( $filter_settings, 'legacy', false ) ) {
			// This filter was forced in by a legady filter by ProductPrice::LEGACY_FIELD_SLUG.
			return $this->get_legacy_proxy_filter_settings( $filter_settings );
		}

		$filter_settings = array(
			'mode' => toolset_getarr( $filter_settings, 'mode', 'query_filter' ),
			'values' => array(
				'min' => toolset_getnest( $filter_settings, [ 'values', 'min' ], 0 ),
				'max' => toolset_getnest( $filter_settings, [ 'values', 'max' ], PHP_INT_MAX ),
			),
		);

		return $filter_settings;
	}

	/**
	 * Decide whether the filter should be applied.
	 *
	 * @param string[] $view_settings
	 * @param string $usage Whether a view or a wpa.
	 * @return bool
	 */
	private function should_apply_filter( $view_settings, $usage ) {
		$filter_settings = $this->get_filter_settings( $view_settings );

		if ( false === $filter_settings ) {
			return false;
		}

		if ( false === toolset_getarr( $filter_settings, 'mode', false ) ) {
			return false;
		}

		switch ( $filter_settings[ 'mode' ] ) {
			case 'query_filter':
				return true;
			case 'shortcode_attribute':
				$view_attributes = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', array() );
				if ( false !== toolset_getarr( $view_attributes, self::SHORTCODE_ATTRIBUTE_MIN_PRICE, false ) ) {
					return true;
				}
				if ( false !== toolset_getarr( $view_attributes, self::SHORTCODE_ATTRIBUTE_MAX_PRICE, false ) ) {
					return true;
				}
				return false;
			case 'url_parameter':
				if ( false !== toolset_getget( WcQuery::URL_PARAM_MIN_MAX_PRICE, false) ) {
					return true;
				}
				if (
					'wpa' === $usage
					&& (
						false !== toolset_getget( WcQuery::URL_PARAM_MIN_PRICE, false )
						|| false !== toolset_getget( WcQuery::URL_PARAM_MAX_PRICE, false )
					)
				) {
					return true;
				}
				if (
					'view' === $usage
					&& (
						false !== toolset_getget( WcQuery::URL_PARAM_VIEW_MIN_PRICE, false )
						|| false !== toolset_getget( WcQuery::URL_PARAM_VIEW_MAX_PRICE, false )
					)
				) {
					return true;
				}
				return false;
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

		if ( false === $this->should_apply_filter( $view_settings, 'view' ) ) {
			return $query;
		}

		// Only for loops including products.
		$post_type_in_loop = toolset_getarr( $view_settings, 'post_type', array());
		if ( false === $this->is_listing_post_type( $post_type_in_loop, 'product' ) ) {
			$query['post__in'] = array( '0' );
			return $query;
		}

		$filter_settings = $this->get_filter_settings( $view_settings );
		if ( false === $filter_settings ) {
			return $query;
		}

		$filter_min = 0;
		$filter_max = PHP_INT_MAX;

		switch ( $filter_settings['mode'] ) {
			case 'query_filter':
				$filter_min = $filter_settings['values']['min'];
				$filter_max = $filter_settings['values']['max'];
				break;
			case 'shortcode_attribute':
				$view_attributes = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', array() );
				$filter_min = toolset_getarr( $view_attributes, self::SHORTCODE_ATTRIBUTE_MIN_PRICE, 0 );
				$filter_max = toolset_getarr( $view_attributes, self::SHORTCODE_ATTRIBUTE_MAX_PRICE, PHP_INT_MAX );

				break;
			case 'url_parameter':
				$filter_min_max = toolset_getget( WcQuery::URL_PARAM_MIN_MAX_PRICE, '0-' . strval( PHP_INT_MAX ) );
				$filter_min_max_array = explode( '-', $filter_min_max );

				$filter_min = intval( $filter_min_max_array[ 0 ] );
				$filter_max = ! empty( $filter_min_max_array[1] ) ? $filter_min_max_array[1] : PHP_INT_MAX;

				$filter_min = toolset_getget( WcQuery::URL_PARAM_VIEW_MIN_PRICE, $filter_min );
				$filter_max = toolset_getget( WcQuery::URL_PARAM_VIEW_MAX_PRICE, $filter_max );
				break;
		}

		if (
			0 !== (int) $filter_min
			|| PHP_INT_MAX !== (int) $filter_max
		) {
			$query[ WcQuery::QUERY_PARAM_MIN_PRICE ] = $filter_min;
			$query[ WcQuery::QUERY_PARAM_MAX_PRICE ] = $filter_max;
		}

		return $query;
	}

	/**
	 * Apply the filter to the WPA query.
	 *
	 * Note that WooCommerce applies it already to the native archive,
	 * but we need to enforce it when doing AJAX.
	 *
	 * @param \WP_Query $query
	 * @param mixed[] $archive_settings
	 * @return void
	 */
	public function filter_archive_query( $query, $archive_settings ) {
		if ( $this->is_empty_post__in_query_var( $query ) ) {
			return;
		}

		if ( false === $this->should_apply_filter( $archive_settings, 'wpa' ) ) {
			return;
		}

		if ( false === $this->is_post_type_archive_query( $query, 'product', true ) ) {
			$query->set( 'post__in', array( '0' ) );
			return;
		}

		$filter_settings = $this->get_filter_settings( $archive_settings );
		if ( false === $filter_settings ) {
			return;
		}

		$filter_min = 0;
		$filter_max = PHP_INT_MAX;

		switch ( $filter_settings['mode'] ) {
			case 'query_filter':
				$filter_min = $filter_settings['values']['min'];
				$filter_max = $filter_settings['values']['max'];
				break;
			case 'url_parameter':
				$filter_min_max = toolset_getget( WcQuery::URL_PARAM_MIN_MAX_PRICE, '0-' . strval( PHP_INT_MAX ) );
				$filter_min_max_array = explode( '-', $filter_min_max );

				$filter_min = intval( $filter_min_max_array[ 0 ] );
				$filter_max = ! empty( $filter_min_max_array[1] ) ? $filter_min_max_array[1] : PHP_INT_MAX;

				$filter_min = toolset_getget( WcQuery::URL_PARAM_MIN_PRICE, $filter_min );
				$filter_max = toolset_getget( WcQuery::URL_PARAM_MAX_PRICE, $filter_max );
				break;
		}

		if (
			0 !== (int) $filter_min
			|| PHP_INT_MAX !== (int) $filter_max
		) {
			$query->set( WcQuery::QUERY_PARAM_MIN_PRICE, $filter_min );
			$query->set( WcQuery::QUERY_PARAM_MAX_PRICE, $filter_max );
		}

		return;
	}

	/**
	 * Override and replace the legacy filter by the custom postmeta field crafted by Toolset WooCommerce Blocks.
	 *
	 * @todo This needs adustment, this filter has no URL param not shortcoe attribute free values.
	 * @todo Supporting legacy means that existing attributes and URL params will also work!
	 * @param mixed[] $view_settings
	 * @return mixed[]
	 */
	public function replace_legacy_query_filter( $view_settings ) {
		$field_filter_value = toolset_getarr( $view_settings, 'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_value', false );
		$field_filter_compare = toolset_getarr( $view_settings, 'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_compare', false );

		if (
			false === $field_filter_value
			|| false === $field_filter_compare
		) {
			return $view_settings;
		}

		// Create a new filter with default values and legacy information.
		$filters = toolset_getarr( $view_settings, \WPV_Filter_Manager::SETTING_KEY, array() );
		$filters[ ProductPrice::SLUG ] = array(
			'mode' => toolset_getnest( $filters, [ ProductPrice::SLUG, 'mode' ], 'query_filter' ),
			'values' => array(
				'min' => toolset_getnest( $filters, [ ProductPrice::SLUG, 'values', 'min' ], 0 ),
				'max' => toolset_getnest( $filters, [ ProductPrice::SLUG, 'values', 'max' ], PHP_INT_MAX ),
			),
			'legacy' => array(
				'value' => toolset_getnest( $filters, [ ProductPrice::SLUG, 'legacy', 'value' ], $field_filter_value ),
				'compare' => toolset_getnest( $filters, [ ProductPrice::SLUG, 'legacy', 'compare' ], $field_filter_compare ),
			),
			\WPV_Filter_Manager::EDITOR_MODE => toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, 'postmeta', ProductPrice::LEGACY_FIELD_SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL ),
		);

		$view_settings[ \WPV_Filter_Manager::SETTING_KEY ] = $filters;

		// Remove the old filter.
		$settings_to_remove = array(
			'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_type',
			'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_decimals',
			'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_compare',
			'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_value',
		);
		foreach ( $settings_to_remove as $removing ) {
			if ( isset( $view_settings[ $removing ] ) ) {
				unset( $view_settings[ $removing ] );
			}
		}

		if ( false !== toolset_getnest( $view_settings, [ \WPV_Filter_Manager::SETTING_KEY, 'postmeta', ProductPrice::LEGACY_FIELD_SLUG ], false ) ) {
			unset( $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ 'postmeta' ][ ProductPrice::LEGACY_FIELD_SLUG ] );
		}

		return $view_settings;
	}

}
