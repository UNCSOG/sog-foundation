<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\Theme;

use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Compatibility with the Astra theme.
 *
 * Make sure that the theme renders properly CTs and WPAs also for WooCommerce products.
 */
class Astra extends Base {

	/**
	 * Initializes the compatibility layer.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initializes the hooks for the compatibility.
	 */
	private function init_hooks() {
		add_action( 'wpv_action_after_archive_set', array( $this, 'disable_grid_on_products_archive' ) );
	}

	/**
	 * Disable the grid CSS classes in product archives.
	 *
	 * @param null|int $wpa_id
	 */
	public function disable_grid_on_products_archive( $wpa_id ) {
		if ( null === $wpa_id ) {
			return;
		}

		if ( 0 === $wpa_id ) {
			return;
		}

		if (
			is_post_type_archive( 'product' )
			|| is_tax( get_object_taxonomies( 'product' ) )
		) {
			add_filter( 'woocommerce_enqueue_styles', array( $this, 'dequeue_astra_styles' ), 99 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_styles' ) );
		}
	}

	/**
	 * Make sure Astra does not load its own WooCommerce lyout styles on product archives.
	 *
	 * @param mixed[] $styles
	 * @return mixed[]
	 */
	public function dequeue_astra_styles( $styles ) {
		$styles = array_filter(
			$styles,
			function( $style_key ) {
				return ! in_array( $style_key, array( 'woocommerce-layout', 'woocommerce-smallscreen' ), true );
			},
			ARRAY_FILTER_USE_KEY
		);
		return $styles;
	}

	/**
	 * Recover the styles for the frontend pagination counter and sorting controls.
	 *
	 * Those are usually included in the 'woocommerce-layout' asset, but we are dequeuing it.
	 */
	public function add_inline_styles() {
		// Pagination counter.
		$required_extra_styles = '.woocommerce .woocommerce-result-count, .woocommerce-page .woocommerce-result-count { float: left; }';
		// Sorting controls.
		$required_extra_styles .= '.woocommerce .woocommerce-ordering, .woocommerce-page .woocommerce-ordering { float: right; }';
		wp_add_inline_style( 'woocommerce-general', $required_extra_styles );
	}

}
