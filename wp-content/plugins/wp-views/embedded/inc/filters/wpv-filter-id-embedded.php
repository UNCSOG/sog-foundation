<?php

/**
* ID frontend filter
*
* @package Views
*
* @since 2.1
*/

WPV_ID_Frontend_Filter::on_load();

/**
* WPV_Author_Filter
*
* Views ID Filter Frontend Class
*
* @since 2.1
*/

class WPV_ID_Frontend_Filter {
	
	static function on_load() {
		// Apply frontend filter by post ID
        add_filter( 'wpv_filter_query',										array( 'WPV_ID_Frontend_Filter', 'filter_post_id' ), 13, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_ID_Frontend_Filter', 'archive_filter_post_id' ), 40, 3 );
		// Auxiliar methods for requirements
		add_filter( 'wpv_filter_requires_framework_values',					array( 'WPV_ID_Frontend_Filter', 'requires_framework_values' ), 20, 2 );
		// Auxiliar methods for gathering data
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts',	array( 'WPV_ID_Frontend_Filter', 'shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts',			array( 'WPV_ID_Frontend_Filter', 'url_parameters' ), 10, 2 );
    }
	
	/**
	* filter_post_id
	*
	* Apply the query filter by post IDs on Views.
	*
	* @since unknown
	*/
	
	static function filter_post_id( $query, $view_settings, $view_id ) {
		// @todo are IDs adjusted in WPML? Maybe yes, because it filters the post__in and post__not_in arguments
		if ( isset( $view_settings['id_mode'][0] ) ) {
			$include = true;
			if ( 
				isset( $view_settings['id_in_or_out'] ) 
				&& 'out' == $view_settings['id_in_or_out'] 
			) {
				$include = false;
			}
			$show_id_array = WPV_ID_Frontend_Filter::get_settings( $query, $view_settings, $view_id );
			if ( isset( $show_id_array ) ) {
				if ( count( $show_id_array ) > 0 ) {
					if ( $include ) {
						if ( isset( $query['post__in'] ) ) {
							$query['post__in'] = array_intersect( (array) $query['post__in'], $show_id_array );
							$query['post__in'] = array_values( $query['post__in'] );
							if ( empty( $query['post__in'] ) ) {
								$query['post__in'] = array( '0' );
							}
						} else {
							$query['post__in'] = $show_id_array;
						}
					} else {
						if ( isset( $query['post__not_in'] ) ) {
							$query['post__not_in'] = array_merge( (array) $query['post__not_in'], $show_id_array );
						} else {
							$query['post__not_in'] = $show_id_array;
						}
					}
				} else {
					// @todo review this, we might not want to apply only when ( ! isset )
					if ( $include ) {
						if ( ! isset( $query['post__in'] ) ) {
							$query['post__in'] = array('0');
						}
					} else {
						if ( ! isset( $query['post__not_in'] ) ) {
							$query['post__not_in'] = array('0');
						}
					}
				}
			}	
		}
		return $query;
	}
	
	/**
	* archive_filter_post_id
	*
	* Apply te query filter by post IDs on WPAs.
	*
	* @since 2.1
	*/
	
	static function archive_filter_post_id( $query, $archive_settings, $archive_id ) {
		if ( isset( $archive_settings['id_mode'][0] ) ) {
			$include = true;
			if ( 
				isset( $archive_settings['id_in_or_out'] ) 
				&& 'out' == $archive_settings['id_in_or_out'] 
			) {
				$include = false;
			}
			$show_id_array = WPV_ID_Frontend_Filter::get_settings( $query, $archive_settings, $archive_id );	
			if ( isset( $show_id_array ) ) {
				$post__in = $query->get( 'post__in' );
				$post__in = isset( $post__in ) ? $post__in : array();
				$post__not_in = $query->get( 'post__not_in' );
				$post__not_in = isset( $post__not_in ) ? $post__not_in : array();
				if ( count( $show_id_array ) > 0 ) {
					if ( $include ) {
						if ( count( $post__in ) > 0 ) {
							$post__in = array_intersect( (array) $post__in, $show_id_array );
							$post__in = array_values( $post__in );
							if ( empty( $post__in ) ) {
								$post__in = array( '0' );
							}
						} else {
							$post__in = $show_id_array;
						}
						$query->set( 'post__in', $post__in );
					} else {
						if ( count( $post__not_in ) > 0 ) {
							$post__not_in = array_merge( (array) $post__not_in, $show_id_array );
						} else {
							$post__not_in = $show_id_array;
						}
						$query->set( 'post__not_in', $post__not_in );
					}
				} else {
					// @todo review this, we might not want to apply only when ( ! isset )
					if ( $include ) {
						if ( ! isset( $post__in ) ) {
							$post__in = array( '0' );
							$query->set( 'post__in', $post__in );
						}
					} else {
						if ( ! isset( $post__not_in ) ) {
							$post__not_in = array( '0' );
							$query->set( 'post__not_in', $post__not_in );
						}
					}
				}
			}	
		}
	}
	
	/**
	* get_settings
	*
	* Auxiliar method to get the author filter frontend data.
	*
	* @since 2.1
	*/
	
	static function get_settings( $query, $view_settings, $view_id ) {
		$show_id_array = array();
		switch ( $view_settings['id_mode'][0] ) {
			case 'by_ids':
				if (
					isset( $view_settings['post_id_ids_list'] ) 
					&& '' != $view_settings['post_id_ids_list']
				) {
					$id_ids_list = explode( ',', $view_settings['post_id_ids_list'] );
					foreach ( $id_ids_list as $id_candidate ) {
						$id_candidate = (int) trim( $id_candidate );
						$id_candidate = apply_filters( 'translate_object_id', $id_candidate, 'any', true, null );
						$show_id_array[] = $id_candidate;
					}
				}
				else {
					$show_id_array = null;
				}
				break;
			case 'by_url':
				if (
					isset( $view_settings['post_ids_url'] ) 
					&& '' != $view_settings['post_ids_url']
				) {
					$id_parameter = $view_settings['post_ids_url'];	
					if ( isset( $_GET[$id_parameter] ) ) {
						$ids_to_load = $_GET[$id_parameter];
						if ( is_array( $ids_to_load ) ) {
							if ( 
								0 == count( $ids_to_load ) 
								|| '' == $ids_to_load[0] 
							) {
								$show_id_array = null;
							} else {
								foreach ( $ids_to_load as $id_candidate ) {
									$id_candidate = (int) trim( $id_candidate );
									$id_candidate = apply_filters( 'translate_object_id', $id_candidate, 'any', true, null );
									$show_id_array[] = $id_candidate;
								}
							}
						} else {
							if ( '' == $ids_to_load ) {
								$show_id_array = null;
							} else {
								$id_candidate = (int) trim( $ids_to_load );
								$id_candidate = apply_filters( 'translate_object_id', $id_candidate, 'any', true, null );
								$show_id_array[] = $id_candidate;
							}
						}
					} else {
						$show_id_array = null;
					}
				}
				break;
			case 'shortcode':
				global $WP_Views;
				if (
					isset( $view_settings['post_ids_shortcode'] ) 
					&& '' != $view_settings['post_ids_shortcode']
				) {
					$id_shortcode = $view_settings['post_ids_shortcode'];	
					$view_attrs = $WP_Views->get_view_shortcodes_attributes();
					if ( 
						isset( $view_attrs[$id_shortcode] ) 
						&& '' != $view_attrs[$id_shortcode]
					) {
						$ids_to_load = explode( ',', $view_attrs[$id_shortcode] );
						if ( count( $ids_to_load ) > 0 ) {
							foreach ( $ids_to_load as $id_candidate ) {
								$id_candidate = (int) trim( $id_candidate );
								$id_candidate = apply_filters( 'translate_object_id', $id_candidate, 'any', true, null );
								$show_id_array[] = $id_candidate;
							}
						}
					} else {
						$show_id_array = null;
					}
				}
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					if (
						isset( $view_settings['post_ids_framework'] ) 
						&& '' != $view_settings['post_ids_framework']
					) {
						$post_ids_framework = $view_settings['post_ids_framework'];
						$post_ids_candidates = $WP_Views_fapi->get_framework_value( $post_ids_framework, array() );
						if ( ! is_array( $post_ids_candidates ) ) {
							$post_ids_candidates = explode( ',', $post_ids_candidates );
						}
						if ( count( $post_ids_candidates ) > 0 ) {
							foreach ( $post_ids_candidates as $id_candidate ) {
								if ( is_numeric( $id_candidate ) ) {
									$id_candidate = (int) trim( $id_candidate );
									$id_candidate = apply_filters( 'translate_object_id', $id_candidate, 'any', true, null );
									$show_id_array[] = $id_candidate;
								}
							}
						}
					}
				} else {
					$show_id_array = null;
				}
				break;
		}
		return $show_id_array;
	}
	
	/**
	* requires_framework_values
	*
	* Whether the current View requires framework data for the filter by post ID
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.10
	* @since 2.1	Rename from wpv_filter_id_requires_framework_values and move to a static method
	*/
	
	static function requires_framework_values( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( isset( $view_settings['id_mode'] ) && isset( $view_settings['id_mode'][0] ) && $view_settings['id_mode'][0] == 'framework' ) {
			$state = true;
		}
		return $state;
	}
	
	/**
	* wpv_filter_register_post_id_filter_shortcode_attributes
	*
	* Register the filter by post IDs on the method to get View shortcode attributes
	*
	* @since 1.10
	* @since 2.1	Renamed from wpv_filter_register_post_id_filter_shortcode_attributes and moved to a static method
	*/
	
	static function shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['id_mode'] ) 
			&& isset( $view_settings['id_mode'][0] ) 
			&& $view_settings['id_mode'][0] == 'shortcode' 
		) {
			$attributes[] = array (
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_id',
				'filter_label'	=> __( 'Post ID', 'wpv-views' ),
				'value'			=> 'post_id',
				'attribute'		=> $view_settings['post_ids_shortcode'],
				'expected'		=> 'numberlist',
				'placeholder'	=> '10, 13, 21',
				'description'	=> __( 'Please type a comma separated list of post IDs', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	* wpv_filter_register_post_id_filter_url_parameters
	*
	* Register the filter by post IDs on the method to get View URL parameters
	*
	* @since 1.11
	* @since 2.1	Renamed from wpv_filter_register_post_id_filter_url_parameters and moved to a static method
	*/
	
	static function url_parameters( $attributes, $view_settings ) {
		if (
			isset( $view_settings['id_mode'] ) 
			&& isset( $view_settings['id_mode'][0] ) 
			&& $view_settings['id_mode'][0] == 'by_url' 
		) {
			$attributes[] = array (
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_id',
				'filter_label'	=> __( 'Post ID', 'wpv-views' ),
				'value'			=> 'post_id',
				'attribute'		=> $view_settings['post_ids_url'],
				'expected'		=> 'numberlist',
				'placeholder'	=> '10, 13, 21',
				'description'	=> __( 'Please type a comma separated list of post IDs', 'wpv-views' )
			);
		}
		return $attributes;
	}
	
}