<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\Theme;

use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Compatibility with the Blocksy theme.
 *
 * Make sure that the theme renders properly CTs and WPAs also for WooCommerce products.
 */
class Blocksy extends Base {

	const ARCHIVE_PREFIXES = [
		'woo_categories',
		'categories',
		'blog',
		'search',
		'author',
		'post_archive',
	];

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
		add_action( 'wpv_action_after_archive_set', array( $this, 'disable_grid_on_archives' ) );
		add_action( 'wpv_action_apply_archive_query_settings', array( $this, 'keep_pagination_settings' ), 99, 3 );
	}

	/**
	 * Disable the grid CSS classes in archives.
	 *
	 * - Apply common pattern for archives.
	 * - Apply variable pattern on CPT archives.
	 * - Apply variable pattern on tax archives for taxonomies assigned to post types.
	 * - Apply pattern for WooCommerce product-related archives.
	 *
	 * @param null|int $wpa_id
	 */
	public function disable_grid_on_archives( $wpa_id ) {
		if ( null === $wpa_id ) {
			return;
		}

		if ( 0 === $wpa_id ) {
			return;
		}

		foreach ( self::ARCHIVE_PREFIXES as $prefix ) {
			add_filter( 'theme_mod_' . $prefix . '_columns', array( $this, 'enforce_single_archive_column' ), 99 );
			add_filter( 'theme_mod_' . $prefix . '_structure', array( $this, 'enforce_archive_structure' ), 99 );
		}

		if ( is_post_type_archive() ) {
			global $wp_query;
			$post_type = $wp_query->get( 'post_type' );
			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}
			add_filter( 'theme_mod_' . $post_type . '_archive_columns', array( $this, 'enforce_single_archive_column' ), 99 );
			add_filter( 'theme_mod_' . $post_type . '_archive_structure', array( $this, 'enforce_archive_structure' ), 99 );
		}

		if ( is_tax() ) {
			global $wp_query;
			$term = $wp_query->get_queried_object();
			if (
				$term
				&& isset( $term->taxonomy )
			) {
				global $wp_taxonomies;
				if ( isset( $wp_taxonomies[ $term->taxonomy ] ) ) {
					$all_tax_post_types = $wp_taxonomies[ $term->taxonomy ]->object_type;

					if (
						! empty( $all_tax_post_types )
						&& isset( $all_tax_post_types[0] )
					) {
						$post_type = $all_tax_post_types[0];
						add_filter( 'theme_mod_' . $post_type . '_archive_columns', array( $this, 'enforce_single_archive_column' ), 99 );
						add_filter( 'theme_mod_' . $post_type . '_archive_structure', array( $this, 'enforce_archive_structure' ), 99 );
					}
				}
			}
		}

		if (
			is_post_type_archive( 'product' )
			|| is_tax( get_object_taxonomies( 'product' ) )
		) {
			add_filter( 'theme_mod_blocksy_woo_columns', array( $this, 'enforce_single_woo_column' ), 99 );
			add_filter( 'pre_option_woocommerce_catalog_columns', array( $this, 'enforce_single_desktop_woo_column' ), 99 );
		}
	}

	/**
	 * Override the Blocksy mechanism to override WPA pagination settings.
	 *
	 * Blocksy enforces a posts_per_page setting on parse_tax_query:10
	 * that overrides the one we set on pre_get_posts.
	 *
	 * @param \WP_Query $query
	 * @param mixed[]] $archive_settings
	 * @param int] $wpa_id
	 */
	public function keep_pagination_settings( $query, $archive_settings, $wpa_id ) {
		if ( null === $wpa_id ) {
			return;
		}

		if ( 0 === $wpa_id ) {
			return;
		}

		add_filter( 'parse_tax_query', function( $query ) {
			$posts_per_page_mod = $query->get( 'posts_per_page' );

			add_filter( 'parse_tax_query', function( $inner_query ) use ( $posts_per_page_mod ) {
				$posts_per_page_mod = $inner_query->set( 'posts_per_page', (int) $posts_per_page_mod );
			}, 99 );
		}, 1 );
	}

	/**
	 * Enforce a single column in native archives, when a WPA is assigned.
	 *
	 * @param string $column
	 * @return string
	 */
	public function enforce_single_archive_column( $column ) {
		return '1';
	}

	public function enforce_archive_structure( $structure )  {
		return 'simple';
	}

	/**
	 * Enforce a single column in desktop product archives, when a WPA is assigned.
	 *
	 * @param int $column
	 * @return int
	 */
	public function enforce_single_desktop_woo_column( $column ) {
		$column = 1;
		return $column;
	}

	/**
	 * Enforce a single column in product archives, when a WPA is assigned.
	 *
	 * @param int[]] $columns
	 * @return int[]
	 */
	public function enforce_single_woo_column( $columns ) {
		$columns = array(
			'desktop' => 1,
			'tablet' => 1,
			'mobile' => 1,
		);
		return $columns;
	}

}
