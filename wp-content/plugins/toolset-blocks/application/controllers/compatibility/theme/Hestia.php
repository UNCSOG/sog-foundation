<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\Theme;

use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Compatibility with the Hestia theme.
 *
 * Make sure that the theme renders properly CTs and WPAs also for WooCommerce products.
 */
class Hestia extends Base {

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
			add_filter( 'pre_option_woocommerce_catalog_columns', array( $this, 'force_single_column' ) );
		}
	}

	/**
	 * Make sure that the number of columns in WooCommerce catalogs is 1 if a WPA is assigned.
	 *
	 * @param int $columns
	 * @return int
	 */
	public function force_single_column( $columns ) {
		$columns = 1;
		return $columns;
	}

}
