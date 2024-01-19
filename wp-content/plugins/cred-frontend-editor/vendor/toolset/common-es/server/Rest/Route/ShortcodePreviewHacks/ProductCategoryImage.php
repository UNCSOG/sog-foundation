<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * Hacks for shortcode [wpv-woo-productcategory-images]
 *
 * Needs:
 * - Hack $wp_query so it simulates it is in a product category archive
 * - It seems that even WooCommerce Views shortcode is executed after ☝️ but Woocommerce hooks are not initializated, so it fakes them
 */
class ProductCategoryImage extends AHack {
	private $wp_query;
	private $product_id;
	private $shortcode_attributes;

	public function __construct( $product_id ) {
		$this->product_id = $product_id;
	}

	public function do_hack() {
		$this->hack_wp_query();
	}

	/**
	 * Simulates that WP is loading a product page
	 */
	private function hack_wp_query() {
		global $wp_query;

		$this->wp_query = $wp_query;

		$wp_query->is_tax = true;
		// Pick a taxonomy with image
		$terms = get_the_terms( $this->product_id, 'product_cat' );
		foreach ($terms as $term) {
			$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
			if ( $thumbnail_id ) {
				$wp_query->queried_object = $term;
				return;
			}
		}
	}

	public function restore() {
		global $wp_query;
		$wp_query = $this->wp_query;
	}

	public function has_default_content() {
		return true;
	}

	public function get_default_content() {
		return '<span class="tb-fake-image">' . __( 'Product Category may display an image', 'wpv-views' ) . '</span>';
	}
}
