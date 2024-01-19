<?php

/**
* Search frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_Search_Frontend_Filter::on_load();

/**
* WPV_Author_Filter
*
* Views Search Filter Frontend Class
*
* @since 2.1
*/

class WPV_Search_Frontend_Filter {

	static $search_content_where = '';

	static function on_load() {
		// Apply frontend filter by post search
        add_filter( 'wpv_filter_query',										    array( 'WPV_Search_Frontend_Filter', 'filter_post_search' ), 10, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				    array( 'WPV_Search_Frontend_Filter', 'archive_filter_post_search' ), 40, 3 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts',			    array( 'WPV_Search_Frontend_Filter', 'url_parameters_for_posts' ), 10, 2 );
		// Apply frontend filter by taxonomy search
		add_filter( 'wpv_filter_taxonomy_query',							    array( 'WPV_Search_Frontend_Filter', 'filter_taxonomy_search' ), 10, 3 );
		add_filter( 'wpv_filter_register_url_parameters_for_taxonomy',		    array( 'WPV_Search_Frontend_Filter', 'url_parameters_for_taxonomy' ), 10, 2 );
		// Auxiliar methods for gathering data
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts',	    array( 'WPV_Search_Frontend_Filter', 'post_shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_shortcode_attributes_for_taxonomy',	array( 'WPV_Search_Frontend_Filter', 'tax_shortcode_attributes' ), 10, 2 );
    }

	/**
	* filter_post_search
	*
	* Apply the query filter by post search to Views.
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_post_search and moved to a static method
	*/

	static function filter_post_search( $query, $view_settings, $view_id ) {
		if (
			isset( $view_settings['post_search_value'] )
			&& $view_settings['post_search_value'] != ''
			&& isset( $view_settings['search_mode'] )
			&& $view_settings['search_mode'][0] == 'specific'
		) {
			$query['s'] = $view_settings['post_search_value'];
		}
		if (
			isset( $view_settings['post_search_shortcode'] )
			&& $view_settings['post_search_shortcode'] != ''
			&& isset( $view_settings['search_mode'] )
			&& $view_settings['search_mode'][0] == 'shortcode'
		) {
			$post_search_shortcode = $view_settings['post_search_shortcode'];
			$view_attrs = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', false );
			if (
				isset( $view_attrs[$post_search_shortcode] )
				&& '' != $view_attrs[$post_search_shortcode]
			) {
				$query['s'] = $view_attrs[$post_search_shortcode];
			}
		}
		if (
			isset( $view_settings['search_mode'] )
			&& isset( $_GET['wpv_post_search'] )
		) {
			$search_term = rawurldecode( sanitize_text_field( $_GET['wpv_post_search'] ) );
			if ( ! empty( $search_term ) ) {
				$query['s'] = $search_term;
			}
		}
		if (
			isset( $view_settings['post_search_content'] )
			&& 'just_title' == $view_settings['post_search_content']
			&& isset( $query['s'] )
		) {
			add_filter( 'posts_search', array( 'WPV_Search_Frontend_Filter', 'search_by_title_only' ), 500, 2 );
		}

		return $query;
	}

	/**
	* archive_filter_post_search
	*
	* Apply the query filter by post search to WPAs.
	*
	* @since 2.1		Renamed from wpv_filter_post_search and moved to a static method
	*/

	static function archive_filter_post_search( $query, $archive_settings, $archive_id ) {
		$search_term = '';
		if (
			isset( $archive_settings['post_search_value'] )
			&& $archive_settings['post_search_value'] != ''
			&& isset( $archive_settings['search_mode'] )
			&& $archive_settings['search_mode'][0] == 'specific'
		) {
			$search_term = $archive_settings['post_search_value'];
		}
		if (
			isset( $archive_settings['search_mode'] )
			&& isset( $_GET['wpv_post_search'] )
		) {
			$search_term = rawurldecode( sanitize_text_field( $_GET['wpv_post_search'] ) );

		}
		if ( ! empty( $search_term ) ) {
			$query->set( 's', $search_term );
		}
		if (
			isset( $archive_settings['post_search_content'] )
			&& 'just_title' == $archive_settings['post_search_content']
			&& ! empty( $search_term )
		) {
			add_filter( 'posts_search', array( 'WPV_Search_Frontend_Filter', 'search_by_title_only' ), 500, 2 );
		}
	}

	/**
	 * Force a faked query filter when we know there is a frontend filter.
	 * Since the new Views editor, we should not generate query filters manually,
	 * but on the fly instead, when applying the filter to the query.
	 *
	 * @param array $view_settings
	 * @return array
	 * @since 3.0
	 */
	public static function maybe_force_fake_query_filter( $view_settings ) {
		$filter_html = toolset_getarr( $view_settings, 'filter_meta_html', '' );
		if ( strpos( $filter_html, '[wpv-filter-search-box' ) === false ) {
			return $view_settings;
		}

		if (
			'manual' !== toolset_getarr( $view_settings, 'search_mode' )
			|| false === toolset_getarr( $view_settings, 'post_search_content', false )
		) {
			// Force a query filter by manual search:
			// there is a search shortcode and there might be a value to search by!
			global $shortcode_tags;
			$orig_shortcode_tags = $shortcode_tags;
			remove_all_shortcodes();
			add_shortcode( 'wpv-filter-search-box', array( 'WPV_Search_Frontend_Filter', 'fake_search_box_shortcode' ) );
			do_shortcode( $filter_html );
			$shortcode_tags = $orig_shortcode_tags;
			// Force a query filter by manual search:
			// there is a search shortcode and there might be a value to search by!
			// Note that if the content to search already exists, we keep it.
			$view_settings['search_mode'] = 'manual';
			$view_settings['post_search_content'] = toolset_getarr( $view_settings, 'post_search_content', self::$search_content_where );
		}

		self::$search_content_where = '';

		$filters = toolset_getarr( $view_settings, \WPV_Filter_Manager::SETTING_KEY, array() );
		$filters[ 'post_search' ] = array(
			\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_SEARCH_FILTER,
		);
		$view_settings[ \WPV_Filter_Manager::SETTING_KEY ] = $filters;

		return $view_settings;
	}

	/**
	 * Fake the wpv-filter-search-box shortcode to get its attributs and operate over them.
	 *
	 * @param array $atts
	 * @param string|null $content
	 * @since 3.0
	 */
	public static function fake_search_box_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'value_where' => 'full_content',
		), $atts );
		self::$search_content_where = toolset_getarr( $atts, 'value_where', 'full_content', array( 'full_content', 'just_title', 'content_extended' ) );
	}


	/**
	* search_by_title_only
	*
	* Auxiliar method to force searching just in post titles
	*
	* @since unknown
	* @since 2.1		Renamed and moved to a static method
    * @since 2.3.0      Due to a change introduced in PHP 7.1, the second argument which was a reference, was now changed to be a value to prevent
    *                   a PHP Warning (http://php.net/manual/en/migration71.incompatible.php#migration71.incompatible.call_user_func-with-ref-args)
	*/


	static function search_by_title_only( $search, $wp_query ) {
		global $wpdb;
		if ( empty( $search ) )
			return $search; // skip processing - no search term in query
		$q = $wp_query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';
		$search = '';
		$searchand = "";
		foreach ( (array) $q['search_terms'] as $term ) {
			$term = $n . wpv_esc_like( $term ) . $n;
			$search .= $wpdb->prepare( $searchand . "( $wpdb->posts.post_title LIKE %s )", $term );
			$searchand = " AND ";
		}
		if ( ! empty( $search ) ) {
			$search = " AND ( {$search} ) ";
			if ( ! is_user_logged_in() )
				$search .= " AND ( $wpdb->posts.post_password = '' ) ";
		}
		return $search;
	}

	/**
	* filter_taxonomy_search
	*
	* Apply the query filter by taxonomy search to Views.
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_taxonomy_search and moved to a static method
	*/

	static function filter_taxonomy_search( $tax_query_settings, $view_settings, $view_id ) {
		if ( isset( $view_settings['taxonomy_search_mode'] ) ) {
			if ( $view_settings['taxonomy_search_mode'][0] == 'specific' ) {
				if (
					isset( $view_settings['taxonomy_search_value'] )
					&& $view_settings['taxonomy_search_value'] != ''
				) {
					$tax_query_settings['search'] = sanitize_text_field( $view_settings['taxonomy_search_value'] );
				}
			} else if ( $view_settings['taxonomy_search_mode'][0] == 'shortcode' ) {
				if (
					isset( $view_settings['taxonomy_search_shortcode'] )
					&& $view_settings['taxonomy_search_shortcode'] != ''
				) {
					$taxonomy_search_shortcode = $view_settings['taxonomy_search_shortcode'];
					$view_attrs = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', false );
					if (
						isset( $view_attrs[$taxonomy_search_shortcode] )
						&& '' != $view_attrs[$taxonomy_search_shortcode]
					) {
						$tax_query_settings['search'] = sanitize_text_field( $view_attrs[$taxonomy_search_shortcode] );
					}

				}
			}
			else if ( isset( $_GET['wpv_taxonomy_search'] ) ) {
				$search_term = rawurldecode( sanitize_text_field( $_GET['wpv_taxonomy_search'] ) );
				if ( ! empty( $search_term ) ) {
					$tax_query_settings['search'] = $search_term;
				}
			}
		}
		return $tax_query_settings;
	}

	static function url_parameters_for_posts( $attributes, $view_settings ) {
		if (
			isset( $view_settings['search_mode'][0] )
			&& $view_settings['search_mode'][0] != 'specific'
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_search',
				'filter_label'	=> __( 'Post search', 'wpv-views' ),
				'value'			=> '',
				'attribute'		=> 'wpv_post_search',
				'expected'		=> 'string',
				'placeholder'	=> 'search term',
				'description'	=> __( 'Please type a search term', 'wpv-views' )
			);
		}
		return $attributes;
	}

	static function url_parameters_for_taxonomy( $attributes, $view_settings ) {
		if (
			isset( $view_settings['taxonomy_search_mode'][0] )
			&& $view_settings['taxonomy_search_mode'][0] != 'specific'
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'taxonomy_search',
				'filter_label'	=> __( 'Taxonomy search', 'wpv-views' ),
				'value'			=> '',
				'attribute'		=> 'wpv_taxonomy_search',
				'expected'		=> 'string',
				'placeholder'	=> 'search term',
				'description'	=> __( 'Please type a search term', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	 * wpv_filter_register_post_search_filter_shortcode_attributes
	 *
	 * Register the filter by post search on the method to get View shortcode attributes
	 *
	 * @since 2.3.0
	 */

	static function post_shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['search_mode'] )
			&& isset( $view_settings['search_mode'][0] )
			&& $view_settings['search_mode'][0] == 'shortcode'
		) {
			$attributes[] = array (
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_search',
				'filter_label'	=> __( 'Post search', 'wpv-views' ),
				'value'			=> 'post_search',
				'attribute'		=> $view_settings['post_search_shortcode'],
				'expected'		=> 'string',
				'placeholder'	=> 'search term',
				'description'	=> __( 'Please type a search term', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	 * wpv_filter_register_taxonomy_search_filter_shortcode_attributes
	 *
	 * Register the filter by taxonomy search on the method to get View shortcode attributes
	 *
	 * @since 2.3.0
	 */

	static function tax_shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['taxonomy_search_mode'] )
			&& isset( $view_settings['taxonomy_search_mode'][0] )
			&& $view_settings['taxonomy_search_mode'][0] == 'shortcode'
		) {
			$attributes[] = array (
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'taxonomy_search',
				'filter_label'	=> __( 'Taxonomy search', 'wpv-views' ),
				'value'			=> 'taxonomy_search',
				'attribute'		=> $view_settings['taxonomy_search_shortcode'],
				'expected'		=> 'string',
				'placeholder'	=> 'search term',
				'description'	=> __( 'Please type a search term', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/*
	* ---------------------
	* Helpers
	* ---------------------
	*/

	static function get_post_search_content_options() {
		$post_search_content_options = array(
			'full_content'	=> array(
				'label'			=> __( 'Posts content and title', 'wpv-views' ),
				'description'	=> __( 'Use this to search in both post contents and titles', 'wpv-views' ),
				'summary'		=> __( 'post content and title', 'wpv-views' )
			),
			'just_title'	=> array(
				'label'			=> __( 'Just post title', 'wpv-views' ),
				'description'	=> __( 'Use this to search just in titles', 'wpv-views' ),
				'summary'		=> __( 'post title', 'wpv-views' )
			)
		);
		$post_search_content_options = apply_filters( 'wpv_filter_wpv_extend_post_search_content_options', $post_search_content_options );
		return $post_search_content_options;
	}

}
