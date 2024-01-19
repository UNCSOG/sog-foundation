<?php

/**
* wpv-layout-embedded.php
*
* @package Views
*
* @since unknown
*/

/**
* WPV_Layout_Embedded
*
* @since 2.2
*/

class WPV_Layout_Embedded {
	
	public function __construct() {
		add_action( 'init',		array( $this, 'init' ) );
		
		add_filter( 'wpv_filter_wpv_has_default_loop_output', array( $this, 'has_default_loop_output' ), 10, 3 );

		add_filter( 'wpv_filter_wpv_is_wrapper_div_required', array( $this, 'is_wrapper_div_required' ), 10, 2 );

		add_filter( 'wpv_filter_wpv_is_separators_list_layout_selected', array( $this, 'is_separators_list_layout_selected' ), 10, 2 );
    }
	
	function init() {
		
	}
	
	function has_default_loop_output( $state, $view_layout_settings = array(), $view_id = 0 ) {
		if ( 
			empty( $view_layout_settings )
			&& $view_id > 0 
		) {
			$view_layout_settings	= apply_filters( 'wpv_filter_wpv_get_object_layout_settings', array(), $view_id );
		}
		if ( 
			is_array( $view_layout_settings ) 
			&& ! empty( $view_layout_settings ) 
		) {
			$loop_output			= isset( $view_layout_settings['layout_meta_html'] ) ? $view_layout_settings['layout_meta_html'] : '';
			$generate_loop_output	= WPV_View_Base::generate_loop_output();
			$loop_output_empty		= $generate_loop_output['loop_output_settings']['layout_meta_html'];
			if ( $loop_output == $loop_output_empty ) {
				$state = true;
			}
		}
		return $state;
	}

	/**
	 * Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
	 *
	 * @param bool     $required True if the list with separators is selected as the View layout.
	 * @param null|int $view_id  The ID of the View or null.
	 *
	 * @return bool
	 */
	public function is_wrapper_div_required( $required = true, $view_id = null ) {
		$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id );

		// Check if the View wrapper is disabled in the View settings.
		if ( 'true' === toolset_getnest( $view_settings, array( 'disable_view_wrapper' ), 'false' ) ) {
			return false;
		}

		return $required;
	}

	/**
	 * Checks if the list with separators is selected as the View layout.
	 *
	 * @param bool     $selected True if the list with separators is selected as the View layout.
	 * @param null|int $view_id  The ID of the View or null.
	 *
	 * @return bool
	 */
	public function is_separators_list_layout_selected( $selected = false, $view_id = null ) {
		$view_meta = apply_filters( 'wpv_filter_wpv_get_view_layout_settings', array(), $view_id );

		// Check if the selected layout for the View is the separated list.
		if ( 'separators_list' === toolset_getnest( $view_meta, array( 'style' ), '' ) ) {
			return true;
		}

		return $selected;
	}
}

global $WPV_Layout_Embedded;
$WPV_Layout_Embedded = new WPV_Layout_Embedded();

/*
 
    Shortcode for sorting by the column heading in
    table layout mode.
    
*/

// @todo normalize the data-orderby value so it matches the ones in order embedded: static methods can be used here :-)
// - normalize native values with aliases
// - normalize meta field keys
add_shortcode('wpv-heading', 'wpv_header_shortcode');
function wpv_header_shortcode( $atts, $value ) {
    extract(
        $atts = shortcode_atts( array(
            'name' => '',
            'style' => '',
            'class' => ''
            ), $atts )
    );

    if (isset($atts['name']) && strpos($atts['name'], 'types-field-')) {
        $atts['name'] = strtolower($atts['name']);
    }
    
    if ( ! empty( $style ) ) {
        $style = ' style="'. esc_attr( $style ).'"';
    }
    if ( ! empty( $class) ) {
        $class = ' ' . esc_attr( $class );
    }
        
    global $wp_version;
    $view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
    
    $order_class	= 'wpv-header-no-sort';
	$dir			= "asc";
	$orderby_name 	= $atts['name'];
	$orderby_as		= '';
	$can_order		= true;
	$default_order	= $view_settings['order'];
	
	if ( 
		$view_settings['view-query-mode'] == 'normal' 
		&& ! empty( $atts['name'] ) 
		&& isset( $view_settings['query_type'][0] )
	) {
		switch ( $view_settings['query_type'][0] ) {
			case 'posts':
				$default_order = $view_settings['order'];
				if ( in_array( $atts['name'], array( 'post-body', 'post-url', 'wpv-post-taxonomy' ) ) ) {
					$can_order = false;
				} else if ( strpos( $atts['name'], 'types-field-' ) === 0 ) {
					$field_name = strtolower( substr( $atts['name'], 12 ) );
					$types_field_data = wpv_is_types_custom_field( $field_name );
					if (
						isset( $types_field_data['meta_key'] )
						&& isset( $types_field_data['type'] ) 
					) {
						$field_type = $types_field_data['type'];
						if ( in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
							$can_order = false;
						} else {
							if ( in_array( $field_type, array( 'numeric', 'date' ) ) ) {
								$orderby_as = 'numeric';
							}
							$orderby_name = 'field-' . $types_field_data['meta_key'];
						}
					} else {
						$can_order = false;
					}
				} else if ( strpos( $atts['name'], 'post-field-' ) === 0 ) {
					$orderby_name = strtolower( substr( $atts['name'], 5 ) );
				} else {
					switch( $atts['name'] ) {
						case 'post-title':
							$orderby_name = 'post_title';
							break;
						case 'post-date':
							$orderby_name = 'post_date';
							break;
						case 'post-author':
							$orderby_name = 'post_author';
							break;
						case 'post-id':
							$orderby_name = 'ID';
							break;
						case 'post-type':
							$orderby_name = 'post_type';
							break;
					}
				}
				break;
			case 'taxonomy':
				$default_order = $view_settings['taxonomy_order'];
				if ( strpos( $atts['name'], 'taxonomy-field-') === 0 ) {
					$field_name = strtolower( substr( $atts['name'], 15 ) );
					$field_type = wpv_types_get_field_type( $field_name, 'tf' );
					if ( in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
						$can_order = false;
					} else if ( in_array( $field_type, array( 'numeric', 'date' ) ) ) {
						$orderby_as = 'numeric';
					}
					if ( version_compare( $wp_version, '4.5', '<' ) ) {
						$can_order = false;
					}
				}
				break;
			case 'users':
				$default_order = $view_settings['users_order'];
				if ( strpos( $atts['name'], 'user-field-') === 0 ) {
					$field_name = strtolower( substr( $atts['name'], 15 ) );
					$field_type = wpv_types_get_field_type( $field_name, 'uf' );
					if ( in_array( $field_type, array( 'checkboxes', 'skype' ) ) ) {
						$can_order = false;
					} else if ( in_array( $field_type, array( 'numeric', 'date' ) ) ) {
						$orderby_as = 'numeric';
					}
				} else if ( ! in_array( $atts['name'], array( 'user_email', 'user_login', 'display_name', 'user_url', 'user_registered', 'user_nicename' ) ) ) {
					$can_order = false;
				}
				break;
		}
	} else {
		$can_order = false;
	}
	
	if ( $can_order ) {
		$view_number = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		if (
			isset( $_GET['wpv_sort_orderby'] ) 
			&& esc_attr( $_GET['wpv_sort_orderby'] ) == $orderby_name 
			&& isset( $_GET['wpv_view_count'] ) 
			&& $view_number == esc_attr( $_GET['wpv_view_count'] ) 
		) {
			if ( isset( $_GET['wpv_sort_order'] ) ) {
				$passed_dort_dir = esc_attr( strtolower( $_GET['wpv_sort_order'] ) );
				switch ( $passed_dort_dir ) {
					case 'asc':
						$order_class = 'wpv-header-asc';
						$dir = "desc";
						break;
					case 'desc':
						$order_class = 'wpv-header-desc';
						$dir = "asc";
						break;
					default:
						if ( strtolower( $default_order ) == 'asc' ) {
							$order_class = 'wpv-header-asc';
							$dir = "desc";
						} else {
							$order_class = 'wpv-header-desc';
							$dir = "asc";
						}
						break;
				}
			}
		}
        $link = '<a href="#"'
			. ' class="' . $order_class . ' js-wpv-sort-trigger js-wpv-column-header-click'. $class .'"'
			. $style 
			. ' data-viewnumber="' 	. $view_number . '"'
			. ' data-orderby="'		. $orderby_name . '"'
			. ' data-order="'	 	. $dir . '"'
			. ' data-orderbyas="' 	. $orderby_as . '"'
			. '>' 
			. wpv_do_shortcode( $value ) 
			. '<span class="wpv-sorting-indicator"></span>'
			. '</a>';
        return $link;
	} else {
		return wpv_do_shortcode( $value );
	}
}

add_shortcode('wpv-layout-start', 'wpv_layout_start_shortcode');
function wpv_layout_start_shortcode( $atts ){
	$view_id				= apply_filters( 'wpv_filter_wpv_get_current_view', null );
    $view_settings			= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );

	/**
	 * wpv_filter_wpv_is_wrapper_div_required
	 *
	 * Allow for overriding the requirement of the View wrapper DIV (and the filter FORM along with the pagination).
	 *
	 * @param bool     $is_wrapper_div_required
	 * @param null|int $view_id                 The ID of the View to check.
	 *
	 * @since 2.6.4
	 */
	if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
		return '';
	}

	$view_number			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
	$pagination_data		= apply_filters( 'wpv_filter_wpv_get_pagination_settings', array(), $view_settings );
	$pagination_permalinks	= apply_filters( 'wpv_filter_wpv_get_pagination_permalinks', array(), $view_settings, $view_id );
	if ( $pagination_data['page'] == 1 ) {
		$pagination_permalink = $pagination_permalinks['first'];
	} else {
		$pagination_permalink = str_replace( 'WPV_PAGE_NUM', $pagination_data['page'], $pagination_permalinks['other'] );
	}
	
	
    $class = array( 
		'js-wpv-view-layout', 
		'js-wpv-layout-responsive',
		'js-wpv-view-layout-' . esc_attr( $view_number )
	);
    $style = wpv_layout_start_shortcode_style( $pagination_data );
	$add = '';
	
	if ( in_array( $pagination_data['type'], array( 'ajaxed', 'rollover' ) ) ) {
		$class[] = 'wpv-pagination';
		$class[] = 'js-wpv-layout-has-pagination';
		
		if ( $pagination_data['effect'] == 'infinite' ) {
			$class[] = 'js-wpv-layout-infinite-scrolling';
		}
		
		if ( $pagination_data['preload_images'] == 'enabled'  ) {
            $class[] = 'wpv-pagination-preload-images';
			$class[] = 'js-wpv-layout-preload-images';
        }
		
		if ( $pagination_data['preload_pages'] == 'enabled'  ) {
			$class[] = 'wpv-pagination-preload-pages';
			$class[] = 'js-wpv-layout-preload-pages';
		}
		
	}
    
	if ( ! empty( $class ) ) {
		$add .= ' class="' . implode( ' ', $class ) . '"';
	}
	if ( ! empty( $style ) ) {
		$add .= ' style="' . implode( ' ', $style ) . '"';
	}
		
	$add .= ' data-viewnumber="' . esc_attr( $view_number ) . '"';
	
	$return = '<div'
		. ' id="wpv-view-layout-' . esc_attr( $view_number ) . '"'
		. $add
		. ' data-pagination="' . esc_js( wp_json_encode( $pagination_data ) ) . '"'
		. ' data-permalink="' . esc_url( $pagination_permalink ) . '"'
		. ">\n";
		
	return $return;
}

/**
 * Populates the styling array for the layout start shortrcode.
 *
 * @param array $pagination_data The pagination data needed for the building of the styling array.
 *
 * @return array
 */
function wpv_layout_start_shortcode_style( $pagination_data = array() ) {
	$style = array();

	$maybe_ajax_action = toolset_getpost( 'action', '' );
	if (
		in_array( $pagination_data['type'], array( 'ajaxed', 'rollover' ), true ) &&
		'enabled' === $pagination_data['preload_images'] &&
		// For the case when the shortcode is rendered during an existing View preview rendering inside the View block,
		// the inline styling for the AJAX pagination with the preloading of images enabled needs to be skipped, otherwise
		// nothing will show up in the preview.
		'wpv_get_view_block_preview' !== sanitize_text_field( $maybe_ajax_action )
	) {
		$style[] = 'visibility:hidden;';
	}

	return $style;
}

add_shortcode('wpv-layout-end', 'wpv_layout_end_shortcode');
function wpv_layout_end_shortcode( $atts ){
	// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
	/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
	if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
		return '';
	}
	return '</div>';
}

add_shortcode('wpv-layout-row', 'wpv_layout_row');
function wpv_layout_row( $atts, $value ){
	extract(
		shortcode_atts( array(
			'framework' => 'bootstrap',
			'cols' => 12,
			'col_options' => '',
		), $atts )
	);
	if ( 'bootstrap' == $framework ) {
		$elements = substr_count( $value, '[wpv-layout-cell-span]' );
		$counter = 1;
		$pattern = array();

		// if we have col_options
		preg_match_all('/\{([^}]*)\}/', $col_options, $pieces);
		foreach($pieces[1] as $match) {
			$piece = explode(',', $match);
			if ( ( count( $piece ) == $elements ) && ( array_sum( $piece ) == $cols ) ) {
				$pattern = $piece;
			}
		}
		while(preg_match('#\\[wpv-layout-cell-span]#', $value, $matches)) {
			$pos = strpos( $value, $matches[0] );
			$len = strlen( $matches[0] );
			if ( 0 < count( $pattern ) ) {
				$value = substr_replace( $value, 'span' . $pattern[$counter - 1], $pos, $len );
				$counter++;
			} elseif ( $counter < $elements ) {
				$counter++;
				$value = substr_replace( $value, 'span' . floor( $cols/$elements ), $pos, $len );
			} else {
				$value = substr_replace( $value, 'span' . ( $cols - ( ( $elements -1 ) * ( floor( $cols/$elements ) ) ) ), $pos, $len );
			}
		}
	}
	
	return wpv_do_shortcode( $value );
        
}

add_shortcode('wpv-layout-meta-html', 'wpv_layout_meta_html');
function wpv_layout_meta_html($atts) {
    extract(
        shortcode_atts( array(), $atts )
    );

    $view_layout_settings = apply_filters( 'wpv_filter_wpv_get_view_layout_settings', array() );
    
    if (isset($view_layout_settings['layout_meta_html'])) {
        
        $content = $view_layout_settings['layout_meta_html'];
        
        return wpv_do_shortcode($content);
    } else {
        return '';
    }
}
