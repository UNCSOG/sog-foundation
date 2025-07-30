<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * Hacks for shortcode [wpv-woo-buy-options]
 *
 * Needs:
 * - Hack $wp_query so it simulates it is in a product page
 * - It seems that even WooCommerce Views shortcode is executed after ☝️ but Woocommerce hooks are not initializated, so it fakes them
 */
class BuyOptions extends AHack {
	private $wp_query;
	private $product;
	private $product_id;

	public function __construct( $product_id ) {
		$this->product_id = $product_id;
	}

	public function do_hack() {
		$this->hack_wp_query();
		$this->hack_woocommerce();
	}

	/**
	 * Simulates that WP is loading a product page
	 */
	private function hack_wp_query() {
		global $wp_query;

		$this->wp_query = $wp_query;

		$wp_query->is_singular = true;
		$wp_query->queried_object = get_post( $this->product_id );
	}

	/**
	 * It hacks action woocommerce_simple_add_to_cart
	 */
	private function hack_woocommerce() {
		global $product;
		// The product must be loaded
		$this->product = $product;
		$product_factory = new \WC_Product_Factory();
		$product = $product_factory->get_product( $this->product_id );
		add_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart' );
	}

	public function restore() {
		global $wp_query, $product;
		$wp_query = $this->wp_query;
		$product = $this->product;
	}
}
