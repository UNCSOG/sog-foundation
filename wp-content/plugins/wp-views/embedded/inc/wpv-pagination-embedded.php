<?php
/**
* wpv-pagination-embedded.php
*
* @package Views
*
* @since unknown
*/

/**
* WPV_Pagination_Embedded
*
* @since 1.11
*/

class WPV_Pagination_Embedded {


	/** @var array Cached output of self::generate_pagination_settings() indexed by View IDs. */
	private $cache_pagination_settings = array();

	public function __construct() {
		add_action( 'init',														array( $this, 'init' ) );
	}

	function init() {
		$this->register_shortcodes();

		add_filter( 'wpv_view_settings',										array( $this, 'get_pagination_defaults' ) );

		add_filter( 'wpv_filter_query',											array( $this, 'set_pagination_in_query' ), 1, 3 );

		add_filter( 'wpv_filter_wpv_get_pagination_settings',					array( $this, 'get_pagination_settings' ), 10, 2 );
		add_filter( 'wpv_filter_wpv_get_pagination_permalinks',					array( $this, 'get_pagination_permalink_data' ), 10, 3 );
		add_filter( 'wpv_filter_wpv_global_pagination_manage_history_status',	array( $this, 'wpv_global_pagination_manage_history_status' ), 1 );

		// Compatibility
		add_filter( 'icl_current_language',										array( $this, 'wpv_ajax_pagination_lang' ) );
		add_filter( 'wpv_filter_pager_nav_links_url',							array( $this, 'fix_indexed_arrays_in_nav_link_url' ) );

		add_filter( 'wpv_filter_get_spinner_url', array( $this, 'get_spinner_url' ) );
	}

	/**
	* register_shortcodes
	*
	* Register the pagination control shortcodes.
	*
	* @since unknown
	*/

	function register_shortcodes() {
		/**
		* ---------------------------
		* Views pagination
		* ---------------------------
		*/
		add_shortcode( 'wpv-pagination',								array( $this, 'wpv_pagination_shortcode_callback' ) );

		add_shortcode( 'wpv-pager-current-page',						array( $this, 'wpv_pager_current_page_shortcode' ) );
		add_shortcode( 'wpv-pager-num-page',							array( $this, 'wpv_pager_total_pages_shortcode' ) );
		add_shortcode( 'wpv-pager-total-pages',							array( $this, 'wpv_pager_total_pages_shortcode' ) );

		add_shortcode( 'wpv-pager-prev-page',							array( $this, 'wpv_pager_prev_page_callback' ) );
		add_shortcode( 'wpv-pager-next-page',							array( $this, 'wpv_pager_next_page_callback' ) );

		add_shortcode( 'wpv-pager-nav-dropdown',						array( $this, 'wpv_pager_nav_dropdown_callback' ) );
		add_shortcode( 'wpv-pager-nav-links',							array( $this, 'wpv_pager_nav_links_callback' ) );

		/**
		* ---------------------------
		* WPAs pagination
		* ---------------------------
		*/

		add_shortcode( 'wpv-pager-archive-current-page',				array( $this, 'wpv_pager_current_page_shortcode' ) );
		add_shortcode( 'wpv-pager-archive-total-pages',					array( $this, 'wpv_pager_total_pages_shortcode' ) );

		add_shortcode( 'wpv-pager-archive-prev-page',					array( $this, 'wpv_pager_archive_prev_page_shortcode' ) );
		add_shortcode( 'wpv-pager-archive-next-page',					array( $this, 'wpv_pager_archive_next_page_shortcode' ) );

		add_shortcode( 'wpv-pager-archive-nav-links',					array( $this, 'wpv_pager_archive_nav_links_callback' ) );

		/**
		* ---------------------------
		* Deprecated
		* ---------------------------
		*/

		add_shortcode( 'wpv-archive-pager-prev-page',					array( $this, 'wpv_archive_pager_prev_page_shortcode' ) );
		add_shortcode( 'wpv-archive-pager-next-page',					array( $this, 'wpv_archive_pager_next_page_shortcode' ) );

		//add_shortcode( 'wpv-pager-pause-rollover', array( $this, 'wpv_pager_pause_rollover_callback' ) );
		//add_shortcode( 'wpv-pager-resume-rollover', array( $this, 'wpv_pager_resume_rollover_callback' ) );
	}

	/**
	* get_pagination_defaults
	*
	* Get the default pagination data.
	*
	* Pagination defaults merge Views and WPAs default options, with the aim to reach a common ground soon.
	*
	* @since unknown
	*/

	function get_pagination_defaults( $view_settings ) {
		$defaults = array(
			'pagination'		=> array(
				'type'								=> 'paged',
				'posts_per_page'					=> 'default',
				'effect'							=> 'fade',
				'duration'							=> 500,
				'speed'								=> 5,
				'preload_images'					=> 1,
				'cache_pages'						=> 1,
				'preload_pages'						=> 1,
				'spinner'							=> 'default',
				'spinner_image'						=> WPV_URL_EMBEDDED . '/res/img/ajax-loader.gif',
				'spinner_image_uploaded'			=> '',
				'callback_next'						=> '',
				'manage_history'					=> 'on',
			),
		);
		$view_settings = wpv_parse_args_recursive( $view_settings, $defaults );

		$view_settings = $this->upgrade_pagination_schema( $view_settings );

		return $view_settings;
	}

	function upgrade_pagination_schema( $view_settings ) {

		if (
			! isset( $view_settings['view-query-mode'] )
			|| ( 'normal' == $view_settings['view-query-mode'] )
		) {
			$query_mode = 'normal';
		} else {
			// we assume 'archive' or 'layouts-loop'
			$query_mode = 'archive';
		}

		if (
			$query_mode == 'normal'
			&& isset( $view_settings['posts_per_page'] )
			&& isset( $view_settings['pagination']['mode'] )
		) {

			$pagination_settings = $view_settings['pagination'];

			$pagination_settings['posts_per_page'] = $view_settings['posts_per_page'];

			$type = 'disabled';
			$mode = 'disabled';

			if ( isset( $pagination_settings['mode'] ) ) {
				$mode = $pagination_settings['mode'];
				unset( $pagination_settings['mode'] );
			}
			if ( isset( $pagination_settings[0] ) ) {
				if ( $pagination_settings[0] == 'disable' ) {
					$mode = 'disabled';
				}
				unset( $pagination_settings[0] );
			}

			switch ( $mode ) {
				case 'rollover':
					$type = 'rollover';
					break;
				case 'paged':
					if (
						isset( $view_settings['ajax_pagination'][0] )
						&& $view_settings['ajax_pagination'][0] == 'enable'
					) {
						$type = 'ajaxed';
					} else {
						$type = 'paged';
					}
					break;
				case 'ajaxed':
					$type = 'ajaxed';
					break;
				case 'disabled':
				case 'none':
				default:
					$type = 'disabled';
					break;
			}
			$pagination_settings['type'] = $type;

			/**
			* AJAX effect and duration
			*/
			$pagination_settings['effect'] = isset( $view_settings['ajax_pagination']['style'] ) ? $view_settings['ajax_pagination']['style'] : 'fade';
			$pagination_settings['duration'] = isset( $view_settings['ajax_pagination']['duration'] ) ? $view_settings['ajax_pagination']['duration'] : '500';
			$preload_images = isset( $pagination_settings['preload_images'] ) ? $pagination_settings['preload_images'] : true;
			// Adjust for rollover
			if (
				$pagination_settings['type'] == 'rollover'
			) {
				$pagination_settings['posts_per_page'] = isset( $view_settings['rollover']['posts_per_page'] ) ? $view_settings['rollover']['posts_per_page'] : $pagination_settings['posts_per_page'];
				$pagination_settings['effect'] = isset( $view_settings['rollover']['effect'] ) ? $view_settings['rollover']['effect'] : $pagination_settings['effect'];
				$pagination_settings['duration'] = isset( $view_settings['rollover']['duration'] ) ? $view_settings['rollover']['duration'] : $pagination_settings['duration'];
				$pagination_settings['speed'] = isset( $view_settings['rollover']['speed'] ) ? $view_settings['rollover']['speed'] : 5;
				$preload_images = isset( $view_settings['rollover']['preload_images'] ) ? $view_settings['rollover']['preload_images'] : true;
			}

			$pagination_settings['preload_images'] = $preload_images;

			if ( $pagination_settings['effect'] == 'fadeslow' ) {
				$pagination_settings['effect']		= 'fade';
				$pagination_settings['duration']	= '1500';
			} else if ( $pagination_settings['effect'] == 'fadefast' ) {
				$pagination_settings['effect']		= 'fade';
				$pagination_settings['duration']	= '1';
			}

			$pagination_settings['pre_reach'] = isset( $pagination_settings['pre_reach'] ) ? $pagination_settings['pre_reach'] : 1;

			$view_settings['pagination'] = $pagination_settings;

			unset( $view_settings['posts_per_page'] );
			unset( $view_settings['ajax_pagination'] );
			unset( $view_settings['rollover'] );

		}

		return $view_settings;
	}

	function set_pagination_in_query( $query, $view_settings, $id  ) {

		$view_settings = $this->upgrade_pagination_schema( $view_settings );

		if ( $view_settings['pagination']['type'] == 'disabled' ) {
			$query['posts_per_page'] = -1;
		} else {
			$view_settings['pagination']['posts_per_page'] = ( $view_settings['pagination']['posts_per_page'] == 'default' ) ? 10 : $view_settings['pagination']['posts_per_page'];
			$query['posts_per_page'] = $view_settings['pagination']['posts_per_page'];
		}

		return $query;
	}

	/**
	 * Get the View or WordPress Archive pagination settings, using a filter for uniformity:
	 * $pagination_settings = apply_filters( 'wpv_filter_wpv_get_pagination_settings', array(), $view_settings );
	 *
	 * Proxy between the old and new pagination data structure.
	 *
	 * @return array
	 * 	'id'					=>											The object ID
	 * 	'query'					=> 'normal'|'archive'						The kind of object
	 * 	'base_permalink'		=> 											The current permalink with all URL parameters, but a placeholded page number
	 * 	'type'					=> 'disabled'|'paged'|'ajaxed'|'rollover'	The pagination mode
	 * 	'effect'				=> 											The AJAX pagination effect name
	 * 	'duration'				=> 											The AJAX pagination effect duration, in milisecons
	 *   'speed'					=>											The rollover speed, if any, in miliseconds
	 *   'pause_on_hover'		=>											Whether to pause on mouse hover in the case of rollover pagination
	 * 	'stop_rollover'			=> 											The AJAX pagination rollover status, whether it should be stopped on item selection TO BE DEPRECATED
	 * 	'cache_pages'			=> 'enabled'|'disabled'						The AJAX pagination cache pages status
	 * 	'preload_pages'			=> 'enabled'|'disabled'						The AJAX pagination preload pages status
	 * 	'preload_reach'			=> 											The AJAX pagination preload pages reach, in natural number
	 * 	'preload_images'		=> 'enabled'|'disabled'						The AJAX pagination preload images status
	 * 	'spinner'				=> 'builtin'|'uploaded'|'disabled'			The AJAX pagination spinner mode
	 * 	'spinner_image'			=> 											The AJAX pagination spinner image, as empty string or URL
	 * 	'callback_next'			=> 											The AJAX pagination callback to execute after paging
	 * 	'manage_history'		=> 'enabled'|'disabled'						The AJAX pagination history mnageent mode
	 * 	'controls_in_form'		=> 'enabled'|'disabled'						Whether there are pagination controls in the form editor
	 * 	'infinite_tolerance'	=> 											The AJAX pagination infinite scrolling tolerance, in integer number
	 * 	'page'					=>											The current page
	 * 	'max_pages'				=>											The total number of pages
	 * 	'query'					=> 'normal'|'archive'						The object query mode
	 *
	 * @since 2.1
	 * @since 2.9.3 Cache as much as we can, noting that some parts are dynamic and can not be cached :-/
	 */
	public function get_pagination_settings( $pagination_data, $view_settings ) {
		$current_view_id = apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$pagination_settings = toolset_getarr( $this->cache_pagination_settings, $current_view_id, null );

		if ( null === $pagination_settings ) {
			$pagination_settings = $this->generate_pagination_settings( $pagination_data, $view_settings );
			$this->cache_pagination_settings[ $current_view_id ] = $pagination_settings;
		}

		$pagination_settings['id'] = $current_view_id;

		// This might not be needed but if we can avoid POSTing the form when doing non-AJAXed pagination...
		$permalinks_data					= $this->pagination_permalink_data( $view_settings, $pagination_settings['id'] );
		$pagination_settings['base_permalink']	= $permalinks_data['other'];

		$pagination_settings['max_pages']	= (int) apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );
		$pagination_settings['page']		= apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );

		return $pagination_settings;
	}

	/**
	 * Generate the basic pagination settings for a given View.
	 *
	 * @param array $pagination_data
	 * @param array $view_settings
	 * @return array
	 */
	private function generate_pagination_settings( $pagination_data, $view_settings ) {

		$view_settings = $this->upgrade_pagination_schema( $view_settings );

		$pagination_data['id'] = apply_filters( 'wpv_filter_wpv_get_current_view', null );

		if (
			! isset( $view_settings['view-query-mode'] )
			|| ( 'normal' == $view_settings['view-query-mode'] )
		) {
			$query_mode = 'normal';
		} else {
			// we assume 'archive' or 'layouts-loop'
			$query_mode = 'archive';
		}

		$pagination_data['query']		= $query_mode;
		$pagination_data['type']		= isset( $view_settings['pagination']['type'] ) ? $view_settings['pagination']['type'] : 'paged';
		$pagination_data['effect']		= isset( $view_settings['pagination']['effect'] ) ? $view_settings['pagination']['effect'] : 'fade';
		$pagination_data['duration']	= isset( $view_settings['pagination']['duration'] ) ? $view_settings['pagination']['duration'] : '500';
		$pagination_data['speed']		= isset( $view_settings['pagination']['speed'] ) ? $view_settings['pagination']['speed'] : 5;

		$pause_on_hover = isset( $view_settings['pagination']['pause_on_hover'] ) ? $view_settings['pagination']['pause_on_hover'] : false;
		$pagination_data['pause_on_hover'] = $pause_on_hover ? 'enabled' : 'disabled';

		$pagination_data['stop_rollover'] = 'false';
		if (
			$pagination_data['type'] == 'rollover'
		) {
			$pagination_data['stop_rollover'] = 'true';
		}

		$cache_pages = isset( $view_settings['pagination']['cache_pages'] ) ? $view_settings['pagination']['cache_pages'] : true;
		$pagination_data['cache_pages'] = $cache_pages ? 'enabled' : 'disabled';

		$preload_images = isset( $view_settings['pagination']['preload_images'] ) ? $view_settings['pagination']['preload_images'] : true;
		$pagination_data['preload_images'] = $preload_images ? 'enabled' : 'disabled';

		$preload_pages = isset( $view_settings['pagination']['preload_pages'] ) ? $view_settings['pagination']['preload_pages'] : true;
		$pagination_data['preload_pages'] = $preload_pages ? 'enabled' : 'disabled';

		$pagination_data['preload_reach'] = ( isset( $view_settings['pagination']['pre_reach'] ) ) ? $view_settings['pagination']['pre_reach'] : '1';

		/**
		* Spinner & spinner image
		*
		* By default, we pass the spinner_image setting, unless using an uploaded one,
		* in which case we go with the spinner_image_uploaded setting.
		*/
		$spinner = ( isset( $view_settings['pagination']['spinner'] ) ) ? $view_settings['pagination']['spinner'] : 'disabled';
		$spinner_image_key = 'spinner_image';
		switch ( $spinner ) {
			case 'default':
			case 'builtin':
				$pagination_data['spinner'] = 'builtin';
				break;
			case 'uploaded':
				$pagination_data['spinner'] = 'uploaded';
				$spinner_image_key = 'spinner_image_uploaded';
				break;
			case 'disabled':
			case 'no':
			default:
				$pagination_data['spinner'] = 'disabled';
				break;
		}

		$pagination_data['spinner_image'] = ( isset( $view_settings['pagination'][ $spinner_image_key ] ) ) ? $view_settings['pagination'][ $spinner_image_key ] : '';

		$pagination_data['spinner_image'] = $this->get_spinner_url( $pagination_data['spinner_image'] );

		// $spinner_image might need to get SSL adjusted
		if ( is_ssl() ) {
			$pagination_data['spinner_image'] = str_replace( 'http://', 'https://', $pagination_data['spinner_image'] );
		} else {
			$pagination_data['spinner_image'] = str_replace( 'https://', 'http://', $pagination_data['spinner_image'] );
		}

		/**
		* Callback next
		*/
		$pagination_data['callback_next'] = ( isset( $view_settings['pagination']['callback_next'] ) ) ? $view_settings['pagination']['callback_next'] : '';

		/**
		* History management
		*/
		$manage_history = ( isset( $view_settings['pagination']['manage_history'] ) ) ? $view_settings['pagination']['manage_history'] : 'on';
		if ( $manage_history == 'on' ) {
			$global_enable_manage_history = apply_filters( 'wpv_filter_wpv_global_pagination_manage_history_status', true );
			if ( ! $global_enable_manage_history ) {
				$manage_history = 'off';
			}
		}
		switch ( $manage_history ) {
			case 'off':
				$pagination_data['manage_history'] = 'disabled';
				break;
			case 'on':
			default:
				$pagination_data['manage_history'] = 'enabled';
				break;
		}

		/**
		* Whether the View has pagination controls in the Form editor
		*/
		$pagination_data['has_controls_in_form'] = 'disabled';
		if (
			isset( $view_settings['filter_meta_html'] )
			&& strpos( $view_settings['filter_meta_html'], '[wpv-pager-' ) !== false
		) {
			$pagination_data['has_controls_in_form'] = 'enabled';
		}

		/**
		* Infinite scrolling tolerance
		*/
		$pagination_data['infinite_tolerance'] = ( isset( $view_settings['pagination']['tolerance'] ) ) ? $view_settings['pagination']['tolerance'] : '0';

		$pagination_data['max_pages']	= (int) apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );
		$pagination_data['page']		= apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );

		return $pagination_data;
	}

	/**
	 * Get the built-in spinner icon URL from the spinner type.
	 *
	 * @param  string $spinner_type The spinner type.
	 *
	 * @return string The URL of the spinner.
	 */
	public function get_spinner_url( $spinner_type ) {
		// Since Views 3.0, Views created as blocks store here
		// just a reference to the selected spinner:
		// we need to adjust it to produce a full URL
		switch ( $spinner_type ) {
			case 'spinner1':
				$spinner_type = WPV_URL_EMBEDDED . '/res/img/ajax-loader.svg';
				break;
			case 'spinner2':
				$spinner_type = WPV_URL_EMBEDDED . '/res/img/ajax-loader2.svg';
				break;
			case 'spinner3':
				$spinner_type = WPV_URL_EMBEDDED . '/res/img/ajax-loader3.svg';
				break;
			case 'spinner4':
				$spinner_type = WPV_URL_EMBEDDED . '/res/img/ajax-loader4.svg';
				break;
			case 'spinner5':
				$spinner_type = WPV_URL_EMBEDDED . '/res/img/ajax-loader-overlay.svg';
				break;
		}
		return $spinner_type;
	}

	/**
	* wpv_pagination_shortcode_callback
	*
	* Callback for the [wpv-pagination] shortcode.
	*
	* @since unknown
	* @since 2.6.4 Prevent the shortcode transformation when the View wrapper DIV option is selected.
	*/

	function wpv_pagination_shortcode_callback( $atts, $value ) {
		// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
		/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
		if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
			return '';
		}

		extract(
			shortcode_atts(
				array(),
				$atts
			)
		);
		if ( apply_filters( 'wpv_filter_wpv_get_max_pages', 1 ) > 1.0 ) {
			return wpv_do_shortcode( $value );
		} else {
			return '';
		}
	}

	/**
	* wpv_pager_current_page_shortcode
	*
	* Callback for the [wpv-pager-current-page] shortcode.
	*
	* Contains some legacy code for when this shortcode was used to display pagination controls like dropdowns or links.
	*
	* @since unknown
	* @since 2.6.4 Prevent the shortcode transformation when the View wrapper DIV option is selected.
	*/

	function wpv_pager_current_page_shortcode( $atts ) {
		// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
		/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
		if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
			return '';
		}

		extract(
			shortcode_atts(
				array(
					'force' => 'false'
				),
				$atts
			)
		);

		$view_id			= apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_settings		= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
		$view_max_pages		= (int) apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );
		$view_hash			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );

		if ( $view_max_pages <= 1.0 ) {
			return ( $force == 'true' ) ? '1' : '';
		}

		$page = apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );

		if ( isset( $atts['style'] ) ) {

			/**
			* Deprecated on Views 1.11, keep for backwards compatibility
			*/

			switch( $atts['style'] ) {
				case 'drop_down':
					$out = '';
					$out .= '<select class="wpv-page-selector-' . $view_hash . ' js-wpv-page-selector" data-viewnumber="' . $view_hash . '">' . "\n";

					for ($i = 1; $i < $view_max_pages + 1; $i++) {
						$is_selected = $i == $page ? ' selected="selected"' : '';
						$page_number = apply_filters( 'wpv_pagination_page_number', $i, $atts['style'], $view_id ) ;
						$out .= '<option value="' . $i . '" ' . $is_selected . '>' . $page_number . "</option>\n";
					}
					$out .= "</select>\n";

					return $out;

				case 'link':
					// output a series of dots linking to each page.
					$out = '<div class="wpv_pagination_links">';
					$classname = 'wpv_pagination_dots';
					$classname = apply_filters( 'wpv_pagination_container_classname', $classname, $atts['style'], $view_id );
					$out .= '<ul class="' . $classname . '">';

					for ( $i = 1; $i < $view_max_pages + 1; $i++ ) {
						$page_number = apply_filters( 'wpv_pagination_page_number', $i, $atts['style'], $view_id );

						$link = sprintf(
							'<a title="%s" href="#" class="wpv-filter-pagination-link js-wpv-pagination-link page-link" data-viewnumber="%s" data-page="%d">%s</a>',
							esc_attr( apply_filters( 'wpv_pagination_page_title', sprintf( __( 'Page %s', 'wpv-views' ), $i ), $i, $atts['style'], $view_id ) ),
							esc_attr( $view_hash ),
							$i,
							$page_number
						);
						$list_item_class = ' wpv-page-link-' . $view_hash . '-' . $i . ' js-wpv-page-link-' . $view_hash . '-' . $i;
						$item = '';
						if ( $i == $page ) {
							$item .= '<li class="' . $classname . '_item wpv_page_current' . $list_item_class . '">' . $link . '</li>';
						} else {
							$item .= '<li class="' . $classname . '_item' . $list_item_class . '">' . $link . '</li>';
						}
						$item = apply_filters( 'wpv_pagination_page_item', $item, $i, $page, $view_max_pages, $atts['style'], $view_id );
						$out .= $item;
					}
					$out .= '</ul>';
					$out .= '</div>';
					//$out .= '<br />'; NOTE: this extra br tag was removed in Views 1.5
					return $out;

			}
		} else {
			// show the page number.
			return sprintf( '%d', $page );
		}
	}

	/**
	* wpv_pager_total_pages_shortcode
	*
	* Callback for the [wpv-pager-num-page], [wpv-pager-total-pages] and [wpv-pager-archive-total-pages] shortcode.
	*
	* [wpv-pager-num-page] is deprecated, but kept for legasy.
	* The other two shortcodes are for Views and WordPress Archive.
	*
	* @since unknown
	* @since 2.6.4 Prevent the shortcode transformation when the View wrapper DIV option is selected.
	*/

	function wpv_pager_total_pages_shortcode( $atts ) {
		// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
		/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
		if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
			return '';
		}

		extract(
			shortcode_atts(
				array(
					'force'	=> 'false'
				),
				$atts
			)
		);
		$max_pages = apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );
		if ( $max_pages > 1.0 ) {
			return sprintf( '%1.0f', $max_pages );
		} else {
			return ( $force == 'true' ) ? '1' : '';
		}
	}


	/**
	 * wpv_pager_prev_page_callback
	 *
	 * Callback for the [wpv-pager-prev-page] shortcode, used on Views pagination.
	 *
	 * @param $atts
	 * @param $value
	 *
	 * @return string
	 * @since 2.6.4 Prevent the shortcode transformation when the View wrapper DIV option is selected.
	 * @since unknown
	 */
	function wpv_pager_prev_page_callback( $atts, $value ) {
		// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
		/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
		if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'style' => '',
			'class' => '',
			'force' => 'false',
		), $atts );

		// @refactoring Do not use extract();
		extract( $atts );

		$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
		$page = apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );
		$max_page = (int) apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );

		$class = toolset_getarr( $atts, 'class' );
		if ( ! empty( $class ) ) {
			$class = ' ' . esc_attr( $class );
		}

		$style = toolset_getarr( $atts, 'style' );
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style )  .'"';
		}

		$display = (
			$max_page > 1.0
			&& ( $view_settings['pagination']['type'] == 'rollover' || $page > 1 )
		);

		if ( $display ) {
			$page--;
			$value = wpv_do_shortcode( $value );

			$view_count = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );

			if ( $page <= 0 ) {
				$page = $max_page;
			} else if ( $page > $max_page ) {
				$page = 1;
			}

			$return = sprintf(
				'<a class="wpv-filter-previous-link js-wpv-pagination-previous-link page-link %s"
					%s href="%s" data-viewnumber="%s" data-page="%s"
				>%s</a>',
				$class,
				$style,
				esc_url( $this->get_pager_permalink( $page, $view_count ) ),
				esc_attr( $view_count ),
				esc_attr( $page ),
				$value
			);

			return $return;
		} else {
			if ( toolset_getarr( $atts, 'force' ) === 'true' ) {
				$value = wpv_do_shortcode( $value );
				return '<span class="wpv-filter-previous-link page-link ' . $class . '"' . $style . '>' . $value . '</span>';
			} else {
				return '';
			}
		}
	}

	/**
	* wpv_pager_next_page_callback
	*
	* Callback for the [wpv-pager-next-page] shortcode, used on Views pagination.
	*
	* @since unknown
	* @since 2.6.4 Prevent the shortcode transformation when the View wrapper DIV option is selected.
	*/

	function wpv_pager_next_page_callback( $atts, $value ) {
		// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
		/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
		if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'style' => '',
			'class' => '',
			'force' => 'false',
		), $atts );

		// @refactoring Do not use extract().
		extract( $atts );

		$view_settings	= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
		$page			= apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );
		$max_page		= (int) apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );

		$display = false;
		if (
			$max_page > 1.0
			&& (
				$view_settings['pagination']['type'] == 'rollover'
				|| $page < $max_page
			)
		) {
			$display = true;
		}

		$style = toolset_getarr( $atts, 'style' );
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style ) .'"';
		}
		$class = toolset_getarr( $atts, 'class' );
		if ( ! empty( $class ) ) {
			$class = ' ' . esc_attr( $class );
		}

		if ( $display ) {
			$page++;
			$value = wpv_do_shortcode( $value );

			$view_count = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );

			if ( $page <= 0 ) {
				$page = $max_page;
			} else if ( $page > $max_page ) {
				$page = 1;
			}

			$return = sprintf(
				'<a class="wpv-filter-next-link js-wpv-pagination-next-link page-link %s" %s href="%s"
					data-viewnumber="%s" data-page="%s">%s</a>',
				$class,
				$style,
				esc_url( $this->get_pager_permalink( $page, $view_count ) ),
				esc_attr( $view_count ),
				esc_attr( $page ),
				$value
			);

			return $return;
		} else {
			if ( toolset_getarr( $atts, 'force' ) === 'true' ) {
				$value = wpv_do_shortcode( $value );
				return '<span class="wpv-filter-next-link page-link' . $class . '"' . $style . '>' . $value . '</span>';
			} else {
				return '';
			}
		}
	}

	/**
	 * Callback for the [wpv-pager-first-page] and the [wpv-pager-last-page] shortcodes, used on Views pagination.
	 *
	 * @since 2.4.1
	 */
	function wpv_pager_first_last_page_callback( $atts, $value, $tag ) {
		$atts = shortcode_atts(
			array(
				'style' => '',
				'class' => '',
			),
			$atts
		);

		/**
		 * Filter to get the current View settings.
		 *
		 * @param array		$view_settings 	The View settings.
		 * @param integer 	$view_id 		The View ID.
		 * @param array		$options_array 	Unserialized array with options.
		 *
		 * @return array	$view_settings	The View settings.
		 *
		 * @since 2.4.1
		 */
		$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array() );

		/**
		 * Filter to get the current page number for a pagination control.
		 *
		 * @param integer	$page	The current page number.
		 *
		 * @return integer	$page	The current page number.
		 *
		 * @since 2.4.1
		 */
		$current_page = apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );

		/**
		 * Filter to get the maximum number of pages for a pagination control
		 *
		 * @param integer	$max_pages	The maximum number of pages.
		 *
		 * @return integer	$max_pages	The maximum number of pages.
		 *
		 * @since 2.4.1
		 */
		$max_page = (int) apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );

		$display = false;

		$return = '';

		$page = ( 'wpv_pager_first_page_callback' == $tag  ) ? 1 : $max_page;

		$first_last_links_eligible_page = ( 'wpv_pager_first_page_callback' == $tag ) ? $current_page > 1 : $current_page < $max_page;

		if (
			$max_page > 1.0
			&& (
				$view_settings['pagination']['type'] == 'rollover'
				|| $first_last_links_eligible_page
			)
		) {
			$display = true;
		}

		$class = ( 'wpv_pager_first_page_callback' == $tag ) ? 'wpv-filter-first-link js-wpv-pagination-first-link' : 'wpv-filter-last-link js-wpv-pagination-last-link';
		$class .= ' page-link';

		if ( ! empty( $atts['class']) ) {
			$class .= ' ' . esc_attr( $atts['class'] );
		}

		if ( ! empty( $atts['style'] ) ) {
			$style = ' style="'. esc_attr( $atts['style'] )  .'"';
		} else {
			$style = '';
		}

		if ( $display ) {
			$value = wpv_do_shortcode( $value );

			$view_count = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );

			$return .= '<a'
					   . ' class="' . $class . '" ' . $style
					   . ' href="' . esc_url( $this->get_pager_permalink( $page, $view_count ) ) . '"'
					   . ' data-viewnumber="' . esc_attr( $view_count ) . '"'
					   . ' data-page="' . esc_attr( $page ) . '"'
					   . '>'
					   . $value
					   . '</a>';
		}

		return $return;
	}


	/**
	 * Callback for the [wpv-pager-nav-dropdown] shortcode, displays a select dropdown for Views pagination.
	 *
	 * @param $atts
	 *
	 * @return string
	 * @since 2.4.0 Added the output attribute
	 * @since 2.6.4 Prevent the shortcode transformation when the View wrapper DIV option is selected.
	 *
	 * @todo remember that the classname js-wpv-page-selector does nothing as of now...
	 * @since 1.11.0
	 */
	function wpv_pager_nav_dropdown_callback( $atts ) {
		// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
		/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
		if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'class' => '',
			'output' => '',
		), $atts );

		// @refactoring do not rely on extract();
		extract( $atts );

		$output = toolset_getarr( $atts, 'output' );

		$return = '';

		$view_settings		= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
		$view_count			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$page				= apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );
		$max_page			= (int) apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );

		$class_array = array(
			'wpv-page-selector-' . esc_attr( $view_count ),
			'js-wpv-page-selector',
			toolset_getarr( $atts, 'class' )
		);

		if ( 'bootstrap' === $output ) {
			array_push( $class_array, 'form-control' );
		}

		$return .= sprintf(
			'<select class="%s" data-viewnumber="%s" name="wpv-pager-nav-dropdown">',
			esc_attr( implode( ' ', $class_array ) ),
			esc_attr( $view_count )
		);
		for ( $i = 1; $i < $max_page + 1; $i++ ) {
			$return .= '<option value="' . esc_attr( $i ) . '" ' . selected( $i, $page, false ) . '>' . esc_html( $i ) . '</option>';
		}
		$return .= '</select>';

		return $return;
	}


	/**
	 * Callback for the [wpv-pager-nav-links] shortcode, for Views pagination.
	 *
	 * @param $atts
	 *
	 * @return string
	 * @since 1.11.0
	 * @since 2.4.0 Added the output attribute
	 * @since 2.4.0 Added the anchor_class attribute
	 * @since 2.4.0 Added the previous_next_links attribute
	 * @since 2.4.0 Added the force_previous_next attribute
	 * @since 2.4.0 Added the links_type attribute
	 * @since 2.4.1 Added the first_last_links attribute
	 * @since 2.6.4 Prevent the shortcode transformation when the View wrapper DIV option is selected.
	 *
	 * @todo Bring in sync with the paginate_links methos, to have a single, shaed mechanism for Views an WPAs:
	 *     - apply the classname wpv-pagination-nav-links-item-current to the current list item.
	 *         (mind the wpv_page_current classnme)
	 *     - apply the classname wpv-pagination-link-current to the current anchor.
	 *         (mind there is nor a equivalent yet)
	 */
	function wpv_pager_nav_links_callback( $atts ) {
		// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
		/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
		if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
			return '';
		}

		extract(
			shortcode_atts(
				array(
					'ul_class'		=> '',
					'li_class'		=> '',
					'anchor_class'	=> '',
					'current_type'	=> 'text',

					'anchor_text'	=> __( '%%PAGE%%', 'wpv-views' ),
					'anchor_title'	=> __( '%%PAGE%%', 'wpv-views' ),

					'sticky_first'			=> 'true',
					'sticky_last'			=> 'true',
					'step'					=> false,
					'reach'					=> false,
					'ellipsis'				=> '...',

					'output' => '',
					'previous_next_links' => 'false',
					'force_previous_next' => 'false',
					'prev_next'				=> 'none',// To be implemented: can be 'none', 'maybe' or 'force'

					'first_last_links' => 'false',

					'links_type'	=> '',
				),
				$atts
			)
		);

		$return = '';

		$view_settings		= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
		$view_count			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$max_page			= (int) apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );
		$page				= (int) apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );

		if ( $max_page <= 1.0 ) {
			return '';
		}

		$ul_class_array = array( 'wpv-pagination-nav-links-container', 'js-wpv-pagination-nav-links-container' );
		if ( 'bootstrap' == $output ) {
			array_push( $ul_class_array, 'pagination' );
		}
		if ( 'bootstrap' == $output && 'dots' == $links_type ) {
			array_push( $ul_class_array, 'pagination-dots' );
		}
		if ( ! empty( $ul_class ) ) {
			$ul_class_array = array_merge( $ul_class_array, array_map( 'esc_attr', explode( ' ', $ul_class ) ) );
		}
		$ul_class_array = array_values( $ul_class_array );

		$li_class_array = array( 'wpv-pagination-nav-links-item', 'js-wpv-pagination-nav-links-item' );
		if ( 'bootstrap' == $output ) {
			array_push( $li_class_array, 'page-item' );
		}
		if ( ! empty( $li_class ) ) {
			$li_class_array = array_merge( $li_class_array, array_map( 'esc_attr', explode( ' ', $li_class ) ) );
		}
		$li_class_array = array_values( $li_class_array );

		$step = ( $step === false ) ? $step : intval( $step );
		$reach = ( $reach === false ) ? $reach : intval( $reach );
		$needs_ellipsis = true;

		$return .= '<ul class="' . implode( ' ', $ul_class_array ) . '">';

		for ( $i = 1; $i < $max_page + 1; $i++ ) {
			$is_visible = false;
			if (
				(
					$i == 1
					&& $sticky_first == 'true'
				) || (
					$i == $max_page
					&& $sticky_last == 'true'
				)
			) {
				$is_visible = true;
			}
			if ( $step === false ) {
				if ( $reach === false ) {
					$is_visible = true;
				} else {
					if (
						( $i >= ( $page - $reach ) )
						&& ( $i <= ( $page + $reach ) )
					) {
						$is_visible = true;
					}
				}
			} else {
				if ( $i % $step == 0 ) {
					$is_visible = true;
				}
				if (
					$reach !== false
					&& ( $i >= ( $page - $reach ) )
					&& ( $i <= ( $page + $reach ) )
				) {
					$is_visible = true;
				}
			}
			if ( $is_visible ) {

				$return .= ( 'true' === $first_last_links ) ? $this->wpv_pager_nav_links_render_first_last( $i, $page, $max_page, $output, $view_settings['view_slug'], $atts, 'wpv_pager_first_page_callback' ) : '';

				// If we are rendering the pagination for the first page
				if( 1 == $i && 'true' == $previous_next_links ) {
					// ... and the first page is the current page and we are not forcing previous link
					if ( $i == $page && 'false' == $force_previous_next ) {
						// ... don't do anything
						$return .= '';
					} else {
						// ... otherwise render the the previous link
						$prev_link_class_array = array();

						if ( 'bootstrap' == $output ) {
							array_push( $prev_link_class_array, 'page-item' );
						}

						if ( $i == $page ) {
							array_push( $prev_link_class_array, 'disabled' );
						}

						$args = array(
							'style' => '',
							'class' => '',
							'force'	=> $force_previous_next,
						);
						$prev_link_class_array = array_values( $prev_link_class_array );
						$name = 'pagination_control_for_' . 'previous_link' . '_' . md5( isset( $atts['text_for_previous_link'] ) ? $atts['text_for_previous_link'] : '' );
						$string = isset( $atts['text_for_previous_link'] ) ? __( $atts['text_for_previous_link'], 'wpv-views' ) : __( 'Previous', 'wpv-views' );
						$context = 'View ' . $view_settings['view_slug'];
						$previous_link_text = wpv_translate( $name, $string, false, $context );
						$return .= '<li ' . ' class="' . implode( ' ', $prev_link_class_array ) . '"' . '>' . $this->wpv_pager_prev_page_callback( $args, $previous_link_text ) . '</li>';
					}
				}

				$needs_ellipsis = true;
				$anchor_text_i = str_replace( '%%PAGE%%', $i, $anchor_text );
				$anchor_title_i = str_replace( '%%PAGE%%', $i, $anchor_title );
				$li_current_class_array = $li_class_array;

				$li_current_class_array[] = 'wpv-page-link-' . $view_count . '-' . $i;
				$li_current_class_array[] = 'js-wpv-page-link-' . $view_count . '-' . $i;

				$anchor_class_array = array( 'wpv-filter-pagination-link', 'js-wpv-pagination-link' );
				if ( ! empty( $anchor_class ) ) {
					$anchor_class_array = array_merge( $anchor_class_array, array_map( 'esc_attr', explode( ' ', $anchor_class ) ) );
				}
				if ( 'bootstrap' == $output ) {
					array_push( $anchor_class_array, 'page-link' );
					if ( 'dots' == $links_type ) {
						$current_type = 'link';
					}
				}
				$anchor_class_array = array_values( $anchor_class_array );
				$li_current_content = '<a'
					. ' class="' . implode( ' ', $anchor_class_array ) . '"'
					. ' title="'				. $anchor_title_i . '"'
					. ' href="'					. esc_url( $this->get_pager_permalink( $i, $view_count ) ) . '"'
					. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
					. ' data-page="' 			. esc_attr( $i ) . '"'
					. '>'
					. $anchor_text_i
					. '</a>';

				if ( $i == $page ) {
					$li_current_class_array[] = 'wpv_page_current';
					$li_current_class_array[] = 'wpv-pagination-nav-links-item-current';

					if ( 'bootstrap' == $output ) {
						if( 'dots' != $links_type ) {
						$li_current_class_array[] = 'active';
						} else {
							$li_current_class_array[] = 'active-dot';
						}
					}

					if ( $current_type == 'text' ) {
						$li_current_content = '<span'
						. ' class="wpv-filter-pagination-link page-link"'
						. '>'
						. $anchor_text_i
						. '</span>';
					}
				}

				$li_current_class_string = ( empty( $li_current_class_array ) ) ? '' : ' class="' . implode( ' ', $li_current_class_array ) . '"';
				$return  .= '<li' . $li_current_class_string . '>'
					. $li_current_content
					. '</li>';

				// If we are rendering the pagination for the last page
				if( $i == $max_page && 'true' == $previous_next_links ) {
					// ... and the last page is the current page and we are not forcing next link
					if ( $i == $page && 'false' == $force_previous_next ) {
						// ... don't do anything
						$return .= '';
					} else {
						// ... otherwise render the the next link
						$next_link_class_array = array();

						if ( 'bootstrap' == $output ) {
							array_push( $next_link_class_array, 'page-item' );
						}

						if ( $i == $page ) {
							array_push( $next_link_class_array, 'disabled' );
						}

						$args = array(
							'style' => '',
							'class' => '',
							'force'	=> $force_previous_next,
						);
						$next_link_class_array = array_values( $next_link_class_array );
						$name = 'pagination_control_for_' . 'next_link' . '_' . md5( isset( $atts['text_for_next_link'] ) ? $atts['text_for_next_link'] : '' );
						$string = isset( $atts['text_for_next_link'] ) ? __( $atts['text_for_next_link'], 'wpv-views' ) : __( 'Next', 'wpv-views' );
						$context = 'View ' . $view_settings['view_slug'];
						$next_link_text = wpv_translate( $name, $string, false, $context );
						$return .= '<li ' . ' class="' . implode( ' ', $next_link_class_array ) . '"' . '>' . $this->wpv_pager_next_page_callback( $args, $next_link_text ) . '</li>';
					}
				}


				$return .= ( 'true' === $first_last_links ) ? $this->wpv_pager_nav_links_render_first_last( $i, $page, $max_page, $output, $view_settings['view_slug'], $atts, 'wpv_pager_last_page_callback' ) : '';

			} else if ( $needs_ellipsis ) {
				$needs_ellipsis = false;
				$return  .= '<li class="' . implode( ' ', $li_class_array ) . '">'
					. '<span class="wpv_page_ellipsis">' . $ellipsis . '</span>'
					. '</li>';
			}
		}

		$return .= '</ul>';

		return $return;
	}

	/**
	 * Function that renders the first/last links on the navigation links pagination.
	 *
	 * @param	integer		$currently_parsed_page	The currently parsed page inside the pagination control.
	 * @param	integer		$current_page			The current page of the pagination.
	 * @param	integer		$max_page				The maximum page number.
	 * @param	string		$output					The selected output style.
	 * @param	string		$view_slug				The View slug.
	 * @param	array		$atts					The array containing the attributes of the shortcode.
	 * @param	string		$tag					The string that identifies the first or the last navigation link.
	 *
	 * @return	string		$first_last_link		The markup for the first/last link.
	 *
	 * @since 2.4.1 Added the first_last_links attribute
	 *
	 */
	function wpv_pager_nav_links_render_first_last( $currently_parsed_page, $current_page, $max_page, $output, $view_slug, $atts, $tag ) {
		$out = '';
		$page = ( 'wpv_pager_first_page_callback' == $tag  ) ? 1 : $max_page;
		$link_id = ( 'wpv_pager_first_page_callback' == $tag  ) ? 'first_link' : 'last_link';
		$link_text = ( 'wpv_pager_first_page_callback' == $tag  ) ? __( 'First', 'wpv-views' ) : __( 'Last', 'wpv-views' );

		// If we are rendering the pagination for the first/last page
		if( $page === $currently_parsed_page ) {
			// ... and the first/last page is not the current page
			if ( $currently_parsed_page !== $current_page ) {

				$first_last_link_class_array = array();

				if ( 'bootstrap' == $output ) {
					array_push( $first_last_link_class_array, 'page-item' );
				}

				$args = array(
					'style' => '',
					'class' => '',
				);

				$first_last_link_class_array = array_values( $first_last_link_class_array );
				$name = 'pagination_control_for_' . $link_id . '_' . md5( isset( $atts['text_for_' . $link_id ] ) ? $atts['text_for_' . $link_id ] : '' );
				$string = isset( $atts['text_for_' . $link_id ] ) ? __( $atts['text_for_' . $link_id ], 'wpv-views' ) : $link_text;
				$context = 'View ' . $view_slug;
				$first_last_link_text = wpv_translate( $name, $string, false, $context );
				$out .= '<li ' . ' class="' . implode( ' ', $first_last_link_class_array ) . '"' . '>' . $this->wpv_pager_first_last_page_callback( $args, $first_last_link_text, $tag ) . '</li>';
			}
		}

		return $out;
	}

	/**
	* wpv_archive_pager_prev_page_shortcode
	*
	* Callback for the [wpv-archive-pager-prev-page] shortcode, used on WordPress Archives pagination.
	*
	* Generates the natural WordPress pagination link for previous page.
	* This shortcode generates the pagination on the reverse direction:
	* prev means older, which means moving from page 1 to 2
	* next means newer, which means moving from page 2 to 1
	*
	* @since 1.7
	* @since 2.1	Moved to this general class
	* @since 2.1	Adjusted to work with AJAX pagination
	*
	* @deprecated 2.1
	*/

	function wpv_archive_pager_prev_page_shortcode( $atts, $value ) {
		return $this->wpv_pager_archive_next_page_shortcode( $atts, $value );
	}

	/**
	* wpv_archive_pager_next_page_shortcode
	*
	* Callback for the [wpv-archive-pager-next-page] shortcode, used on WordPress Archives pagination.
	*
	* Generates the natural WordPress pagination link for previous page.
	* This shortcode generates the pagination on the reverse direction:
	* prev means older, which means moving from page 1 to 2
	* next means newer, which means moving from page 2 to 1
	*
	* @since 1.7
	* @since 2.1	Moved to this general class
	* @since 2.1	Adjusted to work with AJAX pagination
	*
	* @deprecated 2.1
	*/

	function wpv_archive_pager_next_page_shortcode( $atts, $value ) {
		return $this->wpv_pager_archive_prev_page_shortcode( $atts, $value );
	}

	/**
	* wpv_pager_archive_prev_page_shortcode
	*
	* Callback for the [wpv-pager-archive-prev-page] on WPAs pagination.
	*
	* @since 2.1
	*/

	function wpv_pager_archive_prev_page_shortcode( $atts, $value ) {
		extract(
			shortcode_atts(
				array( 'force' => 'false' ),
				$atts
			)
		);
		if ( ! is_single() ) {
			global $paged;
			$label = wpv_do_shortcode( $value );
			if ( $paged > 1 ) {
				$prevpage = intval( $paged ) - 1;
				/**
				* Filter the anchor tag attributes for the previous posts page link.
				*
				* @since 2.7.0
				*
				* @param string $attributes Attributes for the anchor tag.
				*/
				$attr				= apply_filters( 'previous_posts_link_attributes', '' );
				$view_id			= apply_filters( 'wpv_filter_wpv_get_current_view', null );
				$view_settings		= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
				$view_unique_hash	= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
				$permalinks			= $this->pagination_archive_permalink_data( $view_settings, $view_id );
				if ( $prevpage == 1 ) {
					$base_permalink		= $permalinks['first'];
				} else {
					$base_permalink		= str_replace( 'WPV_PAGE_NUM', $prevpage, $permalinks['other'] );
				}
				return '<a'
					. ' class="wpv-archive-pagination-prev-link js-wpv-archive-pagination-prev-link page-link"'
					. ' data-viewnumber="' . esc_attr( $view_unique_hash ) . '"'
					. ' data-page="' . esc_attr( $prevpage ) . '"'
					. ' href="' . $base_permalink . "\" $attr>"
					. preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
					.'</a>';
			} else if ( $force == 'true' ) {
				return '<span class="wpv-archive-pagination-prev-link wpv-archive-pagination-prev-link-first page-link">' . $label . '</span>';
			}
		}
	}

	/**
	* wpv_pager_archive_next_page_shortcode
	*
	* Callback for the [wpv-pager-archive-next-page] on WPAs pagination.
	*
	* @since 2.1
	*/

	function wpv_pager_archive_next_page_shortcode( $atts, $value ) {
		extract(
			shortcode_atts(
				array( 'force' => 'false' ),
				$atts
			)
		);
		if ( ! is_single() ) {
			global $paged;
			$max_pages = apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );
			if ( ! $paged ) {
				$paged = 1;
			}
			$nextpage = intval( $paged ) + 1;
			$label = wpv_do_shortcode( $value );
			if ( $nextpage <= $max_pages ) {
				/**
				* Filter the anchor tag attributes for the next posts page link.
				*
				* @since 2.7.0
				*
				* @param string $attributes Attributes for the anchor tag.
				*/
				$attr = apply_filters( 'next_posts_link_attributes', '' );
				$view_id			= apply_filters( 'wpv_filter_wpv_get_current_view', null );
				$view_settings		= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
				$view_unique_hash	= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
				$permalinks			= $this->pagination_archive_permalink_data( $view_settings, $view_id );
				if ( $nextpage == 1 ) {
					$base_permalink		= $permalinks['first'];
				} else {
					$base_permalink		= str_replace( 'WPV_PAGE_NUM', $nextpage, $permalinks['other'] );
				}
				return '<a'
					. ' class="wpv-archive-pagination-next-link js-wpv-archive-pagination-next-link page-link"'
					. ' data-viewnumber="' . esc_attr( $view_unique_hash ) . '"'
					. ' data-page="' . esc_attr( $nextpage ) . '"'
					. ' href="' . $base_permalink . "\" $attr>"
					. preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label)
					. '</a>';
			} else if ( $force == 'true' ) {
				return '<span class="wpv-archive-pagination-next-link wpv-archive-pagination-next-link-last">' . $label . '</span>';
			}
		}
	}

	/**
	 * wpv_pager_archive_nav_links_callback
	 *
	 * Callback for the [wpv-pager-archive-nav-links] shortcode.
	 *
	 * Pagination links for WordPress Archives.
	 *
	 * @param array $atts {
	 *     Optional. Array of arguments for generating paginated links for archives.
	 *
	 *     @type string $type               Controls format of the returned value. Possible values are 'plain' and 'list'. Default is 'list'.
	 *     @type string $ul_class           The list class attribute value. Default empty.
	 *     @type string $li_class           The list item class attribute value. Default empty.
	 *
	 *     @type int    $reach              How many numbers to either side of the current pages. Default 0 which means show all.
	 *     @type int    $end_size           How many numbers on either the start and the end list edges. Default 1.
	 *     @type int    $step               Big numbers to show. Default 0 wich means no step pages will be shown.
	 *     @type string $ellipsis           The ellipsis text when skipping pages. Default is '...'.
	 *
	 *     @type string $prev_next          Whether to include the previous and next links in the list. Possible values are 'none', 'maybe' and 'force'. Default is 'none'.
	 *     @type bool   $prev_text          The previous page text. Default '« Previous'.
	 *     @type bool   $next_text          The next page text. Default '« Previous'.
	 *
	 *     @type string $current_type       The type of output for the current page item. Possible values are 'text' and 'link'. Default is 'text'.
	 *     @type string $anchor_text	       The anchor for each page link. Default is '%%PAGE%%.
	 *     @type string $anchor_title       The anchor title for each page link. Default is '%%PAGE%%.
	 * }
	 *
	 * @return string
	 *
	 * @since 2.1.0
	 * @since 2.4.0 Added the output attribute
	 * @since 2.4.0 Added the previous_next_links attribute
	 * @since 2.4.0 Added the force_previous_next attribute
	 * @since 2.4.1 Added the first_last_links attribute
	 */
	function wpv_pager_archive_nav_links_callback( $atts ) {
		extract(
			shortcode_atts(
				array(
					'type'			=> 'list',
					'ul_class'		=> '',
					'li_class'		=> '',

					'reach'			=> 0,
					'end_size'		=> 1,
					'step'			=> 0,
					'ellipsis'		=> '...',

					'prev_next'		=> 'none',
					'prev_text'		=> __( 'Previous', 'wpv-views' ),
					'next_text'		=> __( 'Next', 'wpv-views' ),

					'current_type'	=> 'text',
					'anchor_text'	=> __( '%%PAGE%%', 'wpv-views' ),
					'anchor_title'	=> __( '%%PAGE%%', 'wpv-views' ),

					'output' => '',
					'previous_next_links' => 'false',
					'force_previous_next' => 'false',

					'first_last_links' => 'false',
					'first_text' => __( 'First', 'wpv-views' ),
					'last_text'	=> __( 'Last', 'wpv-views' ),
				),
				$atts
			)
		);

		$view_id = apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$view_unique_hash = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );

		$name = 'pagination_control_for_' . 'previous_link' . '_' . md5( isset( $atts['text_for_previous_link'] ) ? $atts['text_for_previous_link'] : '' );
		$string = isset( $atts['text_for_previous_link'] ) ? __( $atts['text_for_previous_link'], 'wpv-views' ) : $prev_text;
		$context = 'View ' . $view_settings['view_slug'];
		$previous_link_text = wpv_translate( $name, $string, false, $context );

		$name = 'pagination_control_for_' . 'next_link' . '_' . md5( isset( $atts['text_for_next_link'] ) ? $atts['text_for_next_link'] : '' );
		$string = isset( $atts['text_for_next_link'] ) ? __( $atts['text_for_next_link'], 'wpv-views' ) : $next_text;
		$next_link_text = wpv_translate( $name, $string, false, $context );

		$name = 'pagination_control_for_' . 'first_link' . '_' . md5( isset( $atts['text_for_first_link'] ) ? $atts['text_for_first_link'] : '' );
		$string = isset( $atts['text_for_first_link'] ) ? __( $atts['text_for_first_link'], 'wpv-views' ) : $first_text;
		$first_link_text = wpv_translate( $name, $string, false, $context );

		$name = 'pagination_control_for_' . 'last_link' . '_' . md5( isset( $atts['text_for_last_link'] ) ? $atts['text_for_last_link'] : '' );
		$string = isset( $atts['text_for_last_link'] ) ? __( $atts['text_for_last_link'], 'wpv-views' ) : $last_text;
		$last_link_text = wpv_translate( $name, $string, false, $context );

		$links_type = isset( $atts['links_type'] ) ? $atts['links_type'] : null;

		$args = array(
			'type'				=> $type,
			'ul_class'			=> $ul_class,
			'li_class'			=> $li_class,

			'show_all'			=> ( ! ( $reach || $step ) ),
			'mid_size'			=> ( ! empty( $reach ) ) ? $reach : 1,
			'end_size'			=> ( ! empty( $end_size ) ) ? $end_size : 1,
			'step'				=> $step,
			'ellipsis'			=> $ellipsis,

			'prev_next'			=> ( 'true' == $previous_next_links ) ? ( 'true' == $force_previous_next ) ? 'force' : 'maybe' : $prev_next,
			'prev_text'			=> $previous_link_text,
			'next_text'			=> $next_link_text,

			'current_type'		=> $current_type,
			'anchor_text'		=> $anchor_text,
			'anchor_title'		=> $anchor_title,

			'ul_class_force'	=> 'wpv-archive-pagination-nav-links-container js-wpv-archive-pagination-nav-links-container',
			'li_class_force'	=> 'wpv-archive-pagination-nav-links-item js-wpv-archive-pagination-nav-links-item',
			'li_class_current'	=> 'wpv-archive-pagination-nav-links-item-current',
			'a_class_force'		=> 'wpv-archive-pagination-link js-wpv-archive-pagination-link',
			'a_class_current'	=> 'wpv-archive-pagination-link-current',
			'span_class_force'	=> 'wpv-archive-pagination-link wpv-archive-pagination-link-current',
			'ellipsis_class_force' => 'wpv-archive-pagination-link wpv-archive-pagination-link-ellipsis',

			'output' => $output,
			'previous_next_links' => $previous_next_links,
			'force_previous_next' => $force_previous_next,

			'first_last' => $first_last_links,
			'first_text' => $first_link_text,
			'last_text' => $last_link_text,

			'links_type' => $links_type,
		);

		$args['viewnumber']	= $view_unique_hash;

		$view_url_data					= get_view_allowed_url_parameters( $view_id );
		$view_url_parameters			= wp_list_pluck( $view_url_data, 'attribute', 'filter_type' );
		$view_url_parameters['lang']	= 'lang';

		$view_url_param_maybe	= array();
		$view_url_param_maybe[]	= 'orderby';
		$view_url_param_maybe[]	= 'order';
		$view_url_param_maybe[]	= 'orderby_as';
		$view_url_param_maybe[]	= 'orderby_second';
		$view_url_param_maybe[]	= 'order_second';

		$query_args			= array();
		$query_args_remove	= array();

		// Avoid parametric search URL parameters when they are empty - post_relationship
		foreach ( $view_url_parameters as $param_key => $param_value ) {
			if ( isset( $_GET[ $param_value ] ) ) {
				// Empty values on taxonomy filters are removed
				// Real emoty values on field filters are removed
				// Empty values on other filters are kept
				if ( strpos( $param_key, 'post_taxonomy_' ) === 0 ) {
					if (
						empty( $_GET[ $param_value ] )
						|| $_GET[ $param_value ] == array( "0" )
					) {
						$query_args_remove[] = $param_value;
					} else {
						$query_args[ $param_value ] = $_GET[ $param_value ];
					}
				} else if (	strpos( $param_key, 'post_custom_field_' ) === 0 ) {
					if ( $_GET[ $param_value ] == '' ) {
						$query_args_remove[] = $param_value;
					} else {
						$query_args[ $param_value ] = $_GET[ $param_value ];
					}
				} else if ( strpos( $param_key, 'post_relationship' ) === 0 ) {
					if (
						empty( $_GET[ $param_value ] )
						|| $_GET[ $param_value ] == array( "0" )
					) {
						$query_args_remove[] = $param_value;
					} else {
						$query_args[ $param_value ] = $_GET[ $param_value ];
					}
				} else {
					$query_args[ $param_value ] = $_GET[ $param_value ];
				}
			} else {
				$query_args_remove[] = $param_value;
			}
		}

		// Avoid sorting URL parameters if they match the defaults
		foreach ( $view_url_param_maybe as $param ) {
			if (
				isset( $_GET[ 'wpv_sort_' . $param ] )
				&& (
					! isset( $view_settings[ $param ] )
					|| strtolower( $_GET[ 'wpv_sort_' . $param ] ) != strtolower( $view_settings[ $param ] )
				)
			) {
				$query_args[ $param ] = $_GET[ 'wpv_sort_' . $param ];
			} else {
				$query_args_remove[] = $param;
			}
		}

		$query_args				= urlencode_deep( $query_args );

		$args['add_args']		= $query_args;
		$args['remove_args']	= $query_args_remove;

		// @todo we need to abstract the code in wpv_pager_nav_links_callback using this one as model
		$return = $this->paginate_links( $args );

		return $return;
	}

	/**
	* wpv_pager_pause_rollover_callback
	*
	* Work in progress
	*/

	function wpv_pager_pause_rollover_callback( $atts, $content ) {
		extract(
			shortcode_atts(
				array(
					'style' => '',
					'class' => '',
					'force'	=> 'false'
				),
				$atts
			)
		);
		$view_settings		= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
		$view_count			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$content = wpv_do_shortcode( $content );
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style ) .'"';
		}
		if ( ! empty( $class ) ) {
			$class = ' ' . esc_attr( $class );
		}

		$return = '<a'
			. ' class="wpv-filter-previous-link js-wpv-pagination-pause-rollover'. $class .'"' . $style
			. ' href="#"'
			. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
			. '>'
			. $content
			. '</a>';

		return $return;
	}

	/**
	* wpv_pager_resume_rollover_callback
	*
	* Work in progress
	*/

	function wpv_pager_resume_rollover_callback( $atts, $content ) {
		extract(
			shortcode_atts(
				array(
					'style' => '',
					'class' => '',
					'force'	=> 'false'
				),
				$atts
			)
		);
		$view_settings		= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
		$view_count			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$content			= wpv_do_shortcode( $content );
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style ) .'"';
		}
		if ( ! empty( $class ) ) {
			$class = ' ' . esc_attr( $class );
		}

		$return = '<a'
			. ' class="wpv-filter-previous-link js-wpv-pagination-resume-rollover'. $class .'"' . $style
			. ' href="#"'
			. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
			. '>'
			. $content
			. '</a>';

		return $return;
	}

	/**
	* wpv_ajax_pagination_lang
	*
	* Adjust the language when doing AJAX pagination.
	*
	* @todo This might not be needed.
	*
	* @since unknown
	*/

	function wpv_ajax_pagination_lang( $lang ) {
		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			&& isset( $_REQUEST['action'] )
			&& (
				$_REQUEST['action'] == 'wpv_get_view_query_results'
				|| $_REQUEST['action'] == 'wpv_get_archive_query_results'
			)
			&& isset( $_POST['lang'] )
		) {
			$lang = esc_attr( $_POST['lang'] );
		}
		return $lang;
	}

	/**
	* get_pagination_permalink_data
	*
	* Generates the permalink reference data.
	*
	* The permalink reference data is an array with just two entries:
	* 	'first'	Contains the permalink for the current object on its first page.
	* 	'other'	Contains the permalink for the current object on any page but the first one, using a WPV_PAGE_NUM placeholder for the page number.
	* This is a callback for a helper filter to get this data.
	*
	* @since 2.1
	*/

	function get_pagination_permalink_data( $permalink_data, $view_settings = array(), $view_id = null ) {
		$permalinks = $this->pagination_permalink_data( $view_settings, $view_id );
		$permalinks = array_map( array( $this, 'clean_permalink_url' ), $permalinks );
		return $permalinks;
	}

	/**
	* pagination_permalink_data
	*
	* Generates the permalink reference data.
	*
	* The permalink reference data is an array with just two entries:
	* 	'first'	Contains the permalink for the current object on its first page.
	* 	'other'	Contains the permalink for the current object on any page but the first one, using a WPV_PAGE_NUM placeholder for the page number.
	*
	* @since 2.1
	*/

	function pagination_permalink_data( $view_settings = array(), $view_id = null ) {
		$permalink_data = array(
			'first'	=> '',
			'other'	=> ''
		);
		if (
			! isset( $view_settings['view-query-mode'] )
			|| ( 'normal' == $view_settings['view-query-mode'] )
		) {
			$permalink_data = $this->pagination_view_permalink_data( $view_settings, $view_id );
		} else {
			// we assume 'archive' or 'layouts-loop'
			$permalink_data = $this->pagination_archive_permalink_data( $view_settings, $view_id );
		}
		return $permalink_data;
	}

	/**
	* pagination_view_permalink_data
	*
	* Generates the permalink reference data for Views pagination.
	*
	* The permalink reference data is an array with just two entries:
	* 	'first'	Contains the permalink for the current object on its first page.
	* 	'other'	Contains the permalink for the current object on any page but the first one, using a WPV_PAGE_NUM placeholder for the page number.
	*
	* @since 2.1
	*/

	function pagination_view_permalink_data( $view_settings = array(), $view_id = null ) {
		$permalink_data = array();
		$view_hash = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$permalink = $this->get_pager_permalink( 'WPV_PAGE_NUM', $view_hash, array(), $view_id );
		$permalink_data['other'] = $permalink;
		$permalink_data['first'] = remove_query_arg( 'wpv_paged', $permalink );
		return $permalink_data;
	}

	/**
	* pagination_archive_permalink_data
	*
	* Generates the permalink reference data for WPAs pagination.
	*
	* The permalink reference data is an array with just two entries:
	* 	'first'	Contains the permalink for the current object on its first page.
	* 	'other'	Contains the permalink for the current object on any page but the first one, using a WPV_PAGE_NUM placeholder for the page number.
	*
	* @since 2.1
	*/

	function pagination_archive_permalink_data( $view_settings = array(), $view_id = null ) {
		global $wp_rewrite;

		$permalink_data = array();

		// Setting up default values based on the current URL.
		$pagenum_link = html_entity_decode( $this->get_pagenum_link() );
		$url_parts    = explode( '?', $pagenum_link );

		// Append the format placeholder to the base URL.
		$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

		// URL base depends on permalink settings.
		$format  	= $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format_one	= $format;

		$format		.= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';
		//$format_one	.= $wp_rewrite->using_permalinks() ? user_trailingslashit( '', '' ) : '';

		// Merge additional query vars found in the original URL into $add_args.
		$add_args		= array();
		$add_args_one	= array();

		if ( isset( $url_parts[1] ) ) {
			// Find the format argument.
			$format_exploded		= explode( '?', str_replace( '%_%', $format, $pagenum_link ) );
			$format_one_exploded	= explode( '?', str_replace( '%_%', $format_one, $pagenum_link ) );

			$format_query		= isset( $format_exploded[1] ) ? $format_exploded[1] : '';
			$format_one_query	= isset( $format_one_exploded[1] ) ? $format_one_exploded[1] : '';

			wp_parse_str( $format_query, $format_args );
			wp_parse_str( $format_one_query, $format_one_args );

			// Find the query args of the requested URL.
			wp_parse_str( $url_parts[1], $url_query_args );
			wp_parse_str( $url_parts[1], $url_query_args_one );

			// Remove the format argument from the array of query arguments, to avoid overwriting custom format.
			foreach ( $format_args as $format_arg => $format_arg_value ) {
				unset( $url_query_args[ $format_arg ] );
			}

			foreach ( $format_one_args as $format_arg => $format_arg_value ) {
				unset( $url_query_args_one[ $format_arg ] );
			}

			$add_args		= urlencode_deep( $url_query_args );
			$add_args_one	= urlencode_deep( $url_query_args_one );
		}

		$permalink		= str_replace( '%_%', $format, $pagenum_link );
		$permalink_one	= str_replace( '%_%', $format_one, $pagenum_link );

		$permalink		= str_replace( '%#%', 'WPV_PAGE_NUM', $permalink );
		$permalink_one	= str_replace( '%#%', 'WPV_PAGE_NUM', $permalink_one );// Might not be needed, does not hunt

		if ( $add_args ) {
			$permalink = add_query_arg( $add_args, $permalink );
		}

		if ( $add_args_one ) {
			$permalink_one = add_query_arg( $add_args_one, $permalink_one );
		}

		if ( ! is_null( $view_id ) ) {

			$view_url_data					= get_view_allowed_url_parameters( $view_id );
			$view_url_parameters			= wp_list_pluck( $view_url_data, 'attribute', 'filter_type' );
			$view_url_parameters['lang']	= 'lang';

			$view_url_param_maybe	= array();
			$view_url_param_maybe[]	= 'orderby';
			$view_url_param_maybe[]	= 'order';
			$view_url_param_maybe[]	= 'orderby_as';
			$view_url_param_maybe[]	= 'orderby_second';
			$view_url_param_maybe[]	= 'order_second';

			$query_args			= array();
			$query_args_remove	= array();

			// Avoid parametric search URL parameters when they are empty - post_relationship
			foreach ( $view_url_parameters as $param_key => $param_value ) {
				if ( isset( $_GET[ $param_value ] ) ) {
					// Empty values on taxonomy filters are removed
					// Real emoty values on field filters are removed
					// Empty values on other filters are kept
					if ( strpos( $param_key, 'post_taxonomy_' ) === 0 ) {
						if (
							empty( $_GET[ $param_value ] )
							|| $_GET[ $param_value ] == array( "0" )
						) {
							$query_args_remove[] = $param_value;
						} else {
							$query_args[ $param_value ] = $_GET[ $param_value ];
						}
					} else if (	strpos( $param_key, 'post_custom_field_' ) === 0 ) {
						if (
							$_GET[ $param_value ] == ''
							|| $_GET[ $param_value ] == array( '' )
						) {
							$query_args_remove[] = $param_value;
						} else {
							$query_args[ $param_value ] = $_GET[ $param_value ];
						}
					} else if ( strpos( $param_key, 'post_relationship' ) === 0 ) {
						if (
							empty( $_GET[ $param_value ] )
							|| $_GET[ $param_value ] == array( "0" )
						) {
							$query_args_remove[] = $param_value;
						} else {
							$query_args[ $param_value ] = $_GET[ $param_value ];
						}
					} else {
						$query_args[ $param_value ] = $_GET[ $param_value ];
					}
				} else {
					$query_args_remove[] = $param_value;
				}
			}

			// Avoid sorting URL parameters if they match the defaults
			foreach ( $view_url_param_maybe as $param ) {
				if (
					isset( $_GET[ 'wpv_sort_' . $param ] )
					&& $_GET[ 'wpv_sort_' . $param ] != ''
					&& (
						! isset( $view_settings[ $param ] )
						|| strtolower( $_GET[ 'wpv_sort_' . $param ] ) != strtolower( $view_settings[ $param ] )
					)
				) {
					$query_args[ 'wpv_sort_' . $param ] = $_GET[ 'wpv_sort_' . $param ];
				} else {
					$query_args_remove[] = 'wpv_sort_' . $param;
				}
			}

			if (
				in_array( 'wpv_sort_orderby_second', $query_args_remove )
				&& isset( $query_args['wpv_sort_order_second'] )
			) {
				$query_args_remove[] = 'wpv_sort_order_second';
				unset( $query_args['wpv_sort_order_second'] );
			}

			$view_hash = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
			if ( $view_hash ) {
				$query_args['wpv_view_count'] = $view_hash;
			} else {
				$query_args_remove[] = 'wpv_view_count';
			}

			$query_args = urlencode_deep( $query_args );

			$permalink = remove_query_arg(
				$query_args_remove,
				$permalink
			);

			$permalink = add_query_arg(
				$query_args,
				$permalink
			);

			$permalink_one = remove_query_arg(
				$query_args_remove,
				$permalink_one
			);

			$permalink_one = add_query_arg(
				$query_args,
				$permalink_one
			);
		}

		$permalink_data['other'] = $permalink;
		$permalink_data['first'] = $permalink_one;

		return $permalink_data;
	}

	/**
	* get_pager_permalink
	*
	* Generates the permalink for a View page.
	* Keeps and adjusts the URL parameters for query filters, if they are included in the $_GET global.
	*
	* @since 2.0	No parameters will return the current clean permalink.
	* 				$page defaults to 1, which produces no wpv_paged URL parameter.
	* 				$view_hash defaults to false and produces no wpv_view_count URL parameter.
	* @since 2.1	$page can be WPV_PAGE_NUM to generate generic permalink URLs.
	*/

	function get_pager_permalink( $page = 1, $view_hash = false, $get_override = array(), $view_id = null ) {
		if ( is_null( $view_id ) ) {
			$view_id				= apply_filters( 'wpv_filter_wpv_get_current_view', $view_id );
		}

		$view_settings					= apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id );

		$view_url_data					= get_view_allowed_url_parameters( $view_id );
		$view_url_parameters			= wp_list_pluck( $view_url_data, 'attribute', 'filter_type' );
		$view_url_parameters['lang']	= 'lang';
		$view_url_parameters['wpv_aux_current_post_id']		= 'wpv_aux_current_post_id';
		$view_url_parameters['wpv_aux_parent_post_id']		= 'wpv_aux_parent_post_id';
		$view_url_parameters['wpv_aux_parent_term_id']		= 'wpv_aux_parent_term_id';
		$view_url_parameters['wpv_aux_parent_user_id']		= 'wpv_aux_parent_user_id';

		// We can not avoid sorting URL parameters as they might need to be forced over shortcode attribute settings even for the default values
		$view_url_parameters['wpv_sort_orderby']		= 'wpv_sort_orderby';
		$view_url_parameters['wpv_sort_order']			= 'wpv_sort_order';
		$view_url_parameters['wpv_sort_orderby_as']		= 'wpv_sort_orderby_as';
		$view_url_parameters['wpv_sort_orderby_second']	= 'wpv_sort_orderby_second';
		$view_url_parameters['wpv_sort_order_second']	= 'wpv_sort_order_second';
		$view_url_param_maybe	= array();

		$origin					= false;
		$url_request			= $_SERVER['REQUEST_URI'];
		$query_args				= array();
		$query_args_remove		= array();

		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			&& isset( $_REQUEST['action'] )
			&& $_REQUEST['action'] == 'wpv_get_view_query_results'
		) {
			$origin = wp_get_referer();
		}

		if ( ! empty( $get_override ) ) {
			$post_old = $_GET;
			foreach ( $get_override as $key => $value ) {
				$_GET[ $key ] = $value;
			}
		}

		// Avoid parametric search URL parameters when they are empty
		foreach ( $view_url_parameters as $param_key => $param_value ) {
			if ( isset( $_GET[ $param_value ] ) ) {
				// Empty values on taxonomy filters are removed
				// Real emoty values on field filters are removed
				// Empty values on other filters are kept
				if ( strpos( $param_key, 'post_taxonomy_' ) === 0 ) {
					if (
						empty( $_GET[ $param_value ] )
						|| $_GET[ $param_value ] == array( "0" )
					) {
						$query_args_remove[] = $param_value;
					} else {
						$query_args[ $param_value ] = $_GET[ $param_value ];
					}
				} else if (	strpos( $param_key, 'post_custom_field_' ) === 0 ) {
					if (
						$_GET[ $param_value ] == ''
						|| $_GET[ $param_value ] == array( '' )
					) {
						$query_args_remove[] = $param_value;
					} else {
						$query_args[ $param_value ] = $_GET[ $param_value ];
					}
				} else if ( strpos( $param_key, 'post_relationship' ) === 0 ) {
					if (
						empty( $_GET[ $param_value ] )
						|| $_GET[ $param_value ] == array( "0" )
					) {
						$query_args_remove[] = $param_value;
					} else {
						$query_args[ $param_value ] = $_GET[ $param_value ];
					}
				} else {
					if ( $_GET[ $param_value ] == '' ) {
						$query_args_remove[] = $param_value;
					} else {
						$query_args[ $param_value ] = $_GET[ $param_value ];
					}
				}
			} else {
				$query_args_remove[] = $param_value;
			}
		}

		if (
			in_array( 'wpv_sort_orderby_second', $query_args_remove )
			&& isset( $query_args['wpv_sort_order_second'] )
		) {
			$query_args_remove[] = 'wpv_sort_order_second';
			unset( $query_args['wpv_sort_order_second'] );
		}

		if ( $view_hash ) {
			$query_args['wpv_view_count']	= $view_hash;
		} else {
			$query_args_remove[] = 'wpv_view_count';
		}

		if ( $page != 1 ) {
			$query_args['wpv_paged']		= $page;
		} else {
			$query_args_remove[] = 'wpv_paged';
		}

		if ( ! empty( $get_override ) ) {
			$_GET = $post_old;
		}

		$url = remove_query_arg(
			$query_args_remove,
			$origin
		);

		$query_args = urlencode_deep( $query_args );

		$url = add_query_arg(
			$query_args,
			$url
		);

		$url = $this->clean_permalink_url( $url );

		return $url;
	}

	/**
	* wpv_global_pagination_manage_history_status
	*
	* Get the global Views pagination history management status.
	*
	* @since 1.12.1
	*/

	function wpv_global_pagination_manage_history_status( $status ) {
		$settings = WPV_Settings::get_instance();
		if ( $settings->wpv_enable_pagination_manage_history ) {
			$status = true;
		} else {
			$status = false;
		}
		return $status;
	}

	/**
	* paginate_links
	*
	* Extend the native paginate_links to be used in Views and WordPress Archives.
	*
	* @param array $args {
	*     Optional. Array of arguments for generating paginated links for archives.
	*
	*     @type string $base               Base of the paginated url. Default empty.
	*     @type string $format             Format for the pagination structure. Default empty.
	*     @type bool   $show_all           Whether to show all pages. Default false.
	*     @type int    $end_size           How many numbers on either the start and the end list edges. Default 1.
	*     @type bool   $prev_text          The previous page text. Default '« Previous'.
	*     @type bool   $next_text          The next page text. Default '« Previous'.
	*     @type array  $add_args           An array of query args to add. Default false.
	*     @type string $add_fragment       A string to append to each link. Default empty.
	*
	*     // Modified parameters from the native function
	*
	*     @type int    $total              The total amount of pages. Default is the result of the 'wpv_filter_wpv_get_max_pages' filter or 1.
	*     @type int    $current            The current page number. Default is the result of the 'wpv_filter_wpv_get_current_page_number' filter or 1.
	*     @type string $type               Controls format of the returned value. Possible values are 'plain' and 'list'. Default is 'list'. Removed 'array'.
	*     @type string $prev_next          Whether to include the previous and next links in the list. Possible values are 'none', 'maybe' and 'force'. Default is 'none'.
	*     @type int    $mid_size           How many numbers to either side of the current pages. Default 1. Was 2.
	*
	*     // Removed parameters from the native function
	*
	*     @type string $before_page_number A string to appear before the page number. Default empty.
	*     @type string $after_page_number  A string to append after the page number. Default empty.
	*
	*     // Additional parameters not in the native function
	*
	*     @type array  $remove_args        An array of query args to remove. Default false.
	*     @type string $ul_class           The list class attribute value. Default empty.
	*     @type string $ul_class_force     The list class attribute value that needs to be forced. Defaults to "wpv-archive-pagination-nav-links-container js-wpv-archive-pagination-nav-links-container".
	*     @type string $li_class           The list item class attribute value. Default empty.
	*     @type string $li_class_force     The list item class attribute value that needs to be forced. Defaults to "wpv-archive-pagination-nav-links-item js-wpv-archive-pagination-nav-links-item".
	*     @type string $li_class_current   The list item class attribute value that needs to be set for the current item. Defaults to "wpv-archive-pagination-nav-links-item-current".
	*     @type string $a_class_force      The link class attribute value that needs to be forced. Default empty.
	*     @type string $a_class_current    The link class attribute value that needs to be set for the current item. Default empty.
	*     @type string $span_class_force   The span class attribute value that needs to be forced. Default empty.
	*     @type string $current_type       The type of output for the current page item. Possible values are 'text' and 'link'. Default is 'text'.
	*     @type string $anchor_text	       The anchor for each page link. Default is '%%PAGE%%.
	*     @type string $anchor_title       The anchor title for each page link. Default is '%%PAGE%%.
	*     @type int    $step               The big numbers to include. Default 0 which means no big numbers to show.
	*     @type string $ellipsis           The ellipsis text when skipping pages. Default is '...'.
	* }
	*
	* @return array|string|void String of page links or array of page links.
	*
	* @todo Track changes from paginate_links on wp-includes/general-template.php although this has not been changed since 2.1
	* @todo	Extend the URL generation so we can use it on Views too.
	* @todo Use the get_pagination_permalink_data filter here to generate pagination links on a shared way too. By now we are passign add_args and remove_args data.
	* @todo Document the default values for WPAs in wpv_pager_archive_nav_links_callback instead, as this will be source agnostic.
	*
	* @since 2.1
	*/

	function paginate_links( $args = array() ) {
		global $wp_rewrite;

		// Setting up default values based on the current URL.
		$pagenum_link = html_entity_decode( $this->get_pagenum_link() );
		$url_parts    = explode( '?', $pagenum_link );

		// Get max pages and current page out of the current query, if available.
		$total   = apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );
		$current = apply_filters( 'wpv_filter_wpv_get_current_page_number', 1 );

		// Append the format placeholder to the base URL.
		$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

		// URL base depends on permalink settings.
		$format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

		$defaults = array(
			'base'					=> $pagenum_link, // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
			'format'				=> $format, // ?page=%#% : %#% is replaced by the page number
			'total'					=> $total,
			'current'				=> $current,
			'show_all'				=> false,

			'prev_text'				=> __('&laquo; Previous'),
			'next_text'				=> __('Next &raquo;'),
			'end_size'				=> 1,
			'step'					=> 0,

			'add_args'				=> array(),
			'add_fragment'			=> '',

			'type'					=> 'list',
			'prev_next'				=> 'none',
			'mid_size'				=> 1,

			'remove_args'			=> array(),
			'ul_class'				=> '',
			'ul_class_force'		=> '',
			'li_class'				=> '',
			'li_class_force'		=> '',
			'li_class_current'		=> '',
			'a_class_force'			=> '',
			'a_class_current'		=> '',
			'span_class_force'		=> '',
			'ellipsis_class_force'	=> '',
			'current_type'			=> 'text',
			'anchor_text'			=> '%%PAGE%%',
			'anchor_title'			=> '%%PAGE%%',
			'ellipsis'				=> '...',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! is_array( $args['add_args'] ) ) {
			$args['add_args'] = array();
		}
		if ( ! is_array( $args['remove_args'] ) ) {
			$args['remove_args'] = array();
		}

		$view_unique_hash = $args['viewnumber'];

		$ul_class_array = array();
		if ( ! empty( $args['ul_class_force'] ) ) {
			$ul_class_array = array_map( 'esc_attr', explode( ' ', $args['ul_class_force'] ) );
		}
		if ( 'bootstrap' == $args['output'] ) {
			array_push( $ul_class_array, 'pagination' );
		}
		if ( ! empty( $args['ul_class'] ) ) {
			$ul_class_array = array_merge( $ul_class_array, array_map( 'esc_attr', explode( ' ', $args['ul_class'] ) ) );
		}
		$ul_class_array = array_values( $ul_class_array );

		$li_class_array = array();
		if ( ! empty( $args['li_class_force'] ) ) {
			$li_class_array = array_map( 'esc_attr', explode( ' ', $args['li_class_force'] ) );
		}
		if ( 'bootstrap' === $args['output'] ) {
			array_push( $li_class_array, 'page-item' );
			if ( 'dots' === $args['links_type'] ) {
				array_push( $ul_class_array, 'pagination-dots' );
				$args['current_type'] = 'link';
			}
		}
		if ( ! empty( $args['li_class'] ) ) {
			$li_class_array = array_merge( $li_class_array, array_map( 'esc_attr', explode( ' ', $args['li_class'] ) ) );
		}
		$li_class_array = array_values( $li_class_array );

		$a_class_array = array();
		if ( ! empty( $args['a_class_force'] ) ) {
			$a_class_array = array_map( 'esc_attr', explode( ' ', $args['a_class_force'] ) );
		}
		$a_class_array_current = $a_class_array;
		$a_class_array_current[] = $args['a_class_current'];

		$span_class_array = array();
		if ( ! empty( $args['span_class_force'] ) ) {
			$span_class_array = array_map( 'esc_attr', explode( ' ', $args['span_class_force'] ) );
		}

		$ellipsis_class_array = array();
		if ( !empty ( $args['ellipsis_class_force'] ) ) {
			$ellipsis_class_array = array_map( 'esc_attr', explode( ' ', $args['ellipsis_class_force'] ) );
		}

		// Merge additional query vars found in the original URL into 'add_args' array.
		if ( isset( $url_parts[1] ) ) {
			// Find the format argument.
			$format = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
			$format_query = isset( $format[1] ) ? $format[1] : '';
			wp_parse_str( $format_query, $format_args );

			// Find the query args of the requested URL.
			wp_parse_str( $url_parts[1], $url_query_args );

			// Remove the format argument from the array of query arguments, to avoid overwriting custom format.
			foreach ( $format_args as $format_arg => $format_arg_value ) {
				unset( $url_query_args[ $format_arg ] );
			}

			$args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
		}

		// Who knows what else people pass in $args
		$total = (int) $args['total'];
		if ( $total < 2 ) {
			return;
		}
		$current  = (int) $args['current'];
		$end_size = (int) $args['end_size']; // Out of bounds?  Make it the default.
		if ( $end_size < 1 ) {
			$end_size = 1;
		}
		$mid_size = (int) $args['mid_size'];
		if ( $mid_size < 0 ) {
			$mid_size = 2;
		}
		$add_args		= $args['add_args'];
		$remove_args	= $args['remove_args'];
		$r				= '';
		$page_links		= array();
		$dots			= false;

		$link_prefix = '';
		$link_prefix_current = '';
		$link_suffix = '';
		if ( 'list' == $args['type'] ) {
			$link_prefix = "<li class='" . implode( ' ', $li_class_array ) . "'>";
			$li_class_array_current = $li_class_array;
			if ( 'bootstrap' == $args['output'] ) {
				array_push( $li_class_array_current, 'dots' === $args['links_type'] ? 'active-dot' : 'active' );
			}
			$link_prefix_current = "<li class='" . implode( ' ', $li_class_array_current ) . " " . $args['li_class_current'] . "'>";
			$li_class_array_current_previous_next = $li_class_array;
			if ( 'bootstrap' == $args['output'] ) {
				array_push( $li_class_array_current_previous_next, 'disabled' );
			}
			$link_prefix_current_previous_next = "<li class='" . implode( ' ', $li_class_array_current_previous_next ) . " " . $args['li_class_current'] . "'>";
			$link_suffix = "</li>";
		}

		if ( 'true' == $args['first_last'] ) {
			if (
				$current
				&& 1 < $current
			) {
				$link = str_replace( '%_%', '', $args['base'] );

				if ( $add_args ) {
					$link = add_query_arg( $add_args, $link );
				}
				if ( $remove_args ) {
					$link = remove_query_arg( $remove_args, $link );
				}
				$link .= $args['add_fragment'];

				$first_page = 1;

				/**
				 * Filter the paginated links for the given pages.
				 *
				 * @since 2.1
				 *
				 * @param string $link The paginated link URL.
				 */
				$page_links[] = $link_prefix
								. '<a'
								. ' class="wpv-archive-pagination-links-first-link js-wpv-archive-pagination-links-first-link page-link"'
								. ' data-viewnumber="' . esc_attr( $view_unique_hash ) . '"'
								. ' data-page="' . esc_attr( $first_page ) . '"'
								. ' href="' . esc_url( apply_filters( 'wpv_filter_pager_nav_links_url', $link ) ) . '"'
								. '>'
								. $args['first_text']
								. '</a>'
								. $link_suffix;
			}
		}

		if ( 'none' != $args['prev_next'] ) {
			switch ( $args['prev_next'] ) {
				case 'maybe':
				case 'force':
					if (
						$current
						&& 1 < $current
					) {
						$link = str_replace( '%_%', 2 == $current ? '' : $args['format'], $args['base'] );
						$link = str_replace( '%#%', $current - 1, $link );
						if ( $add_args ) {
							$link = add_query_arg( $add_args, $link );
						}
						if ( $remove_args ) {
							$link = remove_query_arg( $remove_args, $link );
						}
						$link .= $args['add_fragment'];

						/**
						 * Filter the paginated links for the given pages.
						 *
						 * @since 2.1
						 *
						 * @param string $link The paginated link URL.
						 */
						$page_links[] = $link_prefix
							. '<a'
							. ' class="wpv-archive-pagination-links-prev-link js-wpv-archive-pagination-links-prev-link page-link"'
							. ' data-viewnumber="' . esc_attr( $view_unique_hash ) . '"'
							. ' data-page="' . esc_attr( $current - 1 ) . '"'
							. ' href="' . esc_url( apply_filters( 'wpv_filter_pager_nav_links_url', $link ) ) . '"'
							. '>'
							. $args['prev_text']
							. '</a>'
							. $link_suffix;
					} else if ( 'force' == $args['prev_next'] ) {
						$page_links[] = $link_prefix_current_previous_next
							. '<span'
							. ' class="wpv-archive-pagination-links-prev-link wpv-archive-pagination-links-prev-link-first page-link"'
							. '>'
							. $args['prev_text']
							. '</span>'
							. $link_suffix;
					}
					break;
			}
		}
		for ( $n = 1; $n <= $total; $n++ ) {
			if (
				$n == $current
				&& 'text' == $args['current_type']
			) {
				$anchor_text_n = str_replace( '%%PAGE%%', $n, $args['anchor_text'] );
				$page_links[] = $link_prefix_current
					. "<span"
					. " class='" . esc_attr( implode( ' ', $span_class_array ) ) . " page-link'"
					. ">"
					. $anchor_text_n
					. "</span>"
					. $link_suffix;
				$dots = true;
			} else {
				if (
					$args['show_all']
					|| (
						$n <= $end_size
						|| (
							$current
							&& $n >= $current - $mid_size
							&& $n <= $current + $mid_size
						)
						|| $n > $total - $end_size
					) || (
						0 < $args['step']
						&& $n % $args['step'] == 0
					)
				) {
					$link = str_replace( '%_%', 1 == $n ? '' : $args['format'], $args['base'] );
					$link = str_replace( '%#%', $n, $link );
					if ( $add_args ) {
						$link = add_query_arg( $add_args, $link );
					}
					if ( $remove_args ) {
						$link = remove_query_arg( $remove_args, $link );
					}
					$link .= $args['add_fragment'];

					$anchor_text_n	= str_replace( '%%PAGE%%', $n, $args['anchor_text'] );
					$anchor_title_n	= str_replace( '%%PAGE%%', $n, $args['anchor_title'] );

					$link_prefix_n = ( $n == $current ) ? $link_prefix_current : $link_prefix;
					$a_class_array_n = ( $n == $current ) ? $a_class_array_current : $a_class_array;

					$page_links[] = $link_prefix_n
						. "<a"
						. " class='" . implode( ' ', $a_class_array_n ) . " page-link'"
						. " data-page='" . esc_attr( $n ) . "'"
						. " title='" . esc_attr( $anchor_title_n ) . "'"
						. " data-viewnumber='" . esc_attr( $view_unique_hash ) . "'"
						. " href='" . esc_url( apply_filters( 'wpv_filter_pager_nav_links_url', $link ) ) . "'"
						. ">"
						. $anchor_text_n
						. "</a>"
						. $link_suffix;
					$dots = true;
				} elseif (
					$dots
					&& ! $args['show_all']
				) {
					$page_links[] = $link_prefix
						. '<span'
						. ' class="' . implode( ' ', $ellipsis_class_array ) . ' page-link"'
						. '>'
						. $args['ellipsis']
						. '</span>'
						. $link_suffix;
					$dots = false;
				}
			}
		}
		if ( 'none' != $args['prev_next'] ) {
			switch ( $args['prev_next'] ) {
				case 'maybe':
				case 'force':
					if (
						$current
						&& (
							$current < $total
							|| -1 == $total
						)
					) {
						$link = str_replace( '%_%', $args['format'], $args['base'] );
						$link = str_replace( '%#%', $current + 1, $link );
						if ( $add_args ) {
							$link = add_query_arg( $add_args, $link );
						}
						if ( $remove_args ) {
							$link = remove_query_arg( $remove_args, $link );
						}
						$link .= $args['add_fragment'];

						$page_links[] = $link_prefix
							. '<a'
							. ' class="wpv-archive-pagination-links-next-link js-wpv-archive-pagination-links-next-link page-link"'
							. ' data-viewnumber="' . esc_attr( $view_unique_hash ) . '"'
							. ' data-page="' . esc_attr( $current + 1 ) . '"'
							. ' href="' . esc_url( apply_filters( 'wpv_filter_pager_nav_links_url', $link ) ) . '"'
							. '>'
							. $args['next_text']
							. '</a>'
							. $link_suffix;
					} else if ( 'force' == $args['prev_next'] ) {
						$page_links[] = $link_prefix_current_previous_next
							. '<span'
							. ' class="wpv-archive-pagination-links-next-link wpv-archive-pagination-links-next-link-last page-link"'
							. '>'
							. $args['next_text']
							. '</span>'
							. $link_suffix;
					}
					break;
			}
		}
		if ( 'true' == $args['first_last'] ) {
			if (
				$current
				&& (
					$current < $total
					|| -1 == $total
				)
			) {
				$link = str_replace( '%_%', $args['format'], $args['base'] );
				$link = str_replace( '%#%', $total, $link );
				if ( $add_args ) {
					$link = add_query_arg( $add_args, $link );
				}
				if ( $remove_args ) {
					$link = remove_query_arg( $remove_args, $link );
				}
				$link .= $args['add_fragment'];

				$last_page = $total;

				$page_links[] = $link_prefix
								. '<a'
								. ' class="wpv-archive-pagination-links-last-link js-wpv-archive-pagination-links-last-link page-link"'
								. ' data-viewnumber="' . esc_attr( $view_unique_hash ) . '"'
								. ' data-page="' . esc_attr( $last_page ) . '"'
								. ' href="' . esc_url( apply_filters( 'wpv_filter_pager_nav_links_url', $link ) ) . '"'
								. '>'
								. $args['last_text']
								. '</a>'
								. $link_suffix;
			}
		}
		switch ( $args['type'] ) {
			case 'list' :
				$r .= "<ul class='" . implode( ' ', $ul_class_array ) . "'>\n\t";
				$r .= implode( "\n\t", $page_links );
				$r .= "\n</ul>\n";
				break;

			default :
				$r = implode( "\n", $page_links );
				break;
		}
		return $r;
	}

	/**
	* get_pagenum_link
	*
	* Get current page permalink structure for a given page number.
	*
	* Mocks the native WordPress function get_pagenum_link, but allowing a custom origin and base when doing AJAX.
	* Used for WordPress Archives AJAX pagination.
	*
	* @since 2.1
	*/

	function get_pagenum_link( $pagenum = 1, $escape = true ) {
		global $wp_rewrite;

		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			&& isset( $_REQUEST['action'] )
			&& $_REQUEST['action'] == 'wpv_get_archive_query_results'
		) {
			$origin	= wp_get_referer();
			$base	= '';
		} else {
			$origin	= false;
			// Use wp_load_alloptions instead of get_bloginfo() because we need raw, unfiltered data: some third parties, like WPML, can modify the blog URL
			$alloptions = wp_load_alloptions();
			$base = apply_filters( 'option_home', $alloptions['home'] );
			// Convert Home url to language specific permalink when Language URL format = Different languages in directories or A different domain per language
			$language_negotiation_type = apply_filters( 'wpml_setting', 3, 'language_negotiation_type' );
			if ( $language_negotiation_type != 3 ) {
				$base = apply_filters( 'wpml_permalink', $base );
			}
			$base = trailingslashit( $base );
		}

		$pagenum = (int) $pagenum;

		$request = remove_query_arg( 'paged', $origin );

		$home_root = parse_url(home_url());
		$home_root = ( isset($home_root['path']) ) ? $home_root['path'] : '';
		$home_root = preg_quote( $home_root, '|' );

		$request = preg_replace('|^'. $home_root . '|i', '', $request);
		$request = preg_replace('|^/+|', '', $request);

		if (
			! $wp_rewrite->using_permalinks()
			|| ! $this->is_frontend()
		) {
			if ( $pagenum > 1 ) {
				$result = add_query_arg( 'paged', $pagenum, $base . $request );
			} else {
				$result = $base . $request;
			}
		} else {
			$qs_regex = '|\?.*?$|';
			preg_match( $qs_regex, $request, $qs_match );

			if ( !empty( $qs_match[0] ) ) {
				$query_string = $qs_match[0];
				$request = preg_replace( $qs_regex, '', $request );
			} else {
				$query_string = '';
			}

			$request = preg_replace( "|$wp_rewrite->pagination_base/\d+/?$|", '', $request);
			$request = preg_replace( '|^' . preg_quote( $wp_rewrite->index, '|' ) . '|i', '', $request);
			$request = ltrim($request, '/');

			if ( $wp_rewrite->using_index_permalinks() && ( $pagenum > 1 || '' != $request ) )
				$base .= $wp_rewrite->index . '/';

			if ( $pagenum > 1 ) {
				$request = ( ( !empty( $request ) ) ? trailingslashit( $request ) : $request ) . user_trailingslashit( $wp_rewrite->pagination_base . "/" . $pagenum, 'paged' );
			}

			$result = $base . $request . $query_string;
		}

		if ( $escape )
			return esc_url( $result );
		else
			return esc_url_raw( $result );
	}

	/**
	* fix_indexed_arrays_in_nav_link_url
	*
	* Generate clean URLs for pagination.
	*
	* Avoids indexed arrays like ?foo[0]=bar in the request.
	*
	* @param string $url Te URL to fix.
	*
	* @note This does not manage $_GET values when such structure appears. Check whether it is broken, seems not.
	*
	* @since 2.1
	*/

	function fix_indexed_arrays_in_nav_link_url( $url ) {
		$url = preg_replace( '/\[(\d+)]/is', '[]', $url );
		$url = preg_replace( '/%5B(\d+)%5D/is', '%5B%5D', $url );
		return $url;
	}

	/**
	* is_frontend
	*
	* Check whether we are are in frontend or doing an AJAX pagination.
	*
	* @since 2.1
	*/
	function is_frontend() {
		if ( ! is_admin() ) {
			return true;
		} else if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			&& isset( $_REQUEST['action'] )
			&& (
				$_REQUEST['action'] == 'wpv_get_view_query_results'
				|| $_REQUEST['action'] == 'wpv_get_archive_query_results'
			)
		) {
			return true;
		} else {
			return false;
		}
	}

	function clean_permalink_url( $url ) {
		// Avoid problems with array-ed posted data
		// We must remove the numeric indexes, or the history API will add them and break further AJAX calls
		$url = preg_replace( '/\[(\d+)]/is', '[]', $url );
		$url = preg_replace( '/%5B(\d+)%5D/is', '%5B%5D', $url );
		$url = preg_replace( '/%255B(\d+)%255D/is', '%255B%255D', $url );
		return $url;
	}

}

global $WPV_Pagination_Embedded;
$WPV_Pagination_Embedded = new WPV_Pagination_Embedded();


/**
* @since 2.1	DEPRECATED
*/

function wpv_get_pagination_page_permalink( $page = 1, $view_count = false, $get_override = array() ) {
	global $WPV_Pagination_Embedded;
	return $WPV_Pagination_Embedded->get_pager_permalink( $page, $view_count, $get_override );
}

