<?php

namespace OTGS\Toolset\Views\Services;

/**
 * Class ViewStructureConvertor
 * Provides redux (front-end) view settings structure conversion
 * to structure which is currently supported on the backend.
 * @package OTGS\Toolset\Views\Services
 */
class ViewStructureConvertor {
	protected $data = null;

	/**
	 * ViewStructureConvertor constructor.
	 * @param array $data Assoc array of data received from frontend
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}

	/**
	 * Returns value from $data assoc array
	 * Source data location is provided as $src_path which uses / as nested level separator,
	 * this means if we have a/b/c, function will access $data['a']['b']['c']
	 * $default is used if $src_path is not set
	 *
	 * @param string $src_path Path in the $data array
	 * @param mixed  $default Default value
	 *
	 * @return mixed
	 */
	protected function get_value( $src_path, $default ) {
		if ( empty( $src_path ) ) {
			return $default;
		}

		$data = $this->data;

		$source = explode( '/', $src_path );
		$value = toolset_getnest( $data, $source, $default );
		if ( ! empty( $value ) || $value == '0' ) {
			return $value;
		}

		return $default;
	}

	/**
	 * Mappings for view settings
	 */
	protected $view_mappings = array(
		'filter_meta_html' => 'loop/filter_template',
		'view_description' => 'general/description',
		'view_purpose' => 'screen_options/purpose',
//		'filter_meta_html' => 'pagination/template',
		//content selection
		'query_type' => 'content_selection/query_type',
		'post_type' => 'content_selection/post_type',
		'taxonomy_type' => 'content_selection/taxonomy_type',
		'roles_type' => 'content_selection/roles_type',
		'post_type_dont_include_current_page' => 'content_selection/post_type_dont_include_current_page',
		'taxonomy_hide_empty' => 'content_selection/taxonomy_hide_empty',
		'taxonomy_include_non_empty_decendants' => 'content_selection/taxonomy_include_non_empty_decendants',
		'taxonomy_pad_counts' => 'content_selection/taxonomy_pad_counts',
		// ordering
		'orderby' => 'ordering/first',
		'order' => 'ordering/first_dir',
		'orderby_as' => 'ordering/first_as',
		'orderby_second' => 'ordering/second',
		'order_second' => 'ordering/second_dir',
		'taxonomy_orderby' => 'ordering/taxonomy',
		'taxonomy_order' => 'ordering/taxonomy_dir',
		'users_orderby' => 'ordering/users',
		'users_order' => 'ordering/users_dir',
		// limit and offset attributes
		'offset' => 'limit_offset/offset',
		'limit' => 'limit_offset/limit',
		'taxonomy_offset' => 'limit_offset/taxonomy_offset',
		'taxonomy_limit' => 'limit_offset/taxonomy_limit',
		'users_offset' => 'limit_offset/users_offset',
		'users_limit' => 'limit_offset/users_limit',
		// custom js and css
		'layout_meta_html_css' => 'loop/custom_css',
		'layout_meta_html_js' => 'loop/custom_js',
		'woocommerce_pagination_enabled' => 'woocommerceOptions/woocommerce_pagination_enabled',
		'woocommerce_sorting_enabled' => 'woocommerceOptions/woocommerce_sorting_enabled'
	);

	/**
	 * Mappings for pagination settings
	 */
	protected $view_pagination_mappings = array(
		'type' => 'pagination/pagination_type',
		'posts_per_page' => 'pagination/page_size',
		'effect' => 'pagination/pagination_effect',
		'duration' => 'pagination/pagination_effect_duration',
		'speed' => 'pagination/new_page_interval',
		'preload_images' => 'pagination/preload_images',
		'cache_pages' => 'pagination/cache_pages',
		'preload_pages' => 'pagination/preload_prev_next',
		'pre_reach' => 'pagination/pages_to_preload',
		'spinner' => 'pagination/spinner_type',
		'spinner_image' => 'pagination/views_spinner_type',
		'callback_next' => 'pagination/js_callback',
		'pause_on_hover' => 'pagination/pause_on_hover',
		'manage_history' => 'pagination/browser_history_change',
		'spinner_image_uploaded' => 'pagination/spinner_image_uploaded'
	);

	protected $screen_settings_mappings = array(
		'content-selection' => 'screen_options/query_show_content_selection',
		'query-options' => 'screen_options/query_show_query_options',
		'ordering' => 'screen_options/query_show_ordering',
		'limit-offset' => 'screen_options/query_show_limit_and_offset',
		'content-filter' => 'screen_options/query_show_query_filter',
		'pagination' => 'screen_options/filter_show_pagination',
		'filter-extra-parametric' => 'screen_options/filter_show_custom_search',
		'filter-extra' => 'screen_options/filter_show_search_and_pagination',
		'layout-extra' => 'screen_options/loop_show_loop_editor',
		'content' => 'screen_options/loop_show_output_editor'
	);

	/**
	 * Mappings for loop settings
	 */
	protected $loop_mappings = array(
		'layout_meta_html' => 'loop/loop_template',
		'included_ct_ids' => '',
		'style' => 'loop/loop_type',
		'table_cols' => 'loop/number_of_columns',
		'bootstrap_grid_cols' => 'loop/number_of_columns',
		'bootstrap_grid_container' => 'loop/add_container',
		'bootstrap_grid_row_class' => 'loop/add_row_class',
		'bootstrap_grid_individual' => 'loop/html_structure',
		'include_field_names' => '',
		'list_separator' => 'loop/list_separator'
	);

	/**
	 * Converts loop settings
	 * @return array
	 */
	public function convert_loop_for_backend() {
		$result = wpv_view_default_layout_settings($this->data['screen_options']['purpose']);
		foreach ($this->loop_mappings as $key => $value) {
		    $v = empty($result[$key]) ? '' : $result[$key];
			$result[$key] = $this->get_value($value, $v);
		}
		//add fields and real_fields later
		return $result;
	}

	/**
	 * Converts view settings
	 * @return array
	 */
	public function convert_view_for_backend() {
		$result = wpv_view_default_settings($this->data['screen_options']['purpose']);
		$result['post_type'] = array();
		// all attributes
		foreach ($this->view_mappings as $key => $value) {
			$result[$key] = $this->get_value($value, $result[$key]);
		}
		// pagination attributes, they're nested array, so we need to convert them separately
		if (!$this->data['pagination']['enable_pagination']) {
			$result['pagination']['type'] = 'disabled';
		}
		else {
			if (isset($this->data['pagination']['browser_history_change']) && $this->data['pagination']['browser_history_change']) {
				$this->data['pagination']['browser_history_change'] = 'on';
			}
			foreach ($this->view_pagination_mappings as $key => $value) {
				$result['pagination'][$key] = isset( $result['pagination'][ $key ] ) ?
					$this->get_value($value, $result['pagination'][$key]) :
					null;
			}
		}
		$result['query_type'] = array($result['query_type']);
		// screen settings
		$result['sections-show-hide'] = array();
		foreach ($this->screen_settings_mappings as $key => $value) {
			$result['sections-show-hide'][$key] = ($this->get_value($value, false) ? 'on' : 'off');
		}
		$result['dps'] = $this->data['custom_search'];

		/**
		 * Filters the View settings after they have been converted for the backend.
		 *
		 * @param array $rest The View settings after the conversion.
		 * @param array $data The View settings as they come from the editor.
		 *
		 * @return array
		 */
		return apply_filters( 'wpv_filter_converted_view_settings_for_backend', $result, $this->data );
	}
}
