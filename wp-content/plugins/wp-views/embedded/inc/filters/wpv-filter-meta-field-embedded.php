<?php

use OTGS\Toolset\Views\Controller\Filters\Post\ProductStock;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductStock\ProductStockQuery;

/**
 * Meta frontend filter
 *
 * @package Views
 * @since 2.1
 * @codeCoverageIgnore
 */

WPV_Meta_Frontend_Filter::on_load();

/**
* WPV_Meta_Frontend_Filter
*
* Views Meta Filter Frontend Class
*
* @since 2.1
*/

class WPV_Meta_Frontend_Filter {

	static $current_filter_view_settings = array();
	static $extra_fake_view_settings = array();
	static $extra_fake_view_data = array();

	static function on_load() {
		// Apply frontend filter by post meta
        add_filter( 'wpv_filter_query',										array( 'WPV_Meta_Frontend_Filter', 'filter_post_meta' ), 10, 3 );
		add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_Meta_Frontend_Filter', 'archive_filter_post_meta' ), 40, 3 );
		// Auxiliar methods for requirements
		add_filter( 'wpv_filter_requires_framework_values',					array( 'WPV_Meta_Frontend_Filter', 'requires_framework_values' ), 20, 2 );
		// Auxiliar methods for gathering data
		//add_filter( 'wpv_filter_register_shortcode_attributes_for_posts',	array( 'WPV_Meta_Frontend_Filter', 'shortcode_attributes' ), 10, 2 );
		//add_filter( 'wpv_filter_register_url_parameters_for_posts',			array( 'WPV_Meta_Frontend_Filter', 'url_parameters' ), 10, 2 );
		// Apply frontend filter by taxonomy meta
		add_filter( 'wpv_filter_taxonomy_query',							array( 'WPV_Meta_Frontend_Filter', 'filter_taxonomy_meta' ), 40, 3 );

		add_filter( 'wpv_filter_user_query',								array( 'WPV_Meta_Frontend_Filter', 'filter_user_meta' ), 70, 3 );

		// Apply frontend filter to adjust type casting.
		add_filter( 'wpv_filter_custom_field_filter_type', 					array( 'WPV_Meta_Frontend_Filter', 'filter_meta_field_filter_type' ), 10, 4 );
		add_filter( 'wpv_filter_termmeta_field_filter_type', 				array( 'WPV_Meta_Frontend_Filter', 'filter_meta_field_filter_type' ), 10, 4 );
		add_filter( 'wpv_filter_usermeta_field_filter_type', 				array( 'WPV_Meta_Frontend_Filter', 'filter_meta_field_filter_type' ), 10, 4 );

		add_shortcode( 'wpv-control-postmeta',								array( 'WPV_Meta_Frontend_Filter', 'wpv_shortcode_wpv_control_postmeta' ) );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data',					array( 'WPV_Meta_Frontend_Filter', 'wpv_shortcodes_register_wpv_control_postmeta_data' ) );
		add_filter( 'wpv_filter_custom_field_filter_compare',				array( 'WPV_Meta_Frontend_Filter', 'force_in_comparison_for_multiple_values'), 10, 4 );
    }

	/**
	 * filter_meta_field_filter_type
	 *
	 * Applies meta fields filter.
	 *
	 * @param @param $type the type coming from the View settings filter: <CHAR>, <NUMERIC>, <BINARY>, <DATE>, <DATETIME>, <DECIMAL>, <SIGNED>, <TIME>, <UNSIGNED>
	 * @param $meta_name the key of the custom field being used to filter by
	 * @param $view_id the ID of the View being displayed
	 * @param $view_settings View's settings object
	 *
	 * @return mixed
	 *
	 * @since 2.3
	 */
    static function filter_meta_field_filter_type( $type, $meta_name, $view_id, $view_settings ) {
		if( isset( $view_settings['view-query-mode'] ) && 'normal' == $view_settings['view-query-mode'] ) {
			$query_type = isset( $view_settings['query_type'][0] ) ? $view_settings['query_type'][0] : 'posts';
		} else {
			$query_type = 'posts';
		}

		if( 'DECIMAL' == strtoupper( $type ) ) {
			$meta_type = '';

			switch( $query_type ) {
				case 'posts':
					$meta_type = 'custom';
					break;

				case 'taxonomy':
					$meta_type = 'termmeta';
					break;

				case 'users':
					$meta_type = 'usermeta';
					break;
			}

			$decimals = $meta_type . '-field-' . $meta_name . '_decimals';

			if( isset( $view_settings[$decimals] ) ) {
				$type = 'DECIMAL(11,' . $view_settings[$decimals] . ')';
			} else {
				// Currently, frontend filters casting to DECIMAL can not set decimal points.
				$type = 'DECIMAL(11,10)';
			}
		}

    	return $type;
	}

	/**
	* filter_post_meta
	*
	* Apply the postmeta filter for Views.
	*
	* @since 2.1
	*/

	static function filter_post_meta( $query, $view_settings, $view_id ) {
		$meta_keys = array();
		$meta_queries = array();
		foreach ( array_keys( $view_settings ) as $key ) {
			if (
				strpos( $key, 'custom-field-' ) === 0
				&& strpos( $key, '_compare' ) === strlen( $key ) - strlen( '_compare' )
			) {
				$name = substr( $key, 0, strlen( $key ) - strlen( '_compare' ) );
				$name = substr( $name, strlen( 'custom-field-' ) );
				$type = $view_settings['custom-field-' . $name . '_type'];
				$compare = $view_settings['custom-field-' . $name . '_compare'];
				$value = $view_settings['custom-field-' . $name . '_value'];

				$meta_name = $name;

				$meta_name = self::prevent_filtering_by_meta_keys_containing_spaces( $meta_name, $meta_keys );

				/**
				* Filter wpv_filter_custom_field_filter_original_value
				*
				* @param $value			string	The value coming from the View settings filter before passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $meta_name		string	The key of the custom field being used to filter by
				* @param $view_id		integer	The ID of the View being displayed
				*
				* $value comes from the View settings. It's a string containing a single-value or a comma-separated list of single-values if the filter needs more than one value (for IN, NOT IN, BETWEEN and NOT BETWEEN comparisons)
				* Each individual single-value element in the list can use any of the following formats:
				* (string|numeric) if the single-value item is fixed
				* (string) URL_PARAM(parameter) if the filter is done via a URL param "parameter"
				* (string) VIEW_PARAM(parameter) if the filter is done via a [wpv-view] shortcode attribute "parameter"
				* (string) NOW() | TODAY() | FUTURE_DAY() | PAST_DAY() | THIS_MONTH() | FUTURE_MONTH() | PAST_MONTH() | THIS_YEAR() | FUTURE_YEAR() | PAST_YEAR() | SECONDS_FROM_NOW() | MONTHS_FROM_NOW() | YEARS_FROM_NOW() | DATE()
				*
				* @since 1.4
				*/

				$original_filter = $value = apply_filters( 'wpv_filter_custom_field_filter_original_value', $value, $meta_name, $view_id );

				/**
				* Filter wpv_resolve_variable_values
				*
				* @param $value the value coming from the View settings filter after passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $resolve_attr Array containing the filters that need to be applied as resolvers
				*
				* @since 1.8
				*/

				$resolve_attr = array(
					'filters' => array( 'url_parameter', 'shortcode_attribute', 'date_timestamp', 'framework_value' )
				);
				$value = apply_filters( 'wpv_resolve_variable_values', $value, $resolve_attr );

				/**
				* Filter wpv_filter_custom_field_filter_processed_value
				*
				* @param $value			string	The value coming from the View settings filter after passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $meta_name		string	The key of the custom field being used to filter by
				* @param $view_id		integer	The ID of the View being displayed
				*
				* @since 1.4
				*/

				$value = apply_filters( 'wpv_filter_custom_field_filter_processed_value', $value, $meta_name, $view_id );

				/**
				* Filter wpv_filter_custom_field_filter_type
				*
				* @param $type the type coming from the View settings filter: <CHAR>, <NUMERIC>, <BINARY>, <DATE>, <DATETIME>, <DECIMAL>, <SIGNED>, <TIME>, <UNSIGNED>
				* @param $meta_name the key of the custom field being used to filter by
				* @param $view_id the ID of the View being displayed
				*
				* @since 1.6
				*/

				$type = apply_filters( 'wpv_filter_custom_field_filter_type', $type, $meta_name, $view_id, $view_settings );

				/**
				* Filter wpv_filter_custom_field_filter_compare
				*
				* @param $type the type coming from the View settings filter: <CHAR>, <NUMERIC>, <BINARY>, <DATE>, <DATETIME>, <DECIMAL>, <SIGNED>, <TIME>, <UNSIGNED>
				* @param $original_filter the original filter, for example: URL_PARAM(...)
				* @param $view_id the ID of the View being displayed
				* @param $view_settings View settings array
				*
				* @since 3.0
				*/

				$compare = apply_filters( 'wpv_filter_custom_field_filter_compare', $compare, $original_filter, $view_id, $view_settings );

				$has_meta_query = WPV_Meta_Frontend_Filter::resolve_meta_query( $meta_name, $value, $type, $compare );
				if ( $has_meta_query ) {
					$meta_queries[] = $has_meta_query;
				}
			}
		}

		//Set field relation
		if ( count( $meta_queries ) ) {
			$query['meta_query'] = $meta_queries;
			$query['meta_query']['relation'] = isset( $view_settings['custom_fields_relationship'] ) ? $view_settings['custom_fields_relationship'] : 'AND';
		}

		return $query;
	}

	static function archive_filter_post_meta( $query, $archive_settings, $archive_id ) {
		$meta_keys = array();
		$meta_queries = array();
		$postmeta_to_exclude = array();
		if ( $query->get( 'wpv_dependency_query' ) ) {
			$wpv_dependency_query = $query->get( 'wpv_dependency_query' );
			if ( isset( $wpv_dependency_query['postmeta'] ) ) {
				$postmeta_to_exclude[] = $wpv_dependency_query['postmeta'];
			}
		}
		foreach ( $archive_settings as $index => $value ) {
			if ( preg_match( "/custom-field-(.*)_type/", $index, $match ) ) {
				$field = $match[1];
				$type = $value;
				$compare = $archive_settings['custom-field-' . $field . '_compare'];
				$value = $archive_settings['custom-field-' . $field . '_value'];

				if ( in_array( $field, $postmeta_to_exclude ) ) {
					continue;
				}

				$field = self::prevent_filtering_by_meta_keys_containing_spaces( $field, $meta_keys );

				/**
				* Filter wpv_filter_custom_field_filter_original_value
				*
				* @param $value			string	The value coming from the WPA settings filter before passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $field			string	The key of the custom field being used to filter by
				* @param $archive_id	integer	The ID of the WPA being displayed
				*
				* $value comes from the WPA settings. It's a string containing a single-value or a comma-separated list of single-values if the filter needs more than one value (for IN, NOT IN, BETWEEN and NOT BETWEEN comparisons)
				* Each individual single-value element in the list can use any of the following formats:
				* (string|numeric) if the single-value item is fixed
				* (string) URL_PARAM(parameter) if the filter is done via a URL param "parameter"
				* (string) VIEW_PARAM(parameter) if the filter is done via a [wpv-view] shortcode attribute "parameter"
				* (string) NOW() | TODAY() | FUTURE_DAY() | PAST_DAY() | THIS_MONTH() | FUTURE_MONTH() | PAST_MONTH() | THIS_YEAR() | FUTURE_YEAR() | PAST_YEAR() | SECONDS_FROM_NOW() | MONTHS_FROM_NOW() | YEARS_FROM_NOW() | DATE()
				*
				* @since 1.4
				*/

				$value = apply_filters( 'wpv_filter_custom_field_filter_original_value', $value, $field, $archive_id );

				/**
				* Filter wpv_resolve_variable_values
				*
				* @param $value			string	The value coming from the WPA settings filter after passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $resolve_attr	array	Array containing the filters that need to be applied as resolvers
				*
				* @since 1.8
				*/

				$resolve_attr = array(
					'filters' => array( 'url_parameter', 'shortcode_attribute', 'date_timestamp', 'framework_value' )
				);
				$value = apply_filters( 'wpv_resolve_variable_values', $value, $resolve_attr );

				/**
				* Filter wpv_filter_custom_field_filter_processed_value
				*
				* @param $value			string	The value coming from the WPA settings filter after passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $field			string	The key of the custom field being used to filter by
				* @param $archive_id	integer	The ID of the WPA being displayed
				*
				* @since 1.4
				*/

				$value = apply_filters( 'wpv_filter_custom_field_filter_processed_value', $value, $field, $archive_id );

				/**
				* Filter wpv_filter_custom_field_filter_type
				*
				* @param $type			string	The type coming from the WPA settings filter: <CHAR>, <NUMERIC>, <BINARY>, <DATE>, <DATETIME>, <DECIMAL>, <SIGNED>, <TIME>, <UNSIGNED>
				* @param $field			string	The key of the custom field being used to filter by
				* @param $archive_id	integer	The ID of the WPA being displayed
				*
				* @since 1.6
				*/

				$type = apply_filters( 'wpv_filter_custom_field_filter_type', $type, $field, $archive_id, $archive_settings );

				$has_meta_query = WPV_Meta_Frontend_Filter::resolve_meta_query( $field, $value, $type, $compare );
				if ( $has_meta_query ) {
					$meta_queries[] = $has_meta_query;
				}
			}
		}

		//Set field relation
		if ( count( $meta_queries ) ) {
			$meta_queries['relation'] = isset( $archive_settings['custom_fields_relationship'] ) ? $archive_settings['custom_fields_relationship'] : 'AND';
			$meta_queries = apply_filters( 'wpv_filter_wpv_before_set_meta_query', $meta_queries );
			$query->set( 'meta_query', $meta_queries );
		}
	}

	/**
	 * Force a faked query filter when we know there is a frontend filter.
	 * Since the new Views editor, we should not generate query filters manually,
	 * but on the fly instead, when applying the filter to the query.
	 *
	 * @param array $view_settings
	 * @return array
	 * @since 3.0
	 */
	public static function maybe_force_fake_query_filter( $view_settings ) {
		$filter_html = toolset_getarr( $view_settings, 'filter_meta_html', '' );
		if ( strpos( $filter_html, '[wpv-control-postmeta' ) === false ) {
			return $view_settings;
		}

		self::$current_filter_view_settings = $view_settings;

		global $shortcode_tags;
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();
		add_shortcode( 'wpv-control-postmeta', array( 'WPV_Meta_Frontend_Filter', 'fake_postmeta_search_shortcode' ) );
		do_shortcode( $filter_html );
		$shortcode_tags = $orig_shortcode_tags;
		$extra_fake_view_settings = self::$extra_fake_view_settings;
		$extra_fake_view_data = self::$extra_fake_view_data;
		foreach ( $extra_fake_view_settings as $key => $value ) {
			$view_settings[ $key ] = $value;
		}
		$view_settings['custom_fields_relationship'] = 'AND';

		$postmeta_field = toolset_getarr( $extra_fake_view_data, 'postmeta', false );
		if ( false !== $postmeta_field ) {
			// For some reason, sometimes the postmeta key is missing.
			$filters = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, 'postmeta' ), array() );
			$filters[ $extra_fake_view_data[ 'postmeta' ] ] = array(
				\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_SEARCH_FILTER,
			);
			$view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ 'postmeta' ] = $filters;
		}

		self::$current_filter_view_settings = array();
		self::$extra_fake_view_settings = array();
		self::$extra_fake_view_data = array();

		return $view_settings;
	}

	/**
	 * Fake the wpv-control-postmeta shortcode to get its attributs and operate over them.
	 *
	 * @param array $atts
	 * @param string|null $content
	 * @since 3.0
	 */
	public static function fake_postmeta_search_shortcode( $original_atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'url_param' => '',
				'value_type' => 'CHAR',
				'value_compare' => '=',
				'field' => '',
			),
			$original_atts
		);

		$current_object_settings = self::$current_filter_view_settings;
		$extra_fake_view_settings = self::$extra_fake_view_settings;
		$extra_fake_view_data = self::$extra_fake_view_data;

		$value_candidate = 'URL_PARAM(' . $atts['url_param'] . ')';
		$stored_value = toolset_getarr( $current_object_settings, 'custom-field-' . $atts['field'] . '_value' );

		// Always force the value to adjust the URL parameter
		// unless when dealing with BETWEEN or NOT BETWEEN filters, managed below,
		// since those have compound values with min/max boundaries,
		// or the original value already contains the right URL parameter.
		if ( false === strpos( $stored_value, $value_candidate ) ) {
			$extra_fake_view_settings[ 'custom-field-' . $atts['field'] . '_value' ] = $value_candidate;
		}

		// Force the compare and type settings only if the shortcode contains data for it:
		// this avoids overriding existing query filters, and avoids targetting legacy Views
		if ( toolset_getarr( $original_atts, 'value_compare', false ) ) {
			$value_compare = $atts['value_compare'];
			switch ( $value_compare ) {
				case 'lt':
					$value_compare = '<';
					break;
				case 'let':
					$value_compare = '<=';
					break;
				case 'gt':
					$value_compare = '>';
					break;
				case 'get':
					$value_compare = '>=';
					break;
			}
			$extra_fake_view_settings[ 'custom-field-' . $atts['field'] . '_compare' ] = $value_compare;

			// Further adjust BETWEEN and NOT BETWEEN comparisons
			if (
				'BETWEEN' === $value_compare
				|| 'NOT BETWEEN' === $value_compare
			) {
				$url_param = 'wpv-' . $atts['field'];
				$extra_fake_view_settings[ 'custom-field-' . $atts['field'] . '_value' ] = 'URL_PARAM(' . $url_param . '_min),URL_PARAM(' . $url_param . '_max)';
			}
		} else {
			$value_compare_existing = toolset_getarr( $current_object_settings, 'custom-field-' . $atts['field'] . '_compare', '=' );
			$extra_fake_view_settings[ 'custom-field-' . $atts['field'] . '_compare' ] = $value_compare_existing;
			if (
				'BETWEEN' === $value_compare_existing
				|| 'NOT BETWEEN' === $value_compare_existing
			) {
				// In this case, do NOT try to adjust the '_value' entry based on the URL parameter of the shortcode,
				// since the frontend filter is made of TWO shortcodes with suffixed URL parameters.
				// Instead, keep the data as originally provided.
				$extra_fake_view_settings[ 'custom-field-' . $atts['field'] . '_value' ] = $stored_value;
			}
		}

		if ( toolset_getarr( $original_atts, 'value_type', false ) ) {
			$extra_fake_view_settings[ 'custom-field-' . $atts['field'] . '_type' ] = $atts['value_type'];
		} else {
			$extra_fake_view_settings[ 'custom-field-' . $atts['field'] . '_type' ] = toolset_getarr( $current_object_settings, 'custom-field-' . $atts['field'] . '_type', 'CHAR' );
		}

		$extra_fake_view_data[ 'postmeta' ] = $atts['field'];

		self::$extra_fake_view_settings = $extra_fake_view_settings;
		self::$extra_fake_view_data = $extra_fake_view_data;
	}

	static private function prevent_filtering_by_meta_keys_containing_spaces( $field, $meta_keys ) {
		$global_views_settings = WPV_Settings::get_instance();

		// Do not support filtering by meta keys containing a space
		// unless specific support has been declared
		if ( $global_views_settings->support_spaces_in_meta_filters ) {
			if ( empty( $meta_keys ) ) {
				$meta_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
			}
			if ( ! in_array( $field, $meta_keys ) ) {
				$field = str_replace( '_', ' ', $field );
			}
			if ( ! in_array( $field, $meta_keys ) ) {
				$field = str_replace( ' ', '.', $field );
			}
		}

		return $field;
	}

	/**
	* requires_framework_values
	*
	* Whether the current View requires framework data for the filter by meta fields
	*
	* @param $state				boolean	The state until this filter is applied
	* @param $view_settings
	*
	* @return $state			boolean
	*
	* @since 1.10
	* @since 2.1	Renamed from wpv_filter_meta_field_requires_framework_values and moved to a static method
	*/

	static function requires_framework_values( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if ( $view_settings['query_type'][0] == 'posts' ) {
			foreach ( $view_settings as $key => $value ) {
				if (
					preg_match( "/custom-field-(.*)_value/", $key, $res )
					&& preg_match( "/FRAME_KEY\(([^\)]+)\)/", $value, $shortcode )
				) {
					$state = true;
					break;
				}
			}
		}
		if ( $state ) {
			return $state;
		}
		if ( $view_settings['query_type'][0] == 'taxonomy' ) {
			foreach ( $view_settings as $key => $value ) {
				if (
					preg_match( "/termmeta-field-(.*)_value/", $key, $res )
					&& preg_match( "/FRAME_KEY\(([^\)]+)\)/", $value, $shortcode )
				) {
					$state = true;
					break;
				}
			}
		}
		if ( $state ) {
			return $state;
		}
		if ( $view_settings['query_type'][0] == 'users' ) {
			foreach ( $view_settings as $key => $value ) {
				if (
					preg_match( "/usermeta-field-(.*)_value/", $key, $res )
					&& preg_match( "/FRAME_KEY\(([^\)]+)\)/", $value, $shortcode )
				) {
					$state = true;
					break;
				}
			}
		}
		return $state;
	}

	static function filter_taxonomy_meta( $tax_query_settings, $view_settings, $view_id ) {
		$termmeta_queries = array();
		foreach ( $view_settings as $index => $value ) {
			if ( preg_match( "/termmeta-field-(.*)_type/", $index, $match ) ) {
				$field = $match[1];
				$type = $value;
				$compare = $view_settings['termmeta-field-' . $field . '_compare'];
				$value = $view_settings['termmeta-field-' . $field . '_value'];

				/**
				* Filter wpv_filter_termmeta_field_filter_original_value
				*
				* @param $value		string	The value coming from the View settings filter before passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $field		string	The key of the termmeta field being used to filter by
				* @param $view_id	integer	The ID of the View being displayed
				*
				* $value comes from the View settings. It's a string containing a single-value or a comma-separated list of single-values if the filter needs more than one value (for IN, NOT IN, BETWEEN and NOT BETWEEN comparisons)
				* Each individual single-value element in the list can use any of the following formats:
				* (string|numeric) if the single-value item is fixed
				* (string) URL_PARAM(parameter) if the filter is done via a URL param "parameter"
				* (string) VIEW_PARAM(parameter) if the filter is done via a [wpv-view] shortcode attribute "parameter"
				* (string) NOW() | TODAY() | FUTURE_DAY() | PAST_DAY() | THIS_MONTH() | FUTURE_MONTH() | PAST_MONTH() | THIS_YEAR() | FUTURE_YEAR() | PAST_YEAR() | SECONDS_FROM_NOW() | MONTHS_FROM_NOW() | YEARS_FROM_NOW() | DATE()
				*
				* @since 1.12
				*/

				$value = apply_filters( 'wpv_filter_termmeta_field_filter_original_value', $value, $field, $view_id );

				/**
				* Filter wpv_resolve_variable_values
				*
				* @param $value the value coming from the View settings filter after passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $resolve_attr Array containing the filters that need to be applied as resolvers
				*
				* @since 1.8
				*/

				$resolve_attr = array(
					'filters' => array( 'url_parameter', 'shortcode_attribute', 'date_timestamp', 'framework_value' )
				);
				$value = apply_filters( 'wpv_resolve_variable_values', $value, $resolve_attr );

				/**
				* Filter wpv_filter_termmeta_field_filter_processed_value
				*
				* @param $value			string	The value coming from the View settings filter after passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $field			string	The key of the termmeta field being used to filter by
				* @param $view_id		integer	The ID of the View being displayed
				*
				* @since 1.12
				*/

				$value = apply_filters( 'wpv_filter_termmeta_field_filter_processed_value', $value, $field, $view_id );

				/**
				* Filter wpv_filter_termmeta_field_filter_type
				*
				* @param $type the type coming from the View settings filter: <CHAR>, <NUMERIC>, <BINARY>, <DATE>, <DATETIME>, <DECIMAL>, <SIGNED>, <TIME>, <UNSIGNED>
				* @param $field the key of the termmeta field being used to filter by
				* @param $view_id the ID of the View being displayed
				*
				* @since 1.12
				*/

				$type = apply_filters( 'wpv_filter_termmeta_field_filter_type', $type, $field, $view_id, $view_settings );

				$has_meta_query = WPV_Meta_Frontend_Filter::resolve_meta_query( $field, $value, $type, $compare );
				if ( $has_meta_query ) {
					$termmeta_queries[] = $has_meta_query;
				}
			}
		}
		//Set termmeta relation
		if ( count( $termmeta_queries ) ) {
			$tax_query_settings['meta_query'] = $termmeta_queries;
			$tax_query_settings['meta_query']['relation'] = isset( $view_settings['termmeta_fields_relationship'] ) ? $view_settings['termmeta_fields_relationship'] : 'AND';
		}

		return $tax_query_settings;

	}

	/**
	* filter_user_meta
	*
	* Filter hooked before query and user basic fields
	*
	* @param $args				array	Arguments to be passed to WP_User_Query
	* @param $view_settings		array
	*
	* @return $args
	*
	* @since 1.6.2
	* @since 2.1	Renamed from wpv_users_query_usermeta_filters and moved to a static method
	*/

	static function filter_user_meta( $args, $view_settings, $view_id ) {
		$usermeta_queries = array();
		foreach ( $view_settings as $index => $value ) {
			if ( preg_match( "/usermeta-field-(.*)_type/", $index, $match ) ) {
				$field = $match[1];
				$type = $value;
				$compare = $view_settings['usermeta-field-' . $field . '_compare'];
				$value = $view_settings['usermeta-field-' . $field . '_value'];

				/**
				* Filter wpv_filter_usermeta_field_filter_original_value
				*
				* @param $value		string	The value coming from the View settings filter before passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $field		string	The key of the usermeta field being used to filter by
				* @param $view_id	integer	The ID of the View being displayed
				*
				* $value comes from the View settings. It's a string containing a single-value or a comma-separated list of single-values if the filter needs more than one value (for IN, NOT IN, BETWEEN and NOT BETWEEN comparisons)
				* Each individual single-value element in the list can use any of the following formats:
				* (string|numeric) if the single-value item is fixed
				* (string) URL_PARAM(parameter) if the filter is done via a URL param "parameter"
				* (string) VIEW_PARAM(parameter) if the filter is done via a [wpv-view] shortcode attribute "parameter"
				* (string) NOW() | TODAY() | FUTURE_DAY() | PAST_DAY() | THIS_MONTH() | FUTURE_MONTH() | PAST_MONTH() | THIS_YEAR() | FUTURE_YEAR() | PAST_YEAR() | SECONDS_FROM_NOW() | MONTHS_FROM_NOW() | YEARS_FROM_NOW() | DATE()
				*
				* @since 1.12
				*/

				$value = apply_filters( 'wpv_filter_usermeta_field_filter_original_value', $value, $field, $view_id );

				/**
				* Filter wpv_resolve_variable_values
				*
				* @param $value the value coming from the View settings filter after passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $resolve_attr Array containing the filters that need to be applied as resolvers
				*
				* @since 1.8
				*/

				$resolve_attr = array(
					'filters' => array( 'url_parameter', 'shortcode_attribute', 'date_timestamp', 'framework_value' )
				);
				$value = apply_filters( 'wpv_resolve_variable_values', $value, $resolve_attr );

				/**
				* Filter wpv_filter_usermeta_field_filter_processed_value
				*
				* @param $value			string	The value coming from the View settings filter after passing through the check for URL params, shortcode attributes and date functions comparison
				* @param $field			string	The key of the usermeta field being used to filter by
				* @param $view_id		integer	The ID of the View being displayed
				*
				* @since 1.12
				*/

				$value = apply_filters( 'wpv_filter_usermeta_field_filter_processed_value', $value, $field, $view_id );

				/**
				* Filter wpv_filter_usermeta_field_filter_type
				*
				* @param $type the type coming from the View settings filter: <CHAR>, <NUMERIC>, <BINARY>, <DATE>, <DATETIME>, <DECIMAL>, <SIGNED>, <TIME>, <UNSIGNED>
				* @param $field the key of the usermeta field being used to filter by
				* @param $view_id the ID of the View being displayed
				*
				* @since 1.8
				*/

				$type = apply_filters( 'wpv_filter_usermeta_field_filter_type', $type, $field, $view_id, $view_settings );

				$has_meta_query = WPV_Meta_Frontend_Filter::resolve_meta_query( $field, $value, $type, $compare );
				if ( $has_meta_query ) {
					$usermeta_queries[] = $has_meta_query;
				}
			}
		}
		//Set usermeta relation
		if ( count( $usermeta_queries ) ) {
			$args['meta_query'] = $usermeta_queries;
			$args['meta_query']['relation'] = isset( $view_settings['usermeta_fields_relationship'] ) ? $view_settings['usermeta_fields_relationship'] : 'AND';
		}

		return $args;

	}

	/**
	* resolve_meta_query
	*
	* Resolves if a meta_query is indeed needed, for filters by meta fields
	*
	* @param $key (string) The field key
	* @param $value (string) The resolved value to filter by
	* @param $type (string) The filtering data type
	* @param $compare (string) The filtering comparison type
	*
	* @return (array|boolean) The meta_query instance on success, false otherwise
	*
	* @since 1.8.0
	* @since 2.1	Renamed from wpv_resolve_meta_query and moved to a static method
	*/

	static function resolve_meta_query( $key, $value, $type, $compare ) {
		global $no_parameter_found;
		$return = false;
		if ( $value == $no_parameter_found ) {
			return false;
		}
		if (
			$compare == 'BETWEEN'
			|| $compare == 'NOT BETWEEN'
		) {
			// We need to make sure we have values for min and max.
			// If any of the values is missing we will transform into lower-than or greater-than filters
			// TODO: Note that we are not covering the case where min or max is an empty constant value, we might want to review that
			$values = explode( ',', $value );
			$values = array_map( 'trim', $values );
			if ( count( $values ) == 0 ) {
				return false;
			}
			if ( count( $values ) == 1 ) {
				if ( $values[0] == $no_parameter_found ) {
					return false;
				}
				if ( $compare == 'BETWEEN' ) {
					$compare =  '>=';
				} else {
					$compare =  '<=';
				}
				$value = $values[0];
			} else {
				if (
					$values[0] == $no_parameter_found
					&& $values[1] == $no_parameter_found
				) {
					return false;
				}
				if ( $values[0] == $no_parameter_found ) {
					if ( $compare == 'BETWEEN' ) {
						$compare = '<=';
					} else {
						$compare = '>=';
					}
					$value = $values[1];
				} elseif ( $values[1] == $no_parameter_found ) {
					if ( $compare == 'BETWEEN' ) {
						$compare = '>=';
					} else {
						$compare = '<=';
					}
					$value = $values[0];
				}
			}
		}

		if (
			'IN' === $compare
			|| 'NOT IN' === $compare
		) {
			// Filtering by several values, let's take out the $no_parameter_found ones.
			$values = explode( ',', $value );
			$values = array_map( 'trim', $values );

			$values = array_filter( $values, function( $candidate ) use ( $no_parameter_found ) {
				return $candidate !== $no_parameter_found;
			});

			$value = implode( ',', $values );
		}

		// If $value still contains a $no_parameter_found value, no filter should be applied
		// Because it means there is a non-existing or empty URL parameter
		// TODO: on shortcode attributes, an empty value as two commas will pass this test
		// Maybe this is OK, as we might want to filter by an empty value too, which is not possible on filters by URL parameter

		if ( strpos( $value, $no_parameter_found ) !== false ) {
			return false;
		}

		// Now that we are sure that the filter should be applied, even for empty values, let's do it

		if (
			$compare == 'IN'
			|| $compare == 'NOT IN'
		) {
			// WordPress query expects an array in this case
			$original_value = $value;
			$value = explode( ',', $value );
			if ( count( $value ) > 1 ) {
				// Add comma-separated combinations of meta values, since a legit value containing a comma might have been removed
				$value = WPV_Meta_Frontend_Filter::recursive_add_comma_meta_values( $value );
				// Also add the original one, as it might be a legitimate value containing several commas instead of a comma-separated list
				$value[] = $original_value;
			}
		}

		// Sanitization
		if ( is_array( $value ) ) {
			foreach ( $value as $v_key => $val ) {
				$value[$v_key] = stripslashes( rawurldecode( sanitize_text_field( trim( $val ) ) ) );
			}
		} else {
			$value = stripslashes( rawurldecode( sanitize_text_field( trim( $value ) ) ) );
		}

		if (
			in_array( $compare, array( '>=', '<=', '>', '<' ) )
			&& (
				empty( $value )
				&& ! is_numeric( $value )
			)
		) {
			// do nothing as we are comparing greater than / lower than to an empty value
			return false;
		} else {
			$return = array(
				'key'		=> $key,
				'value'		=> $value,
				'type'		=> $type,
				'compare'	=> $compare
			);
		}

		return $return;
	}

	/**
	* recursive_add_comma_meta_values
	*
	* Allow filtering by meta valus contaning a comma
	*
	* @since unknown
	* @since 2.1		Renamed from wpv_recursive_add_comma_meta_values and moved to a static method
	*/

	static function recursive_add_comma_meta_values( $values ) {
		$values_orig = array_reverse( $values );
		$values_aux = array();
		$values_end = array();
		if ( count( $values ) > 1 ) {
			foreach ( $values_orig as $v_key => $v_val ) {
				if ( count( $values_aux ) > 0 ) {
					foreach ( $values_aux as &$v_aux ) {
						$values_end[] = $v_val . ',' . $v_aux;
						$v_aux = $v_val . ',' . $v_aux;
					}
				}
				$values_end[] = $v_val;
				$values_aux[] = $v_val;
			}
		} else {
			$values_end = $values;
		}
		return $values_end;
	}

	/**
	 * Callback to display the custom search filter by post meta fields.
	 *
	 * @param array $atts
	 *		'field'			string	The meta key of the field to use
	 * 		'url_param'		string	URL parameter to listen to
	 *		'type'			'select'|'multi-select'|'radios'|'checbboxes'
	 *		'source'		string	The source of the options for this filter: 'database'|'custom'. Defaults to 'dabatase'.
	 *		'values'			string	Comma-separated list of values to use, in case source is 'custom'
	 *		'display_values'	string	Comma-separated list of display values to use, in case source is 'custom'
	 *		'format'		string.	Placeholders: '%%NAME%%', '%%COUNT%%'
	 *		'default_label'	string	Label for the default empty option in select dropdowns
	 '		'order'			string	Order of the options, when coming from the database
	 *		'date_format'	string	Date format for datepicker filters
	 *		'default_date'	string	Timestamp of the date to use as default for datepicker filters
	 *		'title'			string	Label to use on checkbox filters
	 *		'force_zero'	string	Flag to force a hidden checkbox companion for checkbox fields, and force post a zero when unchecked
	 *		'style'			string	Styles to add to the control
	 *		'class'			string	Classnames to add to the control
	 *		'label_style'	string
	 *		'label_class'	string
	 *		'output'		string	The kind of output to produce: 'legacy'|'bootstrap'. Defaults to 'bootstrap'.
	 *
	 * @return string|null
	 *
	 * @since 2.4.0
	 */

	static function wpv_shortcode_wpv_control_postmeta( $atts ) {
		$atts = shortcode_atts(
			array(
				'url_param'	=> '',
				'type'		=> '',
				'field'		=> '',
				'source'	=> 'database',
				'format'	=> '%%NAME%%',
				// When source="database"
				'order'		=> '',
				// When source="custom"
				'values'	=> '',
				'display_values' => '',
				// Select or radio fields
				'default_label'	=> '',
				// Date fields
				'date_format'	=> '',
				'default_date'	=> '',
				// Checkbox fields
				'title'		=> '',
				'force_zero'	=> 'false',
				// Shared
				'style'		=> '',
				'class'		=> '',
				'label_style'	=> '',
				'label_class'	=> '',
				'output'	=> 'bootstrap',
				'placeholder'   => ''
			),
			$atts
		);

		$atts['field'] = ( ProductStock::LEGACY_FIELD_SLUG === $atts['field'] )
			? ProductStock::FIELD_SLUG
			: $atts['field'];

		if ( empty( $atts['url_param'] ) ) {
			return null;
		}
		$url_param = $atts['url_param'];

		$type = $atts['type'];
		$toolset_field_data = wpv_types_get_field_data( $atts['field'] );
		$toolset_type = isset( $toolset_field_data['type'] ) ? strtolower( $toolset_field_data['type'] ) : '';

		if ( empty( $type ) ) {
			if ( empty( $toolset_type ) ) {
				return null;
			} else {
				$type = $toolset_type;
			}
		}

		if ( ! in_array( $type, array( 'select', 'multi-select', 'radio', 'radios', 'checkbox', 'checkboxes', 'text', 'textfield', 'integer', 'natural', 'date', 'datepicker' ) ) ) {
			$type = 'textfield';
		}

		$view_id   = apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_name = get_post_field( 'post_name', $view_id );

		// Translate the value format if any
		if ( ! empty( $atts['format'] ) ) {
			$atts['format'] = wpv_translate( 'wpv_control_postmeta_format_' . esc_attr( $atts['url_param'] ), $atts['format'], false, 'View ' . $view_name );
		}

		// Return early on some specific single-valued types that do not require counters or dependency at all.
		// - textfield, as it can be rendered manually.
		// - datepicker, as we have a dedicated callback for that.
		// - checkbox when there is no associated field.

		switch ( $type ) {
			case 'text':
			case 'textfield':
				$output = WPV_Meta_Frontend_Filter::wpv_control_postmeta_textfield( $atts );
				return $output;
			case 'integer':
				$atts['type'] = 'number';
				$output = WPV_Meta_Frontend_Filter::wpv_control_postmeta_textfield( $atts );
				return $output;
			case 'natural':
				$atts['type'] = 'number';
				$atts['min'] = '0';
				$output = WPV_Meta_Frontend_Filter::wpv_control_postmeta_textfield( $atts );
				return $output;
			case 'date':
			case 'datepicker':
				$output = wpv_render_datepicker( $url_param, $atts['date_format'], $atts['default_date'] );
				return $output;
			case 'checkbox':
				// Backwards compatibility:
				// when it is a checbox and there is no field value, render dummy checkbox.
				// Note that we do not apply the format here, not needed at all.
				if ( empty( $atts['field' ] ) ) {
					$atts['title'] = wpv_translate( $atts['url_param'] . '_title', $atts['title'], false, 'View ' . $view_name );
					$output = WPV_Meta_Frontend_Filter::wpv_control_postmeta_unvalued_checkbox( $atts );
					return $output;
				}
				break;
		}

		// We covered all fields that do not rquire an associated field, so we require it from now on.
		if ( empty( $atts['field'] ) ) {
			return null;
		}

		$field = $atts['field'];
		$toolset_field = isset( $toolset_field_data['meta_key'] ) ? strtolower( $toolset_field_data['meta_key'] ) : $field;

		// Cache, dependency and counters setup

		$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );

		/**
		 * Adjust the View settings just before the main Views query.
		 *
		 * @param array $view_settings
		 * @return $view_settings
		 * @since 3.0
		 */
		$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );

		$dependency_and_counters_attributes = array(
			'field'		=> $toolset_field,
			'type'		=> $type,
			'url_param'	=> $url_param,
			'view_settings'	=> $view_settings,
			'format'	=> $atts['format']
		);
		$dependency_and_counters_data = WPV_Meta_Frontend_Filter::setup_frontend_dependency_and_counters_data( $dependency_and_counters_attributes );

		// Return early in the missing checkbox scenario: there is an associated field.
		// Note that we only allow rendering as a checkbox a Types checkbox field.
		if ( 'checkbox' == $type ) {
			if ( 'checkbox' == $toolset_type ) {
				if ( ! empty( $atts['title'] ) ) {
					$atts['title'] = wpv_translate(
						$url_param . '_title',
						$atts['title'],
						false,
						'View ' . $view_name
					);
				} else {
					$atts['title'] = wpv_translate(
						'field ' . $toolset_field_data['name'] . ' name',
						$toolset_field_data['name'],
						false,
						'plugin Types'
					);
				}
				$output = WPV_Meta_Frontend_Filter::wpv_control_postmeta_types_checkbox( $atts, $dependency_and_counters_data, $toolset_field_data );
				return $output;
			} else {
				return null;
			}
		}

		// Now we only have missing those field types with options: select, multi-select, radio, checkboxes.
		$argument_for_values = array(
			'field'			=> $field,
			'toolset_field'	=> $toolset_field,
			'view_name'		=> $view_name,
			'type'			=> $type,
			'toolset_type'	=> $toolset_type,
			'toolset_field_data' => $toolset_field_data,
			'view_settings'	=> $view_settings
		);

		$fields_to_walk = WPV_Meta_Frontend_Filter::wpv_control_postmeta_get_fields_to_walk( $atts, $argument_for_values );

		$postmeta_walker = null;
		$walker_args = array(
			'field'				=> $toolset_field,
			'name'				=> $url_param,
			'selected'			=> '',
			'format'			=> $atts['format'], //%%NAME%%, %%COUNT%%
			'style'				=> $atts['style'],
			'class'				=> $atts['class'],
			'label_style'		=> $atts['label_style'],
			'label_class'		=> $atts['label_class'],
			'output'			=> $atts['output'],
			'type'				=> $type, //'select'|'multiselect'
			'toolset_type'		=> $toolset_type,
			'dependency'		=> $dependency_and_counters_data['dependency'], //'disabled'|'enabled'
			'counters'			=> $dependency_and_counters_data['counters'], //'disabled'|'enabled'
			'use_query_cache'	=> $dependency_and_counters_data['use_query_cache'],
			'auxiliar_query_count'	=> $dependency_and_counters_data['auxiliar_query_count'],
			'empty_action'		=> $dependency_and_counters_data['empty_action'], //'hide'|'disable'
			'comparator'		=> $dependency_and_counters_data['comparator'],
			'query_cache'		=> $dependency_and_counters_data['postmeta_cache']
		);

		if ( isset( $_GET[ $url_param ] ) ) {
			if ( is_array( $_GET[ $url_param ] ) ) {
				$walker_args['selected'] = $_GET[ $url_param ];
			} else {
				// support csv terms
				$walker_args['selected'] = array( $_GET[ $url_param ] );
			}
		}

		$return = '';

		switch ( $type ) {
			case 'select':
			case 'multi-select':
				$postmeta_walker = new WPV_Walker_Postmeta_Select( $walker_args );
				$select_args = array(
					'id'	=> 'wpv_control_select_' . $field,
					'name'	=> $walker_args['name'],
					'class'	=> ( empty( $walker_args['class'] ) ) ? array() : explode( ' ', $walker_args['class'] )
				);

				$select_args['class'][] = 'js-wpv-filter-trigger';

				switch ( $walker_args['output'] ) {
					case 'bootstrap':
						$select_args['class'][] = 'form-control';
						break;
					case 'legacy':
					default:
						$select_args['class'][] = 'wpcf-form-select form-select select';
						break;
				}

				if ( ! empty( $walker_args['style'] ) ) {
					$select_args['style'] = $walker_args['style'];
				}

				if ( 'multi-select' == $walker_args['type'] ) {
					$select_args['name'] = $walker_args['name'] . '[]';
					$select_args['multiple'] = 'multiple';
					//$select_args['size'] = '10';
				}

				$options_output = '';

				if (
					! empty( $atts['default_label'] )
					&& 'select' === $type
				) {
					// If there aren't manually entered values, there will be an empty option that will be "selected"
					// instead of default label option that has the same empty value
					$default_option = self::get_default_field_object_for_postmeta( $atts['url_param'], $atts['default_label'], $argument_for_values['view_name'] );
					if ( count( $fields_to_walk ) === 1 && $fields_to_walk[0]->meta_value === '' && $fields_to_walk[0]->meta_value === '' ) {
						$fields_to_walk = [ $default_option ];
					} else {
						array_unshift(
							$fields_to_walk,
							$default_option
						);
					}
				}

				$options_output .= call_user_func_array( array( &$postmeta_walker, 'walk' ), array( $fields_to_walk, 0 ) );

				$return .= WPV_Frontend_Filter::get_select( $options_output, $select_args  );
				return $return;
			case 'radio':
			case 'radios':
				if (
					! empty( $atts['default_label'] )
					&& 'radios' === $type
				) {
					array_unshift(
						$fields_to_walk,
						self::get_default_field_object_for_postmeta( $atts['url_param'], $atts['default_label'], $argument_for_values['view_name'] )
					);
				}
				$postmeta_walker = new WPV_Walker_Postmeta_Radios( $walker_args );
				$return .=  call_user_func_array( array( &$postmeta_walker, 'walk' ), array( $fields_to_walk, 0 ) );
				return $return;
			case 'checkboxes':
				$postmeta_walker = new WPV_Walker_Postmeta_Checkboxes( $walker_args );
				$return .=  call_user_func_array( array( &$postmeta_walker, 'walk' ), array( $fields_to_walk, 0 ) );
				return $return;
		}

		return null;
	}

	/**
	 * Create the "default field" object for the cases of postmeta shown as "select" or "radio" fields.
	 *
	 * @param string $url_param
	 * @param string $default_label
	 * @param string $view_name
	 *
	 * @return object               The "default field" object.
	 */
	static function get_default_field_object_for_postmeta( $url_param, $default_label, $view_name ) {
		$default_label = wpv_translate( $url_param . '_auto_fill_default', stripslashes( $default_label ), false, 'View ' . $view_name );

		$default_field = (object) array(
			'meta_key' => 'default',
			'meta_value' => '',
			'display_value' => $default_label,
			'meta_parent' => '',
		);

		return $default_field;
	}

	/**
	 * Generate the frontend HTML for a textfield input.
	 *
	 * @param array $atts The control shortcode attributes
	 *
	 * @return string
	 *
	 * @since 2.4.0
	 */
	public static function wpv_control_postmeta_textfield( $atts ) {
		$postmeta_filter_output = '';
		$input_attributes = array(
			'type'  => toolset_getarr( $atts, 'type', 'text' ),
			'id'    => 'wpv_control_textfield_' . esc_attr( $atts['url_param'] ),
			'name'  => esc_attr( $atts['url_param'] ),
			'value' => isset( $_GET[ $atts['url_param'] ] ) ? esc_attr( wp_unslash( $_GET[ $atts['url_param'] ] ) ) : '',
			'class' => ( empty( $atts['class'] ) ) ? array() : explode( ' ', $atts['class'] )
		);

		$input_attributes['class'][] = 'js-wpv-filter-trigger-delayed';

		if ( ! empty( $atts['style'] ) ) {
			$input_attributes['style'] = $atts['style'];
		}

		if ( ! empty( $atts['placeholder'] ) ) {
			$view_id                         = apply_filters( 'wpv_filter_wpv_get_current_view', null );
			$view_name                       = get_post_field( 'post_name', $view_id );
			$placeholder                     = wpv_translate( 'wpv_control_postmeta_placeholder_' . esc_attr( $atts['url_param'] ), $atts['placeholder'], false, 'View ' . $view_name );
			$input_attributes['placeholder'] = $placeholder;
		}

		switch ( $atts['output'] ) {
			case 'bootstrap':
				$input_attributes['class'][] = 'form-control';
				break;
			case 'legacy':
			default:
				$input_attributes['class'][] = 'wpcf-form-textfield form-textfield textfield';
				break;
		}

		if ( array_key_exists( 'min', $atts ) ) {
			$input_attributes['min'] = $atts['min'];
		}

		$postmeta_filter_output .= WPV_Frontend_Filter::get_input( $input_attributes );

		return $postmeta_filter_output;
	}

	/**
	 * Generate the frontend HTML for a checkbox input with no fixed value.
	 *
	 * @param array $atts The control shortcode attributes
	 *
	 * @return string
	 *
	 * @note The value is taken from the matching URL parameter, if any, for legacy reasons.
	 *
	 * @since 2.4.0
	 */
	public static function wpv_control_postmeta_unvalued_checkbox( $atts ) {
		$postmeta_filter_output = '';

		$el_args = array(
			'attributes'	=> array(
				'input'		=> array(
					'type'		=> 'checkbox',
					'id'		=> 'wpv_control_checkbox_' . esc_attr( $atts['url_param'] ),
					'style'		=> $atts['style'],
					'class'		=> ( empty( $atts['class'] ) ) ? array() : explode( ' ', $atts['class'] ),
					'name'		=> esc_attr( $atts['url_param'] ),
					'value'		=> ( isset( $_GET[ $atts['url_param'] ] ) ) ? esc_attr( $_GET[ $atts['url_param'] ] ) : ''
				),
				'label'		=> array(
					'for'		=> 'wpv_control_checkbox_' . esc_attr( $atts['url_param'] ),
					'style'		=> $atts['label_style'],
					'class'		=> ( empty( $atts['label_class'] ) ) ? array() : explode( ' ', $atts['label_class'] )
				),
			),
			'label'			=> $atts['title']
		);
		$el_args['attributes']['input']['class'][] = 'js-wpv-filter-trigger';

		if (
			isset( $_GET[ $atts['url_param'] ] )
			&& (
				! empty( $_GET[ $atts['url_param'] ] )
				|| is_numeric( $_GET[ $atts['url_param'] ] )
			)
		) {
			$el_args['attributes']['input']['checked'] = 'checked';
		}

		switch ( $atts['output'] ) {
			case 'bootstrap':
				switch( Toolset_Settings::get_instance()->bootstrap_version_numeric ) {
					case \OTGS\Toolset\Common\Settings\BootstrapSetting::NUMERIC_BS4:
						$el_args['attributes']['input']['class'][] = 'form-check-input';
						$el_args['attributes']['label']['class'][] = 'form-check-label';
						$postmeta_filter_output = '<div class="form-check">'
							. WPV_Frontend_Filter::get_input( $el_args['attributes']['input'] )
							. WPV_Frontend_Filter::get_label( $el_args['label'], $el_args['attributes']['label'] )
							. '</div>';
						break;
					default:
						$postmeta_filter_output .= '<div class="checkbox">';
						$input_output = WPV_Frontend_Filter::get_input( $el_args['attributes']['input'] );
						$postmeta_filter_output .= WPV_Frontend_Filter::get_label( $input_output . $el_args['label'], $el_args['attributes']['label'] );
						$postmeta_filter_output .= '</div>';
						break;
				}
				break;
			case 'legacy':
			default:
				$el_args['attributes']['input']['class'][] = 'wpcf-form-checkbox form-checkbox checkbox';
				$el_args['attributes']['label']['class'][] = 'wpcf-form-label wpcf-form-checkbox-label';
				// Input
				$postmeta_filter_output .= WPV_Frontend_Filter::get_input( $el_args['attributes']['input'] );
				// Compatibility: this was added by Enlimbo :-(
				$postmeta_filter_output .= '&nbsp;';
				// Label
				WPV_Frontend_Filter::get_label( $el_args['label'], $el_args['attributes']['label'] );
		}

		return $postmeta_filter_output;
	}


	/**
	 * Generate the frontend HTML for a checkbox input matching a Types checkbox field.
	 *
	 * @param array $atts The control shortcode attributes
	 *
	 * @param $dependency_and_counters_data
	 * @param $toolset_field_data
	 *
	 * @return string
	 *
	 * @note The value is taken from the matching URL parameter, if any, for legacy reasons.
	 *
	 * @since 2.4.0
	 */
	public static function wpv_control_postmeta_types_checkbox( $atts, $dependency_and_counters_data, $toolset_field_data ) {

		$toolset_field = isset( $toolset_field_data['meta_key'] ) ? strtolower( $toolset_field_data['meta_key'] ) : $atts['field'];
		$toolset_type = isset( $toolset_field_data['type'] ) ? strtolower( $toolset_field_data['type'] ) : '';

		$el_args = array(
			'attributes'	=> array(
				'input'		=> array(
					'type'		=> 'checkbox',
					'id'		=> 'wpv_control_checkbox_' . $atts['field'],
					'style'		=> $atts['style'],
					'class'		=> ( empty( $atts['class'] ) ) ? array() : explode( ' ', $atts['class'] ),
					'name'		=> $atts['url_param'],
				),
				'label'		=> array(
					'for'		=> 'wpv_control_checkbox_' . $atts['field'],
					'style'		=> $atts['label_style'],
					'class'		=> ( empty( $atts['label_class'] ) ) ? array() : explode( ' ', $atts['label_class'] )
				),
			),
			'label'			=> $atts['title']
		);

		$el_args['attributes']['input']['class'][] = 'js-wpv-filter-trigger';

		$el_args['attributes']['input']['value'] = isset( $toolset_field_data['data']['set_value'] )
			? $toolset_field_data['data']['set_value']
			: '';

		if ( $atts['format'] ) {
			$display_value_formatted_name = str_replace( '%%NAME%%', $el_args['label'], $atts['format'] );
			$el_args['label'] = $display_value_formatted_name;
		}

		$coming_value = '';
		$filter_value = $el_args['attributes']['input']['value'];
		if (
			isset( $_GET[ $atts['url_param'] ] )
			&& ! empty( $_GET[ $atts['url_param'] ] )
		) {
			$coming_value = esc_attr( $_GET[ $atts['url_param'] ] );
			$filter_value = $coming_value;
			$el_args['attributes']['input']['checked'] = 'checked';
		} else if (
			isset( $_GET[ $atts['url_param'] ] )
			&& is_numeric( $_GET[ $atts['url_param'] ] )
		) {
			// this only happens when the value to store when checked is actually zero - nonsense
			$coming_value = 0;
			$filter_value = $coming_value;
			$el_args['attributes']['input']['checked'] = 'checked';
		} else if ( empty( $_GET[ $atts['url_param'] ] ) ) {
			unset( $_GET[ $atts['url_param'] ] );
		}
		$show_checkbox = true;
		// Dependency stuff
		if (
			'enabled' == $dependency_and_counters_data['dependency']
			|| 'enabled' == $dependency_and_counters_data['counters']
		) {
			$meta_criteria_to_filter = array( $toolset_field => array( $filter_value ) );
			$data = array(
				'list'	=> $dependency_and_counters_data['postmeta_cache'],
				'args'	=> $meta_criteria_to_filter,
				'kind'	=> $toolset_type,
				'comparator' => $dependency_and_counters_data['comparator'],
			);
			if ( 'enabled' == $dependency_and_counters_data['counters'] ) {
				$data['count_matches'] = true;
			}
			$this_checker = wpv_list_filter_checker( $data );
			if ( 'enabled' == $dependency_and_counters_data['counters'] ) {
				$display_value_formatted_count = str_replace( '%%COUNT%%', $this_checker, $el_args['label'] );
				$el_args['label'] = $display_value_formatted_count;
			}
			if (
				! $this_checker
				&& empty( $coming_value )
				&& 'enabled' == $dependency_and_counters_data['dependency']
			) {
				$el_args['attributes']['input']['disabled'] = 'disabled';
				$el_args['attributes']['label']['class'][] = 'wpv-parametric-disabled';
				if ( $dependency_and_counters_data['empty_action'] == 'hide' ) {
					$show_checkbox = false;
				}
			}
		}
		if ( $show_checkbox ) {

			$postmeta_filter_output = '';
			switch ( $atts['output'] ) {
				case 'bootstrap':
					switch( Toolset_Settings::get_instance()->bootstrap_version_numeric ) {
						case \OTGS\Toolset\Common\Settings\BootstrapSetting::NUMERIC_BS4:
							$el_args['attributes']['input']['class'][] = 'form-check-input';
							$el_args['attributes']['label']['class'][] = 'form-check-label';

							$postmeta_filter_output .= '<div class="form-check">'
								. WPV_Frontend_Filter::get_input( $el_args['attributes']['input'] )
								. WPV_Frontend_Filter::get_label( $el_args['label'], $el_args['attributes']['label'] )
								. '</div>';
							break;
						default:
							$postmeta_filter_output .= '<div class="checkbox">';
							$el_args['attributes']['input']['class'][] = 'checkbox';
							$input_output = WPV_Frontend_Filter::get_input( $el_args['attributes']['input'] );
							$postmeta_filter_output .= WPV_Frontend_Filter::get_label( $input_output . $el_args['label'], $el_args['attributes']['label'] );
							$postmeta_filter_output .= '</div>';
							break;
					}
					break;
				case 'legacy':
				default:
					$el_args['attributes']['input']['class'][] = 'wpcf-form-checkbox form-checkbox checkbox';
					$el_args['attributes']['label']['class'][] = 'wpcf-form-label wpcf-form-checkbox-label';
					// Input
					$postmeta_filter_output .= WPV_Frontend_Filter::get_input( $el_args['attributes']['input'] );
					// Compatibility: this was added by Enlimbo :-(
					$postmeta_filter_output .= '&nbsp;';
					// Label
					$postmeta_filter_output .= WPV_Frontend_Filter::get_label( $el_args['label'], $el_args['attributes']['label'] );
					break;
			}
			if (
				isset( $toolset_field_data['data']['save_empty'] )
				&& $toolset_field_data['data']['save_empty'] == 'yes'
				&& $atts['force_zero'] == 'true'
			) {
				$postmeta_filter_output .= '<input'
					. ' type="hidden"'
					. ' checked="checked"'
					. ' id="wpv_control_checkbox_' . $atts['field'] . '_fakezero"'
					. ' name="' . $atts['url_param'] . '_fakezero'
					. ' value="yes"'
					. ' />';
			}

		} else {
			$postmeta_filter_output= '';
		}

		return $postmeta_filter_output;
	}

	/**
	 * Generate the fields to walk on a postmeta frontend filter, basedon the desired source.
	 *
	 * @param array $atts The control shortcode attributes
	 * @param array $args Some extra data needed for this method
	 *     'field'			    string The field, as passed in $atts['field']
	 *     'toolset_field'      string The field slug, matching the Types one if available
	 *     'view_name'          string The View slug, used for translations
	 *     'type'               string The control type, as passed to $atts['type'] and normalized
	 *     'toolset_type'       string The type of field if it is a Types field; empty otherwise
	 *     'toolset_field_data' array  The Types data for this field, if any; empty otherwise
	 *     'view_settings'      array  The View settings
	 *
	 * @return array
	 *
	 * @since 2.4.0
	 */
	public static function wpv_control_postmeta_get_fields_to_walk( $atts, $args ) {
		$fields_to_walk = array();

		if ( 'database' == $atts['source'] ) {
			// @todo Types options need to be sorted too
			switch ( $args['toolset_type'] ) {
				case 'select':
				case 'radio':
				case 'radios':
					$options = isset( $args['toolset_field_data']['data']['options'] )
						? $args['toolset_field_data']['data']['options']
						: array();
					if ( isset( $options['default'] ) ) {
						// remove the default option from the array
						unset( $options['default'] );
					}
					$display_option = '';
					if ( isset( $args['toolset_field_data']['data']['display'] ) ) {
						$display_option = $args['toolset_field_data']['data']['display'];
					}
					foreach( $options as $field_key => $option ) {
						$field_option = new stdClass();
						$field_option->meta_key = $args['toolset_field'];
						$field_option->meta_value = $option['value'];
						if (
							'value' == $display_option
							&& isset( $option['display_value'] )
						) {
							$field_option->display_value = wpv_translate( 'field '. $args['toolset_field_data']['id'] .' option '. $field_key .' title', $option['display_value'], false, 'plugin Types' );
						} else {
							$field_option->display_value = wpv_translate( 'field '. $args['toolset_field_data']['id'] .' option '. $field_key .' title', $option['title'], false, 'plugin Types' );
						}
						$field_option->meta_parent = 0;
						$fields_to_walk[] = $field_option;
					}
					break;
				case 'checkboxes':
					$options = isset( $args['toolset_field_data']['data']['options'] )
						? $args['toolset_field_data']['data']['options']
						: array();
					foreach( $options as $field_key => $option ) {
						$field_option = new stdClass();
						$field_option->meta_key = $args['toolset_field'];
						$field_option->meta_value = $option['title'];
						$field_option->display_value = wpv_translate( 'field '. $args['toolset_field_data']['id'] .' option '. $field_key .' title', $option['title'], false, 'plugin Types' );
						$field_option->meta_parent = 0;
						$fields_to_walk[] = $field_option;
					}
					break;
				default:
					global $wpdb;
					$values_to_prepare = array();
					$values_to_prepare[] = $args['toolset_field'];
					$wpdb_where = '';
					if (
						'normal' === toolset_getarr( $args['view_settings'], 'view-query-mode' )
						&& isset( $args['view_settings']['post_type'] )
						&& is_array( $args['view_settings']['post_type'] )
						&& ! empty( $args['view_settings']['post_type'] )
						&& ! in_array( 'any', $args['view_settings']['post_type'] )
					) {
						$post_type_count = count( $args['view_settings']['post_type'] );
						$post_type_placeholders = array_fill( 0, $post_type_count, '%s' );
						$wpdb_where .= " AND p.post_type IN (" . implode( ",", $post_type_placeholders ) . ") ";
						foreach ( $args['view_settings']['post_type'] as $pt ) {
							$values_to_prepare[] = $pt;
						}
					}
					if (
						isset( $args['view_settings']['post_status'] )
						&& is_array( $args['view_settings']['post_status'] )
						&& ! empty( $args['view_settings']['post_status'] )
					) {
						if ( ! in_array( 'any', $args['view_settings']['post_status'] ) ) {
							$post_status_count = count( $args['view_settings']['post_status'] );
							$post_status_placeholders = array_fill( 0, $post_status_count, '%s' );
							$wpdb_where .= " AND p.post_status IN (" . implode( ",", $post_status_placeholders ) . ") ";
							foreach ( $args['view_settings']['post_status'] as $ps ) {
								$values_to_prepare[] = $ps;
							}
						}
					} else {
						$status = array( 'publish' );
						if ( current_user_can( 'read_private_posts' ) ) {
							$status[] = 'private';
						}
						$wpdb_where .= " AND p.post_status IN ( '" . implode( "','", $status ) . "' ) ";
					}
					switch ( strtolower( $atts['order'] ) ) {
						case 'desc':
							$wpdb_orderby = "ORDER BY pm.meta_value DESC";
							break;
						case 'descnum':
							$wpdb_orderby = "ORDER BY pm.meta_value + 0 DESC";
							break;
						case 'ascnum':
							$wpdb_orderby = "ORDER BY pm.meta_value + 0 ASC";
							break;
						default:
							$wpdb_orderby = "ORDER BY pm.meta_value ASC";
							break;
					}
					$database_values = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT DISTINCT pm.meta_value
							FROM {$wpdb->postmeta} pm
							LEFT JOIN {$wpdb->posts} p
							ON p.ID = pm.post_id
							WHERE pm.meta_key = %s
							AND pm.meta_value IS NOT NULL
							AND pm.meta_value != ''
							{$wpdb_where}
							{$wpdb_orderby}",
							$values_to_prepare
						)
					);
					foreach ( $database_values as $database_field_index => $database_field_option ) {
						$field_option = new stdClass();
						$field_option->meta_key = $args['toolset_field'];
						$field_option->meta_value = $database_field_option;
						$field_option->display_value = wpv_translate( $atts['url_param'] . '_display_values_' . ( $database_field_index + 1 ), stripslashes( $database_field_option ), false, 'View ' . $args['view_name'] );
						$field_option->meta_parent = 0;
						$fields_to_walk[] = $field_option;
					}
					break;
			}


		} else if ( 'custom' == $atts['source'] ) {
			$custom_values = explode( ',', $atts['values'] );
			$custom_display_values = explode( ',', $atts['display_values'] );
			$custom_count = min( count( $custom_values ), count( $custom_display_values ) );

			$custom_values = array_slice( $custom_values, 0, $custom_count );
			$custom_display_values = array_slice( $custom_display_values, 0, $custom_count );

			$custom_values = array_map( array( 'WPV_Meta_Frontend_Filter', 'restore_commas_in_values' ), $custom_values );
			$custom_display_values = array_map( array( 'WPV_Meta_Frontend_Filter', 'restore_commas_in_values' ), $custom_display_values );

			if ( ProductStock::FIELD_SLUG === $args['toolset_field'] ) {
				$custom_values = array_map( array( 'WPV_Meta_Frontend_Filter', 'manage_legacy_woo_stock_fields' ), $custom_values );
			}

			foreach ( $custom_values as $custom_values_key => $custom_values_value ) {
				$field_option = new stdClass();
				$field_option->meta_key = $args['toolset_field'];
				$field_option->meta_value = $custom_values_value;
				$field_option->display_value = wpv_translate( $atts['url_param'] . '_display_values_' . ( $custom_values_key + 1 ), stripslashes( $custom_display_values[ $custom_values_key ] ), false, 'View ' . $args['view_name'] );
				$field_option->meta_parent = 0;
				$fields_to_walk[] = $field_option;
			}

		}

		return $fields_to_walk;
	}

	/**
	 * Auxiliar method to restore commas in values, coming from %%COMMA%%, %comma% and \, placeholders.
	 *
	 * @since 2.4.0
	 */

	public static function restore_commas_in_values( $value ) {
		$value = str_replace( array( '%%COMMA%%', '%comma%', '\,' ), ',', $value );
		return $value;
	}

	/**
	 * Proxy legacy values for the views_woo_in_stock field into values for the native _stock_status field.
	 *
	 * @param string $value
	 * @retuen string
	 */
	public static function manage_legacy_woo_stock_fields( $value ) {
		switch ( $value ) {
			case '0':
				return ProductStockQuery::STOCK_OUT_OF_STOCK;
			case '1':
				return ProductStockQuery::STOCK_IN_STOCK;
		}
		return $value;
	}

	/**
	 * Auxiliar method to calculate some dependency and counters data, and store cache if needed, for the frontend filter by postmeta fields.
	 *
	 * @param $attributes array(
	 *		'field'		string	The field slug to use
	 *		'type'		string	The filter type to render
	 *		'url_param'	string	The URL parameter to listen to
	 *		'view_settings'	array	The invlved View settings
	 *		'fomat'		string	The format of the frontend filter
	 * )
	 *
	 * @return array(
	 *		'use_query_cache'	string	Whether we need to use the cache, meaning whether there is dependency or counters. 'enabled'|'disabled'.
	 *		'dependency'		string	Whether there is dependency. 'enabled'|'disabled'
	 *		'counters'			string	Whether there are counters. 'enabled'|'disabled'
	 *		'comparator'		string	Comparator method for this filter
	 *		'empty_action'		string	Action to execute on items without matching results. 'hide'|'disable'
	 *		'postmeta_cache'	array	Cache structure for postmeta filters
	 *		'auxiliar_query_count'	number|bool	Count of the auxiliar query used for counters on empty values
	 * )
	 *
	 * @since 2.4.0
	 */

	static function setup_frontend_dependency_and_counters_data( $attributes ) {
		$attributes = wp_parse_args(
			$attributes,
			array(
				'field'		=> '',
				'type'		=> '',
				'url_param'	=> '',
				'view_settings'	=> array(),
				'format'	=> ''
			)
		);

		$data = array(
			'dependency'	=> 'disabled',
			'counters'		=> 'disabled',
			'use_query_cache'	=> 'disabled',
			'empty_action'	=> 'hide',
			'comparator'	=> 'equal',
			'postmeta_cache'		=> array(),
			'auxiliar_query_count'	=> false
		);

		if (
			empty( $attributes['field'] )
			|| empty( $attributes['type'] )
			|| empty( $attributes['url_param'] )
		) {
			return $data;
		}

		$field = $attributes['field'];
		$type = $attributes['type'];
		$url_param = $attributes['url_param'];
		$view_settings = $attributes['view_settings'];
		$format = $attributes['format'];

		// Normalize type
		switch ( $type ) {
			case 'checkbox':
				$type = 'checkboxes';
				break;
			case 'radio':
				$type = 'radios';
				break;
			case 'multi-select':
			case 'multiselect':
				$type = 'multi_select';
				break;
		}

		// Dependency
		if ( isset( $view_settings['dps'] )
			&& is_array( $view_settings['dps'] )
			&& isset( $view_settings['dps']['enable_dependency'] )
			&& $view_settings['dps']['enable_dependency'] == 'enable' )
		{
			$data['dependency'] = 'enabled';
			$force_disable_dependant = apply_filters( 'wpv_filter_wpv_get_force_disable_dps', false );
			if ( $force_disable_dependant ) {
				$data['dependency'] = 'disabled';
			}
		}

		// Counters
		$data['counters'] = ( strpos( $format, '%%COUNT%%' ) !== false ) ? 'enabled' : 'disabled';

		$data['use_query_cache'] = ( 'enabled' == $data['dependency'] || 'enabled' == $data['counters'] ) ? 'enabled' : 'disabled';

		// Related data
		if ( 'enabled' == $data['use_query_cache'] ) {

			// Empty action
			if ( isset( $view_settings['dps'][ 'empty_' . $type ] ) ) {
				$data['empty_action'] = $view_settings['dps'][ 'empty_' . $type ];
			}

			global $no_parameter_found;

			$original_value = isset( $view_settings[ 'custom-field-' . $field . '_value' ] ) ? $view_settings[ 'custom-field-' . $field . '_value' ] : '';
			$processed_value = wpv_apply_user_functions( $original_value );
			$compare_function = isset( $view_settings[ 'custom-field-' . $field . '_compare' ] ) ? $view_settings[ 'custom-field-' . $field . '_compare' ] : '=';
			$current_value_key = false;

			// Comparator
			// @todo check IN, NOT IN and != compare functions
			if ( $compare_function == 'BETWEEN' ) {
				$original_value_array = array_map( 'trim', explode( ',', $original_value ) );
				$processed_value_array = array_map( 'trim', explode( ',', $processed_value ) );
				$current_value_key = array_search( 'URL_PARAM(' . $url_param . ')', $original_value_array );
				if ( $current_value_key !== false ) {
					$processed_value = isset( $processed_value_array[ $current_value_key ] ) ? $processed_value_array[ $current_value_key ] : $no_parameter_found;
					if ( $current_value_key < 1 ) {
						$data['comparator'] = 'lower-equal-than';
					} else if ( $current_value_key > 0 ) {
						$data['comparator'] = 'greater-equal-than';
					}
				}
			} else if ( $compare_function == '>' ) {
				$data['comparator'] = 'lower-than';
			} else if ( $compare_function == '>=' ) {
				$data['comparator'] = 'lower-equal-than';
			} else if ( $compare_function == '<' ) {
				$data['comparator'] = 'greater-than';
			} else if ( $compare_function == '<=' ) {
				$data['comparator'] = 'greater-equal-than';
			}

			// Cache postmeta_cache
			// Construct $wpv_data_cache['post_meta']
			$wpv_data_cache = array();
			if ( $processed_value == $no_parameter_found ) {
				$wpv_data_cache = WPV_Cache::$stored_cache;
			} else {
				// When there is a selected value, create a pseudo-cache based on all the other filters
				// Note that checkboxes filters can generate nested meta_query entries
				$query = apply_filters( 'wpv_filter_wpv_get_dependant_extended_query_args', array(), $view_settings, array( 'postmeta' => $field ) );
				$aux_cache_query = null;
				if (
					isset( $query['meta_query'] )
					&& is_array( $query['meta_query'] )
				) {
					foreach ( $query['meta_query'] as $qt_index => $qt_val ) {
						if ( is_array( $qt_val ) ) {
							foreach ( $qt_val as $qt_val_key => $qt_val_val ) {
								if (
									$qt_val_key == 'key'
									&& $qt_val_val == $field
								) {
									if ( $compare_function == 'BETWEEN' ) {
										if (
											$qt_val['compare'] == 'BETWEEN'
											&& $current_value_key !== false
										) {
											$qt_val['value'] = isset( $qt_val['value'] ) ? $qt_val['value'] : '';
											$passed_values = is_array( $qt_val['value'] ) ? $qt_val['value'] : array_map( 'trim', explode( ',', $qt_val['value'] ) );
											if ( $current_value_key < 1 && isset( $passed_values[1] ) ) {
												$query['meta_query'][ $qt_index ]['compare'] = '<=';
												$query['meta_query'][ $qt_index ]['value'] = $passed_values[1];
											} else if ( $current_value_key > 0 && isset( $passed_values[0] ) ) {
												$query['meta_query'][ $qt_index ]['compare'] = '>=';
												$query['meta_query'][ $qt_index ]['value'] = $passed_values[0];
											}
										} else {
											unset( $query['meta_query'][ $qt_index ] );
										}
										// if $compare_function is BETWEEN and we have a meta_query not using BETWEEN, we have a partial query here, so keep it
									} else {
										unset( $query['meta_query'][ $qt_index ] );
									}
								} else if (
									is_array( $qt_val_val )
									&& isset( $qt_val_val['key'] )
									&& $qt_val_val['key'] == $field
								) {
									if ( $compare_function == 'BETWEEN' ) {
										if (
											$qt_val_val['compare'] == 'BETWEEN'
											&& $current_value_key !== false
										) {
											$qt_val_val['value'] = isset( $qt_val_val['value'] ) ? $qt_val_val['value'] : '';
											$passed_values = is_array( $qt_val_val['value'] ) ? $qt_val_val['value'] : array_map( 'trim', explode( ',', $qt_val_val['value'] ) );
											if ( $current_value_key < 1 && isset( $passed_values[1] ) ) {
												$query['meta_query'][ $qt_index ][ $qt_val_key ]['compare'] = '<=';
												$query['meta_query'][ $qt_index ][ $qt_val_key ]['value'] = $passed_values[1];
											} else if ( $current_value_key > 0 && isset( $passed_values[0] ) ) {
												$query['meta_query'][ $qt_index ][ $qt_val_key ]['compare'] = '>=';
												$query['meta_query'][ $qt_index ][ $qt_val_key ]['value'] = $passed_values[0];
											}
										} else {
											unset( $query['meta_query'][ $qt_index ][ $qt_val_key ] );
										}
										// if $compare_function is BETWEEN and we have a meta_query not using BETWEEN, we have a partial query here, so keep it
									} else {
										unset( $query['meta_query'][ $qt_index ][ $qt_val_key ] );
									}
								}
							}
						}
					}
				}
				$aux_cache_query = new WP_Query($query);
				if (
					is_array( $aux_cache_query->posts )
					&& ! empty( $aux_cache_query->posts )
				) {
					$data['auxiliar_query_count'] = count( $aux_cache_query->posts );
					$wpv_data_cache = WPV_Cache::generate_cache( $aux_cache_query->posts, array( 'cf' => array( $field ) ) );
				}
			}
			if ( ! isset( $wpv_data_cache['post_meta'] ) ) {
				$wpv_data_cache['post_meta'] = array();
			}
			$data['postmeta_cache'] = $wpv_data_cache['post_meta'];
			// OK, for checkboxes custom fields the stored value is NOT the one we use for filtering
			// So instead of filtering $wpv_data_cache['post_meta'] we will loop it to see if the $toolset_field key exists
			// AND check the serialized value to see if it contains the given real value (warning, not the label!)
			// AND break as soon as true because we need no counters
			// Expensive, but not sure if more than wp_list_filter though
		}

		return $data;
	}

	/**
	 * Register the wpv-control-postmeta shortcode attributes in the shortcodes GUI API.
	 *
	 * @note Some options are different when adding and when editing, mainly for BETWEEN comparisons.
	 *
	 * @since 2.4.0
	 */

	public static function wpv_shortcodes_register_wpv_control_postmeta_data( $views_shortcodes ) {
		$views_shortcodes['wpv-control-postmeta'] = array(
			'callback' => array( 'WPV_Meta_Frontend_Filter', 'wpv_shortcodes_get_wpv_control_postmeta_data' )
		);
		return $views_shortcodes;
	}

	// @todo adjust this!!
	public static function wpv_shortcodes_get_wpv_control_postmeta_data( $parameters = array(), $overrides = array() ) {
		$gui_action = isset( $_GET['gui_action'] ) ? sanitize_text_field( $_GET['gui_action'] ) : 'insert';

		$current_field = false;
		if ( isset( $parameters['attributes']['field'] ) ) {
			$current_field = $parameters['attributes']['field'];
		}
		if ( isset( $overrides['attributes']['field'] ) ) {
			$current_field = $overrides['attributes']['field'];
		}

		$current_field_data = array();
		if ( $current_field ) {
			$current_field_data = wpv_types_get_field_data( $current_field );
		}
		$current_field_type = isset( $current_field_data['type'] ) ? $current_field_data['type'] : 'native';

		$current_type = isset( $overrides['attributes']['value_type'] ) ? $overrides['attributes']['value_type'] : 'CHAR';
		$current_compare = isset( $overrides['attributes']['value_compare'] ) ? $overrides['attributes']['value_compare'] : '=';
		$current_real = isset( $overrides['attributes']['value_real'] ) ? $overrides['attributes']['value_real'] : '';
		// if explode(current_real).length = 2 && current_compare = between,
		// Look for the current URL being passed, find t on the pieces, set the index to know whether in or max,
		// adjust the comparison to BETWEEN LOW or BETWWEN HIGHT (care of NOT *) and fix so not to edit
		// fix the value_type so it can also not be edited
		$value_type_options = array(
			'CHAR'		=> __( 'string', 'wpv-views' ),
			'NUMERIC'	=> __( 'number', 'wpv-views' ),
			'BINARY' => __( 'boolean', 'wpv-views' ),
			'DECIMAL' => __( 'DECIMAL', 'wpv-views' ),
			'DATE' => __( 'DATE', 'wpv-views' ),
			'DATETIME' => __( 'DATETIME', 'wpv-views' ),
			'TIME' => __( 'TIME', 'wpv-views' ),
			'SIGNED' => __( 'SIGNED', 'wpv-views' ),
			'UNSIGNED' => __( 'UNSIGNED', 'wpv-views' ),
		);

		$value_compare_options = array(
			'='			=> __( 'equal to', 'wpv-views' ),
			'!='		=> __( 'different from', 'wpv-views' ),
			'>'			=> __( 'greater than', 'wpv-views' ),
			'>='		=> __( 'greater than or equal to', 'wpv-views' ),
			'<'			=> __( 'lower than', 'wpv-views' ),
			'<='		=> __( 'lower than or equal to', 'wpv-views' ),
			'LIKE'		=> __( 'like', 'wpv-views' ),
			'NOT LIKE'	=> __( 'not like', 'wpv-views' ),
			'BETWEEN'	=> __( 'between', 'wpv-views' ),
			'NOT BETWEEN'	=> __( 'not between', 'wpv-views' ),
		);

		$url_param_label = __( 'URL parameter to use', 'wpv-views');

		$current_real_multi = explode( ',', $current_real );
		if (
			'edit' == $gui_action
			&& count( $current_real_multi ) == 2
			&& (
				'BETWEEN' == $current_compare
				|| 'NOT BETWEEN' == $current_compare
			)
			&& isset( $overrides['attributes']['url_param'] )
			&& ! empty( $overrides['attributes']['url_param'] )
		) {
			$match_index = -1;
			foreach ( $current_real_multi as $current_real_piece_key => $current_real_piece_value ) {
				if ( $current_real_piece_value == 'URL_PARAM(' . $overrides['attributes']['url_param'] . ')' ) {
					$match_index = $current_real_piece_key;
				}
			}
			if ( $match_index !== -1 ) {
				$value_type_options = array(
					$current_type => $value_type_options[ $current_type ]
				);
			}
			if ( $match_index === 0 ) {
				$value_compare_options = array(
					$current_compare . ' LOW' => __( 'between, lower end', 'wpv-views' )
				);
				$url_param_label = __( 'URL parameter to use (lower end)', 'wpv-views' );
			} else if ( $match_index === 1 ) {
				$value_compare_options = array(
					$current_compare . ' HIGH' => __( 'between, higher end', 'wpv-views' )
				);
				$url_param_label = __( 'URL parameter to use (higher end)', 'wpv-views' );
			}
		}

		$data = array(
			'additional_data' => array(),
			'attributes' => array(
				'display-options' => array(
					'label' => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'type_and_source_combo' => array(
							'label'			=> __( 'Type of control', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'type' => array(
									'type'				=> 'select',
									'options'			=> WPV_Meta_Frontend_Filter::get_shortcode_gui_attribute_type_per_field_type( $current_field_type ),
									'grouped_options'	=> WPV_Meta_Frontend_Filter::get_shortcode_gui_attribute_type_grouped_by_field_type( $current_field_type ),
									// Block prop used for filtering the grouped_options
									'options_filter'	=> 'fieldData',
								),
								'source' => array(
									'type'			=> 'select',
									'default'		=> 'database',
									'options'		=> array(
														'database'	=> __( 'Using existing custom field values', 'wpv-views' ),
														'custom'	=> __( 'Using manually entered values', 'wpv-views' )
													),
									'hidden'		=> true,
								),
							),
						),
						'value_combo' => array(
							'label'			=> __( 'Manually entered values', 'wpv-views' ),
							'type'			=> 'callback',
							'callback'		=> array( 'WPV_Meta_Frontend_Filter', 'backend_custom_values_structure' ),
							'hidden'		=> true,
						),
						'compare_and_type_combo' => array(
							'label'			=> __( 'Filter comparison', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'value_type' => array(
									'pseudolabel'			=> __( 'Compare value as a', 'wpv-views' ),
									'type'			=> 'select',
									'default'		=> 'CHAR',
									'options'		=> $value_type_options,
								),
								'value_compare' => array(
									'pseudolabel'			=> __( 'Using this comparison', 'wpv-views' ),
									'type'			=> 'select',
									'default'		=> '=',
									'options'		=> $value_compare_options,
								),
								'two_columns'   => array(
									'pseudolabel' => __( 'Display in two columns', 'wpv-views' ),
									'type'        => 'checkbox',
									'default'     => '',
								),
							),
						),
						'default_label' => array(
							'label'			=> __( 'Dropdown Placeholder', 'wpv-views' ),
							'description'	=> __( 'Visible when no option is chosen.', 'wpv-views' ),
							'type'			=> 'text',
							'default'		=> '',
							'hidden'		=> true,
						),
						'format' => array(
							'label'			=> __( 'Format of the options', 'wpv-views'),
							'type'			=> 'text',
							'placeholder'	=> '%%NAME%%',
							'description'	=> __( 'You can use %%NAME%% or %%COUNT%% as placeholders.', 'wpv-views' ),
							'hidden'		=> true,
						),
						'order' => array(
							'label'			=> __( 'Order of the options', 'wpv-views'),
							'type'			=> 'select',
							'default'		=> 'asc',
							'options'		=> array(
												'asc'	=> __( 'Ascending', 'wpv-views' ),
												'desc'	=> __( 'Descending', 'wpv-views' ),
												'ascnum'	=> __( 'Ascending Numeric', 'wpv-views' ),
												'descnum'	=> __( 'Descending Numeric', 'wpv-views' ),
												'none'	=> __( 'No sorting', 'wpv-views' )
											),
							'hidden'		=> true,
						),
						'date_format' => array(
							'label'			=> __( 'Date format', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Defaults to the one set in the WordPress General settings.', 'wpv-views' ),
							'documentation'	=> '<a href="http://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">' .  __( 'WordPress date formats', 'wpv-views' ) . '</a>',
							'hidden' 		=> true,
						),
						'default_date' => array(
							'label'			=> __( 'Default date', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Date selected in the datepicker by default, as a timestamp. You can also use Toolset functions like NOW() or TODAY().', 'wpv-views' ),
							'documentation'	=> '<a href="https://toolset.com/documentation/user-guides/date-filters/" target="_blank">' .  __( 'Toolset date filters', 'wpv-views' ) . '</a>',
							'hidden'		=> true,
						),
						'title' => array(
							'label'			=> __( 'Checkbox label', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'description'	=> __( 'Label for the checkbox, leave empty to use the Types name if available.', 'wpv-views' ),
							'hidden'		=> true,
						),
						'boundary_label_combo' => array(
							'label'			=> __( 'Labels for the lower and higher ends of the comparison', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'boundary_label_min' => array(
									'pseudolabel'	=> __( 'Label for lower end', 'wpv-views' ),
									'type'			=> 'text',
									'required'		=> true,
									'default'	=> isset( $current_field_data['name'] ) ? $current_field_data['name'] : $current_field,
								),
								'boundary_label_max' => array(
									'pseudolabel'	=> __( 'Label for the higher end', 'wpv-views' ),
									'type'			=> 'text',
									'required'		=> true,
									'default'	=> isset( $current_field_data['name'] ) ? $current_field_data['name'] : $current_field,
								),
							),
							'description'	=> __( 'The filter will use the min and max values set in the boundary label configuration fields.', 'wpv-views' ),
							'hidden'		=> true,
						),
						'placeholder' => array(
							'label'			=> __( 'Placeholder', 'wpv-views'),
							'type'			=> 'text',
							'default'		=> '',
							'required'		=> false
						),
					),
					'fields_logic' => [
						'type_and_source_combo:source' => [
							[
								'attribute' => 'type',
								'include' => [ 'date', 'textfield', 'toolset-default' ],
								'negative' => true,
							],
						],
						'value_combo' => [
							[
								'attribute' => 'source',
								'value' => 'custom',
							],
						],
						'default_label' => [
							[
								'attribute' => 'type',
								'include' => [ 'date', 'textfield', 'multi-select', 'checkboxes' ],
								'negative' => true,
							],
						],
						'format' => [
							[
								'attribute' => 'type',
								'include' => [ 'date', 'textfield' ],
								'negative' => true,
							],
						],
						'order' => [
							[
								'attribute' => 'type',
								'include' => [ 'date', 'textfield' ],
								'negative' => true,
							],
							[
								'attribute' => 'source',
								'value' => 'custom',
								'negative' => true,
							],
						],
						'date_format' => [
							[
								'attribute' => 'type',
								'value' => 'date',
							],
							[
								'attribute' => 'type',
								'value' => '',
								'negative' => true,
							],
						],
						'default_date' => [
							[
								'attribute' => 'type',
								'value' => 'date',
							],
							[
								'attribute' => 'type',
								'value' => '',
								'negative' => true,
							],
						],
						'title' => [
							[
								'attribute' => 'type',
								'value' => 'checkbox',
							],
						],
						'placeholder' => [
							[
								'attribute' => 'type',
								'include' => [ 'date', 'select', 'multi-select', 'radios', 'checkboxes', 'checkbox' ],
								'negative' => true,
							],
						],
					],
				),
				'style-options' => array(
					'label' => __( 'Style options', 'wpv-views' ),
					'header' => __( 'Style options', 'wpv-views' ),
					'fields' => array(
						'output' => array(
							'label'		=> __( 'Output style', 'wpv-views' ),
							'type'		=> 'radio',
							'options'		=> array(
								'bootstrap'	=> __( 'Fully styled output', 'wpv-views' ),
								'legacy'	=> __( 'Raw output', 'wpv-views' ),
							),
							'default'		=> 'bootstrap',
						),
						'input_frontend_combo' => array(
							'label'			=> __( 'Element styling', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'class' => array(
									'pseudolabel'	=> __( 'Element classnames', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Space-separated list of classnames to apply. For example: classone classtwo', 'wpv-views' )
								),
								'style' => array(
									'pseudolabel'	=> __( 'Element inline style', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Raw inline styles to apply. For example: color:red;background:none;', 'wpv-views' )
								),
							),
						),
						'label_frontend_combo' => array(
							'label'			=> __( 'Label styling', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'label_class' => array(
									'pseudolabel'	=> __( 'Label classnames', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Space-separated list of classnames to apply to the labels. For example: classone classtwo', 'wpv-views' )
								),
								'label_style' => array(
									'pseudolabel'	=> __( 'Label inline style', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Raw inline styles to apply to the labels. For example: color:red;background:none;', 'wpv-views' )
								),
							),
						),
					)
				),
				'filter-options' => array(
					'label' => __( 'Filter options', 'wpv-views' ),
					'header' => __( 'Filter options', 'wpv-views' ),
					'fields' => array(
						'url_param' => array(
							'label'			=> $url_param_label,
							'type'			=> 'text',
							'default_force'	=> $current_field ? 'wpv-' . $current_field : 'wpv-field-filter',
							'required'		=> true,
							'description'	=> __( 'The filter will apply the values passed to this URL parameter.', 'wpv-views' )
						),
						'url_param_combo' => array(
							'label'			=> __( 'URL parameter for the lower and higher ends of the comparison', 'wpv-views' ),
							'type'			=> 'grouped',
							'fields'		=> array(
								'url_param_min' => array(
									'pseudolabel'	=> __( 'Lower end', 'wpv-views'),
									'type'			=> 'text',
									'default_force'	=> ( $current_field ? 'wpv-' . $current_field : 'wpv-field-filter' ) . '_min',
									'required'		=> true
								),
								'url_param_max' => array(
									'pseudolabel'	=> __( 'Higher end', 'wpv-views'),
									'type'			=> 'text',
									'default_force'	=> ( $current_field ? 'wpv-' . $current_field : 'wpv-field-filter' ) . '_max',
									'required'		=> true
								),
							),
							'description'	=> __( 'The filter will use the min and max values set in the URL parameter configuration fields.', 'wpv-views' )
						),
					),
				),
			),
		);

		if (
			$current_field_type == 'checkbox'
			&& isset( $current_field_data['data']['save_empty'] )
			&& $current_field_data['data']['save_empty'] == 'yes'
		) {
			$data['attributes']['display-options']['fields']['forze_zero'] = array(
				'label'			=> __( 'Submit value when unchecked', 'wpv-views'),
				'type'			=> 'radio',
				'default'		=> 'false',
				'options'		=> array(
									'false'	=> __( 'Submit nothing when unchecked', 'wpv-views' ),
									'true'	=> __( 'Submit a zero when unchecked', 'wpv-views' ),
								),
			);
		}

		$dialog_label = __( 'Filter by post field', 'wpv-views' );

		if ( isset( $current_field_data['name'] ) ) {
			$dialog_label = sprintf( __( 'Filter by post field %s', 'wpv-views' ), $current_field_data['name'] );
			$data['additional_data']['shortcode_label'] = $current_field_data['name'];
		} else if ( $current_field ) {
			$dialog_label = sprintf( __( 'Filter by post field %s', 'wpv-views' ), $current_field );
			$data['additional_data']['shortcode_label'] = $current_field;
		}

		$data['name']	= $dialog_label;
		$data['label']	= $dialog_label;

		return $data;
	}

	/**
	 * Auxiliar method to get the available filter types per field type on the wpv-control-postmeta shortcode GUI.
	 *
	 * @since 2.4.0
	 */

	static function get_shortcode_gui_attribute_type_per_field_type( $field_type ) {
		$attribute_type_options = array();
		$toolset_field_type = 'toolset-' . $field_type;
		switch ( $field_type ) {
			case 'checkbox':
				$attribute_type_options = array(
					$toolset_field_type	=> __( 'As defined in Types', 'wpv-views' ),
					'textfield'			=> __( 'Text input', 'wp-views' ),
					'checkbox'			=> __( 'Single checkbox', 'wpv-views' )
				);
				break;
			case 'date':
			case 'datepicker':
				$attribute_type_options = array(
					$toolset_field_type	=> __( 'As defined in Types', 'wpv-views' ),
					'textfield'			=> __( 'Text input', 'wp-views' ),
					'date'				=> __( 'Datepicker', 'wpv-views' )
				);
				break;
			case 'native':
				$attribute_type_options = array(
					'textfield'    => __( 'Text input', 'wpv-views' ),
					'select'       => __( 'Select dropdown', 'wpv-views' ),
					'multi-select' => __( 'Select multiple', 'wpv-views' ),
					'radios'       => __( 'Set of radio buttons', 'wpv-views' ),
					'checkboxes'   => __( 'Set of checkboxes', 'wpv-views' ),
					'date'         => __( 'Datepicker', 'wpv-views' )
				);
				break;
			case 'select':
			case 'multi-select':
			case 'radios':
			case 'radio':
			case 'checkboxes':
			case 'textfield':
			default:
				$attribute_type_options = array(
					$toolset_field_type => __( 'As defined in Types', 'wpv-views' ),
					'textfield'         => __( 'Text input', 'wpv-views' ),
					'integer'           => __( 'Numeric input (integers)', 'wpv-views' ),
					'natural'           => __( 'Numeric input (naturals)', 'wpv-views' ),
					'select'            => __( 'Select dropdown', 'wpv-views' ),
					'multi-select'      => __( 'Select multiple', 'wpv-views' ),
					'radios'            => __( 'Set of radio buttons', 'wpv-views' ),
					'checkboxes'        => __( 'Set of checkboxes', 'wpv-views' ),
				);
				break;
		}
		return $attribute_type_options;
	}

	static function get_shortcode_gui_attribute_type_grouped_by_field_type() {
		$types = [ 'checkbox', 'date', 'datepicker', 'native', 'select', 'multi-select', 'radios', 'radio', 'checkboxes', 'textfield', 'default' ];
		$attributes = [];
		foreach ( $types as $type ) {
			$attributes[ $type ] = WPV_Meta_Frontend_Filter::get_shortcode_gui_attribute_type_per_field_type( $type );
		}
		return $attributes;
	}

	/**
	 * Auxiliar method to render the values|display_values table on the wpv-control-postmeta shortcode GUI.
	 *
	 * @since 2.4.0
	 */

	static function backend_custom_values_structure() {
		$output = '<div style="position:relative;overflow:hidden;padding:0 0 10px;">';
		$output .= '<table class="wpv-editable-list js-wpv-frontend-filter-postmeta-custom-options-list">';
		$output .= '<thead>';
		$output .= '<tr>';
		$output .= '<th class="wpv-collapsed-width"></th>';
		$output .= '<th>' . __( 'Value', 'wpv-views' ) . '</th>';
		$output .= '<th>' . __( 'Display value', 'wpv-views' ) . '</th>';
		$output .= '<th class="wpv-collapsed-width"></th>';
		$output .= '</tr>';
		$output .= '</thead>';
		$output .= '<tbody>';

		$output .= '</tbody>';
		$output .= '<tfoot>';
		$output .= '<tr>';
		$output .= '<th></th>';
		$output .= '<th>' . __( 'Value', 'wpv-views' ) . '</th>';
		$output .= '<th>' . __( 'Display value', 'wpv-views' ) . '</th>';
		$output .= '<th></th>';
		$output .= '</tr>';
		$output .= '</rfoot>';
		$output .= '</table>';
		$output .= '<button class="button-secondary wpv-editable-list-item-add js-wpv-frontend-filter-postmeta-custom-options-list-add">'
			. '<i class="fa fa-plus"></i> ' . __( 'Add a new option', 'wpv-views' )
		. '</button>';
		$output .= '</div>';
		return $output;
	}


	/**
	 * If there are several url parameters for that meta, type must be forced to IN or NOT IN
	 *
	 * @param $compare string Comparasion type
	 * @param $filter string Original filter, must be URL_PARAM(...)
	 * @param $view_id int
	 * @param $view_settings array
	 * @return string
	 */
	static public function force_in_comparison_for_multiple_values( $compare, $filter, $view_id, $view_settings ) {
		$url_parameter = preg_replace( '/^URL_PARAM\(([^\)]+)\)$/', '$1', $filter );
		$values = isset( $_GET[ $url_parameter ] ) ? $_GET[ $url_parameter ] : null;

		if ( is_array( $values ) && ! in_array( $compare, [ 'IN', 'NOT IN' ] ) ) {
			if ( $compare === '!=' ) {
				return 'NOT IN';
			}
			return 'IN';
		}
		return $compare;

	}
}

/**
* wpv_get_custom_field_view_params
*
* This might be deprecated, but does not hurt
* Maybe add a _doing_it_wrong call_user_func
*/

function wpv_get_custom_field_view_params( $view_settings ) {
    $pattern = '/VIEW_PARAM\(([^(]*?)\)/siU';
	$results = array();
	foreach ( array_keys( $view_settings ) as $key ) {
		if (
			strpos( $key, 'custom-field-' ) === 0
			&& strpos( $key, '_compare' ) === strlen( $key ) - strlen( '_compare' )
		) {
			$name = substr( $key, 0, strlen( $key ) - strlen( '_compare' ) );
			$name = substr( $name, strlen( 'custom-field-' ) );
			$value = $view_settings[ 'custom-field-' . $name . '_value' ];
		    if ( preg_match_all( $pattern, $value, $matches, PREG_SET_ORDER ) ) {
		        foreach ( $matches as $match ) {
					$results[] = $match[1];
				}
			}
		}
	}
	return $results;
}

