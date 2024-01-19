<?php

/**
* Post type frontend filter
*
* @package Views
*
* @since 2.4.0
*/

WPV_Post_Type_Frontend_Filter::on_load();

/**
 * WPV_Post_Type_Frontend_Filter
 *
 * Views Post Type Filter Frontend Class
 *
 * @since 2.4.0
 */

class WPV_Post_Type_Frontend_Filter {

	static function on_load() {
		// Apply frontend filter by post author
        add_filter( 'wpv_filter_query',										array( 'WPV_Post_Type_Frontend_Filter', 'filter_post_type' ), 10, 3 );
		//add_action( 'wpv_action_apply_archive_query_settings',				array( 'WPV_Post_Type_Frontend_Filter', 'archive_filter_post_author' ), 40, 3 );
		// Auxiliar methods for requirements
		//add_filter( 'wpv_filter_requires_current_page',						array( 'WPV_Post_Type_Frontend_Filter', 'requires_current_page' ), 20, 2 );
		//add_filter( 'wpv_filter_requires_parent_post',						array( 'WPV_Post_Type_Frontend_Filter', 'requires_parent_post' ), 20, 2 );
		add_filter( 'wpv_filter_requires_framework_values',					array( 'WPV_Post_Type_Frontend_Filter', 'requires_framework_values' ), 20, 2 );
		// Auxiliar methods for gathering data
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts',	array( 'WPV_Post_Type_Frontend_Filter', 'shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts',			array( 'WPV_Post_Type_Frontend_Filter', 'url_parameters' ), 10, 2 );

		add_shortcode( 'wpv-control-post-type',								array( 'WPV_Post_Type_Frontend_Filter', 'wpv_shortcode_wpv_control_post_type' ) );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data',					array( 'WPV_Post_Type_Frontend_Filter', 'wpv_shortcodes_register_wpv_control_post_type_data' ) );
	}

	/**
	 * Apply the filter by post type to Views queries, with values coming from the post_type URL parameter.
	 *
	 * @param $query	array
	 * @param $view_settings	array
	 * @param $view_id	int
	 *
	 * @return array
	 *
	 * @since unknown
	 * @since 2.4.0 Moved to a proper method
	 */

	static function filter_post_type( $query, $view_settings, $view_id ) {

		// Backwards compatibility: this is old...

		$post_type = $query['post_type'];
		// See if the post_type is exposed as a url arg.
		if (
			isset( $view_settings['post_type_expose_arg'] )
			&& $view_settings['post_type_expose_arg']
		) {
			if ( $_GET['wpv_post_type'] ) {
				if ( ! is_array( $_GET['wpv_post_type'] ) ) {
					$post_type = array( $_GET['wpv_post_type'] );
				}
				$post_type = array_map( 'sanitize_text_string', $_GET['wpv_post_type'] );
			}
		}
		$query['post_type'] = $post_type;

		return $query;
	}

	/**
	 * Whether the current View requires framework data for the filter by post type.
	 *
	 * @param $state (boolean) The state of this need until this filter is applied
	 * @param $view_settings
	 *
	 * @return $state (boolean)
	 *
	 * @since 2.4.0
	 */

	static function requires_framework_values( $state, $view_settings ) {
		if ( $state ) {
			return $state;
		}
		if (
			isset( $view_settings['post_type_filter']['mode'] )
			&& $view_settings['post_type_filter']['mode'] == 'framework'
		) {
			$state = true;
		}
		return $state;
	}

	/**
	 * Register the filter by post type on the method to get View shortcode attributes.
	 *
	 * @since 2.4.0
	 */

	static function shortcode_attributes( $attributes, $view_settings ) {
		if (
			isset( $view_settings['post_type_filter']['mode'] )
			&& $view_settings['post_type_filter']['mode'] == 'shortcode'
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_type',
				'filter_label'	=> __( 'Post type', 'wpv-views' ),
				'value'			=> $view_settings['post_type_filter']['shortcode'],
				'attribute'		=> $view_settings['post_type_filter']['shortcode'],
				'expected'		=> 'string',
				'placeholder'	=> 'post, page',
				'description'	=> __( 'Please type a comma separated list of post type slugs', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	 * Register the filter by post type on the method to get URL parameters.
	 *
	 * @since 2.4.0
	 */

	static function url_parameters( $attributes, $view_settings ) {
		if (
			isset( $view_settings['post_type_filter']['mode'] )
			&& $view_settings['post_type_filter']['mode'] == 'url'
		) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_type',
				'filter_label'	=> __( 'Post type', 'wpv-views' ),
				'value'			=> $view_settings['post_type_filter']['url'],
				'attribute'		=> $view_settings['post_type_filter']['url'],
				'expected'		=> 'string',
				'placeholder'	=> 'post, page',
				'description'	=> __( 'Please type a comma separated list of post type slugs', 'wpv-views' )
			);
		}
		return $attributes;
	}

	/**
	 * Callback to display the custom search filter by post type.
	 *
	 * @param $atts array
	 *
	 * @note WIP
	 *
	 * @since 2.4.0
	 */

	static function wpv_shortcode_wpv_control_post_type( $atts ) {

		return '';
	}

	/**
	 * Register the wpv-control-post-type shortcode attributes in the shortcodes GUI API.
	 *
	 * @note WIP
	 *
	 * @since 2.4.0
	 */

	static function wpv_shortcodes_register_wpv_control_post_type_data( $views_shortcodes ) {
		$views_shortcodes['wpv-control-post-type'] = array(
			'callback' => array( 'WPV_Post_Type_Frontend_Filter', 'wpv_shortcodes_get_wpv_control_post_type_data' )
		);
		return $views_shortcodes;
	}

	static function wpv_shortcodes_get_wpv_control_post_type_data() {
		$data = array(
			'name' => __( 'Filter by post type', 'wpv-views' ),
			'label' => __( 'Filter by post type', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label' => __('Display', 'wpv-views'),
					'header' => __('Display', 'wpv-views'),
					'fields' => array(
						'type' => array(
							'label'			=> __( 'Type of control', 'wpv-views'),
							'type'			=> 'select',
							'options'		=> array(
												'select'	=> __( 'Select dropdown', 'wpv-views' ),
												'radio'		=> __( 'Set of radio buttons', 'wpv-views' ),
											),
							'description' 	=> __( 'Type of control to display.', 'wpv-views' ),
							'default_force' => 'select'
						),
						/*
						'format' => array(
							'label'			=> __( 'Format', 'wpv-views'),
							'type'			=> 'text',
							'default_force'	=> '%%DISPLAY_NAME%%',
							'description'	=> __( 'Watch this format.', 'wpv-views' ),
							'required'		=> true
						),
						*/
					),
				),
				'filter-options' => array(
					'label' => __( 'Filter options', 'wpv-views' ),
					'header' => __( 'Filter options', 'wpv-views' ),
					'fields' => array(
						'url_param' => array(
							'label'			=> __( 'URL parameter to use', 'wpv-views'),
							'type'			=> 'text',
							'default_force'	=> 'post-type-filter',
							'description'	=> __( 'Watch this URL parameter.', 'wpv-views' ),
							'required'		=> true
						),
					)
				),
			),
		);
		return $data;
	}

}
