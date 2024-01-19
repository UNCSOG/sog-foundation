<?php

/**
 * Author frontend filter
 *
 * @package Views
 *
 * @since 2.1.0
 * @since 2.4.0 Prepare a custom search frontend filter.
 */

WPV_Author_Frontend_Filter::on_load();

/**
 * WPV_Author_Filter
 *
 * Views Author Filter Frontend Class
 *
 * @since 2.1
 */

class WPV_Author_Frontend_Filter {

	static function on_load() {
		// Apply frontend filter by post author
        add_filter( 'wpv_filter_query',										array( 'WPV_Author_Frontend_Filter', 'filter_post_author' ), 13, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_Author_Frontend_Filter', 'archive_filter_post_author' ), 40, 3 );
		// Auxiliar methods for requirements
		add_filter( 'wpv_filter_requires_current_page',						array( 'WPV_Author_Frontend_Filter', 'requires_current_page' ), 20, 2 );
		add_filter( 'wpv_filter_requires_parent_post',						array( 'WPV_Author_Frontend_Filter', 'requires_parent_post' ), 20, 2 );
		add_filter( 'wpv_filter_requires_current_user',						array( 'WPV_Author_Frontend_Filter', 'requires_current_user' ), 20, 2 );
		add_filter( 'wpv_filter_requires_framework_values',					array( 'WPV_Author_Frontend_Filter', 'requires_framework_values' ), 20, 2 );
		add_filter( 'wpv_filter_requires_parent_user',						array( 'WPV_Author_Frontend_Filter', 'requires_parent_user' ), 20, 2 );
		// Auxiliar methods for gathering data
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts',	array( 'WPV_Author_Frontend_Filter', 'shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts',			array( 'WPV_Author_Frontend_Filter', 'url_parameters' ), 10, 2 );
		// Frontend custom search filter shortcodes
		add_shortcode( 'wpv-control-post-author',							array( 'WPV_Author_Frontend_Filter', 'wpv_shortcode_wpv_control_post_author' ) );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data',					array( 'WPV_Author_Frontend_Filter', 'wpv_shortcodes_register_wpv_control_post_author_data' ) );
    }

	/**
	* filter_post_author
	*
	* Apply the filter by post author on Views.
	*
	* @since unknown
	*/

	static function filter_post_author( $query, $view_settings, $view_id ) {
		if ( isset( $view_settings['author_mode'][0] ) ) {
			$show_author_array = WPV_Author_Frontend_Filter::get_settings( $query, $view_settings, $view_id );
			if ( isset( $show_author_array ) ) { // only modify the query if the URL parameter is present and not empty
				if ( count( $show_author_array ) > 0 ) {
					// $query['author'] must be a string like 'id1,id2,id3'
					// because we're using &get_posts() to run the query
					// and it doesn't accept an array as author parameter
					$show_author_list = implode( ",", $show_author_array );
					if ( isset( $query['author'] ) ) {
						$query['author'] = implode( ",", array_merge( (array) $query['author'], $show_author_array ) );
					} else {
						$query['author'] = implode( ",", $show_author_array );
					}
				} else {
					// this only happens when:
					// - auth_mode = current_user and user is not logged in
					// - auth_mode = by_url and no numeric id or valid nicename is given
					// we need to return an empty query
					$query['post__in'] = array( '0' );
				}
			}
		}
		return $query;
	}

	/**
	* archive_filter_post_author
	*
	* Apply the filter by post author on WPAs.
	*
	* @since 2.1
	*/

	static function archive_filter_post_author( $query, $archive_settings, $archive_id ) {
		if (
			$query->is_archive
			&& $query->is_author
		) {
			// Do not apply on author archive pages
			return;
		}
		if ( isset( $archive_settings['author_mode'][0] ) ) {
			$show_author_array = WPV_Author_Frontend_Filter::get_settings( $query, $archive_settings, $archive_id );
			if ( isset( $show_author_array ) ) {
				if ( count( $show_author_array ) > 0 ) {
					$show_author = implode( ",", $show_author_array );
					$query->set('author', $show_author );
				} else {
					// this only happens when:
					// - auth_mode = current_user and user is not logged in
					// - auth_mode = by_url and no numeric id or valid nicename is given
					// we need to return an empty query
					$query->set('post__in', array( 0 ) );
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
		$show_author_array = array();
		switch ( $view_settings['author_mode'][0] ) {
			case 'top_current_post':
				$current_page = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
				if ( $current_page ) {
					$show_author_array[] = $current_page->post_author;
				}
				break;
			case 'current_page': // @deprecated in 1.12.1
			case 'current_post_or_parent_post_view':
				$current_page = apply_filters( 'wpv_filter_wpv_get_current_post', null );
				if ( $current_page ) {
					$show_author_array[] = $current_page->post_author;
				}
				break;
			case 'current_user':
				global $current_user;
				if ( is_user_logged_in() ) {
					$current_user = wp_get_current_user();
					$show_author_array[] = $current_user->ID; // set the array to only the current user ID if is logged in
				}
				break;
			case 'this_user':
				if (
					isset( $view_settings['author_id'] )
					&& is_numeric( $view_settings['author_id'] )
					&& $view_settings['author_id'] > 0
				) {
					$show_author_array[] = $view_settings['author_id']; // set the array to only the selected user ID
				}
				break;
			case 'parent_view': // @deprecated in 1.12.1
			case 'parent_user_view':
				$parent_user_id = apply_filters( 'wpv_filter_wpv_get_parent_view_user', null );
				if ( $parent_user_id ) {
					$show_author_array[] = $parent_user_id;
				}
				break;
			case 'by_url':
				if (
					isset( $view_settings['author_url'] )
					&& '' != $view_settings['author_url']
					&& isset( $view_settings['author_url_type'] )
					&& '' != $view_settings['author_url_type']
				) {
					$author_parameter = $view_settings['author_url'];
					$author_url_type = $view_settings['author_url_type'];
					if ( isset( $_GET[$author_parameter] ) ) {
						$authors_to_load = $_GET[$author_parameter];
						if ( is_string( $authors_to_load ) ) {
							$authors_to_load = explode( ',', $authors_to_load );
						}
						if ( 1 == count( $authors_to_load ) ) {
							$authors_to_load = explode( ',', $authors_to_load[0] ); // fix on the pagination for the author filter
						}
						if (
							0 == count( $authors_to_load )
							|| '' == $authors_to_load[0]
						) {
							// The URL parameter is empty
							$show_author_array = null;
						} else {
							// The URL parameter is not empty
							switch ( $author_url_type ) {
								case 'id':
									foreach ( $authors_to_load as $id_author_to_load ) {
										if ( is_numeric( $id_author_to_load ) ) { // if ID expected and not a number, skip it
											$show_author_array[] = $id_author_to_load; // if ID expected and is a number, add it to the array
										}
									}
									break;
								case 'username':
									foreach ( $authors_to_load as $username_author_to_load ) {
										$username_author_to_load = strip_tags( $username_author_to_load );
										$author_username_id = username_exists( $username_author_to_load );
										if ($author_username_id) {
											$show_author_array[] = $author_username_id; // if user exists, add it to the array
										}
									}
									break;
							}
						}
					} else {
						$show_author_array = null; // if the URL parameter is missing
					}
				}
				break;
			case 'shortcode':
				if (
					isset( $view_settings['author_shortcode'] )
					&& '' != $view_settings['author_shortcode']
					&& isset( $view_settings['author_shortcode_type'] )
					&& '' != $view_settings['author_shortcode_type']
				) {
					global $WP_Views;
					$author_shortcode = $view_settings['author_shortcode'];
					$author_shortcode_type = $view_settings['author_shortcode_type'];
					$view_attrs = $WP_Views->get_view_shortcodes_attributes();
					if (
						isset( $view_attrs[$author_shortcode] )
						&& '' != $view_attrs[$author_shortcode]
					) {
						$author_candidates = explode( ',', $view_attrs[$author_shortcode] );
						switch ( $author_shortcode_type ) {
							case 'id':
								foreach ( $author_candidates as $id_candid ) {
									$id_candid = trim( strip_tags( $id_candid ) );
									if ( is_numeric( $id_candid ) ) {
										$show_author_array[] = $id_candid;
									}
								}
								break;
							case 'username':
								foreach ( $author_candidates as $username_candid ) {
									$username_candid = trim( strip_tags( $username_candid ) );
									$username_candid_id = username_exists( $username_candid );
									if ( $username_candid_id ) {
										$show_author_array[] = $username_candid_id;
									}
								}
								break;
						}
					} else {
						$show_author_array = null;
					}
				}
				break;
			case 'framework':
				global $WP_Views_fapi;
				if ( $WP_Views_fapi->framework_valid ) {
					if (
						isset( $view_settings['author_framework'] )
						&& '' != $view_settings['author_framework']
						&& isset( $view_settings['author_framework_type'] )
						&& '' != $view_settings['author_framework_type']
					) {
						$author_framework = $view_settings['author_framework'];
						$author_framework_type = $view_settings['author_framework_type'];
						$author_candidates = $WP_Views_fapi->get_framework_value( $author_framework, array() );
						if ( ! is_array( $author_candidates ) ) {
							$author_candidates = explode( ',', $author_candidates );
						}
						$author_candidates = array_map( 'trim', $author_candidates );
						switch ( $author_framework_type ) {
							case 'id':
								foreach ( $author_candidates as $id_candid ) {
									if ( is_numeric( $id_candid ) ) {
										$show_author_array[] = $id_candid;
									}
								}
								break;
							case 'username':
								foreach ( $author_candidates as $username_candid ) {
									$username_candid = trim( strip_tags( $username_candid ) );
									// username_exists adds the sanitization
									$username_candid_id = username_exists( $username_candid );
									if ( $username_candid_id ) {
										$show_author_array[] = $username_candid_id;
									}
								}
								break;
						}
					}
				} else {
					$show_author_array = null;
				}
				break;
		}
		return $show_author_array;
	}

	/**
	* requires_current_page
	*
	* Whether the current View requires the top current post data for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.6.2
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_current_page( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if (
			isset( $view_settings['author_mode'] )
			&& isset( $view_settings['author_mode'][0] )
			&& $view_settings['author_mode'][0] == 'top_current_post'
		) {
			$state = true;
		}
		return $state;
	}

	/**
	* requires_parent_post
	*
	* Whether the current View requires the current post data for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.6.2
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_parent_post( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if (
			isset( $view_settings['author_mode'] )
			&& isset( $view_settings['author_mode'][0] )
			&& in_array( $view_settings['author_mode'][0], array( 'current_page', 'current_post_or_parent_post_view' ) )
		) {
			$state = true;
		}
		return $state;
	}

	/**
	* requires_current_user
	*
	* Whether the current View requires the current user data for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.10
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_current_user( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if (
			isset( $view_settings['author_mode'] )
			&& isset( $view_settings['author_mode'][0] )
			&& $view_settings['author_mode'][0] == 'current_user'
		) {
			$state = true;
		}
		return $state;
	}

	/**
	* requires_framework_values
	*
	* Whether the current View requires framework data for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.10
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_framework_values( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if (
			isset( $view_settings['author_mode'] )
			&& isset( $view_settings['author_mode'][0] )
			&& $view_settings['author_mode'][0] == 'framework'
		) {
			$state = true;
		}
		return $state;
	}

	/**
	* requires_parent_user
	*
	* Whether the current View is nested and requires the user set by the parent View for the filter by author
	*
	* @param $state (boolean) the state of this need until this filter is applied
	* @param $view_settings
	*
	* @return $state (boolean)
	*
	* @since 1.9.0
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function requires_parent_user( $state, $view_settings ) {
		if ( $state ) {
			return $state; // Already set
		}
		if (
			isset( $view_settings['author_mode'] )
			&& isset( $view_settings['author_mode'][0] )
			&& in_array( $view_settings['author_mode'][0], array( 'parent_view', 'parent_user_view' ) )
		) {
			$state = true;
		}
		return $state;
	}

	/**
	* shortcode_attributes
	*
	* Register the filter by post author on the method to get View shortcode attributes
	*
	* @since 1.10
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['author_mode'] )
			&& isset( $view_settings['author_mode'][0] )
			&& $view_settings['author_mode'][0] == 'shortcode'
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_author',
				'filter_label'	=> __( 'Post author', 'wpv-views' ),
				'value'			=> $view_settings['author_shortcode_type'],
				'attribute'		=> $view_settings['author_shortcode'],
				'expected'		=> ( $view_settings['author_shortcode_type'] == 'id' ) ? 'numberlist' : 'string',
				'placeholder'	=> ( $view_settings['author_shortcode_type'] == 'id' ) ? '1, 2' : 'admin, john',
				'description'	=> ( $view_settings['author_shortcode_type'] == 'id' ) ? __( 'Please type a comma separated list of author IDs', 'wpv-views' ) : __( 'Please type a comma separated list of author usernames', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	* url_parameters
	*
	* Register the filter by post author on the method to get URL parameters
	*
	* @since 1.11
	* @since 2.1	Move to the frontend class as a static method.
	*/

	static function url_parameters( $attributes, $view_settings ) {
		if (
			isset( $view_settings['author_mode'] )
			&& isset( $view_settings['author_mode'][0] )
			&& $view_settings['author_mode'][0] == 'by_url'
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_author',
				'filter_label'	=> __( 'Post author', 'wpv-views' ),
				'value'			=> $view_settings['author_url_type'],
				'attribute'		=> $view_settings['author_url'],
				'expected'		=> ( $view_settings['author_url_type'] == 'id' ) ? 'numberlist' : 'string',
				'placeholder'	=> ( $view_settings['author_url_type'] == 'id' ) ? '1, 2' : 'admin, john',
				'description'	=> ( $view_settings['author_url_type'] == 'id' ) ? __( 'Please type a comma separated list of author IDs', 'wpv-views' ) : __( 'Please type a comma separated list of author usernames', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	 * Callback to display the custom search filter by post author.
	 *
	 * @param $atts array
	 * 		'url_param'		string	URL parameter to listen to
	 *		'type'			'select'|'multi-select'|'radios'|'checbboxes'
	 *		'roles'			string	Comma-separated list of roles to include
	 *		'include'		string	Comma-separated list of user IDs to include
	 *		'format'		string.	Placeholders: '%%ID%%', '%%DISPLAY_NAME%%', '%%USER_LOGIN%%', '%%USER_NICENAME%%', '%%USER_EMAIL%%'
	 *		'default_label'	string	Label for the default empty option in select dropdowns
	 *		'style'			string	Styles to add to the control
	 *		'class'			string	Classnames to add to the control
	 *		'label_style'	string
	 *		'label_class'	string
	 *
	 * @since 2.4.0
	 *
	 * @note WIP, extremely tied to select, check with radios and checkboxes.
	 * @todo Do not use Enlimbo at all, write its own walkers.
	 */

	public static function wpv_shortcode_wpv_control_post_author( $atts ) {

		global $wp_version;
		$view_id			= apply_filters( 'wpv_filter_wpv_get_current_view',		null );
		$view_settings		= apply_filters( 'wpv_filter_wpv_get_object_settings',	array() );

		/**
		 * Adjust the View settings just before the main Views query.
		 *
		 * @param array $view_settings
		 * @return $view_settings
		 * @since 3.0
		 */
		$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );

		$return				= '';

		$atts = shortcode_atts(
			array(
				'url_param'		=> '',
				'type'			=> 'select',
				'roles'			=> 'any',
				'include'		=> '',
				'format'		=> '%%DISPLAY_NAME%%',
				'default_label'	=> '',
				'style'			=> '',
				'class'			=> '',
				'label_style'	=> '',
				'label_class'	=> ''
			),
			$atts
		);

		$roles		= explode( ',', $atts['roles'] );
		$roles		= array_map( 'trim', $roles );
		$include	= explode( ',', $atts['include'] );
		$include	= array_map( 'esc_attr', $include );
		$include	= array_map( 'trim', $include );
		$include	= array_filter( $include, 'is_numeric' );
		$include	= array_map( 'intval', $include );
		$fields		= array( 'ID', 'user_login' );
		$format_placeholders = array(
			'display_name'	=> '%%DISPLAY_NAME%%',
			'user_login'	=> '%%USER_LOGIN%%',
			'user_nicename'	=> '%%USER_NICENAME%%',
			'user_email'	=> '%%USER_EMAIL%%'
		);

		if (
			empty( $atts['url_param'] )
			|| ! in_array( $atts['type'], array( 'select', 'multiselect', 'radios', 'checkboxes' ) )
			|| (
				empty( $roles )
				&& empty( $include )
			)
		) {
			return $return;
		}

		foreach ( $format_placeholders as $format_key => $format_value ) {
			if ( strpos( $atts['format'], $format_value ) !== false ) {
				$fields[] = $format_key;
			} else {
				unset( $format_placeholders[ $format_key ] );
			}
		}

		$format_placeholders = is_array( $format_placeholders ) ? $format_placeholders : array();

		$user_query_args = array(
			'fields'	=> $fields
		);

		if ( ! empty( $include ) ) {
			$user_query_args['include'] = $include;
		}

		if (
			! empty( $roles )
			&& ! in_array( 'any', $roles )
		) {
			if ( version_compare( $wp_version, '4.4', '<' ) ) {
				$user_query_args['role'] = $roles[0];
			} else {
				$user_query_args['role__in'] = $roles;
			}
		}

		$user_query			= new WP_User_Query( $user_query_args );
		$user_query_results	= $user_query->results;

		$style = esc_attr( $atts['style'] );
		$class = esc_attr( $atts['class'] );
		$label_style = esc_attr( $atts['label_style'] );
		$label_class = esc_attr( $atts['label_class'] );

		$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$dependant = false;
		$counters = ( $atts['format'] && strpos( $atts['format'], '%%COUNT%%' ) !== false ) ? true : false;

		$wpv_data_cache = WPV_Cache::$stored_cache;

		if (
			isset( $_GET[ $atts['url_param'] ] )
			&& ! empty( $_GET[ $atts['url_param'] ] )
		) {
			$query = apply_filters( 'wpv_filter_wpv_get_dependant_extended_query_args', array(), $view_settings, array( 'author' => 'enabled' ) );
			$aux_cache_query = null;
			$query_args_to_check = array(
				'author', 'author_name', 'author__in', 'author__not_in'
			);
			foreach ( $query_args_to_check as $arg_to_check ) {
				if ( isset( $query[ $arg_to_check ] ) ) {
					unset( $query[ $arg_to_check ] );
				}
			}
			$aux_cache_query = new WP_Query( $query );
			if (
				is_array( $aux_cache_query->posts )
				&& ! empty( $aux_cache_query->posts )
			) {
				$aux_query_count = count( $aux_cache_query->posts );
				$wpv_data_cache = WPV_Cache::generate_cache( $aux_cache_query->posts, array( 'author' => 'enabled' ) );
			}
		}

		if ( isset( $view_settings['dps'] )
			&& is_array( $view_settings['dps'] )
			&& isset( $view_settings['dps']['enable_dependency'] )
			&& $view_settings['dps']['enable_dependency'] == 'enable' )
		{
			$dependant = true;
			$force_disable_dependant = apply_filters( 'wpv_filter_wpv_get_force_disable_dps', false );
			if ( $force_disable_dependant ) {
				$dependant = false;
			}
		}

		$empty_action = array();
		if (
			$dependant
			|| $counters
		) {
			$empty_default = 'hide';
			$empty_alt = 'disable';
			$empty_options = array( 'select', 'radios', 'checkboxes' ); // multi-select is a special case because of dashes and underscores
			foreach ( $empty_options as $empty_opt ) {
				if (
					isset( $view_settings['dps']['empty_' . $empty_opt] )
					&& $view_settings['dps']['empty_' . $empty_opt] == $empty_alt
				) {
					$empty_action[$empty_opt] = $empty_alt;
				} else {
					$empty_action[$empty_opt] = $empty_default;
				}
			}
			if (
				isset( $view_settings['dps']['empty_multi_select'] )
				&& $view_settings['dps']['empty_multi_select'] == $empty_alt
			) {
				$empty_action['multi-select'] = $empty_alt;
			} else {
				$empty_action['multi-select'] = $empty_default;
			}
		}

		$control_data	= array();
		$options_array	= array();
		$default_value	= isset( $_GET[ $atts['url_param'] ] ) ? sanitize_text_field( $_GET[ $atts['url_param'] ] ) : '';
		$user_value_use	= 'ID';
		if (
			isset( $view_settings['author_url_type'] )
			&& 'username' ==  $view_settings['author_url_type']
		) {
			$user_value_use = 'user_login';
		}

		switch ( $atts['type'] ) {
			case 'select':
			case 'multiselect':

				$control_data = array(
					'#type'				=> esc_attr( $atts['type'] ),
					'#id'				=> 'wpv_control_post_author_' . esc_attr( $atts['type'] ) . '_' . esc_attr( $atts['url_param'] ),
					'#name'				=> esc_attr( $atts['url_param'] ),
					'#attributes'		=> array(
												'style'	=> $style,
												'class'	=> 'js-wpv-filter-trigger ' . $class
											),
					'#inline'			=> true,
					'#default_value'	=> $default_value,
				);

				if ( $atts['type'] == 'multiselect' ) {
					$control_data['#multiple']	= true;
					$control_data['#type']		= 'select';
				}

				$options_array[ $atts['default_label'] ] = array(
					'#title' => $atts['default_label'],
					'#value' => '',
					'#inline' => true,
					'#after' => '<br />'
				);


				foreach ( $user_query_results as $user_option ) {

					$display_value = esc_attr( $atts['format'] );
					$display_value = str_replace( '%%ID%%', $user_option->ID, $display_value );

					$user_option_value = $user_option->ID;
					if ( 'user_login' == $user_value_use ) {
						$user_option_value = $user_option->user_login;
					}

					if ( isset( $format_placeholders['display_name'] ) ) {
						$display_value = str_replace( '%%DISPLAY_NAME%%', $user_option->display_name, $display_value );
					}
					if ( isset( $format_placeholders['user_login'] ) ) {
						$display_value = str_replace( '%%USER_LOGIN%%', $user_option->user_login, $display_value );
					}
					if ( isset( $format_placeholders['user_nicename'] ) ) {
						$display_value = str_replace( '%%USER_NICENAME%%', $user_option->user_nicename, $display_value );
					}
					if ( isset( $format_placeholders['USER_EMAIL'] ) ) {
						$display_value = str_replace( '%%USER_EMAIL%%', $user_option->USER_EMAIL, $display_value );
					}

					$user_option_show = true;

					if (
						$dependant
						|| $counters
					) {
						$count_user_option_posts = WPV_Author_Frontend_Filter::count_cached_posts( $user_option->ID, $wpv_data_cache, $counters );
						if (
							$dependant
							&& $count_user_option_posts == 0
						) {
							$user_option_show = false;
						}
						if ( $counters ) {
							$display_value = str_replace( '%%COUNT%%', $count_user_option_posts, $display_value );
						}
					}

					if (
						$user_option_show
						|| $user_option_value == $default_value
					) {

						$options_array[ $display_value ] = array(
							'#title' => $display_value,
							'#value' => $user_option_value,
							'#inline' => true,
							'#after' => '<br />'
						);

					}
				}

				$control_data['#options'] = $options_array;

				$return = wpv_form_control( array( 'field' => $control_data ) );

				break;

			case 'radios':

				$control_data = array(
					'#type'				=> esc_attr( $atts['type'] ),
					'#id'				=> 'wpv_control_post_author_' . esc_attr( $atts['type'] ) . '_' . esc_attr( $atts['url_param'] ),
					'#name'				=> esc_attr( $atts['url_param'] ),
					'#attributes'		=> array(
												'style'	=> $style,
												'class'	=> 'js-wpv-filter-trigger ' . $class
											),
					'#inline'			=> true,
					'#default_value'	=> $default_value,
				);

				if ( '' != $atts['default_label'] ) {
					$options_array[ $atts['default_label'] ] = array(
						'#title'		=> $atts['default_label'],
						'#value'		=> '',
						'#attributes'	=> array(
											'style'	=> $style,
											'class'	=> 'js-wpv-filter-trigger ' . $class
										),
						'#labelclass'	=> $label_class,
						'#labelstyle'	=> $label_style,
						'#inline'		=> true,
						'#after'		=> '<br />'
					);
				}

				foreach ( $user_query_results as $user_option ) {

					$display_value = esc_attr( $atts['format'] );
					$display_value = str_replace( '%%ID%%', $user_option->ID, $display_value );

					$user_option_value = $user_option->ID;
					if ( 'user_login' == $user_value_use ) {
						$user_option_value = $user_option->user_login;
					}

					if ( isset( $format_placeholders['display_name'] ) ) {
						$display_value = str_replace( '%%DISPLAY_NAME%%', $user_option->display_name, $display_value );
					}
					if ( isset( $format_placeholders['user_login'] ) ) {
						$display_value = str_replace( '%%USER_LOGIN%%', $user_option->user_login, $display_value );
					}
					if ( isset( $format_placeholders['user_nicename'] ) ) {
						$display_value = str_replace( '%%USER_NICENAME%%', $user_option->user_nicename, $display_value );
					}
					if ( isset( $format_placeholders['USER_EMAIL'] ) ) {
						$display_value = str_replace( '%%USER_EMAIL%%', $user_option->USER_EMAIL, $display_value );
					}

					$user_option_show = true;

					if (
						$dependant
						|| $counters
					) {
						$count_user_option_posts = WPV_Author_Frontend_Filter::count_cached_posts( $user_option->ID, $wpv_data_cache, $counters );
						if (
							$dependant
							&& $count_user_option_posts == 0
						) {
							$user_option_show = false;
						}
						if ( $counters ) {
							$display_value = str_replace( '%%COUNT%%', $count_user_option_posts, $display_value );
						}
					}

					if (
						$user_option_show
						|| $user_option_value == $default_value
					) {

						$options_array[ $display_value ] = array(
							'#title' => $display_value,
							'#value' => $user_option_value,
							'#attributes'	=> array(
												'style'	=> $style,
												'class'	=> 'js-wpv-filter-trigger ' . $class
											),
							'#labelclass'	=> $label_class,
							'#labelstyle'	=> $label_style,
							'#inline' => true,
							'#after' => '<br />'
						);

					}
				}

				$control_data['#options'] = $options_array;

				$return = wpv_form_control( array( 'field' => $control_data ) );

				break;
		}

		return $return;

	}

	/**
	 * Register the wpv-control-post-author shortcode attributes in the shortcodes GUI API.
	 *
	 * @since 2.4.0
	 */

	public static function wpv_shortcodes_register_wpv_control_post_author_data( $views_shortcodes ) {
		$views_shortcodes['wpv-control-post-author'] = array(
			'callback' => array( 'WPV_Author_Frontend_Filter', 'wpv_shortcodes_get_wpv_control_post_author_data' )
		);
		return $views_shortcodes;
	}

	public static function wpv_shortcodes_get_wpv_control_post_author_data() {
		$data = array(
			'name' => __( 'Filter by post author', 'wpv-views' ),
			'label' => __( 'Filter by post author', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label' => __( 'Display', 'wpv-views' ),
					'header' => __( 'Display', 'wpv-views' ),
					'fields' => array(
						'type' => array(
							'label'			=> __( 'Type of control', 'wpv-views'),
							'type'			=> 'select',
							'options'		=> array(
												'select'		=> __( 'Select dropdown', 'wpv-views' ),
												'multiselect'	=> __( 'Multiselect', 'wpv-views' ),
												'radios'		=> __( 'Set of radio buttons', 'wpv-views' ),
												'checkboxes'	=> __( 'Set of checkboxes', 'wpv-views' )
											),
							'description' 	=> __( 'Type of control to display.', 'wpv-views' ),
							'default_force' => 'select'
						),
						'default_label' => array(
							'label'			=> __( 'Label of the first empty option', 'wpv-views' ),
							'type'			=> 'text',
							'placeholder'	=> __( 'Select one...', 'wpv-views' ),
							'description'	=> __( 'Label for the first options, which returns all posts.', 'wpv-views' )
						),
						'format' => array(
							'label'			=> __( 'Format', 'wpv-views'),
							'type'			=> 'text',
							'default_force'	=> '%%DISPLAY_NAME%%',
							'description'	=> __( 'Render options using this format. You can use the placeholders %%ID%%, %%DISPLAY_NAME%%, %%USER_LOGIN%%, %%USER_NICENAME%%, %%USER_EMAIL%% and %%COUNT%%', 'wpv-views' ),
							'required'		=> true
						),
					),
				),
				'style-options' => array(
					'label' => __( 'Styling', 'wpv-views' ),
					'header' => __( 'Styling', 'wpv-views' ),
					'fields' => array(
						'style' => array(
							'label'			=> __( 'Add this CSS style to the control', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Styling.', 'wpv-views' ),
						),
						'class' => array(
							'label'			=> __( 'Add this classname to the control', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Classing.', 'wpv-views' ),
						),
						'label_style' => array(
							'label'			=> __( 'Add this CSS to the radios labels of the control', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Styling label.', 'wpv-views' ),
						),
						'label_class' => array(
							'label'			=> __( 'Add this classname to the radios labels', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Classing labels.', 'wpv-views' ),
						),
					)
				),
				'filter-options' => array(
					'label' => __( 'Filter options', 'wpv-views' ),
					'header' => __( 'Filter options', 'wpv-views' ),
					'fields' => array(
						'roles' => array(
							'label'			=> __( 'Include only users with those roles', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Comma separated list of roles.', 'wpv-views' ),
						),
						'include' => array(
							'label'			=> __( 'Include only users with those IDs', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Comma separated list of IDs.', 'wpv-views' ),
						),
						'url_param' => array(
							'label'			=> __( 'URL parameter to use', 'wpv-views'),
							'type'			=> 'text',
							'default_force'	=> 'wpv-author-filter',
							'description'	=> __( 'Watch this URL parameter.', 'wpv-views' ),
							'required'		=> true
						),
					),
				),
			),
		);
		return $data;
	}

	/**
	 * Count cached posts that belong to a given author.
	 *
	 * @since 2.4.0
	 */

	static function count_cached_posts( $author_id, $cached_data, $count_matches ) {
		$return = ( $count_matches ) ? 0 : false;

		if ( isset( $cached_data['post_author'][ $author_id ] ) ) {
			if ( $count_matches ) {
				$return = count( $cached_data['post_author'][ $author_id ] );
			} else {
				$return = true;
			}
		}

		return $return;
	}

}
