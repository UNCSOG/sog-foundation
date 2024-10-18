<?php

/**
 * Compatibility class for Page Builder Framework theme.
 */
class Toolset_Compatibility_Theme_page_builder_framework extends Toolset_Compatibility_Theme_Handler {

	/**
	 * Run theme integration hooks.
	 */
	protected function run_hooks() {
		add_filter( 'theme_mod_single_sortable_header', [ $this, 'disable_single_header_items' ] );
		add_filter( 'theme_mod_single_sortable_footer', [ $this, 'disable_single_footer_items' ] );

		add_filter( 'wpbf_title', [ $this, 'disable_single_page_title' ] );

		add_filter( 'theme_mod_breadcrumbs', [ $this, 'force_breadcrumbs' ] );

		add_filter( 'woocommerce_show_page_title', [ $this, 'disable_product_archive_title' ] );
		add_action( 'woocommerce_before_main_content', [ $this, 'disable_product_archive_breadcrumb' ], 1 );
	}

	/**
	 * Disable some elements appearing before the single post content.
	 *
	 * Note that those are stored as a Customizer array option.
	 *
	 * @param string[] $items
	 * @return string[]
	 */
	public function disable_single_header_items( $items ) {
		if ( $this->get_toolset_custom_theme_option( 'toolset_disable_single_title' ) ) {
			$items = array_diff( $items, [ 'title' ] );
		}
		if ( $this->get_toolset_custom_theme_option( 'toolset_disable_single_meta' ) ) {
			$items = array_diff( $items, [ 'meta' ] );
		}
		if ( $this->get_toolset_custom_theme_option( 'toolset_disable_single_featured' ) ) {
			$items = array_diff( $items, [ 'featured' ] );
		}
		return $items;
	}

	/**
	 * Disable some elements appearing after the single post content.
	 *
	 * Note that those are stored as a Customizer array option.
	 *
	 * @param string[] $items
	 * @return string[]
	 */
	public function disable_single_footer_items( $items ) {
		if ( $this->get_toolset_custom_theme_option( 'toolset_disable_single_categories' ) ) {
			$items = array_diff( $items, [ 'categories' ] );
		}
		return $items;
	}

	/**
	 * Disable the title on Page native posts.
	 *
	 * @param string $title
	 * @return void
	 */
	public function disable_single_page_title( $title ) {
		if ( $this->get_toolset_custom_theme_option( 'toolset_disable_single_title' ) ) {
			return '';
		}

		return $title;
	}

	/**
	 * Force using breadcrumbs when the current settig states so.
	 *
	 * Uses the list of locations where breadcrumbs can appear, from the theme.
	 *
	 * @param string[] $locations
	 * @return string[]
	 */
	public function force_breadcrumbs( $locations ) {
		if ( true === (bool) $this->get_toolset_custom_theme_option( 'breadcrumbs_toggle' ) ) {
			return array( 'archive', 'single', 'search', '404', 'page', 'front_page' );
		}
		return $locations;
	}

	/**
	 * Maybe disable our own product archive title.
	 *
	 * @param bool $show_title
	 * @return bool
	 */
	public function disable_product_archive_title( $show_title ) {
		$should_disable_archive_title = apply_filters( 'toolset_theme_integration_get_setting', null, 'get_the_archive_title' );
		if ( (bool) $should_disable_archive_title ) {
			return false;
		}

		return $show_title;
	}

	/**
	 * Proxy the setting on breadcrumbs to our WooCommerce Blocks archive template.
	 */
	public function disable_product_archive_breadcrumb() {
		$raw_breadcrumb_toggle = apply_filters('toolset_theme_integration_get_setting', null, 'breadcrumbs_toggle');
		if ( true === (bool) $raw_breadcrumb_toggle ) {
			return;
		}

		$priority = has_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb' );
		if ( false !== $priority ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', $priority );
		}
	}

}
