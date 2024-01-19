<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post\ProductStock;

use OTGS\Toolset\Views\Controller\Filters\AbstractQuery;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductStock;

/**
 * Query component for the filter.
 */
class ProductStockQuery extends AbstractQuery {

	const LEGACY_ON_STOCK = '1';
	const LEGACY_OUT_OF_STOCK = '0';

	const STOCK_IN_STOCK = 'instock';
	const STOCK_OUT_OF_STOCK = 'outofstock';
	const STOCK_ON_BACKORDER = 'onbackorder';

	/**
	 * Load query component hooks.
	 */
	public function load_hooks() {
		// Happening at :15 so faked legacy filters can be created at :10.
		add_filter( 'wpv_filter_object_settings_for_fake_url_query_filters', array( $this, 'replace_legacy_query_filter' ), 15 );
		add_filter( 'wpv_filter_custom_field_filter_processed_value', array( $this, 'adjust_legacy_field_value' ), 10, 2 );
	}

	/**
	 * Override and replace the legacy filter by the custom postmeta field crafted by Toolset WooCommerce Blocks.
	 *
	 * @param mixed[] $view_settings
	 * @return mixed[]
	 */
	public function replace_legacy_query_filter( $view_settings ) {
		$legacy_filter_value = toolset_getarr( $view_settings, 'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_value', false );

		if ( false === $legacy_filter_value ) {
			return $view_settings;
		}

		$field_filter_value = toolset_getarr( $view_settings, 'custom-field-' . ProductStock::FIELD_SLUG . '_value', false );
		// Create a new postmeta filter using the value from the existing one, if needed.
		if ( false === $field_filter_value ) {
			$view_settings[ 'custom-field-' . ProductStock::FIELD_SLUG . '_type' ] = toolset_getarr(  $view_settings, 'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_type', '' );
			$view_settings[ 'custom-field-' . ProductStock::FIELD_SLUG . '_decimals' ] = toolset_getarr(  $view_settings, 'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_decimals', '' );
			$view_settings[ 'custom-field-' . ProductStock::FIELD_SLUG . '_compare' ] = toolset_getarr(  $view_settings, 'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_compare', '' );
			$view_settings[ 'custom-field-' . ProductStock::FIELD_SLUG . '_value' ] = toolset_getarr(  $view_settings, 'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_value', '' );
		}

		// Remove the old filter.
		$legacy_settings = array(
			'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_type',
			'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_decimals',
			'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_compare',
			'custom-field-' . ProductStock::LEGACY_FIELD_SLUG . '_value',
		);
		foreach ( $legacy_settings as $removing ) {
			if ( isset( $view_settings[ $removing ] ) ) {
				unset( $view_settings[ $removing ] );
			}
		}

		return $view_settings;
	}

	/**
	 * Make sure that legacy filtering still works when replacing with filtering by the native field.
	 *
	 * @param string $value
	 * @param string $meta_slug
	 * @return string
	 */
	public function adjust_legacy_field_value( $value, $meta_slug ) {
		if ( ProductStock::FIELD_SLUG !== $meta_slug ) {
			return $value;
		}

		switch ( $value ) {
			case self::LEGACY_ON_STOCK:
				return self::STOCK_IN_STOCK;
			case self::LEGACY_OUT_OF_STOCK:
				return self::STOCK_OUT_OF_STOCK;
		}

		return $value;
	}
}
