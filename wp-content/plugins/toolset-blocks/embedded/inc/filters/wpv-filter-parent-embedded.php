<?php

/**
* Parent frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_Parent_Frontend_Filter::on_load();

/**
* WPV_Parent_Frontend_Filter
*
* Views Parent Filter Frontend Class
*
* @since 2.1
*/

class WPV_Parent_Frontend_Filter {

	static function on_load() {
		// Apply frontend filter by post parent
        add_filter( 'wpv_filter_query',										array( 'WPV_Parent_Frontend_Filter', 'filter_post_parent' ), 10, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_Parent_Frontend_Filter', 'archive_filter_post_parent' ), 40, 3 );
		// Auxiliar methods for requirements
		add_filter( 'wpv_filter_requires_current_page',						array( 'WPV_Parent_Frontend_Filter', 'requires_current_page' ), 10, 2 );
		add_filter( 'wpv_filter_requires_parent_post',						array( 'WPV_Parent_Frontend_Filter', 'requires_parent_post' ), 20, 2 );
		add_filter( 'wpv_filter_requires_framework_values',					array( 'WPV_Parent_Frontend_Filter', 'requires_framework_values' ), 20, 2 );
		// Auxiliar methods for gathering data
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts',	array( 'WPV_Parent_Frontend_Filter', 'shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts',			array( 'WPV_Parent_Frontend_Filter', 'url_parameters' ), 10, 2 );
		// Apply frontend filter by taxonomy parent
		add_filter( 'wpv_filter_taxonomy_query',							array( 'WPV_Parent_Frontend_Filter', 'filter_taxonomy_parent' ), 20, 3 );
    }

	/**
	* filter_post_parent
	*
	* Apply the filter by post parent to the View query.
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_post_parent and moved to a static method
	*/

	static function filter_post_parent( $query, $view_settings, $view_id ) {
		if ( isset( $view_settings['parent_mode'][0] ) ) {
			$post_parent__in = WPV_Parent_Frontend_Filter::get_settings( $query, $view_settings, $view_id );
			if ( count( $post_parent__in ) > 0 ) {
				$query['post_parent__in'] = $post_parent__in;
			}
		}
		return $query;
	}

	/**
	* archive_filter_post_parent
	*
	* Apply the query filter by post parent to WPAs.
	*
	* @since 2.1
	*/

	static function archive_filter_post_parent( $query, $archive_settings, $archive_id ) {
		if ( isset( $archive_settings['parent_mode'][0] ) ) {
			$post_parent__in = WPV_Parent_Frontend_Filter::get_settings( $query, $archive_settings, $archive_id );
			if ( count( $post_parent__in ) > 0 ) {
				$query->set( 'post_parent__in', $post_parent__in );
			}
		}
	}

	/**
	* get_settings
	*
	* Get settings for the query filter by post parent.
	*
	* @since 2.1
	*/

	static function get_settings( $query, $view_settings, $view_id ) {
		$post_parent__in = array();
		switch ( $view_settings['parent_mode'][0] ) {
			case 'top_current_post':
				$current_page = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
				if ( $current_page ) {
					$post_parent__in[] = $current_page->ID;
				}
				break;
			case 'current_page': // @deprecated in 1.12.1
			case 'current_post_or_parent_post_view':
				$current_page = apply_filters( 'wpv_filter_wpv_get_current_post', null );
				if ( $current_page ) {
					$post_parent__in[] = $current_page->ID;
				}
				break;
			case 'this_page':
				if (
					isset( $view_settings['parent_id'] )
					&& is_numeric( $view_settings['parent_id'] )
					&& $view_settings['parent_id'] > 0
				) {
					// Adjust for WPML support
					// 'any' will make WPML manage the 'post_type' this parent belongs to
					$post_parent__in[] = apply_filters( 'translate_object_id', (int) $view_settings['parent_id'], 'any', true, null );
				} else {
					// filter for items with no parents
					$post_parent__in[] = 0;
				}
				break;
			case 'no_parent':
				$post_parent__in[] = 0;
				break;
			case 'shortcode_attribute':
				if (
					isset( $view_settings['parent_shortcode_attribute'] )
					&& '' != $view_settings['parent_shortcode_attribute']
				) {
					$parent_shortcode = $view_settings['parent_shortcode_attribute'];
					$view_attrs = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', false );
					if (
						isset( $view_attrs[$parent_shortcode] )
						&& intval( $view_attrs[$parent_shortcode] ) > 0
					) {
						// Adjust for WPML support
						// 'any' will make WPML manage the 'post_type' this parent belongs to
						$post_parent__in[] = apply_filters( 'translate_object_id', (int) $view_attrs[$parent_shortcode], 'any', true, null );
					}
				}
				break;
			case 'url_parameter':
				if (
					isset( $view_settings['parent_url_parameter'] )
					&& '' != $view_settings['parent_url_parameter']
				) {
					$parent_url_parameter = $view_settings['parent_url_parameter'];
					if ( isset( $_GET[$parent_url_parameter] )
						&& $_GET[$parent_url_parameter] != array( 0 )
						&& $_GET[$parent_url_parameter] != 0
					) {
						$post_owner_ids_from_url = $_GET[$parent_url_parameter];
						$post_owner_ids_sanitized = array();
						if ( is_array( $post_owner_ids_from_url ) ) {
							foreach ( $post_owner_ids_from_url as $id_value ) {
								$id_value = (int) esc_attr( trim( $id_value ) );
								if ( $id_value > 0 ) {
									// Adjust for WPML support
									// 'any' will make WPML manage the 'post_type' this parent belongs to
									$id_value = apply_filters( 'translate_object_id', $id_value, 'any', true, null );
									$post_owner_ids_sanitized[] = $id_value;
								}
							}
						} else {
							$post_owner_ids_from_url = (int) esc_attr( trim( $post_owner_ids_from_url ) );
							if ( $post_owner_ids_from_url > 0 ) {
								// Adjust for WPML support
								// 'any' will make WPML manage the 'post_type' this parent belongs to
								$post_owner_ids_from_url = apply_filters( 'translate_object_id', $post_owner_ids_from_url, 'any', true, null );
								$post_owner_ids_sanitized[] = $post_owner_ids_from_url;
							}
						}
						if ( count( $post_owner_ids_sanitized ) ) {
							$post_parent__in = $post_owner_ids_sanitized;
						}
					}
				}
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					if (
						isset( $view_settings['parent_framework'] )
						&& '' != $view_settings['parent_framework']
					) {
						$parent_framework = $view_settings['parent_framework'];
						$parent_candidates = $WP_Views_fapi->get_framework_value( $parent_framework, array() );
						$parent_candidates_final = array();
						if ( ! is_array( $parent_candidates ) ) {
							$parent_candidates = explode( ',', $parent_candidates );
						}
						$parent_candidates = array_map( 'esc_attr', $parent_candidates );
						$parent_candidates = array_map( 'trim', $parent_candidates );
						// is_numeric + intval does sanitization
						$parent_candidates = array_filter( $parent_candidates, 'is_numeric' );
						$parent_candidates = array_map( 'intval', $parent_candidates );
						if ( count( $parent_candidates ) ) {
							foreach ( $parent_candidates as $parent_cand ) {
								// Adjust for WPML support
								// 'any' will make WPML manage the 'post_type' this parent belongs to
								$parent_cand = apply_filters( 'translate_object_id', $parent_cand, 'any', true, null );
								$parent_candidates_final[] = $parent_cand;
							}
						}
						if ( count( $parent_candidates_final ) ) {
							$post_parent__in = $parent_candidates_final;
						}
					}
				}
				break;
		}
		return $post_parent__in;
	}

	/**
	* requires_current_page
	*
	* Check if the current filter by post parent needs info about the current page.
	*
	* @since 2.1	Renamed from wpv_filter_parent_requires_current_page and moved to a static method
	*/

	static function requires_current_page( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( isset( $view_settings['parent_mode'][0] ) ) {
			if ( $view_settings['parent_mode'][0] == 'top_current_post' ) {
				$state = true;
			}
		}
		return $state;
	}

	/**
	* requires_parent_post
	*
	* Check if the current filter by post parent needs info about the parent post.
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_filter_parent_requires_parent_post and moved to a static method
	*/

	static function requires_parent_post( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( isset( $view_settings['parent_mode'][0] ) ) {
			if ( in_array( $view_settings['parent_mode'][0], array( 'current_page', 'current_post_or_parent_post_view' ) ) ) {
				$state = true;
			}
		}
		return $state;
	}

	/**
	*
	*
	* Check if the current filter by post parent needs info about the framework values,
	*
	* @since 1.10
	* @since 2.1	Renamed from wpv_filter_parent_requires_framework_values and moved to a static method
	*/

	static function requires_framework_values( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( isset( $view_settings['parent_mode'][0] ) ) {
			if ( $view_settings['parent_mode'][0] == 'framework' ) {
				$state = true;
			}
		}
		return $state;
	}

	/**
	* shortcode_attributes
	*
	* Register the filter by post IDs on the method to get View shortcode attributes
	*
	* @since 1.10
	* @since 2.1	Renamed from wpv_filter_register_post_parent_filter_shortcode_attributes and moved to a static method
	*/

	static function shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['parent_mode'] )
			&& isset( $view_settings['parent_mode'][0] )
			&& $view_settings['parent_mode'][0] == 'shortcode_attribute'
		) {
			$attributes[] = array (
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_parent',
				'filter_label'	=> __( 'Post parent', 'wpv-views' ),
				'value'			=> 'post_parent',
				'attribute'		=> $view_settings['parent_shortcode_attribute'],
				'expected'		=> 'numberlist',
				'placeholder'	=> '10',
				'description'	=> __( 'Please type a post ID to get its native children', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	* url_parameters
	*
	* Register the filter by post IDs on the method to get View URL parameters.
	*
	* @since 1.11
	* @since 2.1	Renamed to wpv_filter_register_post_parent_filter_url_parameters and move to a static method
	*/

	static function url_parameters( $attributes, $view_settings ) {
		if (
			isset( $view_settings['parent_mode'] )
			&& isset( $view_settings['parent_mode'][0] )
			&& $view_settings['parent_mode'][0] == 'url_parameter'
		) {
			$attributes[] = array (
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_parent',
				'filter_label'	=> __( 'Post parent', 'wpv-views' ),
				'value'			=> 'post_parent',
				'attribute'		=> $view_settings['parent_url_parameter'],
				'expected'		=> 'numberlist',
				'placeholder'	=> '10',
				'description'	=> __( 'Please type a post ID to get its native children', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	* filter_taxonomy_parent
	*
	* Apply child_of settings to Views listing taxonomy terms.
	*
	* @since 1.12
	* @since 2.1	Rename from wpv_filter_taxonomy_parent and move to a static method
	*/

	static function filter_taxonomy_parent( $tax_query_settings, $view_settings, $view_id ) {
		$parent_id = null;
		if (
			isset( $view_settings['taxonomy_parent_mode'] )
			&& isset( $view_settings['taxonomy_parent_mode'][0] )
		) {
			switch ( $view_settings['taxonomy_parent_mode'][0] ) {
				case 'current_view': // @deprecated in 1.12.1
				case 'current_taxonomy_view':
					$parent_id = apply_filters( 'wpv_filter_wpv_get_parent_view_taxonomy', null );
					break;
				case 'current_archive_loop':
					$archive_environment = apply_filters( 'wpv_filter_wpv_get_current_archive_loop', array() );
					if (
						isset( $archive_environment['type'] )
						&& $archive_environment['type'] == 'taxonomy'
						&& isset( $archive_environment['data']['term_id'] )
					) {
						$parent_id = $archive_environment['data']['term_id'];
					} else if (
						is_category()
						|| is_tag()
						|| is_tax()
					) {
						$queried_object = get_queried_object();
						$parent_id = $queried_object->term_id;
					}
					break;
				case 'this_parent':
					$parent_id = $view_settings['taxonomy_parent_id'];
					if (
						isset( $view_settings['taxonomy_type'][0] )
						&& ! empty( $parent_id )
					) {
						// WordPress 4.2 compatibility - split terms
						$candidate_term_id_splitted = wpv_compat_get_split_term( $parent_id, $view_settings['taxonomy_type'][0] );
						if ( $candidate_term_id_splitted ) {
							$parent_id = $candidate_term_id_splitted;
						}
						// Adjust for WPML support
						$parent_id = apply_filters( 'translate_object_id', $parent_id, $view_settings['taxonomy_type'][0], true, null );
					}
					break;
			}
		}
		if ( $parent_id !== null ) {
			global $WPVDebug;
			$tax_query_settings['child_of'] = $parent_id;
			$WPVDebug->add_log( 'filters', "Filter by parent with ID {$parent_id}", 'filters', 'Filter by parent term' );
		}

		return $tax_query_settings;
	}

}
