<?php

/**
* wpv-filter-order-by-embedded.php
*
* @package Views
*
* @since unknown
*/

/**
* WPV_Sorting_Embedded
*
* @since 1.11
*/

class WPV_Sorting_Embedded {

	const PREFIX_FIELD = 'field-';
	const PREFIX_TABLE_FIELD = 'post-field-';
	const PREFIX_TABLE_TYPES_FIELD = 'types-field-';

	public $valid_values = array();

	public function __construct() {

		add_action( 'init', array( $this, 'init' ) );

		/**
		 * Valid orderby, orderby_as and orderby_second and order, order_second values.
		 * Used to validate values coming from stored settings, URL parameters, or shortcode attributes.
		 * Empty values means that free values are accepted, although limited to valid values for the WordPress API.
		 *
		 * @note Currently, only post queries accept secondary sorting.
		 *
		 * @note 'orderby' for post queries accepts free values,
		 *     and specifically 'field-', 'post-field-' or 'types-field-' prefixed values for postmeta values.
		 * @note 'orderby_second' for post queries only accept a set of valid native values, and not postmeta values.
		 * @note 'orderby' for taxonomy queries accepts free values,
		 *     and specifically 'taxonomy-field-' prefixed values for termmeta values.
		 * @note 'orderby' for user queries only accept a set of valid values,
		 *     plus 'user-field-' prefixed values for usermeta values.
		 * @note 'orderby_as'  values always get restricted to 'string', 'STRING', 'numeric', 'NUMERIC' .
		 * @note 'order'  values always get restricted to 'asc', 'ASC', 'desc', 'DESC' .
		 *
		 * @since 2.3.0
		 */

		$this->valid_values = array(
			'posts'		=> array(
				'orderby'			=> array(),
				'orderby_as'		=> array( '', 'string', 'STRING', 'numeric', 'NUMERIC' ),
				'order'				=> array( 'asc', 'ASC', 'desc', 'DESC' ),
				'orderby_second'	=> array(
											'date', 'post_date', 'post-date',
											'title', 'post_title', 'post-title', 'post_link', 'post-link',
											'id', 'post_id', 'post-id', 'ID',
											'author', 'post_author', 'post-author',
											'type', 'post_type', 'post-type',
											'name', 'post_name', 'post-name', 'post_slug', 'post-slug',
											'modified', 'menu_order', 'rand'
										),
				'order_second'		=> array( 'asc', 'ASC', 'desc', 'DESC' )
			),
			'taxonomy'	=> array(
				'orderby'			=> array(),
				'orderby_as'		=> array( '', 'string', 'STRING', 'numeric', 'NUMERIC' ),
				'order'				=> array( 'asc', 'ASC', 'desc', 'DESC' ),
				'orderby_second'	=> array(),
				'order_second'		=> array( 'asc', 'ASC', 'desc', 'DESC' )
			),
			'users'		=> array(
				'orderby'			=> array(
											'ID', 'display_name',
											'name', 'user_name',
											'email', 'user_email',
											'login', 'user_login',
											'url', 'user_url',
											'registered', 'user_registered',
											'nicename', 'user_nicename',
											'include', 'post_count'
										),
				'orderby_as'		=> array( '', 'string', 'STRING', 'numeric', 'NUMERIC' ),
				'order'				=> array( 'asc', 'ASC', 'desc', 'DESC' ),
				'orderby_second'	=> array(),
				'order_second'		=> array( 'asc', 'ASC', 'desc', 'DESC' )
			)
		);

		$this->register_shortcodes();

    }

	function init() {

		// Legacy
		add_action( 'wpv_action_wpv_pagination_map_legacy_order',	array( $this, 'map_legacy_order' ) );

		add_filter( 'wpv_view_settings',							array( $this, 'sorting_defaults' ) );
		add_filter( 'wpv_filter_wpv_get_sorting_defaults',			array( $this, 'sorting_defaults' ) );
		add_filter( 'wpv_filter_wpv_get_sorting_settings',			array( $this, 'get_sorting_settings' ), 10, 2 );

		add_filter( 'wpv_filter_query',								array( $this, 'set_post_view_sorting' ), 100, 3 );
		add_filter( 'wpv_filter_wpv_apply_post_view_sorting',		array( $this, 'set_post_view_sorting' ), 10, 3 );

		add_filter( 'wpv_filter_taxonomy_query',					array( $this, 'set_taxonomy_view_sorting' ), 10, 3 );
		add_filter( 'wpv_filter_wpv_apply_taxonomy_view_sorting',	array( $this, 'set_taxonomy_view_sorting' ), 10, 3 );

		add_filter( 'wpv_filter_user_query',						array( $this, 'set_user_view_sorting' ), 40, 3 );
		add_filter( 'wpv_filter_wpv_apply_user_view_sorting',		array( $this, 'set_user_view_sorting' ), 10, 3 );

		//add_action( 'toolset_action_toolset_editor_toolbar_add_buttons', array( $this, 'toolset_editor_toolbar_add_buttons' ), 1, 2 );

		add_filter( 'wpv_filter_wpv_get_sorting_valid_values',		array( $this, 'get_valid_values' ) );

		add_filter( 'wpv_filter_wpv_get_styles_for_list_controls',	array( $this, 'set_default_styles_for_list_controls' ), 0 );
		add_action( 'wpv_action_api_add_sorting_orderby_selector',	array( $this, 'add_sorting_orderby_selector' ) );
		add_action( 'wpv_action_api_add_sorting_order_selector',	array( $this, 'add_sorting_order_selector' ) );

	}

	/**
	 * Register the sorting-related shortcodes.
	 *
	 * This happens on class instantiation so we are not too late for third parties.
	 *
	 * @since unknown
	 */

	function register_shortcodes() {

		add_shortcode( 'wpv-sort-orderby',				array( $this, 'wpv_shortcode_wpv_sort_orderby' ) );
		add_shortcode( 'wpv-sort-order',				array( $this, 'wpv_shortcode_wpv_sort_order' ) );

		//add_shortcode( 'wpv-sort-link',					array( $this, 'wpv_shortcode_wpv_sort_link' ) );

		//add_shortcode( 'wpv-orderby-second',	array( $this, 'wpv_shortcode_wpv_sort_orderby' ) );
		//add_shortcode( 'wpv-order-second',		array( $this, 'wpv_shortcode_wpv_sort_order' ) );
	}

	/**
	 * Get the list of restrictions that we impose over sorting values.
	 *
	 * In addition to the lis of valid values, we accept meta fields
	 * with different prefixes depending on the queried objects.
	 *
	 * @note Empty definitions mean wild input allowed.
	 *
	 * @since 2.3.0
	 */

	function get_valid_values( $valid_values = array() ) {
		return $this->valid_values;
	}

	/**
	 * Port the old, legacy table sorting URL parameters to the new, unique, expected arguments.
	 *
	 * @note This happens o demand, using the wpv_action_wpv_pagination_map_legacy_order action
	 *     when we know that a View has been posted.
	 *
	 * @since 2.0.0
	 */

	function map_legacy_order() {
		if ( isset( $_GET['wpv_column_sort_id'] ) ) {
			$_GET['wpv_sort_orderby'] = $_GET['wpv_column_sort_id'];
			unset( $_GET['wpv_column_sort_id'] );
		}
		if ( isset( $_GET['wpv_column_sort_dir'] ) ) {
			$_GET['wpv_sort_order'] = $_GET['wpv_column_sort_dir'];
			unset( $_GET['wpv_column_sort_dir'] );
		}
	}

	/**
	 * Set the sorting defaults that every View must have.
	 *
	 * @since unknown
	 */

	function sorting_defaults( $view_settings ) {

		$sorting_defaults = array(
			'orderby'				=> 'post_date',
			'orderby_as'			=> '',
			'order'					=> 'DESC',
			'orderby_second'			=> '',
			'order_second'			=> 'DESC',

			'taxonomy_orderby'		=> 'name',
			'taxonomy_orderby_as'	=> '',
			'taxonomy_order'		=> 'DESC',

			'users_orderby'			=> 'user_login',
			'users_orderby_as'		=> '',
			'users_order'			=> 'DESC'
		);

		foreach ( $sorting_defaults as $key => $value ) {
			if ( ! isset( $view_settings[ $key ] ) ) {
				$view_settings[ $key ] = $value ;
			}
		}

		// Migrate legacy WooCommerce Blocks sorting options.
		$migrate_from_to = array(
			self::PREFIX_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice::LEGACY_FIELD_SLUG => 'price',
			self::PREFIX_TABLE_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice::LEGACY_FIELD_SLUG => 'price',
			self::PREFIX_TABLE_TYPES_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice::LEGACY_FIELD_SLUG => 'price',
			self::PREFIX_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductStock::LEGACY_FIELD_SLUG => self::PREFIX_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductStock::FIELD_SLUG,
			self::PREFIX_TABLE_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductStock::LEGACY_FIELD_SLUG => self::PREFIX_TABLE_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductStock::FIELD_SLUG,
			self::PREFIX_TABLE_TYPES_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductStock::LEGACY_FIELD_SLUG => self::PREFIX_TABLE_TYPES_FIELD . \OTGS\Toolset\Views\Controller\Filters\Post\ProductStock::FIELD_SLUG,
		);
		foreach ( $migrate_from_to as $origin => $target ) {
			if ( $origin === $view_settings['orderby'] ) {
				$view_settings['orderby'] = $target;
			}
		}

		return $view_settings;

	}

	/**
	 * Add the sorting button to Search and Pagination, and Loop editors toolbars.
	 *
	 * Currently returning early as terms and users can not add form items to submit changes,
	 * hence the sorting controls are useless there, so we needed to add them manually
	 * only in the Filter editor toolbar, for Views listing posts and WPAs.
	 *
	 * @since 2.3.0
	 */

	function toolset_editor_toolbar_add_buttons ( $editor_id, $toolset_plugin ) {

		return;

		if (
			'views' == $toolset_plugin
			&& in_array( $editor_id, array( 'wpv_filter_meta_html_content', 'wpv_layout_meta_html_content' ) )
		) {
			?>
			<li class="js-wpv-editor-sorting-button-wrapper">
				<button class="button-secondary js-code-editor-toolbar-button js-wpv-sorting-dialog" data-content="<?php echo esc_attr( $editor_id ); ?>">
					<i class="fa fa-sort"></i>
					<span class="button-label"><?php _e('Sorting controls','wpv-views'); ?></span>
				</button>
			</li>
			<?php
		}
	}

	/**
	 * Apply the sorting settings to a View that lists posts.
	 *
	 * @param array	$query			The View query arguments.
	 * @param array $view_settings	The View settings.
	 *
	 * @return $query
	 *
	 * @note for WP < 4.1, we need to apply a workaround when sorting by a custom field if we also have
	 *     a query filter by several custom fields and an OR relation.
	 *
	 * @since unknown
	 */

	function set_post_view_sorting( $query, $view_settings, $view_id ) {

		global $wp_version;

		// Get sorting settings to apply (View settings, shortcode attrbutes, URL parameters)
		$sorting_settings = apply_filters( 'wpv_filter_wpv_get_sorting_settings', array(), $view_id );

		$orderby		= $sorting_settings['orderby'];
		$order			= $sorting_settings['order'];
		$orderby_as		= $sorting_settings['orderby_as'];
		$orderby_second	= $sorting_settings['orderby_second'];
		$order_second	= $sorting_settings['order_second'];

		// If generating a query for a View block preview and the order is Random, ignore the setting.
		// And force to orderby title to prevent infinite Dynamic Source invalidations.
		if ( defined( 'WPV_BLOCK_PREVIEW_RENDER' ) && 'rand' === $orderby ) {
			$orderby = 'title';
		}

		// Adjust values for meta field sorting
		// @todo we might also need a str_replace( '-', '_', $orderby_second ); so post-type becomes post_type, for example
		// Or we need to adjust how table sorting is added: it uses slashs instead of underscores
		$query_meta_key = '';
		if ( strpos( $orderby, 'field-' ) === 0 ) {
			// Natural Views sorting by custom field
			$query_meta_key = substr( $orderby, 6 );
		} else if ( strpos( $orderby, 'post-field-' ) === 0 ) {
			// Table sorting for custom field
			$query_meta_key = substr( $orderby, 11 );
		} else if ( strpos( $orderby, 'types-field-' ) === 0 ) {
			// Table sorting for Types custom field
			$query_meta_key = strtolower( substr( $orderby, 12 ) );
		} else {
			$orderby = str_replace( '-', '_', $orderby );
		}

		if ( ! empty( $query_meta_key ) ) {
			$is_types_field_data = wpv_is_types_custom_field( $query_meta_key );
			// If this is a Types postmeta field, do not accept field types of 'checkboxes' or 'skype',
			// and default to sorting by 'post_date' in those cases. Also, force numeric sorting for
			// Types 'numeric' or 'date' postmeta fields.
			// For non-Types postmeta fields, accept as is.
			if ( $is_types_field_data ) {
				if (
					isset( $is_types_field_data['meta_key'] )
					&& isset( $is_types_field_data['type'] )
					&& ! in_array( $is_types_field_data['type'], array( 'checkboxes', 'skype' ) )
				) {

					$query['meta_key'] = $is_types_field_data['meta_key'];
					$orderby = 'meta_value';

					if ( in_array( $is_types_field_data['type'], array( 'numeric', 'date' ) ) ) {
						$orderby = 'meta_value_num';
					}

				} else {
					$orderby = 'post_date';
				}
			} else {
				$query['meta_key'] = $query_meta_key;
				$orderby = 'meta_value';
			}

			// Adjust sorting as numeric when needed.
			if (
				$orderby == 'meta_value'
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
			}

		}

		// Normalize orderby and orderby_second options
		$orderby		= WPV_Sorting_Embedded::normalize_post_orderby_value( $orderby );
		$orderby_second	= WPV_Sorting_Embedded::normalize_post_orderby_value( $orderby_second );

		// Set sorting sttings
		$query['orderby']	= $orderby;
		$query['order']		= $order;
		$query['wpv_orderby'] = $orderby;
		$query['wpv_order'] = $order;

		// See if filtering by custom fields and sorting by custom field too
		// as WP < 4.1 has a bug here
		if (
			version_compare( $wp_version, '4.1', '<' )
			&& isset( $query['meta_key'] )
			&& isset( $query['meta_query'] )
			&& isset( $query['meta_query']['relation'] )
			&& $query['meta_query']['relation'] == 'OR'
		) {
			// We only need to do something if the relation is OR
			// When the relation is AND it does not matter if we sort by one of the filtering fields, because the filter will add an existence clause anyway
			// When the relation is OR, the natural query will generate an OR clause on the sorting field existence:
			// - if it is one of the filtering fields, it will make its clause useless because just existence will make it pass
			// - if it is not one of the filtering fields it will add an OR clause on this field existence that might pass for results that do not match any of the other requirements
			// See also: https://core.trac.wordpress.org/ticket/25538
			// Since WordPress 4.1 this is indeed not needed, thanks to nested meta_query entries
			// Note that this might contain a bug, since we are removing the meta_query but keeping the sorting by meta_value/meta_value_num in some cases

			$refinedquery = $query;
			unset( $refinedquery['orderby'] );
			unset( $refinedquery['meta_key'] );
			$refinedquery['posts_per_page'] = -1; // remove the limit in the main query to get all the relevant IDs
			$refinedquery['fields'] = 'ids';
			// first query only for filtering
			$filtered_query = new WP_Query( $refinedquery );
			$filtered_ids = array();
			if (
				is_array( $filtered_query->posts )
				&& !empty( $filtered_query->posts )
			) {
				$filtered_ids = $filtered_query->posts;
			}
			// remove the fields filter from the original query and add the filtered IDs
			unset( $query['meta_query'] );
			// we can replace the $query['post__in'] argument because it was applied on the auxiliar query before
			if ( count( $filtered_ids ) ) {
				$query['post__in'] = $filtered_ids;
			} else {
				$query['post__in'] = array('0');
			}

		}

		// Allow for mltiple sorting conditions for WP > 4.0
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
			$query['orderby']	= $orderby_array;
		}

		return $query;
	}

	/**
	 * Apply the sorting settings to a View that lists taxonomy terms.
	 *
	 * @param array		$taxonomy_query	The View query arguments.
	 * @param array 	$view_settings	The View settings.
	 * @param integer	$view_id		The View ID.
	 *
	 * @return $taxonomy_query
	 *
	 * @since unknown
	 */

	function set_taxonomy_view_sorting( $taxonomy_query, $view_settings, $view_id ) {

		// Get sorting settings to apply (View settings, shortcode attrbutes, URL parameters)
		$sorting_settings = apply_filters( 'wpv_filter_wpv_get_sorting_settings', array(), $view_id );

		$orderby		= $sorting_settings['orderby'];
		$order			= $sorting_settings['order'];
		$orderby_as		= $sorting_settings['orderby_as'];

		// Adjust values for meta field sorting
		if ( strpos( $orderby, 'taxonomy-field-' ) === 0 ) {
			global $wp_version;
			if ( version_compare( $wp_version, '4.5', '<' ) ) {
				$orderby = 'name';
			} else {

				$taxonomy_query_meta_key = substr( $orderby, 15 );
				$is_types_field_data = wpv_is_types_custom_field( $taxonomy_query_meta_key, 'tf' );

				// If this is a Types termmeta field, do not accept field types of 'checkboxes' or 'skype',
				// and default to sorting by 'name' in those cases. Also, force numeric sorting for
				// Types 'numeric' or 'date' termmeta fields.
				// For non-Types termmeta fields, accept as is.
				if ( $is_types_field_data ) {
					if (
						isset( $is_types_field_data['meta_key'] )
						&& isset( $is_types_field_data['type'] )
						&& ! in_array( $is_types_field_data['type'], array( 'checkboxes', 'skype' ) )
					) {

						$taxonomy_query['meta_key'] = $is_types_field_data['meta_key'];
						$orderby = 'meta_value';

						if ( in_array( $is_types_field_data['type'], array( 'numeric', 'date' ) ) ) {
							$orderby = 'meta_value_num';
						}

					} else {
						$orderby = 'name';
					}
				} else {
					$taxonomy_query['meta_key'] = $taxonomy_query_meta_key;
					$orderby = 'meta_value';
				}

				// Adjust sorting as numeric when needed.
				if (
					$orderby == 'meta_value'
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
				}

			}
		}

		// Normalize orderby options
		$orderby = WPV_Sorting_Embedded::normalize_taxonomy_orderby_value( $orderby );

		// Set sorting sttings
		$taxonomy_query['orderby']	= $orderby;
		$taxonomy_query['order']	= $order;

		return $taxonomy_query;
	}

	/**
	 * Apply the sorting settings to a View that lists users.
	 *
	 * @param array	$user_query		The View query arguments.
	 * @param array $view_settings	The View settings.
	 *
	 * @return $user_query
	 *
	 * @since unknown
	 */

	function set_user_view_sorting( $user_query, $view_settings, $view_id ) {

		// Get sorting settings to apply (View settings, shortcode attrbutes, URL parameters)
		$sorting_settings = apply_filters( 'wpv_filter_wpv_get_sorting_settings', array(), $view_id );

		$orderby		= $sorting_settings['orderby'];
		$order			= $sorting_settings['order'];
		$orderby_as		= $sorting_settings['orderby_as'];

		// Adjust values for meta field sorting
		if ( strpos( $orderby, 'user-field-' ) === 0 ) {

			$user_query_meta_key = substr( $orderby, 11 );
			$is_types_field_data = wpv_is_types_custom_field( $user_query_meta_key, 'uf' );

			// If this is a Types usermeta field, do not accept field types of 'checkboxes' or 'skype',
			// and default to sorting by 'user_login' in those cases. Also, force numeric sorting for
			// Types 'numeric' or 'date' usermeta fields.
			// For non-Types usermeta fields, accept as is.
			if ( $is_types_field_data ) {
				if (
					isset( $is_types_field_data['meta_key'] )
					&& isset( $is_types_field_data['type'] )
					&& ! in_array( $is_types_field_data['type'], array( 'checkboxes', 'skype' ) )
				) {

					$user_query['meta_key'] = $is_types_field_data['meta_key'];
					$orderby = 'meta_value';

					if ( in_array( $is_types_field_data['type'], array( 'numeric', 'date' ) ) ) {
						$orderby = 'meta_value_num';
					}

				} else {
					$orderby = 'user_login';
				}
			} else {
				$user_query['meta_key'] = $user_query_meta_key;
				$orderby = 'meta_value';
			}

			// Adjust sorting as numeric when needed.
			if (
				$orderby == 'meta_value'
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
			}

		}

		$orderby = WPV_Sorting_Embedded::normalize_user_orderby_value( $orderby );

		// Set sorting sttings
		$user_query['orderby'] = $orderby;
		$user_query['order'] = $order;

		return $user_query;
	}

	/**
	 * Add CSS valus for the default list frontend controls styles: 'default', 'grey' and 'blue'.
	 *
	 * @since 2.3.0
	 */

	function set_default_styles_for_list_controls( $style_options = array() ) {

		$style_options['default'] = array(
			'label'				=> __( 'Default', 'wpv-views' ),
			'border-color'		=> '#cdcdcd',
			'color'				=> '#444',
			'color-current'		=> '#000',
			'color-hover'		=> '#000',
			'background-color'			=> '#fff',
			'background-color-current'	=> '#eee',
			'background-color-hover'	=> '#eee'
		);
		$style_options['grey'] = array(
			'label'				=> __( 'Grey', 'wpv-views' ),
			'border-color'		=> '#cdcdcd',
			'color'				=> '#444',
			'color-current'		=> '#000',
			'color-hover'		=> '#000',
			'background-color'			=> '#eeeeee',
			'background-color-current'	=> '#e5e5e5',
			'background-color-hover'	=> '#e5e5e5'
		);
		$style_options['blue'] = array(
			'label'				=> __( 'Blue', 'wpv-views' ),
			'border-color'		=> '#0099cc',
			'color'				=> '#444',
			'color-current'		=> '#000',
			'color-hover'		=> '#000',
			'background-color'			=> '#cbddeb',
			'background-color-current'	=> '#95bedd',
			'background-color-hover'	=> '#95bedd'
		);

		return $style_options;

	}

	/**
	 * Generate the frontend list control for the orderby setting, according to some arguments.
	 *
	 * @param array $sorting_args
	 *     array	'options'	List of options to generate.
	 *         string	'label'	Label of the option.
	 *         string	'type'	Order by this option as a 'numeric', 'string' or ''.
	 *     string	'style'		Optional. One of the registered frontend sorting control list styles. Defaults to 'default'.
	 *
	 * @since 2.3.0
	 */

	function get_sorting_orderby_selector( $sorting_args ) {
		$sorting_args_options = isset( $sorting_args['options'] )
			? $sorting_args['options']
			: array();

		if ( empty( $sorting_args_options ) ) {
			return;
		}

		$view_settings		= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$view_hash			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$sorting_settings	= apply_filters( 'wpv_filter_wpv_get_sorting_settings', array() );

		$current_style = isset( $sorting_args['style'] )
			? $sorting_args['style']
			: 'default';

		$force_apply = toolset_getarr( $sorting_args, 'force_apply', 'false', array( 'false', 'reload', 'ajax' ) );

		$current_orderby = $sorting_settings['orderby'];
		$current_orderby_as = $sorting_settings['orderby_as'];

		if ( ! isset( $sorting_args_options[ $current_orderby ] ) ) {
			$sorting_args_options[ $current_orderby ] = array(
				'label'	=> $current_orderby,
				'type'	=> $current_orderby_as
			);
		}

		$selector = '';
		$list_width = 0;

		$class_attributes = 'wpv-sort-list-anchor js-wpv-sort-list-orderby';
		if ( in_array( $force_apply, array( 'reload', 'ajax' ) ) ) {
			$class_attributes .= ' js-wpv-sort-control-force-apply-' . $force_apply;
		}

		$selector .= '<span class="wpv-sort-list-dropdown wpv-sort-list-orderby-dropdown wpv-sort-list-dropdown-style-'. esc_attr( $current_style ) . ' js-wpv-sort-list-dropdown js-wpv-sort-list-orderby-dropdown" style="width:%%LISTWIDTH%%;" data-viewnumber="' . esc_attr( $view_hash ) . '">';
			$selector .= '<span class="wpv-sort-list js-wpv-sort-list">';
				$selector .= '<span class="wpv-sort-list-item wpv-sort-list-orderby-item wpv-sort-list-current js-wpv-sort-list-item" style="width:%%LISTWIDTH%%;">';
					$selector .= '<a'
						. ' href="#"'
						. ' class="' . esc_attr( $class_attributes ) . '"'
						. ' data-orderby="' . esc_attr( $current_orderby ) . '"'
						. ' data-orderbyas="' . esc_attr( $sorting_args_options[ $current_orderby ]['type'] ) . '"'
						. ' data-forceorder="' . esc_attr( $sorting_args_options[ $current_orderby ]['order'] ) . '"'
						. ' data-viewnumber="' . esc_attr( $view_hash ) . '">';
						$selector .= '<span>'
							. esc_html( $sorting_args_options[ $current_orderby ]['label'] )
							. '</span>';
					$selector .= '</a>';
				$selector .= '</span>';
				$current_item_width = strlen( $sorting_args_options[ $current_orderby ]['label'] );
				$list_width = max( $list_width, $current_item_width );
				foreach ( $sorting_args_options as $option_candidate => $option_data ) {
					if ( $option_candidate != $current_orderby ) {
						$selector .= '<span class="wpv-sort-list-item wpv-sort-list-orderby-item js-wpv-sort-list-item" style="width:%%LISTWIDTH%%;">';
							$selector .= '<a'
								. ' href="#"'
								. ' class="' . esc_attr( $class_attributes ) . '"'
								. ' data-orderby="' . esc_attr( $option_candidate ) . '"'
								. ' data-orderbyas="' . esc_attr( $option_data['type'] ) . '"'
								. ' data-forceorder="' . esc_attr( $option_data['order'] ) . '"'
								. ' data-viewnumber="' . esc_attr( $view_hash ) . '">';
								$selector .= '<span>'
									. esc_html( $option_data['label'] )
									. '</span>';
							$selector .= '</a>';
						$selector .= '</span>';
						$current_item_width = strlen( $option_data['label'] );
						$list_width = max( $list_width, $current_item_width );
					}
				}
			$selector .= '</span>';
		$selector .= '</span>';

		$selector = str_replace( '%%LISTWIDTH%%', ( ( $list_width * 90 ) / 100 ) . 'em', $selector );

		return $selector;
	}

	/**
	 * Print the frontend list control for the orderby setting, according to some arguments.
	 *
	 * @param array $sorting_args
	 *     array	'options'	Optional. List of options to render. Defaults to adding options for sorting by post date, title and ID.
	 *         string	'label'	Label of the option.
	 *         string	'type'	Order by this option as a 'numeric', 'string' or ''.
	 *     string	'style'		Optional. One of the registered frontend sorting control list styles. Defaults to 'default'.
	 *
	 * @since 2.3.0
	 */

	function add_sorting_orderby_selector( $sorting_args ) {

		$sorting_args_options = isset( $sorting_args['options'] )
			? $sorting_args['options']
			: array();

		if ( empty( $sorting_args_options ) ) {
			$sorting_args['options'] = array(
				'date'	=> array(
					'label'	=> __( 'Post date', 'wpv-views' ),
					'type'	=> '',
					'order'	=> ''
				),
				'title'	=> array(
					'label'	=> __( 'Post title', 'wpv-views' ),
					'type'	=> '',
					'order'	=> ''
				),
				'ID'	=> array(
					'label'	=> __( 'Post ID', 'wpv-views' ),
					'type'	=> '',
					'order'	=> ''
				)
			);
		}

		$sorting_selector = $this->get_sorting_orderby_selector( $sorting_args );

		echo $sorting_selector;

	}

	/**
	 * Generate the frontend list control for the order setting, according to some arguments.
	 *
	 * @param array $sorting_args
	 *     array	'options'	List of options to generate.
	 *         string	'label'	Label of the option.
	 *     string	'style'		Optional. One of the registered frontend sorting control list styles. Defaults to 'default'.
	 *
	 * @since 2.3.0
	 */

	function get_sorting_order_selector( $sorting_args ) {
		$sorting_args_options = isset( $sorting_args['options'] )
			? $sorting_args['options']
			: array();

		if ( empty( $sorting_args_options ) ) {
			return;
		}

		$view_settings		= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$view_hash			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$sorting_settings	= apply_filters( 'wpv_filter_wpv_get_sorting_settings', array() );

		$force_apply = toolset_getarr( $sorting_args, 'force_apply', 'false', array( 'false', 'reload', 'ajax' ) );

		$current_style = isset( $sorting_args['style'] )
			? $sorting_args['style']
			: 'default';

		$current_order = strtolower( $sorting_settings['order'] );

		if ( ! isset( $sorting_args_options[ $current_order ] ) ) {
			$sorting_args_options[ $current_order ] = array(
				'label'	=> $current_order
			);
		}

		$sorting_args['extra']['labels'] = isset( $sorting_args['extra']['labels'] )
			? $sorting_args['extra']['labels']
			: '{}';

		$selector = '';
		$list_width = 0;

		$class_attributes = 'wpv-sort-list-anchor js-wpv-sort-list-order';
		if ( in_array( $force_apply, array( 'reload', 'ajax' ) ) ) {
			$class_attributes .= ' js-wpv-sort-control-force-apply-' . $force_apply;
		}

		$selector .= '<span'
			. ' class="wpv-sort-list-dropdown wpv-sort-list-order-dropdown wpv-sort-list-dropdown-style-'. esc_attr( $current_style ) . ' js-wpv-sort-list-dropdown js-wpv-sort-list-order-dropdown"'
			. ' style="width:%%LISTWIDTH%%;"'
			. ' data-viewnumber="' . esc_attr( $view_hash ) . '"'
			. ' data-labels="' . $sorting_args['extra']['labels'] . '"'
			. '>';
			$selector .= '<span class="wpv-sort-list js-wpv-sort-list">';
				$selector .= '<span class="wpv-sort-list-item wpv-sort-list-order-item wpv-sort-list-current js-wpv-sort-list-item" style="width:%%LISTWIDTH%%;">';
					$selector .= '<a'
						. ' href="#"'
						. ' class="' . esc_attr( $class_attributes ) . '"'
						. ' data-order="' . esc_attr( $current_order ) . '"'
						. ' data-viewnumber="' . esc_attr( $view_hash ) . '">';
						$selector .= '<span>'
							. esc_html( $sorting_args_options[ $current_order ]['label'] )
							. '</span>';
					$selector .= '</a>';
				$selector .= '</span>';
				$current_item_width = strlen( $sorting_args_options[ $current_order ]['label'] );
				$list_width = max( $list_width, $current_item_width );
				foreach ( $sorting_args_options as $option_candidate => $option_data ) {
					if ( $option_candidate != $current_order ) {
						$selector .= '<span class="wpv-sort-list-item wpv-sort-list-order-item js-wpv-sort-list-item" style="width:%%LISTWIDTH%%;">';
							$selector .= '<a'
								. ' href="#"'
								. ' class="' . esc_attr( $class_attributes ) . '"'
								. ' data-order="' . esc_attr( $option_candidate ) . '"'
								. ' data-viewnumber="' . esc_attr( $view_hash ) . '">';
								$selector .= '<span>'
									. esc_html( $option_data['label'] )
									. '</span>';
							$selector .= '</a>';
						$selector .= '</span>';
						$current_item_width = strlen( $option_data['label'] );
						$list_width = max( $list_width, $current_item_width );
					}
				}
			$selector .= '</span>';
		$selector .= '</span>';

		$list_width = ( $list_width < 10 ) ? $list_width + 1 : $list_width;

		$selector = str_replace( '%%LISTWIDTH%%', $list_width . 'em', $selector );

		return $selector;
	}

	/**
	 * Print the frontend list control for the order setting, according to some arguments.
	 *
	 * @param array $sorting_args
	 *     array	'options'	Optional. List of options to render. Defaults to adding options to sort in ascending and descending mode.
	 *         string	'label'	Label of the option.
	 *     string	'style'		Optional. One of the registered frontend sorting control list styles. Defaults to 'default'.
	 *
	 * @since 2.3.0
	 */

	function add_sorting_order_selector( $sorting_args ) {

		$sorting_args_options = isset( $sorting_args['options'] )
			? $sorting_args['options']
			: array();

		if ( empty( $sorting_args_options ) ) {
			$sorting_args['options'] = array(
				'asc'	=> array(
					'label'	=> __( 'Ascending', 'wpv-views' ),
				),
				'desc'	=> array(
					'label'	=> __( 'Descending', 'wpv-views' ),
				)
			);
		}

		$sorting_selector = $this->get_sorting_order_selector( $sorting_args );
		echo $sorting_selector;

	}

	/**
	 * Shortcode for frontend orderby sorting.
	 *
	 * @param $atts
	 *     $type 			String. Type of frontend control. 'select'|'radio'|'list'. Defaults to 'select'.
	 *     $force_current	String. Whether the current orderby setting should be forced in. 'true'|'false'. Defaults to 'true'.
	 *     $options			String. Comma separated list of options to include, Defaults to 'asc,desc'.
	 *     $label_for_***	String. Label for the '***' option. Defaults to the option value.
	 *
	 * @since 2.3.0
	 */

	function wpv_shortcode_wpv_sort_orderby( $atts ) {

		$atts = wp_parse_args(
			$atts,
			array(
				'type' => 'select',
				'force_current' => 'true',
				'options' => '',
				'list_style' => 'default',
				'force_apply' => 'false',
				'select_style' => '',
				'radio_label_style' => '',
				'radio_style' => '',
			)
		);

		$return = '';

		$view_id			= apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_settings		= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$view_hash			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$view_name			= get_post_field( 'post_name', $view_id );
		$sorting_settings	= apply_filters( 'wpv_filter_wpv_get_sorting_settings', array() );

		$current_orderby	= $sorting_settings['orderby'];
		$current_order		= strtolower( $sorting_settings['order'] );

		$atts['force_apply'] = toolset_getarr( $atts, 'force_apply', 'false', array( 'false', 'reload', 'ajax' ) );

		// Note that $current_orderby can come from a shortcode 'orderby' attribute, and that means that
		// the value will not be normalized hence can be repeated: we need to reverse normalize it.
		// Avaialable attribute values are that need to be prefixed with 'post_' are:
		// date, author, title
		switch ( $current_orderby ) {
			case 'date':
			case 'author':
			case 'title':
				$current_orderby = 'post_' . $current_orderby;
				break;
		}

		$orderby_options = explode( ',', $atts['options'] );
		$orderby_options = array_map( 'trim', $orderby_options );
		if ( $atts['force_current'] == 'true' ) {
			$orderby_options = array_merge( $orderby_options, array( $current_orderby ) );
		}
		$orderby_options = array_unique( $orderby_options );

		if ( count( $orderby_options ) == 0 ) {
			return;
		}

		$orderby_as_numeric = array();
		if ( isset( $atts['orderby_as_numeric_for'] ) ) {
			$orderby_as_numeric = explode( ',', $atts['orderby_as_numeric_for'] );
			$orderby_as_numeric = array_map( 'trim', $orderby_as_numeric );
		}

		$orderby_ascending = array();
		if ( isset( $atts['orderby_ascending_for'] ) ) {
			$orderby_ascending = explode( ',', $atts['orderby_ascending_for'] );
			$orderby_ascending = array_map( 'trim', $orderby_ascending );
		}

		$orderby_descending = array();
		if ( isset( $atts['orderby_descending_for'] ) ) {
			$orderby_descending = explode( ',', $atts['orderby_descending_for'] );
			$orderby_descending = array_map( 'trim', $orderby_descending );
		}

		switch ( $atts['type'] ) {
			case 'select':
				$class_attributes = 'wpv-sort-control-select wpv-sort-control-orderby js-wpv-sort-control-orderby';
				if ( in_array( $atts['force_apply'], array( 'reload', 'ajax' ) ) ) {
					$class_attributes .= ' js-wpv-sort-control-force-apply-' . $atts['force_apply'];
				}
				$return .= '<select'
				. ' name="wpv_sort_orderby"'
				. ' class="' . esc_attr( $class_attributes ) . '"'
				. ( ( '' !== $atts['select_style'] ) ? ' style="' . esc_attr( $atts['select_style'] ) . '"' : '' )
				. ' data-viewnumber="' . esc_attr( $view_hash ) . '"'
				. ' autocomplete="off"'
				. '>';
				foreach ( $orderby_options as $orderby_candidate ) {
					$orderby_candidate_label = $this->craft_sorting_setting_label( $orderby_candidate, $atts, $view_name );
					$orderby_candidate_orderbyas = in_array( $orderby_candidate, $orderby_as_numeric ) ? 'numeric' : 'string';

					$orderby_candidate_order = '';
					$orderby_candidate_order = in_array( $orderby_candidate, $orderby_ascending ) ? 'asc' : $orderby_candidate_order;
					$orderby_candidate_order = in_array( $orderby_candidate, $orderby_descending ) ? 'desc' : $orderby_candidate_order;
					$orderby_candidate_order = empty( $orderby_candidate_order ) ? $current_order : $orderby_candidate_order;

					$return .= '<option'
						. ' value="' . esc_attr( $orderby_candidate ) . '"'
						. ' ' . selected( $orderby_candidate, $current_orderby, false )
						. ' data-orderbyas="' . $orderby_candidate_orderbyas . '"'
						. ' data-forceorder="' . $orderby_candidate_order . '"'
						. '>';
					$return .= $orderby_candidate_label;
					$return .= '</option>';
				}
				$return .= '</select>';
				break;
			case 'radio':
				foreach ( $orderby_options as $orderby_candidate ) {
					$orderby_candidate_label = $this->craft_sorting_setting_label( $orderby_candidate, $atts, $view_name );
					$orderby_candidate_orderbyas = in_array( $orderby_candidate, $orderby_as_numeric ) ? 'numeric' : '';

					$orderby_candidate_order = '';
					$orderby_candidate_order = in_array( $orderby_candidate, $orderby_ascending ) ? 'asc' : $orderby_candidate_order;
					$orderby_candidate_order = in_array( $orderby_candidate, $orderby_descending ) ? 'desc' : $orderby_candidate_order;
					$orderby_candidate_order = empty( $orderby_candidate_order ) ? $current_order : $orderby_candidate_order;

					$class_attributes = 'wpv-sort-control-radio wpv-sort-control-orderby js-wpv-sort-control-orderby';
					if ( in_array( $atts['force_apply'], array( 'reload', 'ajax' ) ) ) {
						$class_attributes .= ' js-wpv-sort-control-force-apply-' . $atts['force_apply'];
					}

					$label_start = '<label'
					. ' class="wpv-sort-control-radio-label wpv-sort-control-orderby-radio-label"'
					. ( ( '' !== $atts['radio_label_style'] ) ? ' style="' . esc_attr( $atts['radio_label_style'] ) . '"' : '' )
					. '>';
					$radio_input = '<input'
						. ' type="radio"'
						. ' name="wpv_sort_orderby"'
						. ' class="' . esc_attr( $class_attributes ) . '"'
						. ( ( '' !== $atts['radio_style'] ) ? ' style="' . esc_attr( $atts['radio_style'] ) . '"' : '' )
						. ' value="' . esc_attr( $orderby_candidate ) . '"'
						. ' ' . checked( $current_orderby, $orderby_candidate, false )
						. ' data-viewnumber="' . esc_attr( $view_hash ) . '"'
						. ' data-orderbyas="' . $orderby_candidate_orderbyas . '"'
						. ' data-forceorder="' . $orderby_candidate_order . '"'
						. ' autocomplete="off"'
						. ' />';
					$label_end = '</label>';

					$radio_label_position = toolset_getarr( $atts, 'radio_label_position', 'around', array( 'around', 'before', 'above', 'after' ) );
					switch ( $radio_label_position ) {
						case 'around':
							$return .= $label_start . $radio_input . $orderby_candidate_label . $label_end;
							break;
						case 'above':
						case 'before':
							$return .= $label_start . $orderby_candidate_label . $label_end . $radio_input;
							break;
						case 'after':
							$return .= $radio_input . $label_start . $orderby_candidate_label . $label_end;
							break;
					}
				}
				break;
			case 'list':
				$orderby_options_for_lists = array();
				foreach ( $orderby_options as $orderby_candidate ) {
					$orderby_candidate_label = $this->craft_sorting_setting_label( $orderby_candidate, $atts, $view_name );
					$orderby_candidate_orderbyas = in_array( $orderby_candidate, $orderby_as_numeric ) ? 'numeric' : '';

					$orderby_candidate_order = '';
					$orderby_candidate_order = in_array( $orderby_candidate, $orderby_ascending ) ? 'asc' : $orderby_candidate_order;
					$orderby_candidate_order = in_array( $orderby_candidate, $orderby_descending ) ? 'desc' : $orderby_candidate_order;
					$orderby_candidate_order = empty( $orderby_candidate_order ) ? $current_order : $orderby_candidate_order;

					$orderby_options_for_lists[ $orderby_candidate ] = array(
						'label'	=> $orderby_candidate_label,
						'type'	=> in_array( $orderby_candidate, $orderby_as_numeric ) ? 'numeric' : '',
						'order'	=> $orderby_candidate_order
					);
				}

				$orderby_list_args = array(
					'options' => $orderby_options_for_lists,
					'style' => isset( $atts['list_style'] ) ? $atts['list_style'] : 'default',
					'force_apply' => $atts['force_apply'],

				);

				return $this->get_sorting_orderby_selector( $orderby_list_args );
				break;
		}
		return $return;
	}

	/**
	 * Shortcode for frontend order sorting.
	 *
	 * @param $atts
	 *     $type 			String. Type of frontend control. 'select'|'radio'. Defaults to 'select'.
	 *     $options			String. Comma separated list of options to include, Defaults to 'asc,desc'.
	 *     $label_for_asc	String. Label for the 'asc' option. Defaults to Ascending.
	 *     $label_for_desc	String. Label for the 'desc' option. Defaults to Descending.
	 *     $label_asc_for_	String. Label for the 'asc' option for a specific sorting field.
	 *     $label_desc_for_	String. Label for the 'desc' option for a specific sorting field.
	 *
	 * @since 2.3.0
	 * @since 2.3.1 Added specific asc/desc options for each of the sorting fields.
	 */

	function wpv_shortcode_wpv_sort_order( $atts ) {

		$atts = wp_parse_args(
			$atts ,
			array(
				'type'				=> 'select',
				'options'			=> 'asc,desc',
				'label_for_asc'		=> __( 'Ascending', 'wpv-views' ),
				'label_for_desc'	=> __( 'Descending', 'wpv-views' ),
				'list_style'		=> 'default',
				'force_apply' => 'false',
				'select_style' => '',
			)
		);

		$return = '';

		$view_id			= apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_settings		= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$view_hash			= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$view_name			= get_post_field( 'post_name', $view_id );
		$sorting_settings	= apply_filters( 'wpv_filter_wpv_get_sorting_settings', array() );

		$current_orderby	= strtolower( $sorting_settings['orderby'] );
		$current_order		= strtolower( $sorting_settings['order'] );

		$order_options = explode( ',', $atts['options'] );
		$order_options = array_map( 'trim', $order_options );
		$order_options = array_map( 'strtolower', $order_options );
		$order_options = array_intersect( $order_options, array( 'asc', 'desc' ) );

		$atts['force_apply'] = toolset_getarr( $atts, 'force_apply', 'false', array( 'false', 'reload', 'ajax' ) );

		if ( count( $order_options ) == 0 ) {
			return;
		}

		$order_extra_labels = $this->craft_sorting_setting_direction_labels( $atts, $view_name );

		switch ( $atts['type'] ) {
			case 'select':
				$class_attributes = 'wpv-sort-control-select wpv-sort-control-order js-wpv-sort-control-order';
				if ( in_array( $atts['force_apply'], array( 'reload', 'ajax' ) ) ) {
					$class_attributes .= ' js-wpv-sort-control-force-apply-' . $atts['force_apply'];
				}
				$return .= '<select'
					. ' name="wpv_sort_order"'
					. ' class="' . esc_attr( $class_attributes ) . '"'
					. ( ( '' !== $atts['select_style'] ) ? ' style="' . esc_attr( $atts['select_style'] ) . '"' : '' )
					. ' data-viewnumber="' . esc_attr( $view_hash ) . '"'
					. ' data-labels="' . esc_attr( wp_json_encode( $order_extra_labels ) ) . '"'
					. ' autocomplete="off"'
					. '>';
				foreach ( $order_options as $order_candidate ) {
					$order_candidate_label = ( isset( $order_extra_labels[ $current_orderby ] ) && isset( $order_extra_labels[ $current_orderby ][ $order_candidate ] ) )
						? $order_extra_labels[ $current_orderby ][ $order_candidate ]
						: $order_extra_labels[ 'default' ][ $order_candidate ];

					$return .= '<option value="' . esc_attr( $order_candidate ) . '" ' . selected( $order_candidate, $current_order, false ) . '>';
					$return .= $order_candidate_label;
					$return .= '</option>';
				}
				$return .= '</select>';
				break;
			case 'radio':
				foreach ( $order_options as $order_candidate ) {
					$order_candidate_label = ( isset( $order_extra_labels[ $current_orderby ] ) && isset( $order_extra_labels[ $current_orderby ][ $order_candidate ] ) )
						? $order_extra_labels[ $current_orderby ][ $order_candidate ]
						: $order_extra_labels[ 'default' ][ $order_candidate ];

					$class_attributes = 'wpv-sort-control-radio wpv-sort-control-order js-wpv-sort-control-order';
					if ( in_array( $atts['force_apply'], array( 'reload', 'ajax' ) ) ) {
						$class_attributes .= ' js-wpv-sort-control-force-apply-' . $atts['force_apply'];
					}

					$return .= '<label class="wpv-sort-control-radio-label wpv-sort-control-order-radio-label">';
					$return .= '<input'
						. ' type="radio"'
						. ' name="wpv_sort_order"'
						. ' class="' . esc_attr( $class_attributes ) . '"'
						. ' value="' . esc_attr( $order_candidate ) . '"'
						. ' ' . checked( $current_order, $order_candidate, false )
						. ' data-viewnumber="' . esc_attr( $view_hash ) . '"'
						. ' data-labels="' . esc_attr( wp_json_encode( $order_extra_labels ) ) . '"'
						. ' autocomplete="off"'
						. ' />';
					$return .= $order_candidate_label;
					$return .= '</label>';
				}
				break;
			case 'list':
				$order_options_for_lists = array();
				foreach ( $order_options as $order_candidate ) {
					$order_candidate_label = ( isset( $order_extra_labels[ $current_orderby ] ) && isset( $order_extra_labels[ $current_orderby ][ $order_candidate ] ) )
						? $order_extra_labels[ $current_orderby ][ $order_candidate ]
						: $order_extra_labels[ 'default' ][ $order_candidate ];

					$order_options_for_lists[ $order_candidate ] = array(
						'label'	=> $order_candidate_label
					);
				}

				$order_list_args = array(
					'options'	=> $order_options_for_lists,
					'style'		=> isset( $atts['list_style'] ) ? $atts['list_style'] : 'default',
					'force_apply' => $atts['force_apply'],
					'extra'		=> array(
										'labels' => esc_attr( wp_json_encode( $order_extra_labels ) )
									)

				);

				return $this->get_sorting_order_selector( $order_list_args );
				break;
		}

		return $return;
	}

	function wpv_shortcode_wpv_sort_link( $atts, $content = null ) {

		extract(
			shortcode_atts( array(
				'orderby'		=> '',
				'order'			=> 'desc',
				'orderbyas'		=> '',
				'class'			=> '',
				'style'			=> ''
			), $atts )
		);

		$view_id		= apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_settings	= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$view_hash		= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
		$return			= '';

		$pagination_data		= apply_filters( 'wpv_filter_wpv_get_pagination_settings', array(), $view_settings );
		$pagination_permalinks	= apply_filters( 'wpv_filter_wpv_get_pagination_permalinks', array(), $view_settings, $view_id );
		$permalink = $pagination_permalinks['first'];

		$query_args = array(
			'wpv_sort_orderby'	=> $orderby,
			'wpv_sort_order'	=> $order
		);
		if ( ! empty ( $orderbyas ) ) {
			$query_args['wpv_sort_orderby_as'] = $orderbyas;
		}

		$permalink = remove_query_arg(
			array( 'wpv_sort_orderby', 'wpv_sort_order', 'wpv_sort_orderby_as' ),
			$permalink
		);

		$permalink = add_query_arg(
			$query_args,
			$permalink
		);

		if ( empty( $orderby ) ) {
			return $return;
		}
		$order		= in_array( $order, array( 'asc', 'ASC', 'desc', 'DESC' ) ) ? strtolower( $order ) : 'desc';
		$content	= wpv_do_shortcode( $content );
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style ).'"';
		}
		if ( ! empty( $class) ) {
			$class = ' ' . esc_attr( $class );
		}

		$return = '<a href="' . esc_url( $permalink ) . '"'
			. $style
			. ' class="js-wpv-sort-trigger' . $class . '"'
			. ' data-viewnumber="' 	. esc_attr( $view_hash ) . '"'
			. ' data-orderby="' . esc_attr( $orderby ) . '"'
			. ' data-order="' . esc_attr( $order ) . '"'
			. ' data-orderbyas="' . esc_attr( $orderbyas ) . '"'
			. '>'
			. $content
			. '</a>';

		return $return;
	}

	/**
	 * Normalize the possible values of the $orderby and $orderby_second settings for post Views.
	 *
	 * As $orderby and $orderby_second can take several values and aliases when sorting posts,
	 * they are transformed here into something that WP_Query can understand.
	 *
	 * @param string $orderby
	 *
	 * @return string
	 *
	 * @since 2.3.0
	 */

	static function normalize_post_orderby_value( $orderby ) {

		switch ( $orderby ) {

			case 'date':
			case 'post_date':
			case 'post-date':
				$orderby = 'date';
				break;

			case 'title':
			case 'post_title':
			case 'post-title':
			case 'post_link':
			case 'post-link':
				$orderby = 'title';
				break;

			case 'id':
			case 'post_id':
			case 'post-id':
			case 'ID':
				$orderby = 'ID';
				break;

			case 'author':
			case 'post_author':
			case 'post-author':
				$orderby = 'author';
				break;

			case 'type':
			case 'post_type':
			case 'post-type':
				$orderby = 'type';
				break;

			case 'name':
			case 'post_name':
			case 'post-name':
			case 'post_slug':
			case 'post-slug':
				$orderby = 'name';
				break;

			case 'post_body':
				$orderby = 'post_content';
				break;

			default:
				if ( strpos( $orderby, 'post_' ) === 0 ) {
					$orderby = substr( $orderby, 5 );
				}
				break;

		}

		return $orderby;
	}

	/**
	 * Normalize the possible values of the $orderby and $orderby_second settings for taxonomy Views.
	 *
	 * As $orderby can take several values and aliases when sorting terms,
	 * they are transformed here into something that get_terms can understand.
	 *
	 * @param string $orderby
	 *
	 * @return string
	 *
	 * @since 2.3.0
	 */

	static function normalize_taxonomy_orderby_value( $orderby ) {

		switch ( $orderby ) {
			case 'taxonomy-link':
			case 'taxonomy-title':
				$orderby = 'name';
				break;
			case 'taxonomy-post_count':
				$orderby = 'count';
				break;
			case 'taxonomy-id':
				$orderby = 'id';
				break;
			case 'taxonomy-slug':
				$orderby = 'slug';
				break;
		}

		return $orderby;
	}

	/**
	 * Normalize the possible values of the $orderby and $orderby_second settings for user Views.
	 *
	 * As $orderby can take several values and aliases when sorting terms,
	 * they are transformed here into something that WP_User_Query can understand.
	 *
	 * @param string $orderby
	 *
	 * @return string
	 *
	 * @since 2.3.0
	 */

	static function normalize_user_orderby_value( $orderby ) {

		switch ( $orderby ) {
			case 'login':
			case 'user_login':
				$orderby = 'user_login';
				break;
			case 'name':
			case 'user_name':
				$orderby = 'user_name';
				break;
			case 'nicename':
			case 'user_nicename':
				$orderby = 'user_nicename';
				break;
			case 'email':
			case 'user_email':
				$orderby = 'user_email';
				break;
			case 'url':
			case 'user_url':
				$orderby = 'user_url';
				break;
			case 'registered':
			case 'user_registered':
				$orderby = 'user_registered';
				break;
		}

		return $orderby;
	}

	/**
	 * Get the View sorting settings, after being modified by all available sources:
	 * - from the View settings.
	 * - from the View shortcode attributs.
	 * - from the URL parameters.
	 *
	 * @since 2.3.0
	 */

	function get_sorting_settings( $sorting_settings = array(), $view_id = null ) {

		$defaults = array(
			'orderby'			=> '',
			'order'				=> '',
			'orderby_as'		=> '',
			'orderby_second'	=> '',
			'order_second'		=> ''
		);
		$sorting_settings = wp_parse_args( $sorting_settings, $defaults );

		// Load sorting from settings.
		$sorting_settings = $this->get_sorting_settings_from_settings( $sorting_settings, $view_id );
		// Lod sorting from shortcode attributes
		$sorting_settings = $this->get_sorting_settings_from_attributes( $sorting_settings, $view_id );
		// Load sorting from URL parameers
		$sorting_settings = $this->get_sorting_settings_from_url( $sorting_settings, $view_id );

		return $sorting_settings;

	}

	/**
	 * Set sorting settings from the very View settings.
	 *
	 * @note For posts, use 'orderby', 'order', 'orderby_as', 'orderby_Second', 'order_second'.
	 * @note For terms, use 'taxonomy_orderby', 'taxonomy_order', 'taxonomy_orderby_as'.
	 * @note For users, use 'users_orderby', 'users_order', 'users_orderby_as'.
	 *
	 * @since 2.3.0
	 */

	function get_sorting_settings_from_settings( $sorting_settings = array(), $view_id = null ) {

		$view_settings	= apply_filters( 'wpv_filter_wpv_get_object_settings', array(), $view_id );
		$view_mode		= apply_filters( 'wpv_filter_wpv_get_query_type', 'posts', $view_id );

		switch( $view_mode ) {
			case 'posts':
			default:
				$sorting_settings['orderby']		= $view_settings['orderby'];
				$sorting_settings['order']			= $view_settings['order'];
				$sorting_settings['orderby_as']		= $view_settings['orderby_as'];
				$sorting_settings['orderby_second']	= $view_settings['orderby_second'];
				$sorting_settings['order_second']	= $view_settings['order_second'];
				break;
			case 'taxonomy':
				$sorting_settings['orderby']		= $view_settings['taxonomy_orderby'];
				$sorting_settings['order']			= $view_settings['taxonomy_order'];
				$sorting_settings['orderby_as']		= $view_settings['taxonomy_orderby_as'];
				break;
			case 'users':
				$sorting_settings['orderby']		= $view_settings['users_orderby'];
				$sorting_settings['order']			= $view_settings['users_order'];
				$sorting_settings['orderby_as']		= $view_settings['users_orderby_as'];
				break;
		}

		return $sorting_settings;
	}

	/**
	 * Set sorting settings from the very View shortcode attributes, if any.
	 *
	 * @note All View modes share the same attributes:
	 * 'orderby', 'order', 'orderby_as', 'orderby_second', 'order_second'.
	 *
	 * @note For posts, we allow overriding 'orderby', 'order', 'orderby_as', 'orderby_second', 'order_second'.
	 *     We apply some restrictions to what we accept for 'orderby_second'.
	 * @note For terms, we allow overriding 'orderby', 'order', 'orderby_as'.
	 * @note For users, we allow overriding 'orderby', 'order', 'orderby_as'.
	 *     We apply some restrictions to what we accept for 'orderby',
	 *     as we only allow for sorting by values in $this->valid_values['users']['orderby'] or
	 *     values starting with 'user-field-' for meta sorting.
	 *
	 * @since 2.3.0
	 */

	function get_sorting_settings_from_attributes( $sorting_settings = array(), $view_id = null ) {

		$view_mode		= apply_filters( 'wpv_filter_wpv_get_query_type', 'posts', $view_id );
		$view_attrs		= apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', false );

		switch( $view_mode ) {
			case 'posts':
			default:

				if ( isset( $view_attrs['orderby'] ) ) {
					$sorting_settings['orderby'] = $view_attrs['orderby'];
				}
				if (
					isset( $view_attrs['order'] )
					&& in_array( $view_attrs['order'], $this->valid_values[ $view_mode ]['order'] )
				) {
					$sorting_settings['order'] = strtoupper( $view_attrs['order'] );
				}
				if (
					isset( $view_attrs['orderby_as'] )
					&& in_array( strtoupper( $view_attrs['orderby_as'] ), array( 'STRING', 'NUMERIC' ) )
				) {
					$sorting_settings['orderby_as'] = strtoupper( $view_attrs['orderby_as'] );
				}
				if (
					isset( $view_attrs['orderby_second'] )
					&& in_array( $view_attrs['orderby_second'], $this->valid_values[ $view_mode ]['orderby_second'] )
				) {
					$sorting_settings['orderby_second'] = $view_attrs['orderby_second'];
				}
				if (
					isset( $view_attrs['order_second'] )
					&& in_array( $view_attrs['order_second'], $this->valid_values[ $view_mode ]['order_second'] )
				) {
					$sorting_settings['order_second'] = $view_attrs['order_second'];
				}

				break;
			case 'taxonomy':

				if ( isset( $view_attrs['orderby'] ) ) {
					$sorting_settings['orderby'] = $view_attrs['orderby'];
				}
				if (
					isset( $view_attrs['order'] )
					&& in_array( $view_attrs['order'], $this->valid_values[ $view_mode ]['order'] )
				) {
					$sorting_settings['order'] = strtoupper( $view_attrs['order'] );
				}
				if (
					isset( $view_attrs['orderby_as'] )
					&& in_array( strtoupper( $view_attrs['orderby_as'] ), array( 'STRING', 'NUMERIC' ) )
				) {
					$sorting_settings['orderby_as'] = strtoupper( $view_attrs['orderby_as'] );
				}

				break;
			case 'users':

				if (
					isset( $view_attrs['orderby'] )
					&& (
						in_array( $view_attrs['orderby'], $this->valid_values[ $view_mode ]['orderby'] )
						|| strpos( $view_attrs['orderby'], 'user-field-') === 0
					)
				) {
					$sorting_settings['orderby'] = $view_attrs['orderby'];
				}
				if (
					isset( $view_attrs['order'] )
					&& in_array( $view_attrs['order'], $this->valid_values[ $view_mode ]['order'] )
				) {
					$sorting_settings['order'] = strtoupper( $view_attrs['order'] );
				}
				if (
					isset( $view_attrs['orderby_as'] )
					&& in_array( strtoupper( $view_attrs['orderby_as'] ), array( 'STRING', 'NUMERIC' ) )
				) {
					$sorting_settings['orderby_as'] = strtoupper( $view_attrs['orderby_as'] );
				}

				break;
		}

		return $sorting_settings;
	}

	/**
	 * Set sorting settings from URL parameters, if any.
	 *
	 * @note All View modes share the same URL parameters:
	 * 'wpv_sort_orderby', 'wpv_sort_order', 'wpv_sort_orderby_as', 'wpv_sort_orderby_second', 'wpv_sort_order_second'.
	 *
	 * @note For posts, we allow overriding 'orderby', 'order', 'orderby_as', 'orderby_second', 'order_second'.
	 *     We apply some restrictions to what we accept for 'orderby_second'.
	 * @note For terms, we allow overriding 'orderby', 'order', 'orderby_as'.
	 * @note For users, we allow overriding 'orderby', 'order', 'orderby_as'.
	 *     We apply some restrictions to what we accept for 'orderby',
	 *     as we only allow for sorting by values in $this->valid_values['users']['orderby'] or
	 *     values starting with 'user-field-' for meta sorting.
	 * @note When wpv_sort_orderby points to sorting by a meta field, an empty wpv_sort_orderby_as value is accepted
	 *     nd means that we should sort as a native, string field.
	 *
	 * @since 2.3.0
	 */

	function get_sorting_settings_from_url( $sorting_settings = array(), $view_id = null ) {

		$is_view_posted	= false;
		if ( isset( $_GET['wpv_view_count'] ) ) {
			$view_settings	= apply_filters( 'wpv_filter_wpv_get_object_settings', array(), $view_id );
			$view_unique_hash = apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
			if ( esc_attr( $_GET['wpv_view_count'] ) == $view_unique_hash ) {
				$is_view_posted = true;
				// Map old URL parameters to new ones
				do_action( 'wpv_action_wpv_pagination_map_legacy_order' );
			}
		}

		if ( ! $is_view_posted ) {
			return $sorting_settings;
		}

		$view_mode		= apply_filters( 'wpv_filter_wpv_get_query_type', 'posts', $view_id );

		switch( $view_mode ) {
			case 'posts':
			default:

				// Legacy order URL override
				if (
					isset( $_GET['wpv_order'] )
					&& isset( $_GET['wpv_order'][0] )
					&& in_array( $_GET['wpv_order'][0], $this->valid_values[ $view_mode ]['order'] )
				) {
					$sorting_settings['order'] = esc_attr( $_GET['wpv_order'][0] );
				}

				// Modern order URL override
				if (
					isset( $_GET['wpv_sort_orderby'] )
					&& esc_attr( $_GET['wpv_sort_orderby'] ) != 'undefined'
					&& esc_attr( $_GET['wpv_sort_orderby'] ) != ''
				) {
					$sorting_settings['orderby'] = esc_attr( $_GET['wpv_sort_orderby'] );
					// There is no standalone frontend control for the orderby_as parameter,
					// so only modify it if the orderby one was modified!!!
					if (
						isset( $_GET['wpv_sort_orderby_as'] )
						&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) ), array( '', 'STRING', 'NUMERIC' ) )
					) {
						$sorting_settings['orderby_as'] = strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) );
					}
				}
				if (
					isset( $_GET['wpv_sort_order'] )
					&& in_array( esc_attr( $_GET['wpv_sort_order'] ), $this->valid_values[ $view_mode ]['order'] )
				) {
					$sorting_settings['order'] = strtoupper( esc_attr( $_GET['wpv_sort_order'] ) );
				}

				// Secondary sorting
				if (
					isset( $_GET['wpv_sort_orderby_second'] )
					&& esc_attr( $_GET['wpv_sort_orderby_second'] ) != 'undefined'
					&& esc_attr( $_GET['wpv_sort_orderby_second'] ) != ''
					&& in_array( $_GET['wpv_sort_orderby_second'], $this->valid_values[ $view_mode ]['orderby_second'] )
				) {
					$sorting_settings['orderby_second'] = esc_attr( $_GET['wpv_sort_orderby_second'] );
				}
				if (
					isset( $_GET['wpv_sort_order_second'] )
					&& in_array( esc_attr( $_GET['wpv_sort_order_second'] ), $this->valid_values[ $view_mode ]['order_second'] )
				) {
					$sorting_settings['order_second'] = strtoupper( esc_attr( $_GET['wpv_sort_order_second'] ) );
				}

				break;
			case 'taxonomy':

				if (
					isset( $_GET['wpv_sort_orderby'] )
					&& esc_attr( $_GET['wpv_sort_orderby'] ) != 'undefined'
					&& esc_attr( $_GET['wpv_sort_orderby'] ) != ''
				) {
					$sorting_settings['orderby'] = esc_attr( $_GET['wpv_sort_orderby'] );
					// There is no standalone frontend control for the orderby_as parameter,
					// so only modify it if the orderby one was modified!!!
					if (
						isset( $_GET['wpv_sort_orderby_as'] )
						&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) ), array( 'STRING', 'NUMERIC' ) )
					) {
						$sorting_settings['orderby_as'] = strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) );
					}
				}
				if (
					isset( $_GET['wpv_sort_order'] )
					&& esc_attr( $_GET['wpv_sort_order'] ) != ''
					&& in_array( esc_attr( $_GET['wpv_sort_order'] ), $this->valid_values[ $view_mode ]['order'] )
				) {
					$sorting_settings['order'] = strtoupper( esc_attr( $_GET['wpv_sort_order'] ) );
				}

				break;
			case 'users':

				$column_sort_id = wpv_getget( 'wpv_sort_orderby', '' );
				if (
					! empty( $column_sort_id )
					&& (
						in_array( $column_sort_id, $this->valid_values[ $view_mode ]['orderby'] )
						|| strpos( $column_sort_id, 'user-field-') === 0
					)
				) {
					$sorting_settings['orderby'] = $column_sort_id;
					// There is no standalone frontend control for the orderby_as parameter,
					// so only modify it if the orderby one was modified!!!
					if (
						isset( $_GET['wpv_sort_orderby_as'] )
						&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) ), array( 'STRING', 'NUMERIC' ) )
					) {
						$sorting_settings['orderby_as'] = strtoupper( esc_attr( $_GET['wpv_sort_orderby_as'] ) );
					}
				}
				if (
					isset( $_GET['wpv_sort_order'] )
					&& esc_attr( $_GET['wpv_sort_order'] ) != ''
					&& in_array( esc_attr( $_GET['wpv_sort_order'] ), $this->valid_values[ $view_mode ]['order'] )
				) {
					$sorting_settings['order'] = strtoupper( esc_attr( $_GET['wpv_sort_order'] ) );
				}

				break;
		}

		return $sorting_settings;
	}

	/**
	 * Return a default canonical label for a sorting option.
	 *
	 * When crafting a frontend sorting control that did not get a user-submitted label,
	 * this produces a default label for it.
	 *
	 * @since 2.3.0
	 */

	function get_sorting_setting_default_label( $setting ) {

		$setting_label = $setting;

		switch ( $setting ) {

			// First, ascending and descending options
			case 'asc':
			case 'ASC':
				$setting_label = __( 'Ascending', 'wpv-views' );
				break;
			case 'desc':
			case 'DESC':
				$setting_label = __( 'Descending', 'wpv-views' );
				break;

			// Post fields options - canonical
			case 'date':
			case 'post_date':
			case 'post-date':
				$setting_label = __( 'Post date', 'wpv-views' );
				break;
			case 'title':
			case 'post_title':
			case 'post-title':
			case 'post_link':
			case 'post-link':
				$setting_label = __( 'Post title', 'wpv-views' );
				break;
			case 'id':
			case 'post_id':
			case 'post-id':
			case 'ID':
				$setting_label = __( 'Post ID', 'wpv-views' );
				break;
			case 'author':
			case 'post_author':
			case 'post-author':
				$setting_label = __( 'Post author', 'wpv-views' );
				break;
			case 'type':
			case 'post_type':
			case 'post-type':
				$setting_label = __( 'Post type', 'wpv-views' );
				break;
			case 'modified':
				$setting_label = __( 'Last modified', 'wpv-views' );
				break;
			case 'menu_order':
				$setting_label = __( 'Menu order', 'wpv-views' );
				break;
			case 'rand':
				$setting_label = __( 'Random order', 'wpv-views' );
				break;
			// Post fields options - extra
			case 'name':
			case 'post_name':
			case 'post-name':
			case 'post_slug':
			case 'post-slug':
				$setting_label = __( 'Post name', 'wpv-views' );
				break;
			case 'post_body':
				$setting_label = __( 'Post content', 'wpv-views' );
				break;

			// Guess Types fiels actual name
			default:

				$meta_field = false;
				$field_name = '';

				if ( strpos( $setting_label, 'types-field-' ) === 0 ) {
					$field_name = strtolower( substr( $setting_label, 12 ) );
					$meta_field = 'cf';
				} else if ( strpos( $setting_label, 'post-field-' ) === 0 ) {
					$field_name = strtolower( substr( $setting_label, 11 ) );
					$meta_field = 'cf';
				} else if ( strpos( $setting_label, 'field-' ) === 0 ) {
					$field_name = strtolower( substr( $setting_label, 6 ) );
					$meta_field = 'cf';
				} else if ( strpos( $setting_label, 'taxonomy-field-' ) === 0 ) {
					$field_name = strtolower( substr( $setting_label, 15 ) );
					$meta_field = 'tf';
				} else if ( strpos( $setting_label, 'user-field-' ) === 0 ) {
					$field_name = strtolower( substr( $setting_label, 11 ) );
					$meta_field = 'uf';
				}

				if ( $meta_field ) {
					$types_field_data = wpv_is_types_custom_field( $field_name, $meta_field );
					if (
						$types_field_data
						&& isset( $types_field_data['name'] )
					) {
						$setting_label = sprintf( __( 'Field - %s', 'wpv-views' ), $types_field_data['name'] );
					}
				}

				break;
		}

		return $setting_label;

	}

	/**
	 * Compose a frontend sorting option label, checking whether it is included in the relevant shortcode attributes,
	 * and make sure it gets properly translated with WPML.
	 *
	 * @param $sorting_candidate	string	The option which label we are crafting.
	 * @param $args					array	The array of attributes passed to the relevant shortcode.
	 * @param $view_name			string	The current View slug, used in the WPML ST context value.
	 *
	 * @since 2.3.0
	 */

	function craft_sorting_setting_label( $sorting_candidate, $atts = array(), $view_name = '' ) {

		$sorting_candidate_lowercase = strtolower( $sorting_candidate );

		$sorting_label = isset( $atts[ 'label_for_' . $sorting_candidate_lowercase ] )
			? esc_html( $atts[ 'label_for_' . $sorting_candidate_lowercase ] )
			: esc_html( $this->get_sorting_setting_default_label( $sorting_candidate ) );

		$sorting_label = apply_filters( 'wpv_filter_wpv_deccode_arbitrary_shortcode_value', $sorting_label );

		$sorting_label = wpv_translate(
			'sorting_control_for_' . $sorting_candidate_lowercase,// name
			$sorting_label,// string
			false,// register
			'View ' . $view_name// context
		);

		return $sorting_label;

	}

	/**
	 * Compose a JSON object with all the frontend sorting direction option labels,
	 * coming from the relevant shortcode attributes, grouped by sorting field,
	 * and make sure it gets properly translated with WPML.
	 *
	 * @param $atts					array	The array of attributes passed to the relevant shortcode.
	 * @param $view_name			string	The current View slug, used in the WPML ST context value.
	 *
	 * @since 2.3.1
	 */

	function craft_sorting_setting_direction_labels( $atts = array(), $view_name = '' ) {

		$labels = array(
			'default' => array(
				'asc'	=> $this->craft_sorting_setting_label( 'asc', $atts, $view_name ),
				'desc'	=> $this->craft_sorting_setting_label( 'desc', $atts, $view_name )
			)
		);

		$labels_and_names_for_directions = array(
			'asc'	=> array(
				'label'	=> 'label_asc_for_',
				'name'	=> 'sorting_control_asc_for_'
			),
			'desc'	=> array(
				'label'	=> 'label_desc_for_',
				'name'	=> 'sorting_control_desc_for_'
			)
		);

		foreach ( $atts as $attribute_key => $attribute_value ) {

			foreach ( $labels_and_names_for_directions as $direction => $direction_data ) {

				if ( strpos( $attribute_key, $direction_data['label'] ) === 0 ) {

					$orderby_key = substr( $attribute_key, strlen( $direction_data['label'] ) );

					$labels[ $orderby_key ] = ( isset( $labels[ $orderby_key ] ) ) ? $labels[ $orderby_key ] : array();
					$attribute_value = apply_filters( 'wpv_filter_wpv_deccode_arbitrary_shortcode_value', $attribute_value );

					$labels[ $orderby_key ][ $direction ] = wpv_translate(
						$direction_data['name'] . $orderby_key,// name
						$attribute_value,// string
						false,// register
						'View ' . $view_name// context
					);

					break;

				}

			}

		}

		return $labels;

	}

}

global $WPV_Sorting_Embedded;
$WPV_Sorting_Embedded = new WPV_Sorting_Embedded();

/**
* -------------------------------------------------
* Shared functions
* -------------------------------------------------
*/

/**
 * Auxiliary function that will provide limit and offset settings coming from the Views shortcode atributes, if possible
 *
 * @param array $allowed (array) Valid values that can be used to override
 *
 * @return array $return (array)
 *
 * @since 1.10
 */
function wpv_override_view_orderby_order( $allowed = array() ) {
	$defaults = array(
		'orderby'			=> array(),
		'order'				=> array(),
		'orderby_as'		=> array(),
		'orderby_second'	=> array(),
		'order_second'		=> array()
	);
	$allowed = wp_parse_args( $allowed, $defaults );
	$return = array();
	$view_attrs = apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', false );
	if ( isset( $view_attrs['orderby'] ) ) {
		if ( count( $allowed['orderby'] ) > 0 ) {
			if ( in_array( $view_attrs['orderby'], $allowed['orderby'] ) ) {
				$return['orderby'] = $view_attrs['orderby'];
			}
		} else {
			$return['orderby'] = $view_attrs['orderby'];
		}
	}
	if ( isset( $view_attrs['order'] ) ) {
		if ( count( $allowed['order'] ) > 0 ) {
			if ( in_array( $view_attrs['order'], $allowed['order'] ) ) {
				$return['order'] = $view_attrs['order'];
			}
		} else {
			$return['order'] = $view_attrs['order'];
		}
	}
	if ( isset( $view_attrs['orderby_as'] ) ) {
		if ( count( $allowed['orderby_as'] ) > 0 ) {
			if ( in_array( $view_attrs['orderby_as'], $allowed['orderby_as'] ) ) {
				$return['orderby_as'] = $view_attrs['orderby_as'];
			}
		} else {
			$return['orderby_as'] = $view_attrs['orderby_as'];
		}
	}
	if ( isset( $view_attrs['orderby_second'] ) ) {
		if ( count( $allowed['orderby_second'] ) > 0 ) {
			if ( in_array( $view_attrs['orderby_second'], $allowed['orderby_second'] ) ) {
				$return['orderby_second'] = $view_attrs['orderby_second'];
			}
		} else {
			$return['orderby_second'] = $view_attrs['orderby_second'];
		}
	}
	if ( isset( $view_attrs['order_second'] ) ) {
		if ( count( $allowed['order_second'] ) > 0 ) {
			if ( in_array( $view_attrs['order_second'], $allowed['order_second'] ) ) {
				$return['order_second'] = $view_attrs['order_second'];
			}
		} else {
			$return['order_second'] = $view_attrs['order_second'];
		}
	}
	return $return;
}
