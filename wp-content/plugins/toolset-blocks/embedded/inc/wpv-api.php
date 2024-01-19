<?php

use OTGS\Toolset\Views\Controller\API\Methods\RenderViewHandler;

/**
* wpv-api.php
*
* Contains all public APIs to be used by third-party developers
*
* @package Views
*
* @since 1.8.0
*/

/*
* ----------------------------------------------------------
* Get functions - get results
* ----------------------------------------------------------
*/

/**
 * Returns the result of a query filtered by a View.
 *
 * @param int $view_id ID of the relevant View.
 * @param object $post_in (optional) Sets the global $post.
 * @param object $current_user_in (optional) Sets the global $current_user.
 * @param array $args (optional) Attributes to pass to the View, like shortcode attributes when using [wpv-view].
 * @param string $post_status_allowed (optional) Which post status for View post is allowed. Default is 'publish'.
 * @param string $adjust_wpa_in_gutenberg (optional) Fire some actions to get correct archive View results in Gutenberg.
 *
 * @return Array of $post objects if the View lists posts, $term objects if the View lists taxonomies or $user objects if the View lists users
 *
 * @usage  <?php echo get_view_query_results( 80 ); ?>
 *
 * @since unknown
 * @since 2.2.2 Return an empty array when called before init.
 */
function get_view_query_results(
	$view_id,
	$post_in = null,
	$current_user_in = null,
	$args = array(),
	$post_status_allowed = 'publish',
	$adjust_wpa_in_gutenberg = false
) {
	if ( did_action( 'init' ) == 0 ) {
		_doing_it_wrong(
			'get_view_query_results',
			__( 'Views API functions do not work before the init hook.', 'wpv-views' ),
			'2.2.2'
		);
		return array();
	}

	$view_post = get_post( $view_id );
	if (
		! $view_post
		|| $view_post->post_status != $post_status_allowed
		|| $view_post->post_type != 'view'
	) {
		return array();
	}
	global $WP_Views, $post, $current_user, $authordata;
	// Save current globals to restore them later
	$post_old = $post;
	$current_user_old = $current_user;
	$authordata_old = $authordata;
	$items = array();
	if ( $post_in ) {
		$post = $post_in;
	}
	if ( $current_user_in ) {
		$current_user = $current_user_in;
	}

	do_action( 'wpv_action_wpv_set_current_view', $view_id );

	$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id );

	do_action( 'wpv_action_wpv_set_view_shortcodes_attributes', $args );

	$query_type = ( isset( $view_settings['query_type'][0] ) ) ? $view_settings['query_type'][0] : 'posts';
	switch ( $query_type ) {
		case 'posts':
			// get the posts using the query settings for this view.
			$archive_query = null;
			if (
				isset( $view_settings['view-query-mode'] )
				&& 'archive' === $view_settings['view-query-mode']
			) {
				// check for an archive loop
				global $WPV_view_archive_loop;
				if ( isset( $WPV_view_archive_loop ) ) {
					if ( $adjust_wpa_in_gutenberg ) {
						do_action( 'wpv_action_before_render_view_editor_shortcode', $view_id );
					}
					$archive_query = $WPV_view_archive_loop->get_archive_loop_query();
					if ( $adjust_wpa_in_gutenberg ) {
						do_action( 'wpv_action_after_render_view_editor_shortcode', $view_id );
					}
				}
			} elseif (
				isset( $view_settings['view-query-mode'] )
				&& 'layouts-loop' === $view_settings['view-query-mode']
			) {
				global $wp_query;
				$archive_query = ( isset( $wp_query ) && ( $wp_query instanceof WP_Query ) ) ? clone $wp_query : null;
			}
			if ( $archive_query ) {
				$ret_query = $archive_query;
			} else {
				$ret_query = wpv_filter_get_posts( $view_id );
			}
			$items = $ret_query->posts;
			break;
		case 'taxonomy':
			$items = $WP_Views->taxonomy_query( $view_settings );
			break;
		case 'users':
			$items = $WP_Views->users_query( $view_settings );
			break;
	}
	// Restore current globals
	$post = $post_old;
	$current_user = $current_user_old;
	$authordata = $authordata_old;

	do_action( 'wpv_action_wpv_reset_current_view', $view_id );
	do_action( 'wpv_action_wpv_reset_view_shortcodes_attributes' );

	return $items;
}

/*
* ----------------------------------------------------------
* Render functions
* ----------------------------------------------------------
*/

/**
 * Renders a View and returns the result.
 *
 * @param array $args {
 *	 You can pass one of these keys:
 * 	 $name The View post_name.
 *	 $title The View post_title.
 *	 $id The View post ID.
 *	 $target_id The target page ID if you want to render just the View form.
 * }
 * @param array $get_override An array to be used to override $_GET values.
 *
 * @usage  <?php echo render_view( array( 'title' => 'Top pages' ) ); ?>
 *
 * @since unknown
 * @since 2.2.2	Return nothing when called before init.
 * @since 2.5.1 Pass the outcome over the wpv_filter_wpv_view_shortcode_output filter for consistency.
 *
 * @return string
 *
 * @codeCoverageIgnore
 */

function render_view( $args, $get_override = array() ) {
	/** @var \OTGS\Toolset\Common\Auryn\Injector */
	$dic = apply_filters( 'toolset_dic', false );

	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	global $WP_Views;

	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	$render_view_api_method_handler = $dic->make( RenderViewHandler::class, array( ':wp_views' => $WP_Views ) );

	return $render_view_api_method_handler->process_call( $args, $get_override );
}

/**
* render_view_template
*
* Returns the content of a Content Template applied to a Post
*
* @param integer	$view_template_id	ID of the relevant Content Template
* @param object		$post_in			Post to apply the Content Template to
* @param object		$current_user_in	Sets the global $current_user
* @param array		$args				Extra arguments to be used
*
* @usage  <?php echo render_view_template(80, $mypost)); ?>
*
* @note we need to set the global $authordata to the right user
*
* @since unknown
* @since 2.2.2	Return nothing when called before init.
*/

function render_view_template( $view_template_id, $post_in = null, $current_user_in = null, $args = array() ) {

	if ( did_action( 'init' ) == 0 ) {
		_doing_it_wrong(
			'render_view_template',
			__( 'Views API functions do not work before the init hook.', 'wpv-views' ),
			'2.2.2'
		);
		return '';
	}

	$ct_post = get_post( $view_template_id );
	if (
		! $ct_post
		|| $ct_post->post_status != 'publish'
		|| $ct_post->post_type != 'view-template'
	) {
		return '';
	}

	global $WPV_templates, $post, $current_user, $authordata;
	// Save current globals to restore them later
	$post_old = $post;
	$current_user_old = $current_user;
	$authordata_old = $authordata;
	if ( $post_in ) {
		$post = $post_in;
		$authordata = new WP_User( $post->post_author );
	}
	if ( $current_user_in ) {
		$current_user = $current_user_in;
	}
	// Adjust for WPML support
	$view_template_id = apply_filters( 'translate_object_id', $view_template_id, 'view-template', true, null );
	$content = $WPV_templates->get_template_content( $view_template_id );
	// If this function returns null, $view_template_id does not exist or is not a Content Template or its status is different from 'publish'
	if ( is_null( $content ) ) {
		$content = '';
	} else {
		$WPV_templates->view_template_used_ids[] = $view_template_id;
		$output_mode = get_post_meta( $view_template_id, '_wpv_view_template_mode', true );
		if ( $output_mode == 'raw_mode' ) {
			$WPV_templates->remove_wpautop();
		}
		if (

			/**
			* wpv_filter_wpv_render_view_template_force_suppress_filters
			*
			* Force the use of the restricted wpv_filter_wpv_the_content_suppressed filter instead of the the_content one.
			*
			* @param bool 						Defaults to false
			* @param object	$ct_post 			The Content Template post object
			* @param object	$post_in			Post object to overwrote the global $post
			* @param object $current_user_in	User object that overwrote the global $current_user
			* @param array	$args				Extra arguments passed to the function
			*
			* Since 1.10
			*/

			apply_filters( 'wpv_filter_wpv_render_view_template_force_suppress_filters', false, $ct_post, $post_in, $current_user_in, $args )
			|| (
				isset( $args['suppress_filters'] )
				&& $args['suppress_filters']
			)
		) {
			$content = apply_filters( 'wpv_filter_wpv_the_content_suppressed', $content );
		} else {

			// Even when applyig the full content, make sure we do not over-format.
			// CTs created with blocks do not need to run wpautop.
			$priority = has_filter( 'the_content', 'wpautop' );
			if (
				false !== $priority
				&& function_exists( 'has_blocks' )
				&& has_blocks( $content )
			) {
				remove_filter( 'the_content', 'wpautop', $priority );
				add_filter( 'the_content', '_wpv_restore_wpautop_hook', $priority + 1 );
			}

			$content = apply_filters( 'the_content', $content );
		}
	}
	// Restore current globals
	$post = $post_old;
	$current_user = $current_user_old;
	$authordata = $authordata_old;
	return $content;
}

/*
* ----------------------------------------------------------
* Template tags
* ----------------------------------------------------------
*/

/**
 * has_wpv_wp_archive
 *
 * Official API for checking whether an archive loop has a WPA assigned.
 *
 * Alias for wpv_has_wordpress_archive
 *
 * @deprecated - please use the filter 'wpv_get_archive_for_taxonomy_term' instead.
 * @since 1.8.0
*/

function has_wpv_wp_archive( $kind = 'other', $slug = 'home-blog' ) {
	return wpv_has_wordpress_archive( $kind, $slug );
}

/**
 * wpv_has_wordpress_archive
 *
 * Checks if a given archive page has a WPA assigned to it. Defaults to check the home/blog archive loop.
 *
 * @param string $kind [post|taxonomy|other] The kind of archive to be checked
 * @param string $slug The slug of the archive to be checked:
 *	- if $kind is "post" then the slug of the post type
 *	- if $kind is "taxonomy" then the slug of the taxonomy
 *	- if $kind is "other" it can be [home-blog|search|author|year|month|day]
 *
 * @return (int) The ID of the assigned WPA or 0 if there is no one
 *
 * @deprecated - please use the filter 'wpv_get_archive_for_taxonomy_term' instead.
 * @since 1.6.0
*/

function wpv_has_wordpress_archive( $kind = 'other', $slug = 'home-blog' ) {
	global $WPV_settings;
	$return = 0;
	$identifier = '';
	switch ( $kind ) {
		case 'post':
			$identifier = 'view_cpt_' . $slug;
			break;
		case 'taxonomy':
			$identifier = 'view_taxonomy_loop_' . $slug;
			break;
		case 'other':
			$identifier = 'view_' . $slug . '-page';
			break;
	}
	if (
		! empty( $identifier )
		&& isset( $WPV_settings[$identifier] )
	) {
		$return = $WPV_settings[$identifier];
	}
	return $return;
}

/**
* is_wpv_wp_archive_assigned
*
* Check if the current page is an archive page and has a WPA assigned to it.
*
* @return bool
*
* @since 1.8.0
*/

function is_wpv_wp_archive_assigned() {
	global $wp_query;
	if ( ! $wp_query ) {
		return false;
	}

	if (
		! is_archive()
		&& ! is_home()
		&& ! is_search()
	) {
		return false;
	}
	global $WPV_settings;
	if ( is_home() ) {
		if (
			isset( $WPV_settings['view_home-blog-page'] )
			&& $WPV_settings['view_home-blog-page'] > 0
		) {
			return true;
		} else {
			return false;
		}
	} else if ( is_search() ) {
		if (
			isset( $WPV_settings['view_search-page'] )
			&& $WPV_settings['view_search-page'] > 0
		) {
			return true;
		} else {
			return false;
		}
	} else if ( is_author() ) {
		if (
			isset( $WPV_settings['view_author-page'] )
			&& $WPV_settings['view_author-page'] > 0
		) {
			return true;
		} else {
			return false;
		}
	} else if ( is_year() ) {
		if (
			isset( $WPV_settings['view_year-page'] )
			&& $WPV_settings['view_year-page'] > 0
		) {
			return true;
		} else {
			return false;
		}
	} else if ( is_month() ) {
		if (
			isset( $WPV_settings['view_month-page'] )
			&& $WPV_settings['view_month-page'] > 0
		) {
			return true;
		} else {
			return false;
		}
	} else if ( is_day() ) {
		if (
			isset( $WPV_settings['view_day-page'] )
			&& $WPV_settings['view_day-page'] > 0
		) {
			return true;
		} else {
			return false;
		}
	} else if (
		is_tax()
		|| is_category()
		|| is_tag()
	) {
		global $wp_query;
		$queried_term = $wp_query->get_queried_object();
		if ( $queried_term instanceof WP_Term ) {
			$wpa_id = apply_filters( 'wpv_get_archive_for_taxonomy_term', 0, $queried_term->taxonomy, $queried_term->slug );
			if ( 0 < $wpa_id ) {
				return true;
			}
		}
		if (
			$queried_term
			&& isset( $queried_term->taxonomy )
			&& isset( $WPV_settings['view_taxonomy_loop_' . $queried_term->taxonomy] )
			&& $WPV_settings['view_taxonomy_loop_' . $queried_term->taxonomy] > 0
		) {
			return true;
		} else {
			return false;
		}
	} else if ( is_post_type_archive() ) {
		global $wp_query;
		$queried_post_type = $wp_query->get('post_type');
		if ( is_array( $queried_post_type ) ) {
			$queried_post_type = reset( $queried_post_type );
		}
		if (
			isset( $WPV_settings['view_cpt_' . $queried_post_type] )
			&& $WPV_settings['view_cpt_' . $queried_post_type] > 0
		) {
			return true;
		} else {
			return false;
		}
	}
	return false;
}

/**
 * Check if the current page is an archive page and has a CT assigned to its archive loop.
 *
 * @return bool
 * @since 3.0
 */
function is_wpv_wp_archive_template_assigned() {
	if (
		! is_archive()
		&& ! is_home()
		&& ! is_search()
	) {
		return false;
	}
	global $WPV_settings;
	if (
		is_tax()
		|| is_category()
		|| is_tag()
	) {
		global $wp_query;
		$queried_term = $wp_query->get_queried_object();
		if (
			$queried_term
			&& isset( $queried_term->taxonomy )
			&& isset( $WPV_settings['views_template_loop_' . $queried_term->taxonomy] )
			&& $WPV_settings['views_template_loop_' . $queried_term->taxonomy] > 0
		) {
			return true;
		} else {
			return false;
		}
	} elseif ( is_post_type_archive() ) {
		global $wp_query;
		$queried_post_type = $wp_query->get('post_type');
		if ( is_array( $queried_post_type ) ) {
			$queried_post_type = reset( $queried_post_type );
		}
		if (
			isset( $WPV_settings['views_template_archive_for_' . $queried_post_type] )
			&& $WPV_settings['views_template_archive_for_' . $queried_post_type] > 0
		) {
			return true;
		} else {
			return false;
		}
	}
	return false;
}

/**
* has_wpv_content_template
*
* Check if a given post has a CT assigned to it
*
* @param int $post_id The ID of the post to check
*
* @return (int) The ID of the assigned CT or 0 if there is no one
*
* @since 1.8.0
*/
function has_wpv_content_template( $post_id = null ) {
	if ( 0 === $post_id || null === $post_id ) {
		return 0;
	}
	return apply_filters( 'wpv_content_template_for_post', 0, get_post( $post_id ) );
}

/**
* is_wpv_content_template_assigned
*
* Check if the current page is a singular one and has a CT assigned to it.
*
* @return bool
*
* @since 1.8.0
*/

function is_wpv_content_template_assigned() {
	if ( is_singular() ) {
		global $post;
		$post = get_post( $post );
		$template_selected = apply_filters( 'wpv_content_template_for_post', 0, $post );
		if (
			! empty( $template_selected )
			&& intval( $template_selected ) > 0
		) {
			return true;
		}
	}
	return false;
}
