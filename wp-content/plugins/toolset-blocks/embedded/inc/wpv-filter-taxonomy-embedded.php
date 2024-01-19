<?php

// DEPRECATED global used only in the old wpv_taxonomy_defaults_save function
// TO DELETE

global $taxonomy_checkboxes_defaults;
$taxonomy_checkboxes_defaults = array(
    'taxonomy_hide_empty' => true,
    'taxonomy_include_non_empty_decendants' => true,
    'taxonomy_pad_counts' => false,
);

/**
* wpv_taxonomy_default_settings
*
* Sets the default settings for Views listing taxonomies
*
* @since unknown
*/

add_filter( 'wpv_view_settings', 'wpv_taxonomy_default_settings' );

function wpv_taxonomy_default_settings( $view_settings ) {
	if ( ! isset( $view_settings['taxonomy_type'] ) ) {
		$view_settings['taxonomy_type'] = array();
	}
	$taxonomy_defaults = array(
		'taxonomy_hide_empty' => true,
		'taxonomy_include_non_empty_decendants' => true,
		'taxonomy_pad_counts' => false,
	);
	foreach ( $taxonomy_defaults as $key => $value ) {
		if ( ! isset( $view_settings[$key] ) ) {
			$view_settings[$key] = $value;
		}
	}
	return $view_settings;
}

/**
* get_taxonomy_query
*
* Main function to get the results of a View that lists taxonomy terms
*
* @param $view_settings array
*
* @since unknown
*/

function get_taxonomy_query( $view_settings ) {
    global $WP_Views, $wpdb, $WPVDebug;

    $taxonomies = get_taxonomies( '', 'objects' );
    $view_id = $WP_Views->get_current_view();

    $WPVDebug->add_log( 'info', apply_filters( 'wpv-view-get-content-summary', '', $WP_Views->current_view, $view_settings ), 'short_query' );

    $tax_query_settings = array(
        'hide_empty'	=> $view_settings['taxonomy_hide_empty'],
        'hierarchical'	=> $view_settings['taxonomy_include_non_empty_decendants'],
        'pad_counts'	=> $view_settings['taxonomy_pad_counts']
    );

    $WPVDebug->add_log( 'info', "Basic query arguments\n". print_r( $tax_query_settings, true ) , 'query_args' );

    /**
	* Filter wpv_filter_taxonomy_query
	*
	* This is where all the filters coming from the View settings to modify the query are (or should be) hooked
	*
	* @param $tax_query_settings	array	The relevant elements of the View settings in an array to be used as arguments in a get_terms() call
	* @param $view_settings			array	The View settings
	* @param $view_id				integer	The ID of the View being displayed
	*
	* @return $tax_query_settings
	*
	* @since unknown
	*/

    $tax_query_settings = apply_filters( 'wpv_filter_taxonomy_query', $tax_query_settings, $view_settings, $view_id );

	$WPVDebug->add_log( 'filters', "wpv_filter_taxonomy_query\n". print_r( $tax_query_settings, true ), 'filters', 'Filter arguments before the query using <strong>wpv_filter_taxonomy_query</strong>' );

	if (
		isset( $tax_query_settings['wpv_force_empty_query'] )
		&& $tax_query_settings['wpv_force_empty_query']
	) {
		$items = array();
	} else if ( isset( $taxonomies[$view_settings['taxonomy_type'][0]] ) ) {
        $items = get_terms( $taxonomies[$view_settings['taxonomy_type'][0]]->name, $tax_query_settings );
    } else {
        // taxonomy no longer exists.
        $items = array();
    }

	$WPVDebug->add_log( 'info', print_r( $items, true ), 'query_results', '', true );

	// Register specific taxonomy post query filters on demand.
	$filter_post_query = \OTGS\Toolset\Views\Controller\Filters\Taxonomy\PostQuery::get_instance();
	$filter_post_query->maybe_initialize();

    /**
	 * Filter wpv_filter_taxonomy_post_query
	 *
	 * Filter applied to the results of the get_terms() call
	 *
	 * @param \WP_Term[] $items List of terms returned by the get_terms() call
	 * @param array $tax_query_settings The relevant elements of the View settings in an array to be used as arguments in a get_terms() call
	 * @param array $view_settings The View settings
	 * @param int $view_id The ID of the View being displayed
	 * @return \WP_Term[]
	 * @since unknown
	 */
    $items = apply_filters( 'wpv_filter_taxonomy_post_query', $items, $tax_query_settings, $view_settings, $view_id );

    $WPVDebug->add_log( 'filters', "wpv_filter_taxonomy_post_query\n" . print_r( $items, true ), 'filters', 'Filter the returned query using <strong>wpv_filter_taxonomy_post_query</strong>' );

	$items = array_values( $items );

    return $items;
}

/**
 * Views-Shortcode: wpv-no-taxonomy-found
 *
 * Description: The [wpv-no-taxonomy-found] shortcode will display the text inside
 * the shortcode if there are no taxonomys found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-no-taxonomy-found]No taxonomy found[/wpv-no-taxonomy-found]
 *
 * Link:
 *
 * Note:
 * This shortcode is deprecated in favour of the new [wpv-no-items-found]
 *
 */

add_shortcode('wpv-no-taxonomy-found', 'wpv_no_taxonomy_found');
function wpv_no_taxonomy_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;

    if ($WP_Views->get_taxonomy_found_count() == 0) {
        // display the message when no taxonomys are found.
        return wpv_do_shortcode($value);
    } else {
        return '';
    }

}

/**
* wpv_filter_tax_requires_current_page
*
* Whether the current View requires the current page for any filter
*
* @param $state (boolean) the state of this need until this filter is applied
* @param $view_settings
*
* @return $state (boolean)
*
* @since 1.9.0
*/

add_filter( 'wpv_filter_requires_current_page', 'wpv_filter_tax_requires_current_page', 10, 2 );

function wpv_filter_tax_requires_current_page( $state, $view_settings ) {
	if ( $state ) {
		return $state; // Already set
	}
    if (
		isset( $view_settings['taxonomy_terms_mode'] )
		&& in_array( $view_settings['taxonomy_terms_mode'], array( 'top_current_post' ) )
	) {
        $state = true;
    }
	return $state;

}


/**
* wpv_filter_tax_requires_parent_post
*
* Check if the current filter by post parent needs info about the parent post
*
* @since unknown
*/

add_filter( 'wpv_filter_requires_parent_post', 'wpv_filter_tax_requires_parent_post', 10, 2 );

function wpv_filter_tax_requires_parent_post( $state, $view_settings ) {
	if ( $state ) {
		return $state; // Already set
	}
    if (
		isset( $view_settings['taxonomy_terms_mode'] )
		&& in_array( $view_settings['taxonomy_terms_mode'], array( 'CURRENT_PAGE', 'current_post_or_parent_post_view' ) )
	) {
        $state = true;
    }
	return $state;
}

/**
* wpv_filter_tax_requires_framework_values
*
* Whether the current View requires framework valus for the filter by specific terms
*
* @param $state				boolean	State of this need until this filter is applied
* @param $view_settings		array
*
* @return $state (boolean)
*
* @since 1.10
*/

add_filter( 'wpv_filter_requires_framework_values', 'wpv_filter_tax_requires_framework_values', 10, 2 );

function wpv_filter_tax_requires_framework_values( $state, $view_settings ) {
	if ( $state ) {
		return $state; // Already set
	}
    if (
		isset( $view_settings['taxonomy_terms_mode'] )
		&& $view_settings['taxonomy_terms_mode'] == 'framework'
	) {
        $state = true;
    }
	return $state;

}

/**
* wpv_filter_tax_requires_parent_term
*
* Whether the current View is nested and requires the user set by the parent View for any filter
*
* @param $state				boolean	State of this need until this filter is applied
* @param $view_settings		array
*
* @return $state (boolean)
*
* @since 1.9.0
*/

add_filter( 'wpv_filter_requires_parent_term', 'wpv_filter_tax_requires_parent_term', 10, 2 );

function wpv_filter_tax_requires_parent_term( $state, $view_settings ) {
	if ( $state ) {
		return $state;
	}
	if (
		isset( $view_settings['taxonomy_parent_mode'] )
		&& isset( $view_settings['taxonomy_parent_mode'][0] )
		&& in_array( $view_settings['taxonomy_parent_mode'][0], array( 'current_view', 'current_taxonomy_view' ) )
	) {
        $state = true;
    }
	return $state;
}

/**
* wpv_filter_tax_requires_current_archive
*
* Whether the current View requires the current archive for the taxonomy parent filter
*
* @param $state				boolean	State of this need until this filter is applied
* @param $view_settings		array
*
* @return $state (boolean)
*
* @since 1.10
*/

add_filter( 'wpv_filter_requires_current_archive', 'wpv_filter_tax_requires_current_archive', 10, 2 );

function wpv_filter_tax_requires_current_archive( $state, $view_settings ) {
	if ( $state ) {
		return $state; // Already set
	}
    if (
		isset( $view_settings['taxonomy_parent_mode'] )
		&& isset( $view_settings['taxonomy_parent_mode'][0] )
		&& $view_settings['taxonomy_parent_mode'][0] == 'current_archive_loop'
	) {
        $state = true;
    }
	return $state;

}

/**
* wpv_filter_taxonomy_term
*
* Apply include settings to Views listing taxonomy terms
* Might move this to the filter by ID file
*
* @since 1.12
*/

add_filter( 'wpv_filter_taxonomy_query', 'wpv_filter_taxonomy_term', 20, 3 );

function wpv_filter_taxonomy_term( $tax_query_settings, $view_settings, $view_id ) {
	$taxonomies = get_taxonomies( '', 'objects' );
	if ( isset( $view_settings['taxonomy_terms_mode'] ) ) {
		$terms_to_include = array();
		$force_empty_query = false;
		switch ( $view_settings['taxonomy_terms_mode'] ) {
			case 'top_current_post':
				$force_empty_query = true;
				if ( isset( $taxonomies[$view_settings['taxonomy_type'][0]] ) ) {
					$current_page = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
					if ( $current_page ) {
						$terms_to_include_objects = get_the_terms( $current_page->ID, $view_settings['taxonomy_type'][0] );
						if ( is_array( $terms_to_include_objects ) ) {
							$terms_to_include = wp_list_pluck( $terms_to_include_objects, 'term_id' );
						} else {
							$terms_to_include = array();
						}
					} else {
						$terms_to_include = array();
					}
				} else {
					$terms_to_include = array();
				}
				break;
			case 'CURRENT_PAGE': // @deprecated on 1.12.1
			case 'current_post_or_parent_post_view':
				$force_empty_query = true;
				if ( isset( $taxonomies[$view_settings['taxonomy_type'][0]] ) ) {
					$current_page = apply_filters( 'wpv_filter_wpv_get_current_post', null );
					if ( $current_page ) {
						$terms_to_include_objects = get_the_terms( $current_page->ID, $view_settings['taxonomy_type'][0] );
						if ( is_array( $terms_to_include_objects ) ) {
							$terms_to_include = wp_list_pluck( $terms_to_include_objects, 'term_id' );
						} else {
							$terms_to_include = array();
						}
					} else {
						$terms_to_include = array();
					}
				} else {
					$terms_to_include = array();
				}
				break;
				break;
			case 'THESE':
				if (
					isset( $view_settings['taxonomy_terms'] )
					&& sizeof( $view_settings['taxonomy_terms'] )
				) {
					$force_empty_query = true;
					if (
						isset( $view_settings['taxonomy_type'][0] )
						&& ! empty( $view_settings['taxonomy_terms'] )
					) {
						foreach ( $view_settings['taxonomy_terms'] as $candidate_term_id ) {
							// WordPress 4.2 compatibility - split terms
							$candidate_term_id_splitted = wpv_compat_get_split_term( $candidate_term_id, $view_settings['taxonomy_type'][0] );
							if ( $candidate_term_id_splitted ) {
								$candidate_term_id = $candidate_term_id_splitted;
							}
							// WPML support
							$candidate_term_id = apply_filters( 'translate_object_id', $candidate_term_id, $view_settings['taxonomy_type'][0], true, null );
							$terms_to_include[] = $candidate_term_id;
						}
					}
				}
				break;
			case 'by_url':
				if (
					isset( $view_settings['taxonomy_terms_url'] )
					&& '' != $view_settings['taxonomy_terms_url']
				) {
					$id_parameter = $view_settings['taxonomy_terms_url'];
					if ( isset( $_GET[ $id_parameter ] ) ) {
						$ids_to_load = $_GET[ $id_parameter ];
						if ( is_array( $ids_to_load ) ) {
							if (
								0 != count( $ids_to_load )
								&& '' != $ids_to_load[0]
							) {
								$force_empty_query = true;
								$ids_to_load = array_map( 'trim', $ids_to_load );
								$ids_to_load = array_filter( $ids_to_load, 'is_numeric' );
								foreach ( $ids_to_load as $id_candidate ) {
									$terms_to_include[] = (int) $id_candidate;
								}
							}
						} else {
							$ids_to_load = trim( $ids_to_load );
							if ( '' != $ids_to_load ) {
								$force_empty_query = true;
								if ( is_numeric( $ids_to_load ) ) {
									$terms_to_include[] = (int) $ids_to_load;
								}
							}
						}
					}
				}
				break;
			case 'shortcode':
				if (
					isset( $view_settings['taxonomy_terms_shortcode'] )
					&& '' != $view_settings['taxonomy_terms_shortcode']
				) {
					global $WP_Views;
					$shortcode_attr = $view_settings['taxonomy_terms_shortcode'];
					$view_attrs = $WP_Views->get_view_shortcodes_attributes();
					if (
						isset( $view_attrs[ $shortcode_attr ] )
						&& '' != $view_attrs[ $shortcode_attr ]
					) {
						$force_empty_query = true;
						$ids_to_load = explode( ',', $view_attrs[ $shortcode_attr ] );
						$ids_to_load = array_map( 'trim', $ids_to_load );
						$ids_to_load = array_filter( $ids_to_load, 'is_numeric' );
						foreach ( $ids_to_load as $id_candidate ) {
							$terms_to_include[] = (int) $id_candidate;
						}
					}
				}
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					if (
						isset( $view_settings['taxonomy_terms_framework'] )
						&& '' != $view_settings['taxonomy_terms_framework']
					) {
						$taxonomy_terms_framework = $view_settings['taxonomy_terms_framework'];
						$taxonomy_terms_candidates = $WP_Views_fapi->get_framework_value( $taxonomy_terms_framework, array() );
						if ( ! is_array( $taxonomy_terms_candidates ) ) {
							$taxonomy_terms_candidates = explode( ',', $taxonomy_terms_candidates );
						}
						$taxonomy_terms_candidates = array_map( 'esc_attr', $taxonomy_terms_candidates );
						$taxonomy_terms_candidates = array_map( 'trim', $taxonomy_terms_candidates );
						// is_numeric does sanitization
						$taxonomy_terms_candidates = array_filter( $taxonomy_terms_candidates, 'is_numeric' );
						if ( count( $taxonomy_terms_candidates ) ) {
							$force_empty_query = true;
							if ( isset( $view_settings['taxonomy_type'][0] ) ) {
								foreach ( $taxonomy_terms_candidates as $candidate_term_id ) {
									// WordPress 4.2 compatibility - split terms
									$candidate_term_id_splitted = wpv_compat_get_split_term( $candidate_term_id, $view_settings['taxonomy_type'][0] );
									if ( $candidate_term_id_splitted ) {
										$candidate_term_id = $candidate_term_id_splitted;
									}
									// WPML support
									$candidate_term_id = apply_filters( 'translate_object_id', $candidate_term_id, $view_settings['taxonomy_type'][0], true, null );
									$terms_to_include[] = $candidate_term_id;
								}
							}
						}
					}
				}
				break;
		}
		if (
			$force_empty_query
			&& empty( $terms_to_include )
		) {
			$tax_query_settings['wpv_force_empty_query'] = true;
			$tax_query_settings['wpv_force_empty_query_reason'] = 'filter_taxonomy_term';
		} else {
			$tax_query_settings['include'] = $terms_to_include;
		}
    }

	return $tax_query_settings;
}

/**
* wpv_filter_register_taxonomy_term_shortcode_attributes
*
* Register the filter by taxonomy term on the method to get View shortcode attributes
*
* @since 1.10
*/

add_filter( 'wpv_filter_register_shortcode_attributes_for_taxonomy', 'wpv_filter_register_taxonomy_term_shortcode_attributes', 10, 2 );

function wpv_filter_register_taxonomy_term_shortcode_attributes( $attributes, $view_settings ) {
	if (
		isset( $view_settings['taxonomy_terms_mode'] )
		&& $view_settings['taxonomy_terms_mode'] == 'shortcode'
	) {
		$attributes[] = array(
			'query_type'	=> $view_settings['query_type'][0],
			'filter_type'	=> 'taxonomy_term',
			'filter_label'	=> __( 'Taxonomy term', 'wpv-views' ),
			'value'			=> $view_settings['taxonomy_terms_shortcode'],
			'attribute'		=> $view_settings['taxonomy_terms_shortcode'],
			'expected'		=> 'numberlist',
			'placeholder'	=> '1, 2',
			'description'	=> __( 'Please type a comma separated list of term IDs', 'wpv-views' )
		);
	}
	return $attributes;
}

/**
* wpv_filter_register_taxonomy_term_url_parameters
*
* Register the filter by taxonomy terms on the method to get URL parameters
*
* @since 1.11
*/

add_filter( 'wpv_filter_register_url_parameters_for_taxonomy', 'wpv_filter_register_taxonomy_term_url_parameters', 10, 2 );

function wpv_filter_register_taxonomy_term_url_parameters( $attributes, $view_settings ) {
	if (
		isset( $view_settings['taxonomy_terms_mode'] )
		&& $view_settings['taxonomy_terms_mode'] == 'by_url'
	) {
		$attributes[] = array(
			'query_type'	=> $view_settings['query_type'][0],
			'filter_type'	=> 'taxonomy_term',
			'filter_label'	=> __( 'Taxonomy term', 'wpv-views' ),
			'value'			=> $view_settings['taxonomy_terms_url'],
			'attribute'		=> $view_settings['taxonomy_terms_url'],
			'expected'		=> 'numberlist',
			'placeholder'	=> '1, 2',
			'description'	=> __( 'Please type a comma separated list of term IDs', 'wpv-views' )
		);
	}
	return $attributes;
}
