<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\Theme;

use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Compatibility with the Page Builder Framework theme.
 *
 * Make sure that the theme renders properly CTs and WPAs also for WooCommerce products.
 */
class PageBuilderFramework extends Base {

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
			add_filter( 'theme_mod_woocommerce_loop_products_per_row', array( $this, 'set_woocommerce_loop_products_per_row' ) );
		}
	}

	/**
	 * Enforce one item per row on product archives rendered with a WPA on desktop, tablet and mobile.
	 *
	 * @param string $value
	 * @return string
	 */
	public function set_woocommerce_loop_products_per_row( $value ) {
		$value = wp_json_encode( array(
			'desktop' => '1',
			'tablet' => '1',
			'mobile' => '1',
		) );
		return $value;
	}

}
