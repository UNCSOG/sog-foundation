<?php

namespace OTGS\Toolset\Views\Model\WooCommerce;

/**
 * Extension of the \WC_Query class from WooCommerce.
 *
 * Make sure to not instantiate this if WooCommerce is not active!
 *
 * Adds filter and sorting capabilities to Views and Views/WPAs AJAX callbacks,
 * since the native class only supports non admin main queries.
 */
class WcQuery extends \WC_Query {

	const URL_PARAM_MIN_PRICE = 'min_price';
	const URL_PARAM_MAX_PRICE = 'max_price';

	const URL_PARAM_VIEW_MIN_PRICE = 'wpv-min_price';
	const URL_PARAM_VIEW_MAX_PRICE = 'wpv-max_price';

	const URL_PARAM_MIN_MAX_PRICE = 'wpv-min_max_price';

	const QUERY_PARAM_MIN_PRICE = 'wpv_min_price';
	const QUERY_PARAM_MAX_PRICE = 'wpv_max_price';

	const URL_PARAM_ORDERBY = 'orderby';

	const QUERY_PARAM_ORDERBY = 'wpv_orderby';
	const QUERY_PARAM_ORDER = 'wpv_order';

	/**
	 * Reference to the main product query on the page.
	 *
	 * Cloned from \WC_Query so private is less private!
	 *
	 * @var WP_Query
	 */
	private static $product_query;

	/**
	 * Stores chosen attributes.
	 *
	 * Cloned from \WC_Query so private is less private!
	 *
	 * @var array
	 */
	private static $chosen_attributes;

	/**
	 * The instance of the class that helps filtering with the product attributes lookup table.
	 *
	 * @var \Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer|null
	 */
	private $filterer;

	/**
	 * Extend the original constructor to work on Views and AJAXed WPAs.
	 */
	public function __construct() {
		// WooCommmerce compatbility before and after WC 5.5.
		if (
			class_exists('\Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer')
			&& function_exists('wc_get_container')
		) {
			$this->filterer = \wc_get_container()->get( \Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer::class );
		}

		// Extend key methods beyond frontend and main query.
		$parent_class = get_parent_class();
		if ( method_exists( $parent_class, 'pre_get_posts' ) ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 999 );
		}
	}

	/**
	 * Are we currently on the front page?
	 *
	 * Cloned from \WC_Query so private is less private!
	 *
	 * @param WP_Query $q Query instance.
	 * @return bool
	 */
	private function is_showing_page_on_front( $q ) {
		return ( $q->is_home() && ! $q->is_posts_page ) && 'page' === get_option( 'show_on_front' );
	}

	/**
	 * Is the front page a page we define?
	 *
	 * Cloned from \WC_Query so private is less private!
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	private function page_on_front_is( $page_id ) {
		return absint( get_option( 'page_on_front' ) ) === absint( $page_id );
	}

	/**
	 * Join wc_product_meta_lookup to posts if not already joined.
	 *
	 * Cloned from \WC_Query so private is less private!
	 *
	 * @param string $sql SQL join.
	 * @return string
	 */
	private function append_product_sorting_table_join( $sql ) {
		global $wpdb;

		if ( ! strstr( $sql, 'wc_product_meta_lookup' ) ) {
			$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}
		return $sql;
	}

	private function is_doing_loop_ajax() {
		if (
			wp_doing_ajax()
			&& array_key_exists( 'action', $_REQUEST )
			&& in_array(
				$_REQUEST['action'],
				[ 'wpv_get_view_query_results', 'wpv_get_archive_query_results' ],
				true
			)
		) {
			return true;
		}

		return false;
	}

	private function is_view_query_to_adjust( $q ) {
		if ( true === $q->get( 'wpv_query' ) ) {
			return true;
		}

		return false;
	}

	private function is_wpa_query_to_adjust( $q ) {
		if (
			$q->is_post_type_archive( 'product' )
			|| $q->is_tax( get_object_taxonomies( 'product' ) )
		) {
			if ( null !== apply_filters( 'wpv_filter_wpv_get_current_archive', null ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether we are in a case not covered by native WooCommerce.
	 *
	 * Native WooCommerce covers frontend for native products archive query.
	 * This means that we need to cover:
	 * - Main archive query in Views AJAX.
	 * - Non main query (View) in frontend.
	 * - Non main query (View) in Views AJAX.
	 *
	 * This translates to Views AJAX or non main query (View).
	 *
	 * @param \WP_Query $q
	 * @return bool
	 */
	private function is_query_to_adjust( $q ) {
		if ( true === $this->is_view_query_to_adjust( $q ) ) {
			return true;
		}

		if ( true === $this->is_wpa_query_to_adjust( $q ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the current min_price filter boundary, if any.
	 *
	 * @param \WP_Query $query
	 * @return float|false
	 */
	private function get_min_price( $query ) {
		if ( isset( $_GET[ self::URL_PARAM_MIN_PRICE ] ) ) {
			return floatval( wp_unslash( $_GET[ self::URL_PARAM_MIN_PRICE ] ) );
		}

		$query_min_price = $query->get( self::QUERY_PARAM_MIN_PRICE, false );
		if ( $query_min_price ) {
			return floatval( $query_min_price );
		}

		return false;
	}

	/**
	 * Get the current max_price filter boundary, if any.
	 *
	 * @param \WP_Query $query
	 * @return float|false
	 */
	private function get_max_price( $query ) {
		if ( isset( $_GET[ self::URL_PARAM_MAX_PRICE ] ) ) {
			return floatval( wp_unslash( $_GET[ self::URL_PARAM_MAX_PRICE ] ) );
		}

		$query_max_price = $query->get( self::QUERY_PARAM_MAX_PRICE, false );
		if ( $query_max_price ) {
			return floatval( $query_max_price );
		}

		return false;
	}

	/**
	 * Get the current orderby setting, if any.
	 *
	 * @param \WP_Query $query
	 * @return float|false
	 */
	private function get_orderby( $query ) {
		if ( isset( $_GET[ self::URL_PARAM_ORDERBY ] ) ) {
			return wc_clean( (string) wp_unslash( $_GET[ self::URL_PARAM_ORDERBY ] ) );
		}

		$query_orderby = $query->get( self::QUERY_PARAM_ORDERBY, false );
		if ( $query_orderby ) {
			return wc_clean( (string) $query_orderby );
		}

		return false;
	}

	/**
	 * Get the current order setting, if any.
	 *
	 * @param \WP_Query $query
	 * @return float|false
	 */
	private function get_order( $query ) {
		$query_order = $query->get( self::QUERY_PARAM_ORDER, false );
		if ( $query_order ) {
			return wc_clean( (string) $query_order );
		}

		return false;
	}

	/**
	 * Hook into pre_get_posts to do the main product query.
	 *
	 * Cloned from \WC_Query so Views queries are also main queries.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public function pre_get_posts( $q ) {
		if ( false === $this->is_query_to_adjust( $q ) ) {
			return;
		}

		if ( true === $q->get( 'wpv_query' ) ) {
			$post_type = $q->get( 'post_type' );
			$post_type = toolset_ensarr( $post_type, [ $post_type ] );
			if ( false === in_array( 'product', $post_type, true ) ) {
				return;
			}
		} elseif ( ! $q->is_post_type_archive( 'product' ) && ! $q->is_tax( get_object_taxonomies( 'product' ) ) ) {
			// Only apply to product categories, the product post archive, the shop page, product tags, and product attribute taxonomies.
			return;
		}

		$this->product_query( $q );
	}

	/**
	 * Query the products, applying sorting/ordering etc.
	 * This applies to the main WordPress loop.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public function product_query( $q ) {
		if ( ! is_feed() ) {
			$orderby = $this->get_orderby( $q );
			$order = $this->get_order( $q );
			$orderby = ( false === $orderby ) ? '' : $orderby;
			$order = ( false === $order ) ? '' : $order;
			$ordering = $this->get_catalog_ordering_args( $orderby, $order );
			$q->set( 'orderby', $ordering['orderby'] );
			$q->set( 'order', $ordering['order'] );

			if ( isset( $ordering['meta_key'] ) ) {
				$q->set( 'meta_key', $ordering['meta_key'] );
			}
		}
		if (
			$this->is_wpa_query_to_adjust( $q )
			&& $this->is_doing_loop_ajax()
		) {
			$q->set( 'meta_query', $this->get_meta_query( $q->get( 'meta_query' ), true ) );
			$q->set( 'tax_query', $this->get_tax_query( $q->get( 'tax_query' ), true ) );
			$q->set( 'wc_query', 'product_query' );
		}
		// Work out how many products to query.
		$q->set( 'posts_per_page', $q->get( 'posts_per_page' ) ? $q->get( 'posts_per_page' ) : apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() ) );

		// Store reference to this query.
		self::$product_query = $q;

		// Additonal hooks to change WP Query.
		add_filter( 'posts_clauses', array( $this, 'price_filter_post_clauses' ), 10, 2 );
		add_filter( 'the_posts', array( $this, 'handle_get_posts' ), 10, 2 );

		do_action( 'toolset_woocommerce_product_query', $q, $this );
	}

	/**
	 * Appends tax queries to an array.
	 *
	 * Ported from the original WooCommerce class, because $filterer is a private property since WC 5.5.
	 *
	 * @param  array $tax_query  Tax query.
	 * @param  bool  $main_query If is main query.
	 * @return array
	 */
	public function get_tax_query( $tax_query = array(), $main_query = false ) {
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array(
				'relation' => 'AND',
			);
		}

		if ( $main_query &&
			( null === $this->filterer
			|| ! $this->filterer->filtering_via_lookup_table_is_active()
			)
		) {
			// Layered nav filters on terms.
			foreach ( $this->get_layered_nav_chosen_attributes() as $taxonomy => $data ) {
				$tax_query[] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $data['terms'],
					'operator'         => 'and' === $data['query_type'] ? 'AND' : 'IN',
					'include_children' => false,
				);
			}
		}

		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() && $main_query ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		// Hide out of stock products.
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$product_visibility_not_in[] = $product_visibility_terms['outofstock'];
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Filter by rating.
		if ( isset( $_GET['rating_filter'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$rating_filter = array_filter( array_map( 'absint', explode( ',', wp_unslash( $_GET['rating_filter'] ) ) ) );
			$rating_terms  = array();
			for ( $i = 1; $i <= 5; $i ++ ) {
				if ( in_array( $i, $rating_filter, true ) && isset( $product_visibility_terms[ 'rated-' . $i ] ) ) {
					$rating_terms[] = $product_visibility_terms[ 'rated-' . $i ];
				}
			}
			if ( ! empty( $rating_terms ) ) {
				$tax_query[] = array(
					'taxonomy'      => 'product_visibility',
					'field'         => 'term_taxonomy_id',
					'terms'         => $rating_terms,
					'operator'      => 'IN',
					'rating_filter' => true,
				);
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! empty( $product_visibility_not_in ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_not_in,
				'operator' => 'NOT IN',
			);
		}

		return array_filter( apply_filters( 'woocommerce_product_query_tax_query', $tax_query, $this ) );
	}

	/**
	 * Returns an array of arguments for ordering products based on the selected values.
	 *
	 * @param string $orderby Order by param.
	 * @param string $order Order param.
	 * @return array
	 */
	public function get_catalog_ordering_args( $orderby = '', $order = '' ) {
		// If we know that WooCommerce forced a sorting arg, use it.
		// Note that WooCommerce will use orderby as price, price-desc or price-asc,
		// so if we find one of them without a proper order URL parameter, we need to clean it.
		if (
			! $orderby
			|| 'price-asc' === toolset_getget( self::URL_PARAM_ORDERBY, false )
			|| 'price-desc' === toolset_getget( self::URL_PARAM_ORDERBY, false )
			|| (
				'price' === toolset_getget( self::URL_PARAM_ORDERBY, false )
				&& false === toolset_getget( self::QUERY_PARAM_ORDER, false )
			)
		) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$orderby_value = isset( $_GET[ self::URL_PARAM_ORDERBY ] ) ? wc_clean( (string) wp_unslash( $_GET[ self::URL_PARAM_ORDERBY ] ) ) : wc_clean( get_query_var( 'orderby' ) );
			$order = '';

			if ( ! $orderby_value ) {
				if ( is_search() ) {
					$orderby_value = 'relevance';
				} else {
					$orderby_value = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) );
				}
			}

			// Get order + orderby args from string.
			$orderby_value = is_array( $orderby_value ) ? $orderby_value : explode( '-', $orderby_value );
			$orderby = esc_attr( $orderby_value[0] );
			$order = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
		} else {
			// $orderby might be a string containing order + ordeby if it comes from a search URL parameter, as above.
			$orderby_value = explode( '-', $orderby );
			$orderby = esc_attr( $orderby_value[0] );
			$order = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
		}

		// Convert to correct format.
		$orderby = strtolower( is_array( $orderby ) ? (string) current( $orderby ) : (string) $orderby );
		$order   = strtoupper( is_array( $order ) ? (string) current( $order ) : (string) $order );
		$args    = array(
			'orderby'  => $orderby,
			'order'    => ( 'DESC' === $order ) ? 'DESC' : 'ASC',
		);

		switch ( $orderby ) {
			case 'id':
				$args['orderby'] = 'ID';
				break;
			case 'menu_order':
				$args['orderby'] = 'menu_order title';
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;
			case 'relevance':
				$args['orderby'] = 'relevance';
				$args['order']   = 'DESC';
				break;
			case 'rand':
				$args['orderby'] = 'rand'; // @codingStandardsIgnoreLine
				break;
			case 'date':
				$args['orderby'] = 'date ID';
				$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;
			case 'price':
				$callback = 'DESC' === $order ? 'order_by_price_desc_post_clauses' : 'order_by_price_asc_post_clauses';
				add_filter( 'posts_clauses', array( $this, $callback ) );
				break;
			case 'popularity':
				add_filter( 'posts_clauses', array( $this, 'order_by_popularity_post_clauses' ) );
				break;
			case 'rating':
				add_filter( 'posts_clauses', array( $this, 'order_by_rating_post_clauses' ) );
				break;
		}

		return apply_filters( 'woocommerce_get_catalog_ordering_args', $args, $orderby, $order );
	}

	/**
	 * Custom query used to filter products by price.
	 *
	 * @since 3.6.0
	 *
	 * @param array    $args Query args.
	 * @param WP_Query $wp_query WP_Query object.
	 *
	 * @return array
	 */
	public function price_filter_post_clauses( $args, $wp_query ) {
		global $wpdb;

		$calculated_max_price = $this->get_max_price( $wp_query );
		$calculated_min_price = $this->get_min_price( $wp_query );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (
			false === $this->is_query_to_adjust( $wp_query )
			|| (
				false === $calculated_max_price
				&& false === $calculated_min_price
			)
		) {
			return $args;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current_min_price = ( false !== $calculated_min_price ) ? $calculated_min_price : 0;
		$current_max_price = ( false !== $calculated_max_price ) ? $calculated_max_price : PHP_INT_MAX;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		/**
		 * Adjust if the store taxes are not displayed how they are stored.
		 * Kicks in when prices excluding tax are displayed including tax.
		 */
		if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
			$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
			$tax_rates = WC_Tax::get_rates( $tax_class );

			if ( $tax_rates ) {
				$current_min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_min_price, $tax_rates ) );
				$current_max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_max_price, $tax_rates ) );
			}
		}

		$args['join']   = $this->append_product_sorting_table_join( $args['join'] );
		$args['where'] .= $wpdb->prepare(
			' AND wc_product_meta_lookup.min_price >= %f AND wc_product_meta_lookup.max_price <= %f ',
			$current_min_price,
			$current_max_price
		);
		return $args;
	}

}
