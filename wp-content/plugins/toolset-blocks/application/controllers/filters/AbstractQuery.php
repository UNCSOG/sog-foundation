<?php

namespace OTGS\Toolset\Views\Controller\Filters;

/**
 * Shared methods and routines for the query component of filters.
 */
abstract class AbstractQuery {

	/**
	 * Query component initialization.
	 */
	public function initialize() {
		add_action( 'init', array( $this, 'load_hooks' ) );
	}

	/**
	 * Set callbacks for registering the query for Views and WPAs.
	 *
	 * This requires callbacks in the following hooks:
	 * - wpv_filter_query for general Views.
	 * - wpv_action_apply_archive_query_settings for WPAs.
	 * - wpv_filter_object_settings_for_fake_url_query_filters for replacing legacy filters wiht new ones.
	 */
	abstract public function load_hooks();

	/**
	 * Check whether the post__in query arg is already pushing an empty query.
	 *
	 * @param mixed[] $query
	 * @return bool
	 */
	protected function is_empty_post__in_query_arg( $query ) {
		if (
			isset( $query['post__in'] )
			&& (
				array( 0 ) === $query['post__in']
				|| array( '0' ) === $query['post__in']
			)
		) {
			return true;
		}

		return false;
	}


	/**
	 * Check whether the post__in query property is already pushing an empty query.
	 *
	 * @param \WP_Query $query
	 * @return bool
	 */
	protected function is_empty_post__in_query_var( $query ) {
		$post__in = $query->get( 'post__in' );

		if (
			array( 0 ) === $post__in
			|| array( '0' ) === $post__in
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether a query object is a product-related archive.
	 *
	 * @param \WP_Query
	 * @param string $post_type Post type to check against
	 * @param bool $include_taxonomy_archives Include checks on taxonomy archives assigned to the given post type.
	 * @return bool
	 */
	protected function is_post_type_archive_query( $query, $post_type, $include_taxonomy_archives = false ) {
		if ( $query->is_post_type_archive( $post_type ) ) {
			return true;
		}

		if (
			$include_taxonomy_archives
			|| $query->is_tax( get_object_taxonomies( $post_type ) )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether a View is listing a given post type.
	 *
	 * @param string|string[] $post_types Post types being listed.
	 * @param string $post_type Post type to check against
	 * @return bool
	 */
	protected function is_listing_post_type( $post_types, $post_type ) {
		if (
			is_array( $post_types )
			&& in_array( $post_type, $post_types, true )
		) {
			return true;
		}

		return ( $post_type === $post_types );
	}

}
