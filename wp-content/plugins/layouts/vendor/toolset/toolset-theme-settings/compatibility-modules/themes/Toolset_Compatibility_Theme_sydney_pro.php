<?php

class Toolset_Compatibility_Theme_sydney_pro extends Toolset_Compatibility_Theme_Handler {

	// Giving semantic names to settings whose actual names are dictated by the way they're used by the theme.
	const DISABLE_FEATURED_IMAGE_OPTION = 'toolset_disable_featured_image';
	const DISABLE_SINGLE_POST_NAV_OPTION = 'disable_single_post_nav';
	const DISABLE_ARCHIVE_TITLE_OPTION = 'get_the_archive_title';
	const DISABLE_SIDEBAR_ON_SINGLE_PAGE_OPTION = '_sydney_page_disable_sidebar';
	const DISABLE_SIDEBAR_ON_ARCHIVE_OPTION = 'fullwidth_pages';

	protected function run_hooks() {
		// Handling feature images: Depending on the context, the theme uses different customizer settings
		// but we only have a single setting for all these cases.
		//
		// To make things slightly more complicated, sometimes the setting is negative (truthy value to
		// disable the featured image) and sometimes it's positive (truthy to enable it).
		add_filter( 'theme_mod_enable_page_feat_images', $this->get_feature_image_callback( true ), 99 );
		add_filter( 'theme_mod_post_feat_image', $this->get_feature_image_callback( false ), 99 );
		add_filter( 'theme_mod_index_feat_image', $this->get_feature_image_callback( false ), 99 );

		// Override prev/next navigation on single posts.
		add_filter( 'sydney_single_post_nav_enable', function( $is_enabled_default ) {
			$setting_value = $this->get_setting( self::DISABLE_SINGLE_POST_NAV_OPTION );
			if ( '1' === $setting_value ) {
				return false;
			}

			if ( '0' === $setting_value ) {
				return true;
			}

			return $is_enabled_default;
		}, 99 );

		// Override the blog layout to "classic" when we're dealing with an archive managed by Toolset.
		add_filter( 'theme_mod_blog_layout', function( $default_layout ) {
			if ( $this->helper->fetch_queried_object_id() ) {
				return 'classic';
			}
			return $default_layout;
		}, 100 );

		// When using a WooCommerce template, make sure that the setting to disable archive title is respected.
		add_filter( 'woocommerce_show_page_title', function( $show_title ) {
			$setting_value = $this->get_setting( self::DISABLE_ARCHIVE_TITLE_OPTION );
			if ( '1' === $setting_value ) {
				remove_filter( 'woocommerce_show_page_title', 'sydney_woo_archive_title' );
				return false;
			}

			return $show_title;
		}, 0 );

		// This is necessary for product single page, so that the page gets the correct column width and sidebar visibility.
		// Again, the theme is using a special customizer option for this post type.
		add_filter( 'theme_mod_swc_sidebar_products', function( $default_value ) {
			$setting_value = $this->get_setting( self::DISABLE_SIDEBAR_ON_SINGLE_PAGE_OPTION );
			if ( '1' === $setting_value ) {
				return true;
			}

			return $default_value;
		} );

		// Additional measures are needed to hide the sidebar on custom product taxonomy archives. This is
		// due to how the sydney_wc_archive_check() specifically checks only for the product, product taxonomy
		// and product category archives.
		//
		// Due to this, we unfortunately have to completely override the sydney_wrapper_start() function in order
		// to force the correct content column width.
		add_action( 'woocommerce_sidebar', function() {
			if ( $this->should_disable_sidebar() ) {
				remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar' );
			}
		}, 0 );
		add_action( 'wp', function() {
			if ( ! is_tax( get_object_taxonomies( 'product' ) ) ) {
				return;
			}

			if ( $this->should_disable_sidebar() ) {
				remove_action( 'woocommerce_before_main_content', 'sydney_wrapper_start' );
				add_action( 'woocommerce_before_main_content', static function() {
					echo '<div id="primary" class="content-area col-md-12">';
					echo '<main id="main" class="site-main" role="main">';
				} );
			}
		}, 99 );
	}


	/**
	 * Produce a callback to override a setting to enable, disable or leave unaffected the
	 * visibility of the featured image.
	 *
	 * @param bool $is_enable_setting True if a truthy value of the filter output means the featured image should
	 *     be displayed, false if a truthy value means it should be disabled.
	 *
	 * @return callable
	 */
	private function get_feature_image_callback( $is_enable_setting ) {
		return function( $value ) use( $is_enable_setting ) {
			switch( $this->get_setting(self::DISABLE_FEATURED_IMAGE_OPTION ) ) {
				case 'enable':
					$value = $is_enable_setting ? 1 : 0;
					break;
				case 'disable':
					$value = ! $is_enable_setting ? 1 : 0;
					break;
			}

			return $value;
		};
	}


	/**
	 * Check if the sidebar should be disabled by Theme Settings.
	 *
	 * @return bool
	 */
	private function should_disable_sidebar() {
		$setting_for_single_pages = apply_filters( 'toolset_theme_integration_get_setting', null, self::DISABLE_SIDEBAR_ON_SINGLE_PAGE_OPTION );
		$setting_for_archives = apply_filters( 'toolset_theme_integration_get_setting', null, self::DISABLE_SIDEBAR_ON_ARCHIVE_OPTION );
		return '1' === $setting_for_single_pages || '1' === $setting_for_archives;
	}


	/**
	 * Retrieve a theme setting value.
	 *
	 * @param string $setting_name
	 *
	 * @return string
	 */
	private function get_setting( $setting_name ) {
		return (string) apply_filters( 'toolset_theme_integration_get_setting', null, $setting_name );
	}
}
