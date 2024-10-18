<?php

/**
 * Compatibility class for Total theme
 */
class Toolset_Compatibility_Theme_total extends Toolset_Compatibility_Theme_Handler {

	/**
	 * Run theme integration hooks.
	 */
	protected function run_hooks() {
		// Blog elemets.
		add_filter( 'wpex_blog_single_layout_blocks', [ $this, 'maybe_disable_single_elements' ], 9, 2 );
		add_action( 'get_header', array( $this, 'disable_archive_pagination' ) );
		add_action( 'get_header', array( $this, 'disable_woo_breadcrumbs' ) );
	}

	/**
	 * Extend the settings for singular posts into native single posts,
	 * since they use a different filter / Customizer setting.
	 *
	 * @param string[]] $blocks
	 * @param string $context
	 * @return string[]
	 */
	public function maybe_disable_single_elements( $blocks, $context = 'front-end' ) {
		if ( 'front-end' !== $context ) {
			return $blocks;
		}

		$should_include_single_featured_image = apply_filters( 'toolset_theme_integration_get_setting', null, 'cpt_single_block_media_enabled' );
		if ( false === (bool) $should_include_single_featured_image ) {
			$blocks = array_diff( $blocks, [ 'featured_media' ] );
		} else if (
			'1' === $should_include_single_featured_image
			&& false === in_array( 'featured_media', $blocks, true )
		) {
			$blocks['featured_media'] = 'featured_media';
		}

		$should_include_single_title = apply_filters( 'toolset_theme_integration_get_setting', null, 'cpt_single_block_title_enabled' );
		if ( false === (bool) $should_include_single_title ) {
			$blocks = array_diff( $blocks, [ 'title' ] );
		} else if (
			'1' === $should_include_single_title
			&& false === in_array( 'title', $blocks, true )
		) {
			$blocks['title'] = 'title';
		}

		$should_include_single_meta = apply_filters( 'toolset_theme_integration_get_setting', null, 'cpt_single_block_meta_enabled' );
		if ( false === (bool) $should_include_single_meta ) {
			$blocks = array_diff( $blocks, [ 'meta' ] );
			$blocks = array_diff( $blocks, [ 'post_tags' ] );
		} else if (
			'1' === $should_include_single_meta
			&& (
				false === in_array( 'meta', $blocks, true )
				|| false === in_array( 'meta', $blocks, true )
			)
		) {
			$blocks['meta'] = 'meta';
			$blocks['post_tags'] = 'post_tags';
		}

		$should_include_single_share = apply_filters( 'toolset_theme_integration_get_setting', null, 'cpt_single_block_share_enabled' );
		if ( false === (bool) $should_include_single_share ) {
			$blocks = array_diff( $blocks, [ 'share' ] );
			$blocks = array_diff( $blocks, [ 'social_share' ] );
		} else if (
			'1' === $should_include_single_share
			&& (
				false === in_array( 'share', $blocks, true )
				|| false === in_array( 'social_share', $blocks, true )
			)
		) {
			$blocks['share'] = 'share';
			$blocks['social_share'] = 'social_share';
		}

		$should_include_single_author_bio = apply_filters( 'toolset_theme_integration_get_setting', null, 'cpt_single_block_author_bio_enabled' );
		if ( false === (bool) $should_include_single_author_bio ) {
			$blocks = array_diff( $blocks, [ 'author_bio' ] );
		} else if (
			'1' === $should_include_single_author_bio
			&& false === in_array( 'author_bio', $blocks, true )
		) {
			$blocks['author_bio'] = 'author_bio';
		}

		$should_include_single_related_posts = apply_filters( 'toolset_theme_integration_get_setting', null, 'cpt_single_block_related_posts_enabled' );
		if ( false === (bool) $should_include_single_related_posts ) {
			$blocks = array_diff( $blocks, [ 'related_posts' ] );
		} else if (
			'1' === $should_include_single_related_posts
			&& false === in_array( 'related_posts', $blocks, true )
		) {
			$blocks['related_posts'] = 'related_posts';
		}

		$should_include_single_comments = apply_filters( 'toolset_theme_integration_get_setting', null, 'cpt_single_block_comments_enabled' );
		if ( false === (bool) $should_include_single_comments ) {
			$blocks = array_diff( $blocks, [ 'comments' ] );
		} else if (
			'1' === $should_include_single_comments
			&& false === in_array( 'comments', $blocks, true )
		) {
			$blocks['comments'] = 'comments';
		}

		return $blocks;
	}

	/**
	 * Disable archive pagination over a custom setting.
	 */
	public function disable_archive_pagination() {
		if ( $this->get_toolset_custom_theme_option( 'toolset_disable_archive_pagination' ) ) {
			add_filter( 'theme_mod_blog_pagination_style', function( $style ) {
				return 'standard';
			} );
			add_filter( 'paginate_links_output', '__return_empty_string', 99 );
		}
	}

	/**
	 * Disable WooCommerce breadcrumbs over the main content.
	 */
	public function disable_woo_breadcrumbs() {
		if ( $this->get_toolset_custom_theme_option( 'wpex_has_breadcrumbs' ) ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
		}
	}

}
