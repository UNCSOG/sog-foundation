<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * This class returns the hacker class for a shortcode
 *
 * Some shortcodes need to hack the "enviroment" to render proper results. For example, WooCommerce Views shortcodes might need to load some data
 */
class Factory {
	/**
	 * Returns the hack class for the shortcode
	 *
	 * @param int $post_id
	 * @param string $shortcode
	 */
	public function get_hack( $post_id, $shortcode ) {
		if ( preg_match( '/wpv-woo-buy-options/', $shortcode ) ) {
			return new BuyOptions( $post_id );
		} else if ( preg_match( '/wpv-woo-display-tabs/', $shortcode ) ) {
			return new DisplayTabs( $post_id );
		} else if ( preg_match( '/wpv-woo-onsale/', $shortcode ) ) {
			return new Onsale();
		} else if ( preg_match( '/wpv-woo-list_attributes/', $shortcode ) ) {
			return new ListAttributes();
		} else if ( preg_match( '/wpv-woo-related_products/', $shortcode ) ) {
			return new RelatedProducts();
		} else if ( preg_match( '/wpv-woo-productcategory-images/', $shortcode ) ) {
			return new ProductCategoryImage( $post_id );
		} else if ( preg_match( '/wpv-control-post-relationship/', $shortcode ) ) {
			return new ControlPostRelationship( $post_id );
		}
		return new Dummy();
	}
}
