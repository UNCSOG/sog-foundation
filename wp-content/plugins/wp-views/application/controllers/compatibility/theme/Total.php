<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\Theme;

use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Compatibility with the Total theme.
 *
 * Make sure that the theme renders properly CTs and WPAs also for WooCommerce products.
 */
class Total extends Base {

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
		add_action( 'wpv_action_after_archive_set', array( $this, 'adjust_layout_on_products_archive' ) );
	}

	/**
	 * Disable some elements on product archives.
	 *
	 * @param null|int $wpa_id
	 */
	public function adjust_layout_on_products_archive( $wpa_id ) {
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
			add_filter( 'wpex_woo_loop_wrap_classes', function( $classes ) {
				$classes = str_replace( ' wpex-row ', ' ', $classes );
				return $classes;
			}, 99 );
		}
	}

}
