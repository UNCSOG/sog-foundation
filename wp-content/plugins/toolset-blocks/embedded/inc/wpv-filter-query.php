<?php

/**
* WPV_View_Post_Query
*
* Views Post Query class
*
* @since 2.1
*/

class WPV_View_Post_Query {

	/**
	* @var WPV_View_Post_Query Instance of WPV_View_Post_Query.
	*/

	private static $instance = null;

	/**
	* @return WPV_View_Post_Query The instance of WPV_View_Post_Query.
	*/
	public static function get_instance() {
		if ( null == WPV_View_Post_Query::$instance ) {
			WPV_View_Post_Query::$instance = new WPV_View_Post_Query();
		}
		return WPV_View_Post_Query::$instance;
	}

	public static function clear_instance() {
		if ( WPV_View_Post_Query::$instance ) {
			WPV_View_Post_Query::$instance = null;
		}
	}

	function __construct() {

		/**
		* WordPress fixes
		*/

		add_filter( 'wpv_filter_query',					array( $this, 'wpv_filter_query_post_in_and_not_in_fix' ), 999, 3 );

		/**
		* Extensibility
		*/

		add_filter( 'wpv_filter_query_post_process',						array( $this, 'wpv_filter_extend_query_for_parametric_and_counters' ), 999, 3 );
		add_action( 'wpv_action_extend_query_for_parametric_and_counters',	array( $this, 'wpv_filter_extend_query_for_parametric_and_counters' ), 10, 3 );

		add_filter( 'wpv_filter_wpv_get_dependant_extended_query_args',		array( $this, 'wpv_get_dependant_view_query_args' ), 10, 2 );

		/**
		* AJAX pagination
		*/

		add_action( 'wp_ajax_wpv_get_view_query_results',					array( $this, 'wpv_get_view_query_results' ) );
		add_action( 'wp_ajax_nopriv_wpv_get_view_query_results',			array( $this, 'wpv_get_view_query_results' ) );

		add_filter( 'wpv_filter_wpv_get_current_archive_loop',				array( $this, 'wpv_set_posted_archive_loop' ) );

	}

	/**
	* wpv_filter_query_post_in_and_not_in_fix
	*
	* WP_Query can not manage post__in and post__not_in args at the same time.
	*
	* @since unknown
	* @since 2.1		Moved to a method
	*/

	function wpv_filter_query_post_in_and_not_in_fix( $query, $view_settings, $view_id ) {

		if (
			isset( $query['post__in'] )
			&& isset( $query['post__not_in'] )
		) {
			$query['post__in'] = array_diff( (array) $query['post__in'], (array) $query['post__not_in'] );
			$query['post__in'] = array_values( $query['post__in'] );
			unset( $query['post__not_in'] );
			if ( empty( $query['post__in'] ) ) {
				$query['post__in'] = array( '0' );
			}
		}

		return $query;
	}

	/**
	* wpv_filter_extend_query_for_parametric_and_counters
	*
	* Creates the additional cached data for parametric search dependency and counters.
	* This is also used by the wpv_action_extend_query_for_parametric_and_counters action.
	*
	* @uses WP_Query
	* @uses WPV_Cache::generate_cache
	*
	* @since 1.6
	* @since 2.1	Moved to a method
	*/

	function wpv_filter_extend_query_for_parametric_and_counters( $post_query, $view_settings, $id ) {
		$dps_enabled = false;
		$counters_enabled = false;
		if (
			! isset( $view_settings['dps'] )
			|| ! is_array( $view_settings['dps'] )
		) {
			$view_settings['dps'] = array();
		}
		if (
			isset( $view_settings['dps']['enable_dependency'] )
			&& $view_settings['dps']['enable_dependency'] == 'enable'
		) {
			$dps_enabled = true;
			$controls_per_kind = wpv_count_filter_controls( $view_settings );
			$controls_count = 0;
			$no_intersection = array();
			if ( ! isset( $controls_per_kind['error'] ) ) {
				$controls_count = $controls_per_kind['cf'] + $controls_per_kind['tax'] + $controls_per_kind['pr'] + $controls_per_kind['search'];
				if (
					$controls_per_kind['cf'] > 1
					&& (
						! isset( $view_settings['custom_fields_relationship'] )
						|| $view_settings['custom_fields_relationship'] != 'AND'
					)
				) {
					$no_intersection[] = __( 'custom field', 'wpv-views' );
				}
				if (
					$controls_per_kind['tax'] > 1
					&& (
						! isset( $view_settings['taxonomy_relationship'] )
						|| $view_settings['taxonomy_relationship'] != 'AND'
					)
				) {
					$no_intersection[] = __( 'taxonomy', 'wpv-views' );
				}
			} else {
				$dps_enabled = false;
			}
			if ( $controls_count > 0 ) {
				if ( count( $no_intersection ) > 0 ) {
					$dps_enabled = false;
				}
			} else {
				$dps_enabled = false;
			}
		}
		if ( ! isset( $view_settings['filter_meta_html'] ) ) {
			$view_settings['filter_meta_html'] = '';
		}
		if ( strpos( $view_settings['filter_meta_html'], '%%COUNT%%' ) !== false ) {
			$counters_enabled = true;
		}

		global $WP_Views;
		if (
			! $dps_enabled
			&& ! $counters_enabled
		) {
			// Set the force value
			$WP_Views->set_force_disable_dependant_parametric_search( true );
			return $post_query;
		}

		// In any case, we need to mimic the process that we used to generate the $query
		$view_settings_defaults = array(
			'post_type'         => 'any',
			'orderby'           => 'post-date',
			'order'             => 'DESC',
			'paged'             => '1',
		);
		extract( $view_settings_defaults );

		$view_settings['view_id'] = $id;
		extract( $view_settings, EXTR_OVERWRITE );

		$query = array(
			'paged'             	=> $paged,
			'post_type'         	=> $post_type,
			'order'             	=> $order,
			'suppress_filters'  	=> false,
			'ignore_sticky_posts' 	=> true,
			// Disable ElasticPress for this query.
			'ep_integrate' => false,
		);
		// Add special check for media (attachments) as their default status in not usually published
		if (
			sizeof( $post_type ) == 1
			&& $post_type[0] == 'attachment'
		) {
			$query['post_status'] = 'any'; // Note this can be overriden by adding a status filter.
		}

		$query = apply_filters( 'wpv_filter_query', $query, $view_settings, $id );

		// Now we have the $query as in the original one
		// We now need to overwrite the limit, offset, paged and pagination options
		// Also, we set it to just return the IDs
		$query['posts_per_page'] 	= -1;
		$query['limit'] 			= -1;
		$query['paged'] 			= 1;
		$query['offset'] 			= 0;
		$query['fields'] 			= 'ids';

		$already = array();
		if (
			isset( $post_query->posts )
			&& ! empty( $post_query->posts )
		) {
			foreach ( (array) $post_query->posts as $post_object ) {
				$already[] = $post_object->ID;
			}
		}
		$WP_Views->returned_ids_for_parametric_search = $already;

		$parametric_search_data_to_cache = WPV_Cache::get_parametric_search_data_to_cache( $view_settings );

		WPV_Cache::generate_native_cache( $already, $parametric_search_data_to_cache );

		// Adjust $query to avoid already queried posts
		if ( isset ( $query['pr_filter_post__in'] ) ) {
			// There is a filter by relationship,
			// we should only cache the ones that match this filter regardless other conditions.
			$query['post__in'] = $query['pr_filter_post__in'];
		} else {
			// If querying just the missing ones, generate a post__not_in argument with the original results we already managed.
			if ( isset( $query['post__not_in'] ) ) {
				$query['post__not_in'] = array_merge( (array) $query['post__not_in'], (array) $already );
			} else {
				$query['post__not_in'] = (array) $already;
			}
			// And maybe adjust on the post__in argument in case it already existed,
			// which means that we had a hard post__in statement already.
			// In this case, the post__not_in statement is ignored by WP_Query altogether.
			// Anyway, make sure that diffs between forced in and forced out can produce empty queries.
			if ( isset( $query['post__in'] ) ) {
				$query['post__in'] = array_diff( (array) $query['post__in'], (array) $query['post__not_in'] );
				if ( empty( $query['post__in'] ) ) {
					$query['post__in'] = array( '0' );
				}
			}
		}

		/**
		 * Before performing the auxiliar query for calculating avalable form inputs, or calculate counters.
		 *
		 * @param array $query
		 * @since 2.9
		 */
		do_action( 'wpv_action_wpv_before_extended_view_query_for_parametric_and_counters', $query );

		// Perform the query
		$aux_cache_query = new WP_Query( $query );

		/**
		 * After performing the auxiliar query for calculating avalable form inputs, or calculate counters.
		 *
		 * @param array $query
		 * @param WP_Query $aux_cache_query
		 * @since 2.9
		 */
		do_action( 'wpv_action_wpv_after_extended_view_query_for_parametric_and_counters', $query, $aux_cache_query );

		// Add the auxiliar query results to the list of returned IDs
		// Generate the "extra" cache
		if (
			is_array( $aux_cache_query->posts )
			&& ! empty( $aux_cache_query->posts )
		) {
			$WP_Views->returned_ids_for_parametric_search = array_merge( $WP_Views->returned_ids_for_parametric_search, $aux_cache_query->posts );
			$WP_Views->returned_ids_for_parametric_search = array_unique( $WP_Views->returned_ids_for_parametric_search );
			WPV_Cache::generate_cache( $aux_cache_query->posts, $parametric_search_data_to_cache );
		}

		return $post_query;
	}

	/**
	* -------------------------
	* AJAX pagination
	* -------------------------
	*/

	function wpv_get_view_query_results() {
		global $post;

		$view_id		= ( $_POST['wpv_view_widget_id'] == 0 ) ? (int) $_POST['id'] : (int) $_POST['wpv_view_widget_id'];
		$page			= (int) $_POST['page'];
		$sort			= isset( $_POST['sort'] ) ? $_POST['sort'] : array();
		$environment	= isset( $_POST['environment'] ) ? $_POST['environment'] : array();
		$search			= isset( $_POST['search'] ) ? $_POST['search'] : array();
		$search_keys	= array();
		$extra			= isset( $_POST['extra'] ) ? $_POST['extra'] : array();
		$attributes		= isset( $_POST['attributes'] ) ? $_POST['attributes'] : array();

		$_GET['wpv_view_count']	= sanitize_text_field( $_POST['view_number'] );
		$_GET['wpv_paged']		= $page;

		foreach ( $sort as $sort_key => $sort_value ) {
			if ( in_array( $sort_key, array( 'wpv_sort_orderby', 'wpv_sort_order', 'wpv_sort_orderby_as', 'wpv_sort_orderby_second', 'wpv_sort_order_second' ) ) ) {
				$_GET[ $sort_key ] = sanitize_text_field( $sort_value );
			}
		}

		foreach ( $environment as $environment_key => $environment_value ) {
			if (
				in_array( $environment_key, array( 'wpv_aux_current_post_id', 'wpv_aux_parent_post_id', 'wpv_aux_parent_term_id', 'wpv_aux_parent_user_id' ) )
				&& (int) $environment_value > 0
			) {
				$search_keys[] = $environment_key;
				$_GET[ $environment_key ] = (int) $environment_value;
				switch ( $environment_key ) {
					case 'wpv_aux_current_post_id':
						$top_post_id = (int) $environment_value;
						$top_post = get_post( $top_post_id );
						do_action( 'wpv_action_wpv_set_top_current_post', $top_post );
						break;
					case 'wpv_aux_parent_post_id':
						global $authordata, $id;
						$post_id = (int) $environment_value;
						$post = get_post( $post_id );
						$authordata = new WP_User( $post->post_author );
						$id = $post->ID;
						do_action( 'wpv_action_wpv_set_current_post', $post );
						break;
					case 'wpv_aux_parent_term_id':
						do_action( 'wpv_action_wpv_set_parent_view_taxonomy', (int) $environment_value );
						break;
					case 'wpv_aux_parent_user_id':
						do_action( 'wpv_action_wpv_set_parent_view_user', (int) $environment_value );
						break;
				}
			}
		}

		if ( isset( $search['dps_general'] ) ) {
			$corrected_item = array();
			foreach ( $search['dps_general'] as $dps_pr_item ) {
				if (
					is_array( $dps_pr_item )
					&& isset( $dps_pr_item['name'] )
					&& isset( $dps_pr_item['value'] )
				) {
					if ( strlen( $dps_pr_item['name'] ) < 2 ) {
						$search_keys[] = $dps_pr_item['name'];
						$_GET[ $dps_pr_item['name'] ] = sanitize_text_field( wp_unslash( $dps_pr_item['value'] ) );
					} else {
						if ( strpos( $dps_pr_item['name'], '[]' ) === strlen( $dps_pr_item['name'] ) - 2 ) {
							$name = str_replace( '[]', '', $dps_pr_item['name'] );
							$search_keys[] = $name;
							if ( ! in_array( $name, $corrected_item ) ) {
								$corrected_item[] = $name;
								if ( isset( $_GET[ $name ] ) ) {
									unset( $_GET[ $name ] );
								}
							}
							if ( ! isset( $_GET[ $name ] ) ) {
								$_GET[ $name ] = array( sanitize_text_field( wp_unslash( $dps_pr_item['value'] ) ) );
							} else if ( is_array( $_GET[ $name ] ) ) {
								$_GET[ $name ][] = sanitize_text_field( wp_unslash( $dps_pr_item['value'] ) );
							}
						} else {
							$search_keys[] = $dps_pr_item['name'];
							$_GET[ $dps_pr_item['name'] ] = sanitize_text_field( wp_unslash( $dps_pr_item['value'] ) );
						}
					}
				}
			}
		}

		if ( isset( $search['dps_pr'] ) ) {
			foreach ( $search['dps_pr'] as $dps_pr_item ) {
				if (
					is_array( $dps_pr_item )
					&& isset( $dps_pr_item['name'] )
					&& isset( $dps_pr_item['value'] )
				) {
					if ( strlen( $dps_pr_item['name'] ) < 2 ) {
						if ( ! isset( $_GET[ $dps_pr_item['name'] ] ) ) {
							$search_keys[] = $dps_pr_item['name'];
							$_GET[ $dps_pr_item['name'] ] = sanitize_text_field( wp_unslash( $dps_pr_item['value'] ) );
						}
					} else {
						if ( strpos( $dps_pr_item['name'], '[]' ) === strlen( $dps_pr_item['name'] ) - 2 ) {
							$name = str_replace( '[]', '', $dps_pr_item['name'] );
							$search_keys[] = $name;
							if ( ! isset( $_GET[ $name ] ) ) {
								$_GET[ $name ] = array( sanitize_text_field( wp_unslash( $dps_pr_item['value'] ) ) );
							} else if ( is_array( $_GET[$name] ) ) {
								$_GET[ $name ][] = sanitize_text_field( wp_unslash( $dps_pr_item['value'] ) );
							}
						} else {
							if ( ! isset( $_GET[ $dps_pr_item['name'] ] ) ) {
								$search_keys[] = $dps_pr_item['name'];
								$_GET[ $dps_pr_item['name'] ] = sanitize_text_field( wp_unslash( $dps_pr_item['value'] ) );
							}
						}
					}
				}
			}
		}


		foreach ( $extra as $extra_key => $extra_value ) {
			if ( ! in_array( $extra_key, $search_keys ) ) {
				if ( ! isset( $_GET[ $extra_key ] ) ) { // Might be redundant with the check on $search_keys
					// @hack alert!! We can not avoid this :-(
					if ( strpos( $extra_value, '##URLARRAYVALHACK##' ) !== false ) {
						$_GET[ $extra_key ] = explode( '##URLARRAYVALHACK##', $extra_value );
						$_GET[ $extra_key ] = array_map( 'sanitize_text_field', $_GET[ $extra_key ] );
					} else {
						$_GET[ $extra_key ] = sanitize_text_field( $extra_value );
					}
				}
			}
		}

		if ( toolset_getpost( 'lang', false ) ) {
			do_action( 'wpml_switch_language', sanitize_text_field( toolset_getpost( 'lang', '' ) ) );
		}

		$view_settings			= apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id );

		// Sometimes, the global $post is not set. We need to fill it.
		// Otherwise, Content Templates used on Views loops for taxonomies or users will not work.
		if ( ! isset( $post ) ) {
			// First, check whether we do have a top current post and use it if available.
			// This is important because shortcodes outside the View loop should get the top current post as their global when not dealing with a nested structure.
			// Otherwise, a dummy one will work
			$top_current_post = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
			if ( $top_current_post ) {
				$post = $top_current_post;
			} else {

				$registered_post_types = get_post_types( array(), 'names' );
				$dummy_post_type_counter = 0;
				$dummy_post_type_base = 'view-dummy';
				$dummy_post_type = 'view-dummy';

				while ( in_array( $dummy_post_type, $registered_post_types ) ) {
					$dummy_post_type_counter = $dummy_post_type_counter + 1;
					$dummy_post_type = $dummy_post_type_base . '-' . $dummy_post_type_counter;
				}

				$post = get_post( $view_id );
				$post->post_type = $dummy_post_type;

			}

		}

		if ( sanitize_text_field( $_POST['wpv_view_widget_id'] ) == 0 ) {
			// set the view count so we return the right view number after rendering.
			/*
			$WP_Views->set_view_count( intval( esc_attr( $post_data['view_number'] ) ), $view_id );
			echo $WP_Views->short_tag_wpv_view( $view_data );
			*/

			$args = array(
				'id' => $view_id
			);

			if ( isset( $_POST['target_id'] ) ) {
				$args['target_id'] = $_POST['target_id'];
			}

			$args = array_merge( $args, $attributes );

			$expect = isset( $_POST['expect'] ) ? $_POST['expect'] : 'full';

			$data = array(
				'id'	=> $view_id
			);

			if ( $expect == 'form' ) {
				if ( isset( $args['target_id'] ) ) {
					$data['form'] = render_view( $args );
				}
				$data['full'] = '';
			} else if ( $expect == 'full' ) {
				$data['form'] = '';
				if ( isset( $args['target_id'] ) ) {
					unset( $args['target_id'] );
				}
				$data['full'] = render_view( $args );
			} else if ( $expect == 'both' ) {
				if ( isset( $args['target_id'] ) ) {
					$data['form'] = render_view( $args );
					unset( $args['target_id'] );
				}
				$data['full'] = render_view( $args );
			} else {
				$data['form'] = '';
				$data['full'] = '';
			}
		} else {
			// set the view count so we return the right view number after rendering.
			//$WP_Views->set_view_count( (int) esc_attr( $post_data['view_number'] ), $view_id );
			$expect		= isset( $_POST['expect'] ) ? $_POST['expect'] : 'full';

			$data		= array(
				'id'	=> $view_id,
				'form'	=> '',
				'full'	=> ''
			);

			$args		= array(
				'id' => $view_id
			);
			if ( isset( $_POST['target_id'] ) ) {
				$args['target_id'] = $_POST['target_id'];
			}
			$args		= array_merge( $args, $attributes );

			$widget_args = array(
				'before_widget' => '',
				'before_title' => '',
				'after_title' => '',
				'after_widget' => ''
			);

			if ( $expect == 'form' ) {
				if ( isset( $args['target_id'] ) ) {
					$widget_form = new WPV_Widget_filter();
					ob_start();
					$widget_form->widget(
						$widget_args,
						array(
							'title'		=> '',
							'view'		=> $view_id,
							'target_id'	=> $args['target_id']
						)
					);
					$data['form'] = ob_get_clean();
				}
			} else if ( $expect == 'full' ) {
				$widget = new WPV_Widget();
				ob_start();
				$widget->widget(
					$widget_args,
					array(
						'title' => '',
						'view' => $view_id
					)
				);
				$data['full'] = ob_get_clean();
			} else if ( $expect == 'both' ) {
				if ( isset( $args['target_id'] ) ) {
					$widget_form = new WPV_Widget_filter();
					ob_start();
					$widget_form->widget(
						$widget_args,
						array(
							'title'		=> '',
							'view'		=> $view_id,
							'target_id'	=> $args['target_id']
						)
					);
					$data['form'] = ob_get_clean();
				}
				$widget = new WPV_Widget();
				ob_start();
				$widget->widget(
					$widget_args,
					array(
						'title' => '',
						'view' => $view_id
					)
				);
				$data['full'] = ob_get_clean();
			} else {
				$data['form'] = '';
				$data['full'] = '';
			}
		}

		/**
		* A little hacky
		*
		* To calculate the View hash we need to set the View shortcode attributes, but we remove them when closing render_view(),
		* so we rstore them here
		*/

		do_action( 'wpv_action_wpv_set_view_shortcodes_attributes', $attributes );

		/**
		 * By the time the "wpv_filter_wpv_get_pagination_permalinks" filter is called, the "current_view" property of
		 * the global $WP_Views is set to NULL, as it only gets a proper value while the View is being rendered,
		 * and the URL is generated slightly later.
		 *
		 * We are setting the current View property here, and we don't expect any side effects as the script execution is
		 * terminated right after the AJAX call dies.
		 */
		do_action( 'wpv_action_wpv_set_current_view', $view_id );

		$pagination_permalinks	= apply_filters( 'wpv_filter_wpv_get_pagination_permalinks', array(), $view_settings, $view_id );
		if ( $page == 1 ) {
			$pagination_permalink = $pagination_permalinks['first'];
		} else {
			$pagination_permalink = str_replace( 'WPV_PAGE_NUM', $page, $pagination_permalinks['other'] );
		}

		// For parametric search URL history management
		$data['permalink']				= $pagination_permalink;
		// In theory, this is only used by parametric search, so we should always use the 'first' one above.
		$data['parametric_permalink']	= $pagination_permalink;

		if ( ! wpv_parametric_search_triggers_history( $view_id) ) {
			// When parametric search does not manage history, we need to clean the URL.
			$view_url_data					= get_view_allowed_url_parameters( $view_id );
			$query_args_remove				= wp_list_pluck( $view_url_data, 'attribute' );
			foreach ( $query_args_remove as $query_args_remove_string ) {
				$query_args_remove[] = $query_args_remove_string . '[]';
			}
			$query_args_remove[]			= 'wpv_sort_orderby';
			$query_args_remove[]			= 'wpv_sort_order';
			$query_args_remove[]			= 'wpv_sort_orderby_as';
			$query_args_remove[]			= 'wpv_sort_orderby_second';
			$query_args_remove[]			= 'wpv_sort_order_second';
			$query_args_remove[]			= 'wpv_aux_current_post_id';
			$query_args_remove[]			= 'wpv_aux_parent_post_id';
			$query_args_remove[]			= 'wpv_aux_parent_term_id';
			$query_args_remove[]			= 'wpv_aux_parent_user_id';
			$query_args_remove[]			= 'wpv_view_count';
			$data['parametric_permalink']	= remove_query_arg(
				$query_args_remove,
				$pagination_permalink
			);
		}

		if ( did_action( 'wp_playlist_scripts' ) ) {
			// The View outcome has a playlist shortcode:
			// let's include the playlist templates in the response.
			ob_start();
			wp_underscore_playlist_templates();
			$data['playlist_templates'] = ob_get_clean();
		}

		wp_send_json_success( $data );

	}

	function wpv_get_dependant_view_query_args( $args = array(), $view_settings = array() ) {
		if (
			isset( $view_settings['view-query-mode'] )
			&& $view_settings['view-query-mode'] == 'normal'
		) {
			// In any case, we need to mimic the process that we used to generate the $args
			$view_settings_defaults = array(
				'post_type'         => 'any',
				'orderby'           => 'post-date',
				'order'             => 'DESC',
				'paged'             => '1',
			);
			extract( $view_settings_defaults );

			$id = array_key_exists( 'view_id', $view_settings )
				? $view_settings['view_id']
				: apply_filters( 'wpv_filter_wpv_get_current_view', null );

			$view_settings['view_id'] = $id;
			extract( $view_settings, EXTR_OVERWRITE );

			$args = array(
				'paged'           	 	=> $paged,
				'post_type'     	    => $post_type,
				'order'         	    => $order,
				'suppress_filters'		=> false,
				'ignore_sticky_posts'	=> true
			);
			// Add special check for media (attachments) as their default status in not usually published
			if (
				sizeof( $post_type ) == 1
				&& $post_type[0] == 'attachment'
			) {
				$args['post_status'] = 'any'; // Note this can be overriden by adding a status filter.
			}

			// !IMPORTANT override the sorting options (not important here), sorting by a custom field breaks everything, so revert to post_date
			$view_settings['orderby']	= 'ID';
			$view_settings['order']		= 'DESC';

			$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );
			$args = apply_filters( 'wpv_filter_query', $args, $view_settings, $id );

			// Now we have the $args as in the original one
			// We now need to overwrite the limit, offset, paged and pagination options
			//Also,we set it to just return the IDs
			$args['posts_per_page'] 	= -1;
			$args['limit'] 				= -1;
			$args['paged'] 				= 1;
			$args['offset'] 			= 0;
			$args['fields'] 			= 'ids';
		}
		return $args;
	}

	/**
	* wpv_set_posted_archive_loop
	*
	* Set data for the current archive loop passed by the AJAX pagination.
	*
	* When doing AJAX pagination, some query filters demand the current archive data.
	* We can not recreate the global archive query, but we can get the relevant information, crafted in WPV_WordPress_Archive_Frontend::archive_set on the first page load.
	* Then, this data will be used in the parametric['environment']['archive'] data attribute on the View form.
	*
	* @param $current_archive_loop array
	* 		type:	string	'native'|'post_type'|'taxonomy' The type of archive.
	* 		name:	string	'{post_type}'|'{taxonomy}'|'home'|'search'|'author'|'year'|'month'|'day' The name of the queried object.
	* 		data:	array	The extra data about the queried object, different keys depending on different types.
	*
	* since 2.2
	*/

	function wpv_set_posted_archive_loop( $current_archive_loop = array() ) {
		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			&& isset( $_REQUEST['action'] )
			&& $_REQUEST['action'] == 'wpv_get_view_query_results'
			&& isset( $_POST['environment']['archive'] )
		) {
			$current_archive_loop = array(
				'type'	=> isset( $_POST['environment']['archive']['type'] ) ? sanitize_text_field( $_POST['environment']['archive']['type'] ) : '',
				'name'	=> isset( $_POST['environment']['archive']['name'] ) ? sanitize_text_field( $_POST['environment']['archive']['name'] ) : '',
				'data'	=> isset( $_POST['environment']['archive']['data'] ) ? $_POST['environment']['archive']['data'] : array(),
			);
		}
		return $current_archive_loop;
	}

}

$WPV_View_Post_Query = WPV_View_Post_Query::get_instance();

/**
* wpv_filter_get_posts
*
* Create the query to return the posts based on the View settings
*
* @param $id (integer) The View ID
*
* @return $post_query (object) WP_Query instance
*
* @since unknown
*
* @todo remove extract() calls
*/

function wpv_filter_get_posts( $id ) {
    global $post, $WPVDebug;

	$view_settings_defaults = array(
		'post_type'         => 'any',
		'paged'             => '1',
	);
	extract( $view_settings_defaults );

	$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $id );
	$view_settings['view_id'] = $id;

	extract( $view_settings, EXTR_OVERWRITE );

	// Let URL pagination parameters set the page
	if (
		isset( $_GET['wpv_paged'] )
		&& isset( $_GET['wpv_view_count'] )
		&& esc_attr( $_GET['wpv_view_count'] ) == apply_filters( 'wpv_filter_wpv_get_view_unique_hash', '' )
	) {
		$paged = intval( esc_attr( $_GET['wpv_paged'] ) );
	}

    $query = array(
		'post_type'				=> $post_type,
		'paged'					=> $paged,
		'suppress_filters'		=> false,
		'ignore_sticky_posts'	=> true,
		// Disable ElasticPress for this query.
		'ep_integrate' => false,
    );

	// Add special check for media (attachments) as their default status in not usually published
	if (
		sizeof( $post_type ) == 1
		&& $post_type[0] == 'attachment'
	) {
		$query['post_status'] = 'any'; // Note this can be overriden by adding a status filter.
	}

	$WPVDebug->add_log( 'info', apply_filters( 'wpv-view-get-content-summary', '', $id, $view_settings ), 'short_query' );

	$WPVDebug->add_log( 'info', "Basic query arguments\n". print_r( $query, true ), 'query_args' );

	/**
	 * Adjust the View settings just before the main Views query.
	 *
	 * @param array $view_Settings
	 * @return $view_settings
	 * @since 3.0
	 */
	$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );

	/**
	* Filter wpv_filter_query
	*
	* This is where all the filters coming from the View settings to modify the query are hooked
	*
	* @param $query the Query arguments as in WP_Query
	* @param $view_settings the View settings
	* @param $id the ID of the View being displayed
	*
	* @return $query
	*
	* @since unknown
	*/
    $query = apply_filters( 'wpv_filter_query', $query, $view_settings, $id );

    $WPVDebug->add_log( 'filters', "wpv_filter_query\n" . print_r( $query, true ), 'filters', 'Filter arguments before the query using <strong>wpv_filter_query</strong>' );

	// Flag queries happening for Views.
	$query['wpv_query'] = true;

    $post_query = new WP_Query( $query );

	$WPVDebug->add_log( 'mysql_query', $post_query->request , 'posts', '', true );

	$WPVDebug->add_log( 'info', print_r( $post_query, true ), 'query_results', '', true );

	toolset_wplog( $post_query->query, 'debug', __FILE__, 'wpv_filter_get_posts', 98 );
	toolset_wplog( $post_query->request, 'debug', __FILE__, 'wpv_filter_get_posts', 99 );

	/**
	* Filter wpv_filter_query_post_process
	*
	* This is applied to the results of the main query.
	*
	* @param $post_query the queried object returned by the WordPress WP_Query()
	* @param $view_settings the View settings
	* @param $id the ID of the View being displayed
	*
	* @return $post_query
	*
	* @since unknown
	*/

    $post_query = apply_filters( 'wpv_filter_query_post_process', $post_query, $view_settings, $id );

    $WPVDebug->add_log( 'filters', "wpv_filter_query_post_process\n" . print_r( $post_query, true ), 'filters', 'Filter the returned query using <strong>wpv_filter_query_post_process</strong>' );

    return $post_query;
}
