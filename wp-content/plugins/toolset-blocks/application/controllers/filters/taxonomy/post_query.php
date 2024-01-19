<?php

namespace OTGS\Toolset\Views\Controller\Filters\Taxonomy;

/**
 * Apply taxonomy queries post query filters.
 *
 * As a general rule:
 * - Priorities below 100 are reserved to filters that can alter the included results.
 * - Priorities above 100 are used to manipulate the results: sorting, limit and offset.
 *
 * @since 2.9.4
 */
class PostQuery {

	const PARENT_FILTER_PRIORITY = 10;

	const LIMIT_AND_OFFSET_PRIORITY = 200;

	const SORTING_PRIORITY = 300;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Filters\Taxonomy\PostQuery
	 */
	private static $instance = null;

	/**
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Singleton
	 *
	 * @return \OTGS\Toolset\Views\Controller\Filters\Taxonomy\PostQuery
	 * @since 2.9.4
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Entry point.
	 *
	 * @since 2.9.4
	 */
	public function maybe_initialize() {
		if ( true === $this->initialized ) {
			return;
		}

		$this->initialized = true;
		$this->add_hooks();
	}

	/**
	 * Add the right hooks into the taxonomy post query.
	 *
	 * @since 2.9.4
	 */
	private function add_hooks() {
		add_filter( 'wpv_filter_taxonomy_post_query', array( $this, 'parent_filter' ), self::PARENT_FILTER_PRIORITY, 4 );
		add_filter( 'wpv_filter_taxonomy_post_query', array( $this, 'limit_and_offset' ), self::LIMIT_AND_OFFSET_PRIORITY, 4 );
		add_filter( 'wpv_filter_taxonomy_post_query', array( $this, 'count_sorting' ), self::SORTING_PRIORITY, 4 );
	}

	/**
	 * Filter a list of terms by a fixed parent, if the View settings state so.
	 *
	 * @param \WP_Term[] $items List of terms returned by the Views query.
	 * @param array $tax_query_settings The relevant elements of the View settings in an array to be used as arguments in a get_terms() call.
	 * @param array $view_settings
	 * @param int $view_id
	 * @return \WP_Term[]
	 * @since 1.12
	 */
	public function parent_filter( $items, $tax_query_settings, $view_settings, $view_id ) {
		if ( ! isset( $tax_query_settings['child_of'] ) ) {
			return $items;
		}

		$parent_id = (int) $tax_query_settings['child_of'];
		foreach ( $items as $index => $item ) {
			if ( (int) $item->parent !== $parent_id ) {
				unset( $items[ $index ] );
			}
		}

		return $items;
	}

	/**
	 * Apply limit and offset settings to a taxonomy terms query.
	 *
	 * @param \WP_Term[] $items List of terms returned by the Views query.
	 * @param array $tax_query_settings The relevant elements of the View settings in an array to be used as arguments in a get_terms() call.
	 * @param array $view_settings
	 * @param int $view_id
	 * @return \WP_Term[]
	 * @since unknown
	 */
	public function limit_and_offset( $items, $tax_query_settings, $view_settings, $view_id ) {
		$limit = (int) $view_settings['taxonomy_limit'];
		$offset = (int) $view_settings['taxonomy_offset'];

		$override_values = wpv_override_view_limit_offset();
		if ( isset( $override_values['limit'] ) ) {
			$limit = (int) $override_values['limit'];
		}
		if ( isset( $override_values['offset'] ) ) {
			$offset = (int) $override_values['offset'];
		}

		if (
			$offset !== 0
			|| $limit !== -1
		) {
			if ( $limit === -1 ) {
				$items = array_slice( $items, $offset );
			} else {
				$items = array_slice( $items, $offset, $limit );
			}
			if ( empty( $items ) ) {
				return array();
			}
		}
		return $items;
	}

	/**
	 * Apply sorting settings to a taxonomy terms query ordered by count.
	 *
	 * @param \WP_Term[] $items List of terms returned by the Views query.
	 * @param array $tax_query_settings The relevant elements of the View settings in an array to be used as arguments in a get_terms() call.
	 * @param array $view_settings
	 * @param int $view_id
	 * @return \WP_Term[]
	 * @since unknown
	 */
	public function count_sorting( $items, $taxonomy_query, $view_settings, $view_id ) {
		if ( 'count' !== toolset_getarr( $taxonomy_query, 'orderby' ) ) {
			return $items;
		}

		if ( ! toolset_getarr( $taxonomy_query, 'pad_counts', false ) ) {
			return $items;
		}

		if ( 'ASC' === toolset_getarr( $taxonomy_query, 'order' ) ) {
			usort( $items, function( $a, $b ) {
				if ( $a->count === $b->count ) {
					return 0;
				}
				return ( $a->count < $b->count ) ? -1 : 1;
			});
		} else {
			usort( $items, function( $a, $b ) {
				if ( $a->count === $b->count ) {
					return 0;
				}
				return ( $a->count < $b->count ) ? 1 : -1;
			});
		}

		return $items;
	}

}
