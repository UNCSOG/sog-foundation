<?php

/**
 * WPV_Cache
 *
 * Caching class for Views.
 *
 * This class is useful on a series of scenarios:
 * 	- Parametric search with dependencies or counters, including post relationship filters.
 * 	- Invalidating stored cache in transients for Views output and meta keys
 *
 * @since 1.12
 */

class WPV_Cache {

	static $stored_cache									= array();
	static $stored_cache_extended_for_post_relationship     = array();
	static $stored_relationship_cache						= array();

	static $collected_parametric_search_filter_attributes	= array();

	function __construct() {

		/**
		 * =============================================
		 *  Cache invalidation
		 * =============================================
		 */

		// Invalidate the shortcodes GUI transient data
		add_action( 'save_post',					array( $this, 'delete_shortcodes_gui_transients_action' ), 10, 2 );
		add_action( 'delete_post',					array( $this, 'delete_shortcodes_gui_transients_action' ), 10 );
		add_action( 'wpv_action_wpv_save_item',		array( $this, 'delete_shortcodes_gui_transients_action' ) );
		// Custom action
		add_action( 'wpv_action_wpv_delete_transient_shortcodes_gui_views',		array( $this, 'delete_shortcodes_gui_views_transient_action' ) );
		add_action( 'wpv_action_wpv_delete_transient_shortcodes_gui_cts',		array( $this, 'delete_shortcodes_gui_cts_transient_action' ) );
	}

	/**
	 * Restart the stored cache.
	 *
	 * @since unknown
	 */
	static function restart_cache() {
		self::$stored_cache = array();
	}

	/**
	 * Process the filter_meta_html content, find the wpv-control and wpv-control-set shortcodes and extract their attributes.
	 * Transform that data into something that WPV_Cache can use.
	 *
	 * @param array $view_settings     The object settings
	 * @param array $override_settings Additional settings that will override the ones in $view_settings and needed to perform this action:
	 * 		'post_type' array The post types that the current object will be returning. Needed as WordPress Archives get this on-the-fly.
	 *
	 * @since 2.1.0
	 * @since 2.4.0 Add flags to cache post author data and post type data.
	 * @since 2.4.0 Abstract out the fake shortcodes definitions.
	 * @since m2m Add flag to cache post relationship data in the m2m format.
	 */
	static function get_parametric_search_data_to_cache( $view_settings = array(), $override_settings = array() ) {
		$parametric_search_data_to_cache = array(
			'cf'			=> array(),
			'tax'			=> array(),
			'post_author'	=> 'disabled',
			'post_type'		=> 'disabled'
		);
		if (
			! isset( $view_settings['filter_meta_html'] )
			|| strpos( $view_settings['filter_meta_html'], '[wpv-control' ) === false
		) {
			return $parametric_search_data_to_cache;
		}

		foreach ( $override_settings as $override_key => $override_value ) {
			$view_settings[ $override_key ] = $override_value;
		}

		global $shortcode_tags;
		self::$collected_parametric_search_filter_attributes = array();
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		WPV_Cache::fake_control_shortcodes_callback();

		do_shortcode( $view_settings['filter_meta_html'] );
		$shortcode_tags = $orig_shortcode_tags;

		if ( strpos( $view_settings['filter_meta_html'], '[wpv-control-post-author' ) !== false ) {
			$parametric_search_data_to_cache['post_author'] = 'enabled';
		}

		if ( strpos( $view_settings['filter_meta_html'], '[wpv-control-post-type' ) !== false ) {
			$parametric_search_data_to_cache['post_type'] = 'enabled';
		}

		foreach ( self::$collected_parametric_search_filter_attributes as $atts_set ) {
			if ( isset( $atts_set['ancestors'] ) ) {
				$types_condition = new Toolset_Condition_Plugin_Types_Active();
				if ( $types_condition->is_met() ) {
					if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
						$filters_manager = WPV_Filter_Manager::get_instance();
						$filter = $filters_manager->get_filter( Toolset_Element_Domain::POSTS, 'relationship' );
						$relationship_tree = $filter->get_relationship_tree( $atts_set['ancestors'] );
						$parametric_search_data_to_cache['relationship'] = $relationship_tree;
					} else {
						$filter_manager = WPV_Filter_Manager::get_instance();
						$post_relationship_filter = $filter_manager->get_filter( Toolset_Element_Domain::POSTS, 'relationship' );
						$returned_post_types = $post_relationship_filter->get_returned_post_types( $view_settings );
						$returned_post_type_parents = array();
						if ( empty( $returned_post_types ) ) {
							$returned_post_types = array( 'any' );
						}
						foreach ( $returned_post_types as $returned_post_type_slug ) {
							$parent_parents_array = wpcf_pr_get_belongs( $returned_post_type_slug );
							if ( $parent_parents_array != false && is_array( $parent_parents_array ) ) {
								$returned_post_type_parents = array_merge( $returned_post_type_parents, array_values( array_keys( $parent_parents_array ) ) );
							}
						}
						foreach ( $returned_post_type_parents as $parent_to_cache ) {
							$parametric_search_data_to_cache['cf'][] = '_wpcf_belongs_' . $parent_to_cache . '_id';
						}
					}
				}
			} else if ( isset( $atts_set['taxonomy'] ) ) {
				$parametric_search_data_to_cache['tax'][] = $atts_set['taxonomy'];
			} else if ( isset( $atts_set['auto_fill'] ) ) {
				$parametric_search_data_to_cache['cf'][] = _wpv_get_field_real_slug( $atts_set['auto_fill'] );
			} else if ( isset( $atts_set['field'] ) ) {
				$parametric_search_data_to_cache['cf'][] = _wpv_get_field_real_slug( $atts_set['field'] );
			}
		}
		self::$collected_parametric_search_filter_attributes = array();
		return $parametric_search_data_to_cache;
	}

	/**
	 * Register dummy callbacks for all the control shortcodes, to collect their attributes.
	 *
	 * This method will populate the self::collected_parametric_search_filter_attributes property
	 * with the right shortcode attributes sets.
	 *
	 * @since 2.4.0
	 */
	static function fake_control_shortcodes_callback() {

		add_shortcode( 'wpv-control-post-taxonomy',		array( 'WPV_Cache', 'collect_shortcode_attributes' ) );
		add_shortcode( 'wpv-control-postmeta',			array( 'WPV_Cache', 'collect_shortcode_attributes' ) );
		add_shortcode( 'wpv-control-post-relationship',	array( 'WPV_Cache', 'collect_shortcode_attributes' ) );
		add_shortcode( 'wpv-control',		array( 'WPV_Cache', 'collect_shortcode_attributes' ) );
		add_shortcode( 'wpv-control-set',	array( 'WPV_Cache', 'collect_shortcode_attributes' ) );

	}

	/**
	 * Dummy helper callback for collecting shortcode attributes.
	 *
	 * @param array  $atts
	 * @param string $content
	 *
	 * @since 2.1.0
	 */
	static function collect_shortcode_attributes( $atts, $content = null ) {
		self::$collected_parametric_search_filter_attributes[] = $atts;
		return;
	}

	/**
	 * Generate the potmeta cache for a set of posts and a seleted list of field keys.
	 *
	 * @param array $cache_post_meta The already existing postmeta cache
	 * @param array $id_posts        The lists of posts to generate the cache for
	 * @param array $fields_to_cache The list of fields to generate the cache for
	 *
	 * @return array
	 *
	 * @since 2.4.0
	 */
	static function generate_postmeta_cache( $cache_post_meta = array(), $id_posts = array(), $fields_to_cache = array() ) {
		// Sanitize $id_posts
		// It usually comes from a WP_Query, but still
		$id_posts = array_map( 'esc_attr', $id_posts );
		$id_posts = array_map( 'trim', $id_posts );
		// is_numeric does sanitization
		$id_posts = array_filter( $id_posts, 'is_numeric' );
		$id_posts = array_map( 'intval', $id_posts );

		if ( is_array( $cache_post_meta ) ) {
			$exclude_ids = array_keys( $cache_post_meta );
			$id_posts = array_diff( $id_posts, $exclude_ids );
		} else {
			$cache_post_meta = array();
		}

		$id_posts_postmeta_matched = array();
		$id_posts_postmeta_missed = array();

		if (
			! empty( $fields_to_cache )
			&& ! empty( $id_posts )
		) {
			global $wpdb;
			$id_list = implode( ',', $id_posts );
			$fields_to_cache_count = count( $fields_to_cache );
			$fields_to_cache_placeholders = array_fill( 0, $fields_to_cache_count, '%s' );
			$meta_list = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
					WHERE post_id IN ({$id_list})
					AND meta_key IN (" . implode( ",", $fields_to_cache_placeholders ) . ")
					ORDER BY post_id ASC",
					$fields_to_cache
				),
				ARRAY_A
			);
			if ( ! empty( $meta_list ) ) {
				foreach ( $meta_list as $metarow ) {
					$mpid = intval( $metarow['post_id'] );
					$mkey = $metarow['meta_key'];
					$mval = $metarow['meta_value'];
					if ( ! in_array( $mpid, $id_posts_postmeta_matched ) ) {
						$id_posts_postmeta_matched[] = $mpid;
					}

					if (
						! isset( $cache_post_meta[ $mpid ] )
						|| ! is_array( $cache_post_meta[ $mpid ] )
					) {
						$cache_post_meta[ $mpid ] = array();
					}

					if (
						! isset( $cache_post_meta[ $mpid ][ $mkey ] )
						|| ! is_array( $cache_post_meta[ $mpid ][ $mkey ] )
					) {
						$cache_post_meta[ $mpid ][ $mkey ] = array();
					}

					$cache_post_meta[ $mpid ][ $mkey ][] = $mval;
				}
			}
			// Fill the gaps
			$id_posts_postmeta_missed = array_diff( $id_posts, $id_posts_postmeta_matched );
			foreach ( $id_posts_postmeta_missed as $id_needed ) {
				if ( ! isset( $cache_post_meta[ $id_needed ] ) ) {
					$cache_post_meta[ $id_needed ] = array();
				}
			}
		}
		return $cache_post_meta;
	}

	/**
	 * Generate the taxonomy cache for a set of posts and a seleted list of taxonomies.
	 *
	 * @param array $cache_post_taxes The already existing taxonomy cache
	 * @param array $id_posts         The lists of posts to generate the cache for
	 * @param array $tax_to_cache     The list of taxonomies to generate the cache for
	 *
	 * @return array
	 *
	 * @note The settings might be polluted by non-existing taxonomies, so we need to intersect it.
	 *
	 * @since 2.4.0
	 */
	static function generate_taxonomy_cache( $cache_post_taxes = array(), $id_posts = array(), $tax_to_cache = array() ) {
		// Sanitize $id_posts
		// It usually comes from a WP_Query, but still
		$id_posts = array_map( 'esc_attr', $id_posts );
		$id_posts = array_map( 'trim', $id_posts );
		// is_numeric does sanitization
		$id_posts = array_filter( $id_posts, 'is_numeric' );
		$id_posts = array_map( 'intval', $id_posts );

		$current_taxonomies = get_taxonomies( '', 'names' );
		$tax_to_cache = array_intersect( $tax_to_cache, $current_taxonomies );
		$tax_to_cache = array_values( $tax_to_cache );
		if (
			! empty( $tax_to_cache )
			&& ! empty( $id_posts )
		) {
			$terms = wp_get_object_terms( $id_posts, $tax_to_cache, array( 'fields' => 'all_with_object_id' ) );
			if ( is_wp_error( $terms ) ) {
				$terms = array();
			}
			$object_terms = array();
			foreach ( (array) $terms as $term ) {
				$object_terms[ $term->object_id ][ $term->taxonomy ][ $term->term_id ] = $term;
			}
			foreach ( $id_posts as $id_needed ) {
				foreach ( $tax_to_cache as $taxonomy ) {
					if ( ! isset( $object_terms[ $id_needed ][ $taxonomy ] ) ) {
						if ( ! isset( $object_terms[ $id_needed ] ) ) {
							$object_terms[ $id_needed ] = array();
						}
						$object_terms[ $id_needed ][ $taxonomy ] = array();
					}
				}
			}
			foreach ( $object_terms as $post_id => $value ) {
				foreach ( $value as $taxonomy => $terms ) {
					if ( ! isset( $cache_post_taxes[ $taxonomy . '_relationships' ] ) ) {
						$cache_post_taxes[ $taxonomy . '_relationships' ] = array();
					}
					if ( ! isset( $cache_post_taxes[ $taxonomy . '_relationships' ][ $post_id ] ) ) {
						$cache_post_taxes[ $taxonomy . '_relationships' ][ $post_id ] = $terms;
					}
				}
			}
		}
		return $cache_post_taxes;
	}

	/**
	 * Generate the post data cache for a set of posts and a seleted list of post columns.
	 *
	 * @param array $cache_post_data    The already existing post data cache, each key matches a post column
	 * @param array $id_posts           The lists of posts to generate the cache for
	 * @param array $post_data_to_cache The list of post data columns to generate the cache for
	 *
	 * @return array
	 *
	 * @since 2.4.0
	 */
	static function generate_post_data_cache( $cache_post_data = array(), $id_posts = array(), $post_data_to_cache = array() ) {
		// Sanitize $id_posts
		// It usually comes from a WP_Query, but still
		$id_posts = array_map( 'esc_attr', $id_posts );
		$id_posts = array_map( 'trim', $id_posts );
		// is_numeric does sanitization
		$id_posts = array_filter( $id_posts, 'is_numeric' );
		$id_posts = array_map( 'intval', $id_posts );

		$columns_to_cache = array();
		$id_posts_for_column = array();
		$fields_to_query = array( 'ID' );

		if ( count( $post_data_to_cache ) > 0 ) {
			foreach ( $post_data_to_cache as $post_data_column => $post_data_status ) {
				$id_posts_for_column[ $post_data_column ] = $id_posts;
				if ( 'enabled' == $post_data_status ) {
					$columns_to_cache[] = $post_data_column;
					$fields_to_query[] = $post_data_column;
					if (
						isset( $cache_post_data[ $post_data_column ] )
						&& is_array( $cache_post_data[ $post_data_column ] )
						&& ! empty( $cache_post_data[ $post_data_column ] )
					) {
						$exclude_ids_for_column = call_user_func_array( 'array_merge', $cache_post_data[ $post_data_column ] );
						$id_posts_for_column[ $post_data_column ] = array_diff( $id_posts, $exclude_ids_for_column );
					} else {
						$cache_post_data[ $post_data_column ] = array();
					}
				}
			}
			$id_posts = call_user_func_array( 'array_merge', array_values( $id_posts_for_column ) );
		}
		$id_posts = array_unique( $id_posts );

		if (
			! empty( $id_posts )
			&& count( $columns_to_cache ) > 0
		) {
			global $wpdb;
			$id_list = implode( ',', $id_posts );
			$post_data_list = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT %s FROM {$wpdb->posts}
					WHERE ID IN ({$id_list})
					AND 1 = %d
					ORDER BY ID ASC",
					implode( ',', $fields_to_query ),
					1
				),
				ARRAY_A
			);
			if ( ! empty( $post_data_list ) ) {
				foreach ( $post_data_list as $post_data_list_row ) {
					foreach ( $columns_to_cache as $column_key ) {
						if ( isset( $cache_post_data[ $column_key ][ $post_data_list_row[ $column_key ] ] ) ) {
							if ( ! in_array( $post_data_list_row['ID'], $cache_post_data[ $column_key ][ $post_data_list_row[ $column_key ] ] ) ) {
								$cache_post_data[ $column_key ][ $post_data_list_row[ $column_key ] ][] = $post_data_list_row['ID'];
							}
						} else {
							$cache_post_data[ $column_key ][ $post_data_list_row[ $column_key ] ] = array( $post_data_list_row['ID'] );
						}
					}
				}
			}
		}
		return $cache_post_data;
	}

	/**
	 * Generate the needed cache for the post relationship filter.
	 *
	 * The generated cache follows this structure:
	 * [relationship_slug] => array(
	 *     [ancestor_id] => array(
	 *         post_id_1,
	 *         post_id_2
	 *         ...
	 *     )
	 * )
	 *
	 * @param array $cache_post_relationship
	 * @param array $id_posts Post IDs to generate the cache for
	 * @param array $relationship_to_cache
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	static function generate_post_relationship_cache( $cache_post_relationship = array(), $id_posts = array(), $relationship_to_cache = array() ) {
		// Sanitize $id_posts
		// It usually comes from a WP_Query, but still
		$id_posts = array_map( 'esc_attr', $id_posts );
		$id_posts = array_map( 'trim', $id_posts );
		// is_numeric does sanitization
		$id_posts = array_filter( $id_posts, 'is_numeric' );
		$id_posts = array_map( 'intval', $id_posts );

		if (
			! apply_filters( 'toolset_is_m2m_enabled', false )
			|| empty( $relationship_to_cache )
			|| empty( $id_posts )
		) {
			return $cache_post_relationship;
		}

		do_action( 'toolset_do_m2m_full_init' );

		$direct_relationship = end( $relationship_to_cache );

		try {
			$association_current_role = Toolset_Relationship_Role::role_from_name( $direct_relationship['role_target'] );
		} catch ( InvalidArgumentException $e ) {
			return $cache_post_relationship;
		}

		try {
			$association_ancestor_role = Toolset_Relationship_Role::role_from_name( $direct_relationship['role'] );
		} catch ( InvalidArgumentException $e ) {
			return $cache_post_relationship;
		}

		$association_query = new Toolset_Association_Query_V2();
		$association_query_limit = PHP_INT_MAX;

		$filter_manager = WPV_Filter_Manager::get_instance();
		$relationship_filter_controler = $filter_manager->get_filter( Toolset_Element_Domain::POSTS, 'relationship' );

		if ( ! empty( $direct_relationship['relationship'] ) ) {
			$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
			$relationship_definition = $relationship_repository->get_definition( $direct_relationship['relationship'] );
			if ( null === $relationship_definition ) {
				return $cache_post_relationship;
			}

			$association_query->add( $association_query->relationship_slug( $direct_relationship['relationship'] ) );

			$association_query_limit = $relationship_filter_controler->get_association_adjusted_query_limit(
				PHP_INT_MAX,
				$relationship_definition->get_cardinality(),
				$direct_relationship['role'],
				count( $id_posts )
			);
		}

		// See \OTGS\Toolset\Common\Relationships\API\ElementStatusCondition
		$association_query->add( $association_query->element_status( 'any_but_autodraft' ) );

		$association_query->limit( $association_query_limit )
			->add( $association_query->multiple_elements(
				$id_posts,
				Toolset_Element_Domain::POSTS,
				$association_current_role
			) );

		$associations = $association_query->get_results();

		if ( empty( $associations ) ) {
			return $cache_post_relationship;
		}

		foreach ( $associations as $association ) {
			$current_id = $association->get_element_id( $association_current_role );
			$ancestor_id = $association->get_element_id( $association_ancestor_role );

			if (
				0 === $current_id
				|| 0 === $ancestor_id
			) {
				// Missing or corrupted data: missing pieces in the association.
				continue;
			}

			$filtered_by_status = $relationship_filter_controler->filter_association_query_results_by_status( array( $current_id, $ancestor_id ) );

			if ( 2 !== count( $filtered_by_status ) ) {
				continue;
			}

			$cache_post_relationship[ $direct_relationship['type'] ] =
				isset( $cache_post_relationship[ $direct_relationship['type'] ] )
				? $cache_post_relationship[ $direct_relationship['type'] ]
				: array();

			$cache_post_relationship[ $direct_relationship['type'] ][ $ancestor_id ] =
				isset( $cache_post_relationship[ $direct_relationship['type'] ][ $ancestor_id ] )
				? $cache_post_relationship[ $direct_relationship['type'] ][ $ancestor_id ]
				: array();

			$cache_post_relationship[ $direct_relationship['type'] ][ $ancestor_id ][] = $current_id;
			$cache_post_relationship[ $direct_relationship['type'] ][ $ancestor_id ] = array_unique( $cache_post_relationship[ $direct_relationship['type'] ][ $ancestor_id ] );
		}

		array_pop( $relationship_to_cache );
		if ( null === $relationship_to_cache ) {
			return $cache_post_relationship;
		}

		foreach( $cache_post_relationship as $associated_relationship => $associated_data ) {
			$associated_data_ids = array_keys( $associated_data );
			$cache_post_relationship = self::generate_post_relationship_cache( $cache_post_relationship, $associated_data_ids, $relationship_to_cache );
		}

		return $cache_post_relationship;
	}

	/**
	 * Mimic the caching construction of WordPress so we can use it for counting posts.
	 * update_postmeta_cache should get cached data, so we avoind further queries for postmeta.
	 * We still need to generate the cache for the given taxonomies and post data.
	 *
	 * @param array $id_posts List of post IDs
	 * @param array $to_cache List of data to pseudo-cache
	 *    'tax'         array  List of taxonomy names to cache
	 *    'post_author' string Whether to cache the author of posts
	 *    'post_type'   string Whether to cache the type of posts
	 *
	 * @return array Cached data compatible with the native $wp_object_cache->cache format
	 *
	 * @uses update_postmeta_cache
	 *
	 * @since 1.12.0
	 * @since 2.4.0 Add flags to cache post author data and post type data.
	 * @since 2.4.0 Abstract out the taxonomy and post data cache generator.
	 */
	static function generate_native_cache( $id_posts = array(), $to_cache = array() ) {
		$tax_to_cache		= ( isset( $to_cache['tax'] ) ) ? $to_cache['tax'] : array();
		$post_data_to_cache	= array(
			'post_author'	=> ( isset( $to_cache['post_author'] ) ) ? $to_cache['post_author'] : 'disabled',
			'post_type'		=> ( isset( $to_cache['post_type'] ) ) ? $to_cache['post_type'] : 'disabled'
		);
		$relationship_to_cache = ( isset( $to_cache['relationship'] ) ) ? $to_cache['relationship'] : array();

		// Sanitize $id_posts
		// It usually comes from a WP_Query, but still
		$id_posts = array_map( 'esc_attr', $id_posts );
		$id_posts = array_map( 'trim', $id_posts );
		// is_numeric does sanitization
		$id_posts = array_filter( $id_posts, 'is_numeric' );
		$id_posts = array_map( 'intval', $id_posts );

		// First, the postmeta cache
		$cache_post_meta = update_postmeta_cache( $id_posts );

		// Then, the taxonomies
		$cache_post_taxes = array();
		$cache_post_taxes = WPV_Cache::generate_taxonomy_cache( $cache_post_taxes, $id_posts, $tax_to_cache );

		// Finally, the post data
		$cache_post_data = array(
			'post_author'	=> array(),
			'post_type'		=> array()
		);
		$cache_post_data = WPV_Cache::generate_post_data_cache( $cache_post_data, $id_posts, $post_data_to_cache );

		$cache_combined = array();
		$cache_combined['post_meta'] = $cache_post_meta;
		foreach ( $cache_post_taxes as $tax_key => $tax_cached_values ) {
			$cache_combined[ $tax_key ] = $tax_cached_values;
		}
		$cache_combined['post_author'] = $cache_post_data['post_author'];
		$cache_combined['post_type'] = $cache_post_data['post_type'];

		$cache_post_relationship = array();
		$cache_combined['post_relationship'] = WPV_Cache::generate_post_relationship_cache( $cache_post_relationship, $id_posts, $relationship_to_cache );

		self::$stored_cache = $cache_combined;

		return $cache_combined;
	}



	/**
	 * Mimic the caching construction of WordPress so we can use it for counting posts.
	 * Caches data for the passed custom fields, taxonomies and post data, without adding it to self::$stored_cache
	 *
	 * @param array $id_posts List of post IDs
	 * @param array $to_cache List of data to pseudo-cache
	 *    'tax'         array  Lost of taxonomy names to cache
	 *    'cf'          array  List of of field meta_key's to cache
	 *    'post_author' string Whether to cache the author of posts
	 *    'post_type'   string Whether to cache the type of posts
	 *
	 * @return array Cached data compatible with the native $wp_object_cache->cache format
	 *
	 * @since 1.12.0
	 * @since 2.4.0 Add flags to cache post author data and post type data.
	 * @since 2.4.0 Abstract out the taxonomy and post data cache generator.
	 */
	static function generate_auxiliar_cache( $id_posts = array(), $to_cache = array() ) {
		$cache_combined		= self::$stored_cache;
		$fields_to_cache	= ( isset( $to_cache['cf'] ) ) ? $to_cache['cf'] : array();
		$tax_to_cache		= ( isset( $to_cache['tax'] ) ) ? $to_cache['tax'] : array();
		$post_data_to_cache	= array(
			'post_author'	=> ( isset( $to_cache['post_author'] ) ) ? $to_cache['post_author'] : 'disabled',
			'post_type'		=> ( isset( $to_cache['post_type'] ) ) ? $to_cache['post_type'] : 'disabled'
		);
		$relationship_to_cache = ( isset( $to_cache['relationship'] ) ) ? $to_cache['relationship'] : array();

		// Sanitize $id_posts
		// It usually comes from a WP_Query, but still
		$id_posts = array_map( 'esc_attr', $id_posts );
		$id_posts = array_map( 'trim', $id_posts );
		// is_numeric does sanitization
		$id_posts = array_filter( $id_posts, 'is_numeric' );
		$id_posts = array_map( 'intval', $id_posts );

		// First, the postmeta cache
		$cache_post_meta = isset( $cache_combined['post_meta'] ) ? $cache_combined['post_meta'] : array();
		$cache_combined['post_meta'] = WPV_Cache::generate_postmeta_cache( $cache_post_meta, $id_posts, $fields_to_cache );

		// Then, the taxonomies
		$cache_combined = WPV_Cache::generate_taxonomy_cache( $cache_combined, $id_posts, $tax_to_cache );

		// Finally, the post data
		$cache_post_data = array(
			'post_author'	=> ( isset( $cache_combined['post_author'] ) ) ? $cache_combined['post_author'] : array(),
			'post_type'		=> ( isset( $cache_combined['post_type'] ) ) ? $cache_combined['post_type'] : array(),
		);
		$cache_post_data = WPV_Cache::generate_post_data_cache( $cache_post_data, $id_posts, $post_data_to_cache );

		$cache_combined['post_author'] = $cache_post_data['post_author'];
		$cache_combined['post_type'] = $cache_post_data['post_type'];

		$cache_post_relationship = isset( $cache_combined['post_relationship'] ) ? $cache_combined['post_relationship'] : array();
		$cache_combined['post_relationship'] = WPV_Cache::generate_post_relationship_cache( $cache_post_relationship, $id_posts, $relationship_to_cache );

		return $cache_combined;
	}

	/**
	 * Mimics the caching construction of WordPress so we can use it for counting posts.
	 * Caches data for the passed custom fields, taxonomies and post data, and adds it to self::$stored_cache
	 *
	 * @param array $id_posts List of post IDs
	 * @param array $to_cache List of data to pseudo-cache
	 *    'tax'         array  List of taxonomy names to cache
	 *    'cf'          array  List of field meta_key's to cache
	 *    'post_author' string Whether to cache the author of posts
	 *    'post_type'   string Whether to cache the type of posts
	 *    'relationship' string Whether to generate the relationship cache
	 *
	 * @uses self::generate_auxiliar_cache
	 *
	 * @return (array) cached data compatible with the native $wp_object_cache->cache format
	 *
	 * @since 1.12
	 */
	static function generate_cache( $id_posts = array(), $to_cache = array() ) {
		$cache_combined = self::generate_auxiliar_cache( $id_posts, $to_cache );
		self::$stored_cache = $cache_combined;
		return $cache_combined;
	}

	/**
	 * Mimic the caching construction of WordPress so we can use it for counting posts.
	 * Caches data for the passed custom fields and taxonomies, and adds it to self::$stored_cache_extended_for_post_relationship
	 * Used when rendering post relationship filters with dependency or counters,
	 * as we need to generate a specific query that avoinds the filter by the current post type in the relationship ree, if it exists
	 *
	 * @param array $id_posts List of post IDs
	 * @param array $to_cache List of data to pseudo-cache
	 *    'tax'         array  List of taxonomy names to cache
	 *    'cf'          array  List of field meta_key's to cache
	 *    'post_author' string Whether to cache the author of posts
	 *    'post_type'   string Whether to cache the type of posts
	 *    'relationship' string Whether to generate the relationship cache
	 *
	 * @uses self::generate_auxiliar_cache
	 *
	 * @return array Cached data compatible with the native $wp_object_cache->cache format
	 *
	 * @since 1.12
	 * @since m2m Keep for bakwards compatibility on sites that do not switch to M2M
	 */
	static function generate_cache_extended_for_post_relationship( $id_posts = array(), $to_cache = array() ) {
		$cache_combined = self::generate_auxiliar_cache( $id_posts, $to_cache );
		self::$stored_cache_extended_for_post_relationship = $cache_combined;
		return $cache_combined;
	}

	/**
	 * Generate data for counters and disable/hide elements in a post relationship parametric filter
	 *
	 * @param $tree_array array List of ancestors, in a top-to-bottom order
	 * @param $count bool Whether the count should return the number of matches or just a true/false statement
	 *
	 * @return array
	 *
	 * @since 1.12
	 * @note This is being generated per ancestor shortcode in a relationship query:
	 *       We should be able to cache it per relationship shortcode and do it once.
	 *       At least when no ancestor filter has been submitted yet...
	 */
	static function generate_post_relationship_tree_cache( $tree_array, $count = true ) {
		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return self::generate_post_relationship_tree_cache_from_m2m( $tree_array, $count );
		} else {
			return self::generate_post_relationship_tree_cache_from_postmeta( $tree_array, $count );
		}
	}

	private static function generate_post_relationship_tree_cache_from_postmeta( $tree_array, $count ) {
		$tree_real = array_reverse( $tree_array );
		$tree_remove = array_shift( $tree_real );
		$tree_ground = end( $tree_array );
		$tree_roof = reset( $tree_array );
		$current_post_ids = array();
		$counters = array();
		global $wpdb;
		$cache_combined = self::$stored_cache_extended_for_post_relationship;
		if (
			isset( $cache_combined['post_meta'] )
			&& is_array( $cache_combined['post_meta'] )
		) {
			$cached_postmeta = $cache_combined['post_meta'];
			$field = '_wpcf_belongs_' . $tree_ground . '_id';
			foreach ( $cached_postmeta as $key => $value ) {
				if ( isset( $value[ $field ] ) ) {
					$cached_postmeta[ $key ] = $value[ $field ];
				} else {
					unset( $cached_postmeta[ $key ] );
				}
			}
			$current_post_ids = array();
			if ( count( $cached_postmeta ) > 0 ) {
				$current_post_ids = call_user_func_array('array_merge', $cached_postmeta );
			}
			foreach ( $current_post_ids as $cpi ) {
				$meta_criteria_to_filter = array( '_wpcf_belongs_' . $tree_ground . '_id' => array( $cpi ) );
				$data = array();
				$data['list'] = $cache_combined['post_meta'];
				$data['args'] = $meta_criteria_to_filter;
				$data['kind'] = '';
				$data['comparator'] = 'equal';
				$data['count_matches'] = $count;
				$counters[ $cpi ] = array(
					'type'	=> $tree_ground,
					'count'	=> wpv_list_filter_checker( $data )
				);
			}
		}
		foreach ( $tree_real as $tree_branch ) {
			$current_post_ids = array_map( 'esc_attr', $current_post_ids );
			$current_post_ids = array_map( 'trim', $current_post_ids );
			$current_post_ids = array_filter( $current_post_ids, 'is_numeric' );
			$current_post_ids = array_map( 'intval', $current_post_ids );
			if ( count( $current_post_ids ) > 0 ) {
				$future_post_ids = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_id, meta_value
						FROM {$wpdb->postmeta}
						WHERE meta_key = %s
						AND post_id IN ('" . implode( "','", $current_post_ids ) . "')",
						'_wpcf_belongs_' . $tree_branch . '_id'
					),
					ARRAY_A
				);
				foreach ( $future_post_ids as $fpid ) {
					$add = isset( $counters[ $fpid['post_id'] ] ) ? $counters[ $fpid['post_id'] ]['count'] : 1;
					if ( ! isset( $counters[ $fpid['meta_value'] ] ) ) {
						$counters[ $fpid['meta_value'] ] = array(
							'type'	=> $tree_branch,
							'count'	=> 0
						);
					}
					$counters[ $fpid['meta_value'] ]['count'] = isset( $counters[ $fpid['meta_value'] ] ) ? ( $counters[ $fpid['meta_value'] ]['count'] + $add ) : $add;
				}
				$current_post_ids = wp_list_pluck( $future_post_ids, 'meta_value' );
			} else {
				$current_post_ids = array();
			}
		}
		self::$stored_relationship_cache = $counters;
		return $counters;
	}

	private static function generate_post_relationship_tree_cache_from_m2m( $tree_array, $count ) {
		$counters = array();
		global $wpdb;
		$cache_combined = self::$stored_cache_extended_for_post_relationship;
		if (
			! isset( $cache_combined['post_relationship'] )
			|| ! is_array( $cache_combined['post_relationship'] )
		) {
			return $counters;
		}

		$cached_post_relationship = $cache_combined['post_relationship'];
		if ( 0 === count( $cached_post_relationship ) ) {
			// The cache combined is relationship slug key-based,
			// following the reverse of the tree array
			return $counters;
		}
		$current_relationship = reset( $cached_post_relationship );
		$current_relationship_type = key( $cached_post_relationship );
		foreach ( $cached_post_relationship as $post_type_slug => $relationship_associations ) {
			foreach ( $relationship_associations as $ancestor_id => $ancestor_children ) {
				$counters[ $ancestor_id ] = array(
					'type'	=> $post_type_slug,
					'count'	=> count( $ancestor_children )
				);
				if ( $current_relationship_type != $post_type_slug ) {
					$corrected_count = 0;
					foreach ( $ancestor_children as $ancestor_child_maybe_parent ) {
						if ( isset( $counters[ $ancestor_child_maybe_parent ] ) ) {
							$corrected_count += $counters[ $ancestor_child_maybe_parent ]['count'];
						}
					}
					$counters[ $ancestor_id ]['count'] = $corrected_count;
				}
			}
		}
		self::$stored_relationship_cache = $counters;
		return $counters;
	}

	/**
	* Merge the native and auxiliar caches for taxonomies
	*
	* Note that we can not use array_merge as it does not preserve indexes
	* We would get duplicates when the same page contains more than a View because the generate cache is run twice
	*
	* @param $tax_cache_combined	The existing cache
	* @param $tax_cached_values		The newly cached values
	*
	* @since 2.0
	*/

	static function merge_taxonomy_cache( $tax_cache_combined, $tax_cached_values ) {
		foreach ( $tax_cached_values as $tax_post_id => $tax_post_terms ) {
			if ( ! isset( $tax_cache_combined[ $tax_post_id ] ) ) {
				// If we already have cached data for this taxonomy and post, we should do nothing
				// Otherwise we add it
				$tax_cache_combined[ $tax_post_id ] = $tax_post_terms;
			}
		}
		return $tax_cache_combined;
	}

	/**
	 * Invalidate wpv_transient_published_*** cache when:
	 * 	creating, updating or deleting a View
	 * 	creating, updating or deleting a Content Template
	 *
	 * @todo We might want to use a flag here, not sure
	 *
	 * @since 2.0.0
	 * @since 2.4.0 Clear the cache of the wpv_transient_pub_cts_for_cred_post and wpv_transient_pub_cts_for_cred_user transients
	 *     when creating, editing or removing a Content Template
	 */

	function delete_shortcodes_gui_transients_action( $post_id, $post = null  ) {
		if ( is_null( $post ) ) {
			$post = get_post( $post_id );
			if ( is_null( $post ) ) {
				return;
			}
		}
		$slugs = array( 'view', 'view-template' );
		if ( ! in_array( $post->post_type, $slugs ) ) {
			return;
		}
		switch ( $post->post_type ) {
			case 'view':
				delete_transient( 'wpv_transient_published_views' );
				break;
			case 'view-template':
				delete_transient( 'wpv_transient_published_cts' );
				delete_transient( 'wpv_transient_pub_cts_for_cred_post' );
				delete_transient( 'wpv_transient_pub_cts_for_cred_user' );
				break;

		}
	}

	/**
	* Invalidate wpv_transient_published_views cache manually
	*
	* @since 2.1
	*/

	function delete_shortcodes_gui_views_transient_action() {
		delete_transient( 'wpv_transient_published_views' );
	}

	/**
	* Invalidate wpv_transient_published_cts cache manually
	*
	* @since 2.1
	*/

	function delete_shortcodes_gui_cts_transient_action() {
		delete_transient( 'wpv_transient_published_cts' );
	}

}

$wpv_cache_object = new WPV_Cache();
