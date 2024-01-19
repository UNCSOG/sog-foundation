<?php

/**
 * Hook into the template redirect and see if it's an archive loop.
 *
 * Use the select page (that contains a View) to display the loop items.
 *
 * @todo this needs to happen waaay earlier so we can catch pre_get_posts
 *
 * @since unknown
 */

use OTGS\Toolset\Views\Models\ContentTemplate\UsagePostType;

/**
 * wpv_force_wordpress_archive
 *
 * Applies the wpv_filter_force_wordpress_archive filter to the WPA ID to be displayed
 *
 * @param $wpa_to_apply (integer) the ID of the WPA we want to overwrite
 * @param $wpa_slug (string) [view_cpt_{post_slug}|view_taxonomy_loop_{taxonomy_slug}|view_home-blog-page|view_search-page
 *	 |view_author-page|view_year-page|view_month-page|view_day-page] the kind of WPA being processed
 *
 * @return (int) the ID of the WPA to apply_filters
 *
 * @since 1.6.0
 */
function wpv_force_wordpress_archive( $wpa_to_apply, $wpa_slug ) {

	/**
	 * Filter wpv_filter_force_wordpress_archive
	 *
	 * @param $wpa_to_apply (integer) the ID of the WPA we want to overwrite
	 * @param $wpa_slug (string) [view_cpt_{post_slug}|view_taxonomy_loop_{taxonomy_slug}|view_home-blog-page
	 *	 |view_search-page|view_author-page|view_year-page|view_month-page|view_day-page] the kind of WPA being processed.
	 *
	 * @return (int) the ID of the WPA to apply
	 *
	 * @since 1.6.0
	 * @since 2.3.0 Added a duplicated wpv_filter_wpv_override_wordpress_archive for better naming
	 */
	$wpa_to_apply = apply_filters( 'wpv_filter_force_wordpress_archive', $wpa_to_apply, $wpa_slug );
	$wpa_to_apply = apply_filters( 'wpv_filter_wpv_override_wordpress_archive', $wpa_to_apply, $wpa_slug );
	return $wpa_to_apply;
}


/**
* WPV_WordPress_Archive_Frontend
*
* @todo comment properly
* @todo declare fields that are being declared dynamically in the code
*
* @since 2.00	Renamed to WPV_WordPress_Archive_Frontend from WP_Views_archive_loops and transformed into a singleton
*/

class WPV_WordPress_Archive_Frontend {

	const SETTINGS_TAXONOMY_LOOP = 'view_taxonomy_loop_%s';
	const SETTINGS_TAXONOMY_LOOP_OPTION = 'view_taxonomy_option_loop_%s_option_%s';
	const SETTINGS_TAXONOMY_LOOP_OPTION_REGEXP_TEMPLATE = '/view_taxonomy_option_loop_%s_option_(.*)/';

	const CREATE_ARCHIVE_POPUP_TEMPLATE = <<<EOF
<div class="wpv-modal-form">
	<div class="js-wpv-error-container"></div>
	<label for="wpv-new-archive-name">%s</label>
	<input id="wpv-new-archive-name" type="text" value="" class="js-wpv-new-archive-name wpv-new-archive-name" placeholder="%s" name="wpv-new-archive-name">
</div>
EOF;

	const KEY_LOOP_SINGLE_CT_CONDITIONS = 'single_ct_conditions';

	const KEY_LOOP_SINGLE_CT = 'single_ct';

	const KEY_LOOP_ARCHIVE_CT = 'ct';

	/**
	 * @var WPV_WordPress_Archive_Frontend Instance of WPV_WordPress_Archive_Frontend.
	 */
	private static $instance = null;

	/**
	 * @var \Toolset_Post_Type_Repository
	 */
	private $post_type_repository;

	public $wpa_id = null;
	public $wpa_slug = '';
	public $wpa_type = '';
	public $wpa_name = '';
	public $wpa_data = array();
	public $wpa_settings = array();

	public $query = null;

	public $header_started = false;
	public $in_head = false;

	public $in_the_loop = false;
	public $loop_found = false;

	public $loop_has_no_posts = false;

	public $wpv_settings = null;

	/**
	 * @return WPV_WordPress_Archive_Frontend The instance of WPV_WordPress_Archive_Frontend.
	 */
	public static function get_instance() {
		if( null == WPV_WordPress_Archive_Frontend::$instance ) {
			WPV_WordPress_Archive_Frontend::$instance = new WPV_WordPress_Archive_Frontend();
		}
		return WPV_WordPress_Archive_Frontend::$instance;
	}

	public static function clear_instance() {
		if ( WPV_WordPress_Archive_Frontend::$instance ) {
			WPV_WordPress_Archive_Frontend::$instance = null;
		}
	}

	/**
	 * @param \WPV_Settings|null $wpv_settings - optionally pass a settings instance.
	 * @param \Toolset_Post_Type_Repository|null $post_type_repository - optionally pass a Post Type Repository instance.
	 */
	public function __construct(
		$wpv_settings = null,
		$post_type_repository = null
	) {

		add_action( 'init',					array( $this, 'init' ) );

		// We set the current WPA to use at pre_get_posts with priority 11,
		// since WooCommerce transforms its shop page into the products archive at pre_get_posts:10
		add_action( 'pre_get_posts',		array( $this, 'archive_set' ), 11 );
		add_action( 'pre_get_posts',		array( $this, 'archive_apply_settings' ), 99 );
		add_action( 'template_redirect',	array( $this, 'initialize_archive_loop' ) );
		add_action( 'wp',					array( $this, 'force_disable_404' ), -1 );

		// Fake archive query for AJAX
		add_action( 'pre_get_posts',										array( $this, 'fake_archive_before_set' ), 0 );
		add_action( 'wp_ajax_wpv_get_archive_query_results',				array( $this, 'wpv_get_archive_query_results' ) );
		add_action( 'wp_ajax_nopriv_wpv_get_archive_query_results',			array( $this, 'wpv_get_archive_query_results' ) );

		// Set archive defaults for existing objects
		add_filter( 'wpv_view_settings',									array( $this, 'wpv_view_settings_archive_set_fallbacks' ), 6, 2 );

		add_action( 'wpv_action_apply_archive_query_settings',				array( $this, 'archive_apply_post_type_settings' ), 10, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( $this, 'archive_apply_order_settings' ), 20, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( $this, 'archive_apply_pagination_settings' ), 30, 3 );

		add_action( 'wpv_action_extend_archive_query_for_parametric_and_counters',	array( $this, 'extend_archive_query_for_parametric_and_counters' ), 10, 3 );

		add_filter( 'wpv_filter_wpv_get_dependant_extended_query_args',		array( $this, 'wpv_get_dependant_archive_query_args' ), 10, 3 );

		add_filter( 'wpv_filter_wpv_get_current_archive',					array( $this, 'wpv_get_current_archive' ) );
		add_filter( 'wpv_filter_wpv_get_current_archive_loop',				array( $this, 'wpv_get_current_archive_loop' ) );

		add_filter( 'wpv_filter_wpv_get_archive_unique_hash',				array( $this, 'wpv_get_archive_unique_hash' ) );

		// Extend pagination settings

		add_filter( 'wpv_filter_wpv_get_pagination_settings',				array( $this, 'extend_pagination_settings' ), 20, 2 );
		add_filter( 'wpv_filter_wpv_get_parametric_settings',				array( $this, 'extend_parametric_settings' ), 20, 2 );

		$this->wpa_id				= null;
		$this->wpa_slug				= '';
		$this->wpa_type				= '';
		$this->wpa_name				= '';
		$this->wpa_data				= array();
		$this->wpa_settings			= array();

		$this->query				= null;

		$this->header_started		= false;
		$this->in_head				= false;

		$this->in_the_loop			= false;
		$this->loop_found			= false;

		$this->loop_has_no_posts	= false;

		$this->wpv_settings			= null !== $wpv_settings ? $wpv_settings : WPV_Settings::get_instance();
		$this->post_type_repository = $post_type_repository;

		// Layouts compatibility
		add_action( 'wpv_action_wpv_initialize_wordpress_archive_for_archive_loop',		array( $this, 'wpv_initialize_wordpress_archive_for_archive_loop' ) );
		// General compatibility.
		add_action( 'pre_get_posts', array( $this, 'early_pre_get_posts' ), 9 );
	}

	function __destruct(){

	}

	function init() {
		/*
		DEPRECATED, need some work to delete
		_get_post_type_loops
		*/

		/*
		* ---------------------------------
		* Compatibility
		* ---------------------------------
		*/

		/*
		* WooCommerce
		*
		* Search results on product archive pages with just one result redirect to the product page
		* But if there are no results, the way we fake one dummy post breaks it all
		*
		* @since unknown
		*/

		add_action( 'wpv_action_before_initialize_archive_loop', array( $this, 'wpv_wpa_fix_woocommerce_archives' ), 10, 2 );

	}

	function archive_set( $query ) {
		if (
			! $this->is_frontend()
			|| ! $query->is_main_query()
			|| $query->is_singular
		) {
			return;
		}

		$stored_settings	= $this->wpv_settings;
		$wpa_to_apply		= 0;
		$wpa_slug			= '';

		// See if we have a WPA for the home page
		if ( is_home() ) {
			$wpa_slug = 'view_home-blog-page';
			$this->wpa_type = 'native';
			$this->wpa_name = 'home';
			$this->wpa_data = array();
			if (
				isset( $stored_settings['view_home-blog-page'] )
				&& $stored_settings['view_home-blog-page'] > 0
			) {
				$wpa_to_apply = $stored_settings['view_home-blog-page'];
			}
		}
		// Check if it's a post type archive and if we have a WPA for it
		if ( is_post_type_archive() ) {
			// From $query->is_post_type_archive() using the same logic based on $query->get('post_type')
			// Before 1.7, we checked against $query->get_queried_object()->public and used $query->get_queried_object()->name
			// But sometimes is_post_type_archive() is TRUE and $query->get_queried_object() is not a post type object, but a post object
			// For example, on some scenarios for WooCommerce shop pages
			// In addition, we do not check now whether the post type is public or not: if it wasn't, there would not be a frontend archive for it
			$post_type = $query->get( 'post_type' );
			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}
			$wpa_slug = 'view_cpt_' . $post_type;
			$this->wpa_type = 'post_type';
			$this->wpa_name = $post_type;
			$this->wpa_data = array();
			if (
				isset( $stored_settings['view_cpt_' . $post_type] )
				&& $stored_settings['view_cpt_' . $post_type] > 0
			) {
				$wpa_to_apply = $stored_settings['view_cpt_' . $post_type];
			}
		}
		// Check taxonomy loops
		if ( is_archive() ) {
			if (
				is_tax()
				|| is_category()
				|| is_tag()
			) {// Check this condition, maybe against $query->property directly
				$term = $query->get_queried_object();
				if (
					$term
					&& isset( $term->taxonomy )
				) {
					$wpa_slug = sprintf( self::SETTINGS_TAXONOMY_LOOP, $term->taxonomy );
					$wpa_term_slug = sprintf( self::SETTINGS_TAXONOMY_LOOP_OPTION, $term->taxonomy, $term->slug );

					$current_language = apply_filters( 'wpml_current_language', '' );
					$default_language = apply_filters( 'wpml_default_language', '' );
					if ( $current_language !== $default_language ) {
						$translated_term_id = apply_filters( 'wpml_object_id', $term->term_id, $term->taxonomy, true, $default_language );
						do_action( 'wpml_switch_language', $default_language );
						$original_term = get_term( $translated_term_id, $term->taxonomy );
						do_action( 'wpml_switch_language', $current_language );
						if ( false !== $original_term ) {
							$wpa_term_slug = sprintf( self::SETTINGS_TAXONOMY_LOOP_OPTION, $term->taxonomy, $original_term->slug );
						}
					}

					$this->wpa_type = 'taxonomy';
					$this->wpa_name = $term->taxonomy;
					$this->wpa_data = array(
						'taxonomy'		=> $term->taxonomy,
						'term'			=> $term->slug,
						'term_id'		=> $term->term_id
					);

					if (
						isset( $stored_settings[ $wpa_term_slug ] )
						&& $stored_settings[ $wpa_term_slug ] > 0
					) {
						$wpa_to_apply = $stored_settings[ $wpa_term_slug ];
					} elseif (
						isset( $stored_settings[ $wpa_slug ] )
						&& $stored_settings[ $wpa_slug ] > 0
					) {
						$wpa_to_apply = $stored_settings[ $wpa_slug ];
					}
				}
			}
		}
		// Check other archives
		if ( is_search() ) {
			$wpa_slug = 'view_search-page';
			$this->wpa_type = 'native';
			$this->wpa_name = 'search';
			$this->wpa_data = array(
				's'	=> get_query_var( 's' )
			);
			if (
				isset( $stored_settings['view_search-page'] )
				&& (int) $stored_settings['view_search-page'] > 0
			) {
				$wpa_to_apply = $stored_settings['view_search-page'];
			}
		}
		if ( is_author() ) {
			$wpa_slug = 'view_author-page';
			$this->wpa_type = 'native';
			$this->wpa_name = 'author';
			$this->wpa_data = array(
				'author_name' => get_query_var( 'author_name' )
			);
			if (
				isset( $stored_settings['view_author-page'] )
				&& $stored_settings['view_author-page'] > 0
			) {
				$wpa_to_apply = $stored_settings['view_author-page'];
			}
		}
		if ( is_year() ) {
			$wpa_slug = 'view_year-page';
			$this->wpa_type = 'native';
			$this->wpa_name = 'year';
			$this->wpa_data = array(
				'year' => get_query_var( 'year' )
			);
			if (
				isset( $stored_settings['view_year-page'] )
				&& $stored_settings['view_year-page'] > 0
			) {
				$wpa_to_apply = $stored_settings['view_year-page'];
			}
		}
		if ( is_month() ) {
			$wpa_slug = 'view_month-page';
			$this->wpa_type = 'native';
			$this->wpa_name = 'month';
			$this->wpa_data = array(
				'year'		=> get_query_var( 'year' ),
				'monthnum'	=> get_query_var( 'monthnum' )
			);
			if (
				isset( $stored_settings['view_month-page'] )
				&& $stored_settings['view_month-page'] > 0
			) {
				$wpa_to_apply = $stored_settings['view_month-page'];
			}
		}
		if ( is_day() ) {
			$wpa_slug = 'view_day-page';
			$this->wpa_type = 'native';
			$this->wpa_name = 'day';
			$this->wpa_data = array(
				'year'		=> get_query_var( 'year' ),
				'monthnum'	=> get_query_var( 'monthnum' ),
				'day'		=> get_query_var( 'day' )
			);
			if (
				isset( $stored_settings['view_day-page'] )
				&& $stored_settings['view_day-page'] > 0
			) {
				$wpa_to_apply = $stored_settings['view_day-page'];
			}
		}

		$this->wpa_slug	= $wpa_slug;
		$wpa_to_apply	= wpv_force_wordpress_archive( $wpa_to_apply, $wpa_slug );

		if ( ! is_null( $this->wpa_id ) ) {
			// We have a forced WPA to apply, so we used this method to check which archive page we are in
			// This only hapens on Layouts archive cells as we need to initialize a WPA not assigned to a given loop, overriding the Views stored settings
			$wpa_status = get_post_status( $wpa_to_apply );
			// The WPA must be published ( not trashed )
			if ( $wpa_status == 'publish' ) {
				$this->wpa_settings	= apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $this->wpa_id );
				$this->wpa_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $this->wpa_settings );
			}
		} else if ( $wpa_to_apply > 0 ) {
			// There is a stored WPA to apply
			$wpa_status = get_post_status( $wpa_to_apply );
			// The WPA must be published ( not trashed )
			if ( $wpa_status == 'publish' ) {
				$this->wpa_id		= $wpa_to_apply;
				$this->wpa_settings	= apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $this->wpa_id );
				$this->wpa_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $this->wpa_settings );
			}
		}

		/**
		 * Fire an action after checkign whether the current archive loop has a WPA assigned.
		 *
		 * @param int|null The assigned WPA ID, null otherwise
		 *
		 * @since 2.6.0
		 */
		do_action( 'wpv_action_after_archive_set', $this->wpa_id );

	}

	/**
	* wpv_view_settings_archive_set_fallbacks
	*
	* Set default values for the settings we add on different tags.
	*
	* @since 2.1
	*/

	function wpv_view_settings_archive_set_fallbacks( $view_settings, $view_id ) {
		if (
			isset( $view_settings['view-query-mode'] )
			&& $view_settings['view-query-mode'] != 'normal'
		) {
			$defaults = array(
				'view_purpose'				=> 'all',
				'sections-show-hide'		=> array(
													'filter-extra-parametric'	=> 'off',
													'filter-extra'				=> 'off',
													'content'					=> 'off',
												),
				'orderby'					=> 'post_date',
				'order'						=> 'DESC',
				'orderby_second'			=> '',
				'order_second'				=> 'DESC',
				'pagination'				=> array(
													'type'						=> 'paged',
													'posts_per_page'			=> 'default',
													'effect'					=> 'fade',
													'duration'					=> 500,
													'manage_history'			=> 'off',
													'tolerance'					=> '',
													'preload_images'			=> true,
													'cache_pages'				=> true,
													'preload_pages'				=> true,
													'pre_reach'					=> 1,
													'spinner'					=> 'builtin',
													'spinner_image'				=> WPV_URL . '/res/img/ajax-loader.gif',
													'spinner_image_uploaded'	=> '',
													'callback_next'				=> '' ,
												),
				'filter_meta_html_state'	=> array(
													'html'				=> 'on',
													'css'				=> 'off',
													'js'				=> 'off',
													'img'				=> 'off'
												),
				'filter_meta_html'			=> "[wpv-filter-start hide=\"false\"]\n[wpv-filter-controls][/wpv-filter-controls]\n[wpv-filter-end]",
				'filter_meta_html_css'		=> '',
				'filter_meta_html_js'		=> '',
			);
			foreach ( $defaults as $default_key => $default_value ) {
				if ( ! isset( $view_settings[ $default_key ] ) ) {
					$view_settings[ $default_key ] = $default_value;
				}
			}
			// We need to enforce the screen options, as we already had one entry for the 'content' section
			if ( 'all' == $view_settings['view_purpose'] ) {
				foreach ( $defaults['sections-show-hide'] as $screen_opt_key => $screen_opt_val ) {
					if ( ! isset( $view_settings['sections-show-hide'][ $screen_opt_key ] ) ) {
						$view_settings['sections-show-hide'][ $screen_opt_key ] = $screen_opt_val;
					}
				}
			}

			if ( defined( 'WPV_BLOCK_PREVIEW_RENDER' ) && isset( $view_settings['orderby'] )
				&& 'rand'
				=== $view_settings['orderby'] ) {
				$view_settings['orderby'] = 'title';
			}
		}
		return $view_settings;
	}

	function archive_apply_settings( $query ) {

		if (
			! $this->is_frontend()
			|| ! $query->is_main_query()
			|| ! $this->wpa_id
		) {
			return;
		}

		/**
		 * Adjust the WordPress Archive settings just before the main WordPress Archive query.
		 *
		 * @param array $wpa_settings
		 * @return $wpa_settings
		 * @since 3.0
		 */
		$wpa_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $this->wpa_settings );

		/**
		 * Hook for the actions applied to the archive query settings.
		 *
		 * @param $query
		 * @param $wpa_settings
		 * @param $wpa_id
		 */
		do_action( 'wpv_action_apply_archive_query_settings', $query, $wpa_settings, $this->wpa_id );

	}

	/**
	* archive_apply_post_type_settings
	*
	* Apply post types settings for WordPress Archives.
	*
	* Usefull in the blog and other native loops, categories and tags archives, and search result.
	*
	* @note Post type order seems irrelevant
	*
	* @since 2.1
	*/

	function archive_apply_post_type_settings( $query, $archive_settings, $archive_id ) {
		/*
		if ( $query->get( 'wpv_archive_loop_cell' ) ) {
			// When coming from a Layouts cell, do not apply stored post type options... maybe
			return;
		}
		*/
		$stored_settings = WPV_Settings::get_instance();
		$wpv_post_types_for_archive_loop = $stored_settings->wpv_post_types_for_archive_loop;
		$stored_settings_per_type = isset( $wpv_post_types_for_archive_loop[ $this->wpa_type ] ) ? $wpv_post_types_for_archive_loop[ $this->wpa_type ] : array();
		$stored_settings_per_loop = isset( $stored_settings_per_type[ $this->wpa_name ] ) ? $stored_settings_per_type[ $this->wpa_name ] : array();

		if (
			empty( $stored_settings_per_loop )
			&& 'taxonomy' === $this->wpa_type
			&& in_array( $this->wpa_name, array( 'category', 'post_tag' ) )
		) {
			$stored_settings_per_loop = $this->get_default_post_types_for_native_taxonomy( $this->wpa_name );
		}

		if ( ! empty( $stored_settings_per_loop ) ) {
			$query->set('post_type', $stored_settings_per_loop );
		}
	}

	/**
	 * Adjust the post types assigned to a WPA used on native taxonomies.
	 *
	 * No post type setting means query only for posts, which breaks the archive loop query.
	 * We need to manually enforce CPTs assigned to native taxonomies.
	 *
	 * @param string $taxonomy
	 *
	 * @return array
	 *
	 * @since 2.6.2
	 */
	function get_default_post_types_for_native_taxonomy( $taxonomy ) {
		$types_cpt = get_option('wpcf-custom-types');
		if (
			! is_array( $types_cpt )
			|| empty( $types_cpt )
		) {
			$types_cpt = array();
		}
		$post_types_for_native = array( 'post' );

		foreach ( $types_cpt as $cpt_slug => $cpt ) {
			if (
				array_key_exists( 'taxonomies', $cpt )
				&& is_array( $cpt['taxonomies'] )
			) {
				foreach ( $cpt['taxonomies'] as $tax_slug => $value ) {
					if (
						$taxonomy == $tax_slug
						&& $value
					) {
						$post_types_for_native[] = $cpt_slug;
					}
				}
			}
		}

		return $post_types_for_native;
	}

	/**
	* archive_apply_order_settings
	*
	* Apply sorting settings for WordPress Archives.
	* By now, just two settings: orderby and order.
	*
	* $archive_settings = array(
	*	'orderby'	=> 'post_date'|...,
	*	'order'		=> 'ASC'|'DESC'
	* 	'orderby_as'	=> ''|'STRING'|'NUMERIC'
	* );
	*
	* @since 2.1
	*
	* @todo The posted sorting options demand the wpv_view_count URL parameter, which we are not posting now
	*/

	function archive_apply_order_settings( $query, $archive_settings, $archive_id ) {

		if ( $query->get( 'wpv_dependency_query' ) ) {
			return;
		}

		$is_view_posted = false;
		if ( isset( $_GET['wpv_view_count'] ) ) {
			$view_unique_hash = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $archive_settings );
			if ( esc_attr( $_GET['wpv_view_count'] ) == $view_unique_hash ) {
				$is_view_posted = true;
				// Map old URL parameters to new ones
				do_action( 'wpv_action_wpv_pagination_map_legacy_order' );
			}
		}

		$order		= $archive_settings['order'];
		$orderby	= $archive_settings['orderby'];
		$orderby_as	= $archive_settings['orderby_as'];

		$order_second	= $archive_settings['order_second'];
		$orderby_second	= $archive_settings['orderby_second'];

		$valid_orderby_second	= array(
			'date', 'post_date', 'post-date',
			'title', 'post_title', 'post-title',
			'id', 'post_id', 'post-id', 'ID',
			'author', 'post_author', 'post-author',
			'type', 'post_type', 'post-type',
			'modified', 'menu_order', 'rand'
		);

		// Modern order URL override
		if ( $is_view_posted ) {
			if (
				isset( $_GET['wpv_sort_order'] )
				&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_order'] ) ), array( 'ASC', 'DESC' ) )
			) {
				$order = strtoupper( esc_attr( $_GET['wpv_sort_order'] ) );
			}

			if (
				isset( $_GET['wpv_sort_orderby'] )
				&& esc_attr( $_GET['wpv_sort_orderby'] ) != 'undefined'
				&& esc_attr( $_GET['wpv_sort_orderby'] ) != ''
			) {
				$orderby = esc_attr( $_GET['wpv_sort_orderby'] );
			}

			if (
				isset( $_GET['wpv_sort_orderby_as'] )
				&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) ), array( 'STRING', 'NUMERIC' ) )
			) {
				$orderby_as = strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) );
			}

			// Secondary sorting
			if (
				isset( $_GET['wpv_sort_order_second'] )
				&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_order_second'] ) ), array( 'ASC', 'DESC' ) )
			) {
				$order_second = strtoupper( esc_attr( $_GET['wpv_sort_order_second'] ) );
			}

			if (
				isset( $_GET['wpv_sort_orderby_second'] )
				&& esc_attr( $_GET['wpv_sort_orderby_second'] ) != 'undefined'
				&& esc_attr( $_GET['wpv_sort_orderby_second'] ) != ''
				&& in_array( $_GET['wpv_sort_orderby_second'], $valid_orderby_second )
			) {
				$orderby_second = esc_attr( $_GET['wpv_sort_orderby_second'] );
			}
		}

		if ( strpos( $orderby, 'field-' ) === 0 ) {
			$meta_key = substr( $orderby, 6 );
			$type = $orderby_as;
			$is_types_field_data = wpv_is_types_custom_field ( $meta_key );
			if (
				$is_types_field_data
				&& isset( $is_types_field_data['meta_key'] )
				&& isset( $is_types_field_data['type'] )
			) {
				$meta_key = $is_types_field_data['meta_key'];
				$type = $is_types_field_data['type'];
			}

			// User preference overrides the auto-discover
			if (
					isset( $orderby_as )
					&& in_array( $orderby_as, array( 'STRING', 'NUMERIC' ) )
			) {
				switch ( $orderby_as ) {
					case "STRING":
						$orderby = 'meta_value';
						break;
					case "NUMERIC":
						$orderby = 'meta_value_num';
						break;
				}
			} elseif ( in_array( $type, array( 'numeric', 'date' ) ) ) {	// Auto-Discover
				$orderby = 'meta_value_num';
			} else {
				$orderby = 'meta_value';
			}

			$query->set( 'meta_key', $meta_key );

		}

		// Normalize orderby and orderby_second options
		$orderby		= WPV_Sorting_Embedded::normalize_post_orderby_value( $orderby );
		$orderby_second	= WPV_Sorting_Embedded::normalize_post_orderby_value( $orderby_second );

		global $wp_version;
		if (
			! version_compare( $wp_version, '4.0', '<' )
			&& $orderby != 'rand'
			&& $orderby_second != ''
			&& $orderby != $orderby_second
		) {
			$orderby_array = array(
				$orderby		=> $order,
				$orderby_second	=> $order_second
			);
			$query->set( 'orderby',	$orderby_array );
		} else {
			$query->set( 'orderby', $orderby );
			$query->set( 'order', $order );
			$query->set( 'wpv_orderby', $orderby );
			$query->set( 'wpv_order', $order );
		}

	}

	/**
	* archive_apply_pagination_settings
	*
	* Apply pagination settings for WordPress Archives.
	* By now, just two settings: pagintion>mode and pagination>posts_per_page.
	*
	* $archive_settings['pagination'] = array(
	*	'mode'				=> 'disabled'|'paged'(|'ajaxed'|'rollover'?),
	*	'posts_per_page'	=> 'default'|(int)
	* );
	*
	* @since 2.1
	*/

	function archive_apply_pagination_settings( $query, $archive_settings, $archive_id ) {

		if ( $query->get( 'wpv_dependency_query' ) ) {
			return;
		}

		// Validate stored settings
		$archive_settings['pagination']['type']				=
			( isset( $archive_settings['pagination']['type'] ) && in_array( $archive_settings['pagination']['type'], array( 'disabled', 'paged', 'ajaxed', 'rollover' ) ) ) ?
				$archive_settings['pagination']['type'] :
				'paged';
		$archive_settings['pagination']['posts_per_page']	=
			( isset( $archive_settings['pagination']['posts_per_page'] ) ) ?
				$archive_settings['pagination']['posts_per_page'] :
				'default';

		// Apply settings
		if ( $archive_settings['pagination']['type'] == 'disabled' ) {
			$query->set( 'posts_per_page', -1 );
		} else if ( $archive_settings['pagination']['posts_per_page'] != 'default' ) {
			$query->set( 'posts_per_page', (int) $archive_settings['pagination']['posts_per_page'] );
		}

	}

	static function extend_archive_query_for_parametric_and_counters( $post_query, $archive_settings, $archive_id ) {
		$dps_enabled		= false;
		$counters_enabled	= false;

		if (
			! isset( $archive_settings['dps'] )
			|| ! is_array( $archive_settings['dps'] )
		) {
			$archive_settings['dps'] = array();
		}
		if (
			isset( $archive_settings['dps']['enable_dependency'] )
			&& $archive_settings['dps']['enable_dependency'] == 'enable'
		) {
			$dps_enabled = true;
			$controls_per_kind = wpv_count_filter_controls( $archive_settings );
			$controls_count = 0;
			$no_intersection = array();
			if ( ! isset( $controls_per_kind['error'] ) ) {
				$controls_count = $controls_per_kind['cf'] + $controls_per_kind['tax'] + $controls_per_kind['pr'] + $controls_per_kind['search'];
				if (
					$controls_per_kind['cf'] > 1
					&& (
						! isset( $archive_settings['custom_fields_relationship'] )
						|| $archive_settings['custom_fields_relationship'] != 'AND'
					)
				) {
					$no_intersection[] = __( 'custom field', 'wpv-views' );
				}
				if (
					$controls_per_kind['tax'] > 1
					&& (
						! isset( $archive_settings['taxonomy_relationship'] )
						|| $archive_settings['taxonomy_relationship'] != 'AND'
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
		if ( ! isset( $archive_settings['filter_meta_html'] ) ) {
			$archive_settings['filter_meta_html'] = '';
		}
		if ( strpos( $archive_settings['filter_meta_html'], '%%COUNT%%' ) !== false ) {
			$counters_enabled = true;
		}

		if (
			! $dps_enabled
			&& ! $counters_enabled
		) {
			// Set the force value
			do_action( 'wpv_action_wpv_force_disable_dps', true );
			return;
		} else {
			do_action( 'wpv_action_wpv_force_disable_dps', false );
		}

		$already = array();
		if (
			isset( $post_query->posts )
			&& ! empty( $post_query->posts )
		) {
			foreach ( (array) $post_query->posts as $post_object ) {
				$already[] = $post_object->ID;
			}
		}

		$query_args = $post_query->query_vars;

		$override_settings = array();
		if ( isset( $query_args['post_type'] ) ) {
			$override_settings['post_type'] = is_array( $query_args['post_type'] ) ? $query_args['post_type'] : array( $query_args['post_type'] );
		}

		$parametric_search_data_to_cache = WPV_Cache::get_parametric_search_data_to_cache( $archive_settings, $override_settings );

		WPV_Cache::generate_native_cache( $already, $parametric_search_data_to_cache );

		if ( isset ( $query_args['pr_filter_post__in'] ) ) {
			$query_args['post__in'] = $query_args['pr_filter_post__in'];
		} else {
			// If just for the missing ones, generate the post__not_in argument
			if ( isset( $query_args['post__not_in'] ) ) {
				$query_args['post__not_in'] = array_merge( (array) $query_args['post__not_in'], (array) $already );
			} else {
				$query_args['post__not_in'] = (array) $already;
			}
			// And adjust on the post__in argument
			if ( isset( $query_args['post__in'] ) ) {
				$query_args['post__in'] = array_diff( (array) $query_args['post__in'], (array) $query_args['post__not_in'] );
			}
		}

		$keys = array(
			'error',
			//'m',
			//'p',
			//'post_parent',
			'subpost',
			'subpost_id',
			'attachment',
			'attachment_id',
			'name',
			'static',
			'pagename',
			'page_id',
			//'second',
			//'minute',
			//'hour',
			//'day',
			//'monthnum',
			//'year',
			//'w',
			//'category_name',
			//'tag',
			//'cat',
			//'tag_id',
			//'author',
			//'author_name',
			'feed',
			'tb',
			'paged',
			//'meta_key',
			//'meta_value',
			'preview',
			//'s',
			'sentence',
			'title',
			//'fields',
			'menu_order',
			'embed',
			'wpv_fake_archive_loop'
		);

		foreach ( $keys as $k ) {
			if ( isset( $query_args[$k] ) ) {
				unset( $query_args[$k] );
			}
		}

		$query_args['nopaging'] 		= true;
		$query_args['fields'] 			= 'ids';
		$query_args['posts_per_page']	= -1;

		/**
		 * Before performing the auxiliar query for calculating avalable form inputs, or calculate counters.
		 *
		 * @param array $query_args
		 * @since 2.9
		 */
		do_action( 'wpv_action_wpv_before_extended_archive_query_for_parametric_and_counters', $query_args );

		$aux_cache_query = new WP_Query( $query_args );

		/**
		 * After performing the auxiliar query for calculating avalable form inputs, or calculate counters.
		 *
		 * @param array $query_args
		 * @param WP_Query $aux_cache_query
		 * @since 2.9
		 */
		do_action( 'wpv_action_wpv_after_extended_archive_query_for_parametric_and_counters', $query_args, $aux_cache_query );

		// Add the auxiliar query results to the list of returned IDs
		// Generate the "extra" cache
		if (
			is_array( $aux_cache_query->posts )
			&& ! empty( $aux_cache_query->posts )
		) {
			WPV_Cache::generate_cache( $aux_cache_query->posts, $parametric_search_data_to_cache );
		}
	}

	function wpv_get_dependant_archive_query_args( $args = array(), $archive_settings = array(), $affected_data = array() ) {
		if (
			isset( $archive_settings['view-query-mode'] )
			&& $archive_settings['view-query-mode'] != 'normal'
		) {
			$wpa_loop = array(
				'type'	=> $this->wpa_type,
				'name'	=> $this->wpa_name,
				'data'	=> $this->wpa_data,
				'id'	=> $this->wpa_id
			);
			$args = $this->fake_archive_query( $wpa_loop );
			$args['posts_per_page'] 		= -1;
			$args['limit'] 					= -1;
			$args['paged'] 					= 1;
			$args['offset'] 				= 0;
			$args['fields'] 				= 'ids';
			$args['wpv_dependency_query']	= $affected_data;
		}
		return $args;
	}

	/**
	* initialize_archive_loop
	*
	* @todo check whether we can rename this safely
	* @todo check whether we can move this to the wp hook - TOO EARLY
	*
	* @since unknown
	*/

	function initialize_archive_loop() {

		$wpa_id		= $this->wpa_id;
		$wpa_slug	= $this->wpa_slug;

		if ( ! $wpa_id ) {
			return;
		}

		global $wp_query;

		do_action( 'wpv_action_before_initialize_archive_loop', $wpa_id, $wpa_slug );

		if ( ! have_posts() ) {
			// We need to handle empty loops and force the loop processing
			// Create a dummy WP_Post and set the post count to 1
			// That will fire the loop_start and loop_end hooks
			$wp_query->post_count = 1;
			$dummy_post_obj = (object) array(
				'ID'				=> $wpa_id,
				'post_author'		=> '1',
				'post_name'			=> '',
				'post_type'			=> '',
				'post_title'		=> '',
				'post_date'			=> '0000-00-00 00:00:00',
				'post_date_gmt'		=> '0000-00-00 00:00:00',
				'post_content'		=> '',
				'post_excerpt'		=> '',
				'post_status'		=> 'publish',
				'comment_status'	=> 'closed',
				'ping_status'		=> 'closed',
				'post_password'		=> '',
				'post_parent'		=> 0,
				'post_modified'		=> '0000-00-00 00:00:00',
				'post_modified_gmt'	=> '0000-00-00 00:00:00',
				'comment_count'		=> '0',
				'menu_order'		=> '0'
			);
			$dummy_post = new WP_Post( $dummy_post_obj );
			$wp_query->posts = array( $dummy_post );
			$this->loop_has_no_posts = true;
		}
		if ( have_posts() ) {
			$output_post = get_post( $wpa_id );
            if ( $output_post ) {
				// Save the original query.
				$action_args = array(
					'wpa_id'		=> $wpa_id,
					'wpa_slug'		=> $wpa_slug,
					'wpa_settings'	=> $this->wpa_settings,
					'wpa_object'	=> $this
				);
				do_action( 'wpv_action_wpv_before_clone_archive_loop', $wp_query, $action_args );
				$this->query = ( $wp_query instanceof WP_Query ) ? clone $wp_query : null;
				add_action( 'loop_start',	array( $this, 'loop_start' ), 1, 1 );
				add_action( 'loop_end',		array( $this, 'loop_end' ), 999, 1 );
				add_action( 'get_header',	array( $this, 'get_header' ) );
				// Prevent the view from being displayed in the head.
				// JetPack can cause this.
				add_action( 'wp_head',		array( $this, 'html_head_start' ), -100 ); // try to load first
				add_action( 'wp_head',		array( $this, 'html_head_end' ), 999 ); // try to load last
			}
		}

		do_action( 'wpv_action_extend_archive_query_for_parametric_and_counters', $wp_query, $this->wpa_settings, $wpa_id );

	}

	function force_disable_404() {
		if ( ! is_null( $this->wpa_id ) ) {
			global $wp_query;
			if ( $wp_query->is_404 ) {
				$wp_query->is_404 = false;
			}
		}
	}


	function get_archive_loop_query() {
		if ( $this->in_the_loop ) {
			return $this->query;
		}
	}


	function get_header( $name ) {
		$this->header_started = true;
	}


	function html_head_start() {
		$this->in_head = true;
	}


	function html_head_end() {
		$this->in_head = false;
	}


	/**
	 * Generates a WPA Archive Edit link based on it's post id.
	 *
	 * @param int $post_id Post id for WPA Archive.
	 * @return mixed|void
	 */
	public static function archive_edit_link( $post_id ) {
		return apply_filters(
			'wpv_filter_wpa_edit_link',
			add_query_arg(
				array(
					'page' => 'view-archives-editor',
					'view_id' => $post_id,
				),
				admin_url( 'admin.php' )
			),
			$post_id
		);
	}

	/**
	 * Returns an array with the terms slugs that are assigned to the current archive.
	 *
	 * @param WP_Post $archive_post Post referencing the WPA.
	 * @param string $taxonomy_slug The taxonomy slug.
	 *
	 * @return string[]
	 */
	public function get_archive_taxonomy_terms( $archive_post, $taxonomy_slug ) {
		$pattern_template = self::SETTINGS_TAXONOMY_LOOP_OPTION_REGEXP_TEMPLATE;
		$settings_term_options_pattern = sprintf( $pattern_template, $taxonomy_slug );
		$settings_terms = $this->wpv_settings->preg_key( $settings_term_options_pattern );
		$selected_terms = [];

		foreach ( $settings_terms as $settings_term_key ) {
			if ( intval( $archive_post->ID ) === $this->wpv_settings[ $settings_term_key ] ) {
				$term_matches = [];
				preg_match( $settings_term_options_pattern, $settings_term_key, $term_matches );
				if ( count( $term_matches ) > 0 ) {
					$selected_terms[] = $term_matches[1];
				}
			}
		}

		return $selected_terms;
	}

	function loop_start( $query ) {
		if (
			! $this->in_head
			&& $this->header_started
			&& $query->is_main_query()
			&& (
				$query->query_vars_hash == $this->query->query_vars_hash
				|| $query->request == $this->query->request
			)
		) {
			ob_start();
			$this->post_count = $query->post_count;
			$query->post_count = 1;
			$this->loop_found = true;
		}
	}


	function loop_end( $query ) {
		if (
			$this->loop_found
			&& $query->is_main_query()
		) {
			ob_end_clean();

			if ( $this->loop_has_no_posts ) {
				// Reset everything if the loop has no posts.
				// Then the View will render with no posts.

				global $post, $wp_query;

				$this->post_count = 0;
				$this->query->post_count = 0;
				$wp_query->post_count = 0;

				$wp_query->posts = array();
				$this->query->posts = array();

				$post = null;
			}

			$query->post_count = $this->post_count;

			$this->in_the_loop = true;

			$wpa_output = render_view( array( 'id' => $this->wpa_id ) );

			// For the case where the "toolset_the_content_wpa" filter is applied to the output of a WordPress Archive, the "wpautop" callback
			// is moved to the next priority in which the filter is applied to the WordPress Archive output to prevent undesired
			// markup.
			$priority = has_filter( 'toolset_the_content_wpa', 'wpautop' ) ?: 10;
			remove_filter( 'toolset_the_content_wpa', 'wpautop', $priority );

			/**
			 * Filtering the content of the WordPress Archive output.
			 *
			 * @param string $wpa_output The output of the current WordPress Archive.
			 *
			 * @return string
			 */
			$wpa_output = apply_filters( 'toolset_the_content_wpa', $wpa_output );

			// Here we are restoring the removed "wpautop" callback hooked in the "the_content" filter.
			add_filter( 'toolset_the_content_wpa', 'wpautop', $priority );

			// Because escaping is supposed to be handled already in `render_view()` and `the_content` filters:
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $wpa_output;

			$this->in_the_loop = false;

			$this->loop_found = false;
		}

	}

	/**
	 * @deprecated Use $this->get_archive_loops() instead.
	 * @return array
	 */
	function _get_post_type_loops() {
		$loops = array(
			'home-blog-page'	=> __('Home/Blog', 'wpv-views'),
			'search-page'		=> __('Search results', 'wpv-views'),
			'author-page'		=> __('Author archives', 'wpv-views'),
			'year-page'			=> __('Year archives', 'wpv-views'),
			'month-page'		=> __('Month archives', 'wpv-views'),
			'day-page'			=> __('Day archives', 'wpv-views')
		);

		// Only offer loops for post types that already have an archive
		$post_types = get_post_types( array( 'public'=>true, 'has_archive' => true ), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( ! in_array( $post_type->name, array( 'post', 'page', 'attachment' ) ) ) {
				$type = 'cpt_' . $post_type->name;
				$name = $post_type->labels->name;
				$loops[ $type ] = $name;
			}
		}

		return $loops;
	}


	/**
	 * Wrapper of get_archive_loops only for post_types, returns the array indexed with the post type name as key.
	 *
	 * @param bool $include_wpa Optional. Determines whether the information about WPA assigned to this loop should be
	 *     retrieved (the $wpa element). Default is false.
	 * @param bool $include_ct Optional. Determines whether the information about CT assigned to given post type archive
	 *     or taxonomy archive should be retrieved (the $ct element). Default is false.
	 * @param bool $noexclude Optional. If true, no loops of given type will be excluded. Default is false.
	 *
	 * @return array An array of information about native archive loops and loops for custom post types and taxonomies.
	 *     Each element is an array representing one loop:
	 *     array(
	 *         @type string $slug Unique slug (within loop type) as used in other parts of Views.
	 *         @type string $display_name Display name for the loop.
	 *         @type string $post_type_name For 'post_type' loop type, this will contain "raw" post type slug.
	 *         @type string $loop_type 'native'|'post_type'|'taxonomy'
	 *         @type int $wpa If $include_wpa is true, this contains an ID of WPA assigned to this loop, or zero if
	 *             no WPA is assigned.
	 *         @type int $ct If $include_ct is true, this contains an ID of CT assigned to this custom post type
	 *             archive or taxonomy archive, or zero if no CT is assigned. This element isn't present for native loops.
	 *         @type int $single_ct If $include_ct is true, this contains an ID of CT assigned to single posts of
	 *             this custom post type, or zero if no CT is assigned. This element is present only for post types.
	 *     )
	 */
	public function get_indexed_post_type_loops( $include_wpa = false, $include_ct = false, $noexclude = false ) {
		$loops = $this->get_archive_loops( 'post_type', $include_wpa, $include_ct, $noexclude );
		$indexed_loops = [];
		foreach ( $loops as $loop ) {
			$indexed_loops[ $loop['post_type_name'] ] = $loop;
		}
		return $indexed_loops;
	}


	/**
	 * Get information about currently existing archive loops.
	 *
	 * @param string $loop_type Optional. Desired type of loops. 'native'|'post_type'|'taxonomy'|'all'. Default is 'all'.
	 * @param bool $include_wpa Optional. Determines whether the information about WPA assigned to this loop should be
	 *     retrieved (the $wpa element). Default is false.
     * @param bool $include_ct Optional. Determines whether the information about CT assigned to given post type archive
     *     or taxonomy archive should be retrieved (the $ct element). Default is false.
     * @param bool $noexclude Optional. If true, no loops of given type will be excluded. Default is false.
	 *
	 * @return array An array of information about native archive loops and loops for custom post types and taxonomies.
	 *     Each element is an array representing one loop:
	 *     array(
	 *         @type string $slug Unique slug (within loop type) as used in other parts of Views.
	 *         @type string $display_name Display name for the loop.
	 *         @type string $post_type_name For 'post_type' loop type, this will contain "raw" post type slug.
	 *         @type string $loop_type 'native'|'post_type'|'taxonomy'
	 *         @type int $wpa If $include_wpa is true, this contains an ID of WPA assigned to this loop, or zero if
	 *             no WPA is assigned.
     *         @type int $ct If $include_ct is true, this contains an ID of CT assigned to this custom post type
     *             archive or taxonomy archive, or zero if no CT is assigned. This element isn't present for native loops.
     *         @type int $single_ct If $include_ct is true, this contains an ID of CT assigned to single posts of
     *             this custom post type, or zero if no CT is assigned. This element is present only for post types.
	 *     )
	 *
	 * @since 1.7
	 *
     * @todo consider implementing caching mechanism
	 */
	public function get_archive_loops( $loop_type = 'all', $include_wpa = false, $include_ct = false, $noexclude = false ) {

		$stored_settings = $this->wpv_settings;

		switch( $loop_type ) {

			case 'native':
				$loops = array(
					array(
						'slug'			=> 'home-blog-page',
						'option'		=> 'view_home-blog-page',
						'loop_type'		=> 'native',
						'display_name'	=> __( 'Home/Blog', 'wpv-views' )
					),
					array(
						'slug'			=> 'search-page',
						'option'		=> 'view_search-page',
						'loop_type'		=> 'native',
						'display_name'	=> __( 'Search results', 'wpv-views' )
					),
					array(
						'slug'			=> 'author-page',
						'option'		=> 'view_author-page',
						'loop_type'		=> 'native',
						'display_name'	=> __( 'Author archives', 'wpv-views' )
					),
					array(
						'slug'			=> 'year-page',
						'option'		=> 'view_year-page',
						'loop_type'		=> 'native',
						'display_name'	=> __( 'Year archives', 'wpv-views' )
					),
					array(
						'slug'			=> 'month-page',
						'option'		=> 'view_month-page',
						'loop_type'		=> 'native',
						'display_name'	=> __( 'Month archives', 'wpv-views' )
					),
					array(
						'slug'			=> 'day-page',
						'option'		=> 'view_day-page',
						'loop_type'		=> 'native',
						'display_name'	=> __( 'Day archives', 'wpv-views' )
					)
				);

				if ( $include_wpa ) {
					$loop_count = count( $loops );
					for( $i = 0; $i < $loop_count; ++$i ) {
						$option = $loops[ $i ]['option'];
						$loops[ $i ]['wpa'] = isset( $stored_settings[ $option ] ) ? $stored_settings[ $option ] : 0;
					}
				}
				return $loops;

			case 'post_type':

				$pt_loops = array();
				// Only offer loops for post types that already have an archive, unless $noexclude is given
                $pt_query_args = array( 'public' => true );
                if ( ! $noexclude ) {
                    $pt_query_args['has_archive'] = true;
                }
				$post_types = get_post_types( $pt_query_args, 'objects' );

				foreach ( $post_types as $post_type ) {
					if (
						$noexclude
						|| ! in_array( $post_type->name, array( 'post', 'page', 'attachment' ) )
					) {

						$loop = array(
							'slug'				=> 'cpt_' . $post_type->name,
							'post_type_name'	=> $post_type->name,
							'option'			=> 'view_cpt_' . $post_type->name,
							'display_name'		=> $post_type->labels->name,
							'singular_name'		=> $post_type->labels->singular_name,
							'loop_type'			=> 'post_type'
						);

						if( $include_wpa ) {
							$loop['wpa'] = isset( $stored_settings[ $loop['option'] ] ) ? $stored_settings[ $loop['option'] ] : 0;
						}

                        if( $include_ct ) {

                        	$content_template_archive_settings_key = sprintf(
                        		'%s%s',
								\WPV_Settings::CPT_ARCHIVES_CT_ASSIGNMENT_PREFIX,
								$post_type->name
							);

							$content_template_conditions_settings_key = sprintf(
								'%s%s',
								\WPV_Settings::SINGLE_POST_TYPES_CT_CONDITIONS_ASSIGNMENT_PREFIX,
								$post_type->name
							);

							$content_template_settings_key = sprintf(
								'%s%s',
								\WPV_Settings::SINGLE_POST_TYPES_CT_ASSIGNMENT_PREFIX,
								$post_type->name
							);

							$loop[ self::KEY_LOOP_ARCHIVE_CT ] = wpv_getarr( $stored_settings, $content_template_archive_settings_key, 0 );
							$loop[ self::KEY_LOOP_SINGLE_CT ] = wpv_getarr( $stored_settings, $content_template_settings_key, 0 );

							$loop[ self::KEY_LOOP_SINGLE_CT_CONDITIONS ] = UsagePostType::createFromDatabaseArray(
								$loop[ self::KEY_LOOP_SINGLE_CT ],
								wpv_getarr( $stored_settings, $content_template_conditions_settings_key, [] )
							);

                        }

						$pt_loops[ $post_type->name ] = $loop;
					}
				}

				return $pt_loops;

			case 'taxonomy':

				$tx_loops = array();
				$taxonomies = get_taxonomies( '', 'objects' );
				$exclude_tax_slugs = array();
                if ( ! $noexclude ) {
                    $exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
                }
				foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
					if ( in_array( $taxonomy_slug, $exclude_tax_slugs ) ) {
						continue;
					}
					// Only show taxonomies with show_ui set to TRUE
					if ( ! $taxonomy->show_ui ) {
						continue;
					}

					$loop = array(
						'slug'			=> $taxonomy->name,
						'option'		=> 'view_taxonomy_loop_' . $taxonomy->name,
						'display_name'	=> $taxonomy->labels->name,
						'loop_type'		=> 'taxonomy'
					);

					if ( $include_wpa ) {
						$loop['wpa'] = isset( $stored_settings[ $loop['option'] ] ) ? $stored_settings[ $loop['option'] ] : 0;
						$loop['wpa_options'] = $this->get_archive_settings_for_taxonomy_terms( $taxonomy->name );
					}

                    if ( $include_ct ) {
                        $loop['ct'] = wpv_getarr( $stored_settings, "views_template_loop_{$taxonomy->name}", 0 );
                    }

					$tx_loops[] = $loop;
				}

				return $tx_loops;

			case 'all':
			default:
				return array_merge(
					$this->get_archive_loops( 'native', $include_wpa ),
					$this->get_archive_loops( 'post_type', $include_wpa ),
					$this->get_archive_loops( 'taxonomy', $include_wpa )
				);
		}
	}

	function _view_edit_options( $view_id, $options ) { // MAYBE DEPRECATED
		static $js_added = false;

		$title = '';
		if (isset($_GET['view_archive'])) {
			$options['view_' . $_GET['view_archive']] = $view_id;
			$loops = $this->_get_post_type_loops();
			$title = sprintf('%s-archive', $loops[$_GET['view_archive']]);
		}

		if (isset($_GET['view_archive_taxonomy'])) {
			$options['view_taxonomy_loop_' . $_GET['view_archive_taxonomy']] = $view_id;
			$taxonomies = get_taxonomies('', 'objects');
			$title = sprintf('%s-taxonomy-archive', $taxonomies[$_GET['view_archive_taxonomy']]->labels->name);
		}

		if ($title != '' && !$js_added) {
			// add some js to set the post title.

			?>
			<script type="text/javascript">
				jQuery(function($){
					jQuery('#title').val('<?php echo esc_js($title); ?>');
				});
			</script>
			<?php
			$js_added = true;
		}

		return $options;
	}

	/**
	 * Prints the archive creation popup
	 */
	public function create_view_archive_popup() {
		printf(
			self::CREATE_ARCHIVE_POPUP_TEMPLATE,
			esc_attr( __( 'Name this WordPress Archive', 'wpv-views' ) ),
			esc_attr( __( 'WordPress Archive name', 'wpv-views' ) )
		);
	}


	public function check_archive_loops_exists() {
		$stored_settings = $this->wpv_settings;

		$loops = $this->_get_post_type_loops();

		$settings_array = $stored_settings->get();// @todo this might not be needed as it implements ArrayAccess anyway

		foreach ( $loops as $loop => $loop_name ) {

			if (
				isset( $settings_array[ 'view_' . $loop ] )
				&& $settings_array[ 'view_' . $loop ] !== 0
			) {
				unset( $loops[ $loop ] );
			}

		}

		$taxonomies = get_taxonomies( '', 'objects' );
		$exclude_tax_slugs = array();
		$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );

		foreach ( $taxonomies as $category_slug => $category ) {

			if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
				unset( $taxonomies[ $category_slug ] );
				continue;
			}
			if ( ! $category->show_ui ) {
				unset( $taxonomies[ $category_slug ] );
				continue; // Only show taxonomies with show_ui set to TRUE
			}

			if (
				isset( $settings_array[ 'view_taxonomy_loop_' . $category_slug ] )
				&& $settings_array[ 'view_taxonomy_loop_' . $category_slug ] !== 0
			) {
				unset( $taxonomies[ $category_slug ] );
			}

		}


		return ! ( empty( $loops ) && empty( $taxonomies ) );
	}


	function update_view_archive_settings( $post_id, $data ) {
		$stored_settings = $this->wpv_settings;

		$found = false;

		// clear existing ones
		$loops = $this->_get_post_type_loops();
		foreach ( $loops as $type => $name ) {
			if (
				isset( $stored_settings['view_' . $type] )
				&& $stored_settings['view_' . $type] == $post_id
			) {
				unset( $stored_settings['view_' . $type] );
				$found = true;
			}
		}
		$taxonomies = get_taxonomies( '', 'objects' );
		foreach ( $taxonomies as $category_slug => $category ) {
			if (
				isset( $stored_settings['view_taxonomy_loop_' . $category_slug] )
				&& $stored_settings['view_taxonomy_loop_' . $category_slug] === $post_id
			) {
				unset( $stored_settings['view_taxonomy_loop_' . $category_slug] );
				$found = true;
			}

			$pattern_template = self::SETTINGS_TAXONOMY_LOOP_OPTION_REGEXP_TEMPLATE;
			$settings_term_options_pattern = sprintf( $pattern_template, $category_slug );
			$settings_terms = $stored_settings->preg_key( $settings_term_options_pattern );

			foreach ( $settings_terms as $settings_term_key ) {
				if ( $stored_settings[ $settings_term_key ] === $post_id ) {
					unset( $stored_settings[ $settings_term_key ] );
					$found = true;
				}
			}
		}

		foreach ( $data as $key => $value ) {
			$key = sanitize_text_field( $key );
			if ( strpos( $key, 'wpv-view-loop-' ) === 0 ) {
				preg_match( '/wpv-view-loop-(.*)/', $key, $out );
				$stored_settings['view_' . $out[1]] = $post_id;
				$found = true;
			}
			if ( strpos( $key, 'wpv-view-taxonomy-loop-' ) === 0 ) {
				$stored_settings['view_taxonomy_loop_' . substr( $key, 23 )] = $post_id;
				$found = true;
			}
			if ( strpos( $key, 'view_taxonomy_option_loop_' ) === 0 ) {
				$stored_settings[ $key ] = (int) $post_id;
				$found = true;
			}
		}

        $stored_settings->refresh_view_settings_data();

		if ( $found ) {
            $stored_settings->save();
		}
	}

	function wpv_wpa_fix_woocommerce_archives( $wpa_to_apply, $wpa_slug ) {
		global $post, $wp_query;
		if ( ! have_posts() ) {
			add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );
		}
	}

	function wpv_get_current_archive( $current_archive = null ) {
		return $this->wpa_id;
	}

	function wpv_get_current_archive_loop( $current_archive_loop = array() ) {
		return array(
			'type'	=> $this->wpa_type,
			'name'	=> $this->wpa_name,
			'data'	=> $this->wpa_data
		);
	}

	function wpv_get_archive_unique_hash( $unique_hash = '' ) {
		$unique_hash = $this->get_archive_unique_hash();
		return $unique_hash;
	}

	function get_archive_unique_hash() {
		if ( ! $this->wpa_id ) {
			return '';
		}
		return (string) $this->wpa_id;
	}

	function extend_pagination_settings( $pagination_data, $view_settings ) {
		switch ( $pagination_data['query'] ) {
			case 'archive':
				$pagination_data['loop'] = array(
					'type'	=> $this->wpa_type,
					'name'	=> $this->wpa_name,
					'data'	=> $this->wpa_data,
					'id'	=> $this->wpa_id
				);
				break;
			default:
				$pagination_data['loop'] = array(
					'type'	=> '',
					'name'	=> '',
					'data'	=> array(),
					'id'	=> 0
				);
				break;
		}
		return $pagination_data;
	}

	function extend_parametric_settings( $parametric_data, $view_settings ) {
		switch ( $parametric_data['query'] ) {
			case 'archive':
				$parametric_data['loop'] = array(
					'type'	=> $this->wpa_type,
					'name'	=> $this->wpa_name,
					'data'	=> $this->wpa_data,
					'id'	=> $this->wpa_id
				);
				break;
			default:
				$parametric_data['loop'] = array(
					'type'	=> '',
					'name'	=> '',
					'data'	=> array(),
					'id'	=> 0
				);
				break;
		}
		return $parametric_data;
	}

	function fake_archive_query( $loop ) {
		$this->wpa_id		= $loop['id'];
		$this->wpa_settings	= apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $loop['id'] );
		$query_args			= array(
			'wpv_fake_archive_loop'	=> $loop,
			// Disable ElasticPress for this query.
			'ep_integrate' => false,
		);
		switch ( $loop['type'] ) {
			// 'native'|'post_type'|'taxonomy'
			case 'native':
				$query_args = $this->fake_archive_query_native( $query_args, $loop );
				break;
			case 'post_type':
				$query_args = $this->fake_archive_query_post_type( $query_args, $loop );
				break;
			case 'taxonomy':
				$query_args = $this->fake_archive_query_taxonomy( $query_args, $loop );
				break;
		}
		return $query_args;
	}

	function fake_archive_query_native( $query_args, $loop ) {
		// 'home'|'search'|'author'|'year'|'month'|'day'
		switch ( $loop['name'] ) {
			case 'home':

				break;
			case 'search':
				$query_args['s']			= $loop['data']['s'];
				break;
			case 'author':
				$query_args['author_name']	= $loop['data']['author_name'];
				break;
			case 'year':
				$query_args['year']			= $loop['data']['year'];
				break;
			case 'month':
				$query_args['year']			= $loop['data']['year'];
				$query_args['monthnum']		= $loop['data']['monthnum'];
				break;
			case 'day':
				$query_args['year']			= $loop['data']['year'];
				$query_args['monthnum']		= $loop['data']['monthnum'];
				$query_args['day']			= $loop['data']['day'];
				break;
		}
		return $query_args;
	}

	function fake_archive_query_post_type( $query_args, $loop ) {
		$query_args['post_type'] = $loop['name'];
		return $query_args;
	}

	function fake_archive_query_taxonomy( $query_args, $loop ) {
		$query_args['tax_query'] = array(
			'relation' => 'AND',
			array(
				'taxonomy'			=> $loop['data']['taxonomy'],
				'field'				=> 'slug',
				'terms'				=> array( $loop['data']['term'] ),
				'operator'			=> 'IN',
				"include_children"	=> true
			)
		);
		return $query_args;
	}

	function fake_archive_before_set( $query ) {
		if ( $query->get( 'wpv_fake_archive_loop' ) ) {
			// Set the right query properties
			$loop = $query->get( 'wpv_fake_archive_loop' );
			switch ( $loop['type'] ) {
				case 'native':
					switch ( $loop['name'] ) {
						case 'home':
							$query->is_admin	= false;
							$query->is_archive	= false;
							$query->is_home		= true;
							break;
						case 'search':
							$query->is_admin	= false;
							$query->is_archive	= true;
							$query->is_search	= true;
							break;
						case 'author':
							$query->is_admin	= false;
							$query->is_archive	= true;
							$query->is_author	= true;
							break;
						case 'year':
							$query->is_admin	= false;
							$query->is_archive	= true;
							$query->is_date		= true;
							$query->is_year		= true;
							break;
						case 'month':
							$query->is_admin	= false;
							$query->is_archive	= true;
							$query->is_date		= true;
							$query->is_month	= true;
							break;
						case 'day':
							$query->is_admin	= false;
							$query->is_archive	= true;
							$query->is_date		= true;
							$query->is_day		= true;
							break;
					}
					break;
				case 'post_type':
					$query->is_admin				= false;
					$query->is_archive				= true;
					$query->is_post_type_archive	= true;
					break;
				case 'taxonomy':
					switch ( $loop['name'] ) {
						case 'category':
							$query->is_admin	= false;
							$query->is_archive	= true;
							$query->is_category	= true;
							$query->set( 'category_name', $loop['data']['term'] );
							break;
						case 'post_tag':
							$query->is_admin	= false;
							$query->is_archive	= true;
							$query->is_tag		= true;
							$query->set( 'tag', $loop['data']['term'] );
							break;
						default:
							$query->is_admin	= false;
							$query->is_archive	= true;
							$query->is_tax		= true;
							break;
					}
					break;
			}
			// Make this the main query
			global $wp_the_query, $wp_query;
			$wp_the_query = $query;
			$wp_query = $query;
		}
	}

	function wpv_get_archive_query_results() {

		$loop			= $_POST['loop'];// @todo sanitize $loop
		$page			= $_POST['page'];
		$sort			= isset( $_POST['sort'] ) ? $_POST['sort'] : array();
		$environment	= isset( $_POST['environment'] ) ? $_POST['environment'] : array();
		$search			= isset( $_POST['search'] ) ? $_POST['search'] : array();
		$search_keys	= array();
		$extra			= isset( $_POST['extra'] ) ? $_POST['extra'] : array();

		$_GET['wpv_view_count']	= sanitize_text_field( $_POST['view_number'] );

		$query_args	= $this->fake_archive_query( $loop );
		$query_args['paged'] = (int) $page;

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
				global $WP_Views;
				switch ( $environment_key ) {
					case 'wpv_aux_current_post_id':
						$top_post_id = (int) $environment_value;
						$top_post = get_post( $top_post_id );
						$WP_Views->top_current_page = $top_post;
						break;
					case 'wpv_aux_parent_post_id':
						global $post, $authordata, $id;
						$post_id = (int) $environment_value;
						$post = get_post( $post_id );
						$authordata = new WP_User( $post->post_author );
						$id = $post->ID;
						$WP_Views->current_page = array( $post );
						break;
					case 'wpv_aux_parent_term_id':
						$WP_Views->parent_taxonomy = (int) $environment_value;
						break;
					case 'wpv_aux_parent_user_id':
						$WP_Views->parent_user = (int) $environment_value;
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

		global $wp_the_query, $wp_query, $paged;
		$archive_query		= new WP_Query( $query_args );
		$wp_the_query		= $archive_query;
		$wp_query			= $archive_query;
		$paged				= $query_args['paged'];

		$this->initialize_archive_loop();
		if ( $this->loop_has_no_posts ) {
			// Reset everything if the loop has no posts.
			// Then the WPA will render with no posts.
			global $post;
			$this->post_count = 0;
			$this->query->post_count = 0;
			$wp_query->post_count = 0;
			$wp_query->posts = array();
			$this->query->posts = array();
			$post = null;
		}

		if ( $this->loop_has_no_posts ) {
			// Reset everything if the loop has no posts.
			// Then the WPA will render with no posts.
			$this->post_count = 0;
			$this->query->post_count = 0;
			$wp_query->post_count = 0;
			$wp_query->posts = array();
			$this->query->posts = array();
		}

		$this->in_the_loop = true;
		$data = array(
			'id'	=> $loop['id'],
			'full'	=> render_view( array( 'id' => $loop['id'] ) )
		);
		$this->in_the_loop = false;

		$data['form'] = $data['full'];

		$archive_settings			= apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $loop['id'] );
		$pagination_permalinks		= apply_filters( 'wpv_filter_wpv_get_pagination_permalinks', array(), $archive_settings, $loop['id'] );

		if ( $paged == 1 ) {
			$pagination_permalink = $pagination_permalinks['first'];
		} else {
			$pagination_permalink = str_replace( 'WPV_PAGE_NUM', $paged, $pagination_permalinks['other'] );
		}

		// For parametric search URL history management
		$data['permalink']				= $pagination_permalink;
		// In theory, this is only used by parametric search, so we should always use the 'first' one above.
		$data['parametric_permalink']	= $pagination_permalink;

		if ( ! wpv_parametric_search_triggers_history( $loop['id'] ) ) {
			// When parametric search does not manage history, we need to clean the URL.
			$view_url_data					= get_view_allowed_url_parameters( $loop['id'] );
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

	// Auxiliar methods

	function is_frontend() {
		if ( ! is_admin() ) {
			return true;
		} else if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			&& isset( $_REQUEST['action'] )
			&& $_REQUEST['action'] == 'wpv_get_archive_query_results'
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* wpv_initialize_wordpress_archive_for_archive_loop
	*
	* Callback for the wpv_action_wpv_initialize_wordpress_archive_for_archive_loop action.
	*
	* This should be used to override the current archive loop, by:
	* - faking an archive query, based on the current archive settings and a given WPA ID.
	* - overriding the global queries with the generated one.
	* - initializing the current archive loop with the faked now-global query.
	*
	* This is mainly used by Layouts archive cells, since they are rendered after $wp_query is generated
	* and we might not have any WPA assigned to th current archive loop, or have another different one
	* and we do need to force the WPA stored in the Layouts cell settings.
	*
	* @since 2.1
	*/

	function wpv_initialize_wordpress_archive_for_archive_loop( $wpa_id ) {
		WPV_Cache::restart_cache();
		global $wp_the_query, $wp_query, $paged;
		$wpa_loop = array(
			'type'	=> $this->wpa_type,
			'name'	=> $this->wpa_name,
			'data'	=> $this->wpa_data,
			'id'	=> $wpa_id
		);
		$query_args				= $this->fake_archive_query( $wpa_loop );
		$query_args['paged']	= $paged;
		//$query_args['wpv_archive_loop_cell'] = true;
		$archive_query			= new WP_Query( $query_args );
		$wp_the_query			= $archive_query;
		$wp_query				= $archive_query;
		$this->initialize_archive_loop();
		if ( $this->loop_has_no_posts ) {
			// Reset everything if the loop has no posts.
			// Then the WPA will render with no posts.
			global $post;
			$this->post_count = 0;
			$this->query->post_count = 0;
			$wp_query->post_count = 0;
			$wp_query->posts = array();
			$this->query->posts = array();
			$post = null;
		}
	}

	public function early_pre_get_posts( $query ) {
		if ( is_wpv_wp_archive_assigned() ) {
			$query->set( 'ep_integrate', false );
		}
	}

	/**
	 * Returns the WPA assigned to a given term, if any.
	 *
	 * @param string $taxonomy_slug
	 * @param string $term_slug
	 *
	 * @return int
	 */
	public function get_archive_for_taxonomy_term( $taxonomy_slug, $term_slug ) {
		$current_language = apply_filters( 'wpml_current_language', '' );
		$default_language = apply_filters( 'wpml_default_language', '' );

		if ( $current_language !== $default_language ) {
			$term = get_term_by( 'slug', $term_slug, $taxonomy_slug );
			if ( false === $term ) {
				return 0;
			}
			$translated_term_id = apply_filters( 'wpml_object_id', $term->term_id, $term->taxonomy, true, $default_language );
			do_action( 'wpml_switch_language', $default_language );
			$original_term = get_term( $translated_term_id, $term->taxonomy );
			do_action( 'wpml_switch_language', $current_language );
			if ( false !== $original_term ) {
				$term_slug = $original_term->slug;
			}
		}

		$settings_term_key_template = self::SETTINGS_TAXONOMY_LOOP_OPTION;
		$settings_term_key = sprintf( $settings_term_key_template, $taxonomy_slug, $term_slug );

		if ( isset( $this->wpv_settings[ $settings_term_key ] ) && intval( $this->wpv_settings[ $settings_term_key ] ) > 0 ) {
			return intval( $this->wpv_settings[ $settings_term_key ] );
		}

		$settings_key_template = self::SETTINGS_TAXONOMY_LOOP;
		$settings_key = sprintf( $settings_key_template, $taxonomy_slug );

		if ( isset( $this->wpv_settings[ $settings_key ] ) && intval( $this->wpv_settings[ $settings_key ] ) > 0 ) {
			return intval( $this->wpv_settings[ $settings_key ] );
		}
		return 0;
	}

	/**
	 * Returns an array indexed by Archive ID's, and containing an array of term slugs associated to the archive.
	 *
	 * @param string $taxonomy_slug
	 *
	 * @return string[][]
	 */
	private function get_archive_settings_for_taxonomy_terms( $taxonomy_slug ) {
		$pattern_template = self::SETTINGS_TAXONOMY_LOOP_OPTION_REGEXP_TEMPLATE;
		$settings_term_options_pattern = sprintf( $pattern_template, $taxonomy_slug );
		$settings_terms = $this->wpv_settings->preg_key( $settings_term_options_pattern );
		$selected_terms = [];

		foreach ( $settings_terms as $settings_term_key ) {
			$term_matches = [];
			preg_match( $settings_term_options_pattern, $settings_term_key, $term_matches );
			if ( count( $term_matches ) > 0 ) {
				$wpa_id = intval( $this->wpv_settings->get( $settings_term_key ) );
				$selected_terms[ $wpa_id ][] = $term_matches[1];
			}
		}

		return $selected_terms;
	}

	/**
	 * Dynamically loading this instance, as at construct time the class is not yet loaded.
	 *
	 * @return Toolset_Post_Type_Repository
	 */
	private function get_post_type_repository() {
		if ( null === $this->post_type_repository ) {
			$this->post_type_repository = Toolset_Post_Type_Repository::get_instance();
		}
		return $this->post_type_repository;
	}
}


global $WPV_view_archive_loop;
$WPV_view_archive_loop = new WPV_WordPress_Archive_Frontend;

