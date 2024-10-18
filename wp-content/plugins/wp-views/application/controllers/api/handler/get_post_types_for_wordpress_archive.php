<?php

/**
 * Handler for the wpv_get_post_types_for_wordpress_archive filter API.
 *
 * @since 2.8
 */
class WPV_Api_Handler_Get_Post_Types_For_Wordpress_Archive implements WPV_Api_Handler_Interface {

	public function __construct() { }

	/**
	 * @return array
	 *
	 * @since 2.8
	 */
	function process_call( $arguments ) {

		$default_return_value = toolset_getarr( $arguments, 0, array() );
		$wpa_id = toolset_getarr( $arguments, 1 );

		if ( 1 > (int) $wpa_id  ) {
			return $default_return_value;
		}

		$settings = WPV_Settings::get_instance();
		$loop_in_use = $settings->get_key_by_value( $wpa_id );

		if ( false === $loop_in_use ) {
			return $default_return_value;
		}

		$wpv_post_types_for_archive_loop = $settings->wpv_post_types_for_archive_loop;

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		// Native archive loops
		$post_types_in_search = wp_list_filter( $post_types, array( 'exclude_from_search' => 1 ), 'NOT' );
		$wpv_post_types_for_archive_loop['native'] = isset( $wpv_post_types_for_archive_loop['native'] ) ? $wpv_post_types_for_archive_loop['native'] : array();
		$loops = array(
			'home-blog-page' => array(
				'post_type' => array( 'post' ),
				'name' => 'home',
			),
			'search-page' => array(
				'post_type' => array_keys( $post_types_in_search ),
				'name' => 'search',
			),
			'author-page' => array(
				'post_type' => array( 'post' ),
				'name' => 'author',
			),
			'year-page' => array(
				'post_type' => array( 'post' ),
				'name' => 'year',
			),
			'month-page' => array(
				'post_type' => array( 'post' ),
				'name' => 'month',
			),
			'day-page' => array(
				'post_type' => array( 'post' ),
				'name' => 'day',
			),
		);

		$post_types_included = array();
		foreach ( $loops as $loop_key => $loop_data ) {
			if ( 'view_' . $loop_key === $loop_in_use ) {
				if (
					isset( $wpv_post_types_for_archive_loop['native'][ $loop_data['name'] ] )
					&& ! empty( $wpv_post_types_for_archive_loop['native'][ $loop_data['name'] ] )
				) {
					foreach ( $wpv_post_types_for_archive_loop['native'][ $loop_data['name'] ] as $included_post_type ) {
						if ( isset( $post_types[ $included_post_type ] ) ) {
							$post_types_included[] = $included_post_type;
						}
					}
				} else {
					foreach ( $loop_data['post_type'] as $included_post_type ) {
						if ( isset( $post_types[ $included_post_type ] ) ) {
							$post_types_included[] = $included_post_type;
						}
					}
				}

				return $post_types_included;
			}
		}

		// Post type archives
		if ( strpos( $loop_in_use, 'view_cpt_' ) === 0 ) {
			$post_type_archive_candidate = substr( $loop_in_use, 9 );
			if ( isset( $post_types[ $post_type_archive_candidate ] ) ) {
				return array( $post_type_archive_candidate );
			}

			return $default_return_value;
		}

		// Taxonomy archives
		if ( strpos( $loop_in_use, 'view_taxonomy_loop_' ) === 0 ) {
			$taxonomies = get_taxonomies( '', 'objects' );
			$taxonomy_archive_candidate = substr( $loop_in_use, 19 );

			if ( ! isset( $taxonomies[ $taxonomy_archive_candidate ] ) ) {
				return $default_return_value;
			}

			$post_types_included = array();

			$taxonomy_archive = $taxonomies[ $taxonomy_archive_candidate ];
			if (
				isset( $wpv_post_types_for_archive_loop['taxonomy'][ $taxonomy_archive_candidate ] )
				&& ! empty( $wpv_post_types_for_archive_loop['taxonomy'][ $taxonomy_archive_candidate ] )
			) {
				foreach ( $wpv_post_types_for_archive_loop['taxonomy'][ $taxonomy_archive_candidate ] as $included_post_type ) {
					if ( isset( $post_types[ $included_post_type ] ) ) {
						$post_types_included[] = $included_post_type;
					}
				}
			} else {
				if ( in_array( $taxonomy_archive_candidate, array( 'category', 'post_tag' ) ) ) {
					$types_cpt = get_option( 'wpcf-custom-types' );
					$types_cpt = toolset_ensarr( $types_cpt );
					$types_cpt_for_native = array(
						'category'	=> array( 'post' ),
						'post_tag'	=> array( 'post' )
					);

					foreach ( $types_cpt as $cpt_slug => $cpt ) {
						if (
							array_key_exists( 'taxonomies', $cpt )
							&& is_array( $cpt['taxonomies'] )
						) {
							foreach ( $cpt['taxonomies'] as $tax_slug => $value ) {
								if (
									'category' == $tax_slug
									&& $value
								) {
									$types_cpt_for_native['category'][] = $cpt_slug;
								}
								if (
									'post_tag' == $tax_slug
									&& $value
								) {
									$types_cpt_for_native['post_tag'][] = $cpt_slug;
								}
							}
						}
					}
					$post_types_candidtes_included = $types_cpt_for_native[ $taxonomy_archive_candidate ];
				} else {
					$post_types_candidtes_included = $taxonomy_archive->object_type;
				}


				foreach ( $post_types_candidtes_included as $included_post_type ) {
					if ( isset( $post_types[ $included_post_type ] ) ) {
						$post_types_included[] = $included_post_type;
					}
				}
			}

			return $post_types_included;
		}

		return $default_return_value;
	}

}
