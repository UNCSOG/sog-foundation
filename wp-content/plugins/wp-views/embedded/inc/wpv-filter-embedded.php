<?php

/**
 * Frontend filter form
 *
 * @package Views
 *
 * @since 2.3.2
 */

/**
 * WPV_Frontend_Filter
 *
 * Views Frontend Filter Form Class
 *
 * @since 2.3.2
 */

class WPV_Frontend_Filter {

	public function __construct() {

		add_action( 'after_setup_theme', array( $this, 'before_init' ), 999 );

		/**
		 * API
		 */
		add_filter( 'wpv_filter_wpv_is_form_required', array( $this, 'is_form_required' ) );

		/**
		 * Structural shortcodes
		 */
		//add_shortcode( 'wpv-filter-start',					array( 'WPV_Frontend_Filter', 'wpv_filter_shortcode_start' ) );
		add_shortcode( 'wpv-filter-end', 		array( $this, 'wpv_filter_end_shortcode_callback' ) );

		/**
		 * Form items shortcodes
		 */
		add_filter( 'wpv_filter_wpv_get_form_items_shortcodes', array( $this, 'get_form_items_shortcodes' ) );
		add_filter( 'wpv_filter_wpv_get_form_filters_shortcodes', array( $this, 'get_form_filters_shortcodes' ) );

		add_shortcode( 'wpv-filter-submit',		array( $this, 'wpv_filter_submit_shortcode_callback' ) );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data', array( $this, 'wpv_filter_submit_shortcode_register_gui_data' ) );

		add_shortcode( 'wpv-filter-spinner',	array( $this, 'wpv_filter_spinner_shortcode_callback' ) );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data', array( $this, 'wpv_filter_spinner_shortcode_register_gui_data' ) );

		add_shortcode( 'wpv-filter-reset',		array( $this, 'wpv_filter_reset_shortcode_callback' ) );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data', array( $this, 'wpv_filter_reset_shortcode_register_gui_data' ) );

		add_filter( 'wpv_filter_wpv_has_default_filter_editor_content', array( $this, 'has_default_filter_editor_content' ), 10, 3 );

		add_filter( 'wpv_filter_object_settings_for_fake_url_query_filters', array( $this, 'fake_url_query_filters' ) );
	}

	/**
	 * Register the walker classes on demand, using the Toolset_Common_Autoloader.
	 *
	 * @since 2.4.0
	 */
	public function before_init() {
		// Register autoloaded classes.
		$autoloader = Toolset_Common_Autoloader::get_instance();
		$walker_autoload_path = WPV_PATH_EMBEDDED . '/inc/walkers';
		$autoloader->register_classmap(
			array(
				'WPV_Walker_Control_Base'				=> "$walker_autoload_path/wpv_walker_control_base.class.php",
				'WPV_Walker_Taxonomy_Select'			=> "$walker_autoload_path/wpv_walker_taxonomy_select.class.php",
				'WPV_Walker_Taxonomy_Radios'			=> "$walker_autoload_path/wpv_walker_taxonomy_radios.class.php",
				'WPV_Walker_Taxonomy_Checkboxes'		=> "$walker_autoload_path/wpv_walker_taxonomy_checkboxes.class.php",
				'WPV_Walker_Postmeta_Select'			=> "$walker_autoload_path/wpv_walker_postmeta_select.class.php",
				'WPV_Walker_Postmeta_Radios'			=> "$walker_autoload_path/wpv_walker_postmeta_radios.class.php",
				'WPV_Walker_Postmeta_Checkboxes'		=> "$walker_autoload_path/wpv_walker_postmeta_checkboxes.class.php",
				'WPV_Walker_Post_Relationship_Select'	=> "$walker_autoload_path/wpv_walker_post_relationship_select.class.php",
				'WPV_Walker_Post_Relationship_Radios'	=> "$walker_autoload_path/wpv_walker_post_relationship_radios.class.php",
				'WPV_Walker_Post_Relationship_Checkboxes' => "$walker_autoload_path/wpv_walker_post_relationship_checkboxes.class.php",
			)
		);
	}

	/**
	 * API method to check whether the current View does require its form to be rendered.
	 *
	 * @since 2.3.2
	 *
	 * @todo Check whether we can switch preg_match with simple string search.
	 */

	public function is_form_required( $required = false ) {

		if ( apply_filters( 'wpv_filter_wpv_is_rendering_form_view', false ) ) {
			return true;
		}

		// Check if the View wrapper DIV (and the filter FORM along with the pagination) is required.
		/** This filter is documented in embedded/inc/wpv-layout-embedded.php */
		if ( ! apply_filters( 'wpv_filter_wpv_is_wrapper_div_required', true ) ) {
			return false;
		}

		$view_settings			= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$view_layout_settings	= apply_filters( 'wpv_filter_wpv_get_object_layout_settings', array() );

		// Table sorting
		if (
			isset( $view_layout_settings['style'] )
			&& $view_layout_settings['style'] == 'table_of_fields'
		) {
			return true;
		}

		// Pagination
		if (
			isset( $view_settings['pagination']['type'] )
			&& ( ! in_array( $view_settings['pagination']['type'], array( 'disabled' ) ) )
		) {
			return true;
		}

		$view_id		  = apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$filter_meta_html = isset( $view_settings['filter_meta_html'] ) ? $view_settings['filter_meta_html'] : '';
		$layout_meta_html = isset( $view_layout_settings['layout_meta_html'] ) ? $view_layout_settings['layout_meta_html'] : '';
		$view_post_content = \WP_Post::get_instance( $view_id )->post_content;

		// Contains a parametric search with filters
		if ( preg_match('#\\[wpv-control.*?\\]#is', $filter_meta_html, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}
		// Check view post content for case of Gutenberg View editor
		if ( preg_match('#\\[wpv-control.*?\\]#is', $view_post_content, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}
		// Contains a parametric search with text search filter
		if ( preg_match('#\\[wpv-filter-search-box.*?\\]#is', $filter_meta_html, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}
		// Check view post content for case of Gutenberg View editor
		if ( preg_match('#\\[wpv-filter-search-box.*?\\]#is', $view_post_content, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}

		// Contains sorting on the form or the layout output
		if ( preg_match('#\\[wpv-sort.*?\\]#is', $filter_meta_html, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}
		if ( preg_match('#\\[wpv-sort.*?\\]#is', $layout_meta_html, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}
		// Translatable sorting block contains divs.wpv-shortcode-order*.
		// They're replaced with shortcodes on block render.
		// Need to check for their existance also.
		if ( preg_match('/class\=[\"|\']wp\-block\-toolset\-views\-sorting/', $layout_meta_html, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}
		$view = \WPV_View::get_instance( $view_id );
		if ( preg_match('#\\[wpv-sort.*?\\]#is', $view->content, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}

		// Contains table header links for sorting
		if ( preg_match('#\\[wpv-heading.*?\\]#is', $layout_meta_html, $matches ) ) {
			if ( $matches[0] != '' ) {
				return true;
			}
		}

		// Has a search query filter
		if (
			isset( $view_settings['post_search_value'] )
			|| isset( $view_settings['taxonomy_search_value'] )
		) {
			return true;
		}

		return false;
	}

	public function wpv_filter_end_shortcode_callback( $atts ) {

		$view_id			= apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_settings		= apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
		$is_required		= $this->is_form_required();
		$out				= '';

	    if ( $is_required ) {
	        extract(
	            shortcode_atts( array(), $atts )
	        );
	        $out = '</form>';

		}

		/**
		 * Filter wpv_filter_end_filter_form
		 *
		 * This can be useful to create additional inputs for the current form without needing to add them to the Filter HTML textarea
		 *
		 * @param string			$out 			The default form closing tag
		 * @param array 			$view_settings 	The current View settings
		 * @param integer 		$view_id 		The ID of the View being displayed
		 * @param bool 			$is_required 	Whether this View requires a form to be displayed (has a parametric search OR uses table sorting OR uses pagination)
		 *
		 * @return $out
		 *
		 * @since 1.5.1
		 */

		$out = apply_filters( 'wpv_filter_end_filter_form', $out, $view_settings, $view_id, $is_required );
		do_action( 'wpv_action_wpv_force_disable_dps', false );
	    return $out;
	}

	public function get_form_items_shortcodes( $form_items_shortcodes = array() ) {
		$form_items_shortcodes[] = 'wpv-filter-submit';
		$form_items_shortcodes[] = 'wpv-filter-spinner';
		$form_items_shortcodes[] = 'wpv-filter-reset';
		return $form_items_shortcodes;
	}

	public function get_form_filters_shortcodes( $form_filters_shortcodes = array() ) {
		$form_filters_shortcodes = apply_filters( 'wpv_filter_wpv_register_form_filters_shortcodes', $form_filters_shortcodes );
		return $form_filters_shortcodes;
	}


	/**
	 * @param $atts
	 *
	 * @return string|void
	 */
	public function wpv_filter_submit_shortcode_callback( $atts ) {

		if ( ! $this->is_form_required() ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'name'		=> __( 'Submit', 'wpv-views' ),
				'type'		=> 'input',
				'class'		=> '',
				'style'		=> '',
				'output'	=> 'legacy',
				'hide'		=> '',
				'is_translated' => '0',
			),
			$atts
		);

		$view_id    = apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_name  = get_post_field( 'post_name', $view_id );
		$item_value = $atts['is_translated'] == '0' ?
			wpv_translate( 'submit_name', $atts['name'], false, 'View ' . $view_name ) :
			$atts['name'];

		$item_attributes = array(
			'type'		=> 'submit',
			'class'		=> ( empty( $atts['class'] ) ) ? array() : explode( ' ', $atts['class'] ),
			'style'		=> $atts['style']
		);

		if ( 'true' == $atts['hide'] ) {
			$item_attributes['style'] .= 'display:none;';
		}

		$item_attributes['class'][] = 'wpv-submit-trigger js-wpv-submit-trigger';
		if ( 'bootstrap' == $atts['output'] ) {
			$item_attributes['class'][] = 'btn';
		}

		$out = '';

		switch ( $atts['type'] ) {
			case 'button':
				$out .= '<button';
				foreach ( $item_attributes as $att_key => $att_value ) {
					if (
						in_array( $att_key, array( 'style', 'class' ) )
						&& empty( $att_value )
					) {
						continue;
					}
					$out .= ' ' . $att_key . '="';
					if ( is_array( $att_value ) ) {
						$att_value = array_unique( $att_value );
						$att_real_value = implode( ' ', $att_value );
						$out .= $att_real_value;
					} else {
						$out .= $att_value;
					}
					$out .= '"';
				}
				$out .= '>';
				$out .= $item_value;
				$out .= '</button>';

				break;
			case 'input':
			default:
				$item_attributes['name'] = 'wpv_filter_submit';
				$item_attributes['value'] = esc_attr( $item_value );
				$out .= '<input';
				foreach ( $item_attributes as $att_key => $att_value ) {
					if (
						in_array( $att_key, array( 'style', 'class' ) )
						&& empty( $att_value )
					) {
						continue;
					}
					$out .= ' ' . $att_key . '="';
					if ( is_array( $att_value ) ) {
						$att_value = array_unique( $att_value );
						$att_real_value = implode( ' ', $att_value );
						$out .= $att_real_value;
					} else {
						$out .= $att_value;
					}
					$out .= '"';
				}
				$out .= ' />';
				break;
		}

		return $out;
	}

	public function wpv_filter_submit_shortcode_register_gui_data( $views_shortcodes ) {
		$views_shortcodes['wpv-filter-submit'] = array(
			'callback' => array( $this, 'wpv_filter_submit_shortcode_get_gui_data' )
		);
		return $views_shortcodes;
	}

	public function wpv_filter_submit_shortcode_get_gui_data( $parameters = array(), $overrides = array() ) {
		$data = array(
			'attributes' => array(
				'display-options' => array(
					'label'		=> __( 'Display options', 'wpv-views' ),
					'header'	=> __( 'Display options', 'wpv-views' ),
					'fields'	=> array(
						'name_type_combo' => array(
							'label'		=> __( 'Element output', 'wpv-views' ),
							'type'		=> 'grouped',
							'fields'	=> array(
								'name' => array(
									'pseudolabel'	=> __( 'Element label', 'wpv-views'),
									'type'			=> 'text',
									'default'		=> __( 'Submit', 'wpv-views' )
								),
								'type' => array(
									'pseudolabel'	=> __( 'Element HTML tag ', 'wpv-views'),
									'type'			=> 'select',
									'options'		=> array(
										'input'		=> __( 'Input', 'wpv-views' ),
										'button'	=> __( 'Button', 'wpv-views' ),
									),
									'default' 	=> 'input'
								),
							),
						),
						'output' => array(
							'label'		=> __( 'Output style', 'wpv-views' ),
							'type'		=> 'radio',
							'options'	=> array(
								'bootstrap'	=> __( 'Styled submit button', 'wpv-views' ),
								'legacy'	=> __( 'Raw submit button', 'wpv-views' )
							),
							'default_force'	=> 'bootstrap'
						),
						'class_style_combo' => array(
							'label'		=> __( 'Element styling', 'wpv-views' ),
							'type'		=> 'grouped',
							'fields'	=> array(
								'class' => array(
									'pseudolabel'	=> __( 'Button classnames', 'wpv-views'),
									'type'			=> 'text',
									'default_force' => 'btn-secondary',
									'description'	=> __( 'Space-separated list of classnames to apply. For example: classone classtwo', 'wpv-views' )
								),
								'style' => array(
									'pseudolabel'	=> __( 'Button style', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Raw inline styles to apply. For example: color:red;background:none;', 'wpv-views' )
								),
							),
						),
					),
				),
			),
		);

		$dialog_label = __( 'Submit button for this custom search', 'wpv-views' );

		$data['name']	= $dialog_label;
		$data['label']	= $dialog_label;

		return $data;
	}


	/**
	 * Shortcode to display a spinner on parametric search with AJAXed results on the fly
	 *
	 * @param $atts (array) optios for this shortcode
	 *    'container' => HTML tag to be used
	 *    'class' => additional classnames to be used
	 *    'position' => <before> <after> <none> where to add the spinner relative to the $value
	 *     'spinner' => URL of the spinner to be used
	 * @param $value (string) text to be wrapped inside the container
	 *
	 * @return string
	 */
	public function wpv_filter_spinner_shortcode_callback( $atts, $value ) {
		$atts = shortcode_atts(array(
			'container' => 'span',
			'class' => '',
			'position' => 'before',
			'spinner' => '',
			'style' => ''
		), $atts);

		$container = toolset_getarr( $atts, 'container' );
		$class = toolset_getarr( $atts, 'class' );
		$position = toolset_getarr( $atts, 'position' );
		$spinner = toolset_getarr( $atts, 'spinner' );
		$style = toolset_getarr( $atts, 'style' );

		if (
			empty( $spinner )
			&& ! empty( $position )
			&& $position != 'none'
		) {
			// Keep the spinner coming from the View settings for backward compatibility
			$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
			if (
				isset( $view_settings['dps'] )
				&& isset( $view_settings['dps']['spinner'] )
				&& $view_settings['dps']['spinner'] != 'none'
			) {
				if ( $view_settings['dps']['spinner'] == 'custom' ) {
					if ( isset( $view_settings['dps']['spinner_image_uploaded'] ) ) {
						$spinner = $view_settings['dps']['spinner_image_uploaded'];
					}
				} else if ( $view_settings['dps']['spinner'] == 'inhouse' ) {
					if ( isset( $view_settings['dps']['spinner_image'] ) ) {
						$spinner = $view_settings['dps']['spinner_image'];
					}
				}
			}
		}

		// $spinner_image might need to get SSL adjusted
		if ( is_ssl() ) {
			$spinner = str_replace( 'http://', 'https://', $spinner );
		} else {
			$spinner = str_replace( 'https://', 'http://', $spinner );
		}

		if ( ! empty( $style ) ) {
			$style = '; '. esc_attr( $style );
		}

		$return = '<' . $container . ' style="display:none'. $style .'" class="js-wpv-dps-spinner';
		if ( !empty( $class ) ) {
			$return .= ' ' . $class;
		}
		$return .= '">';
		if ( ! empty( $position ) && ! empty( $spinner ) && $position == 'before' ) {
			$return .= '<img src="' . $spinner . '" />';
		}
		$return .= wpv_do_shortcode( $value );
		if ( ! empty( $position ) && ! empty( $spinner ) && $position == 'after' ) {
			$return .= '<img src="' . $spinner . '" />';
		}
		$return .= '</' . $container . '>';
		return $return;
	}

	public function wpv_filter_spinner_shortcode_register_gui_data( $views_shortcodes ) {
		$views_shortcodes['wpv-filter-spinner'] = array(
			'callback' => array( $this, 'wpv_filter_spinner_shortcode_get_gui_data' )
		);
		return $views_shortcodes;
	}

	public function wpv_filter_spinner_shortcode_get_gui_data( $parameters = array(), $overrides = array() ) {

		$available_spinners = array();
		$spinner_options = array();
		$spinner_default = '';
		$available_spinners = apply_filters( 'wpv_admin_available_spinners', $available_spinners );
		foreach ( $available_spinners as $av_spinner ) {
			$spinner_default = empty( $spinner_default ) ? esc_url( $av_spinner['url'] ) : $spinner_default;
			$av_spinner_option = esc_url( $av_spinner['url'] );
			$spinner_options[ $av_spinner_option ] = '<img'
				. ' src="' . $av_spinner_option . '"'
				. ' title="' . esc_attr( $av_spinner['title'] ) . '"'
				. ' />';
		};

		$data = array(
			'attributes' => array(
				'display-options' => array(
					'label' => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'container_position_combo' => array(
							'label'		=> __( 'Container and spinner', 'wpv-views' ),
							'type'		=> 'grouped',
							'fields'	=> array(
								'container' => array(
									'pseudolabel'	=> __( 'Container type', 'wpv-views' ),
									'type'			=> 'select',
									'options'		=> array(
										'div'	=> __( 'Division', 'wpv-views' ),
										'p'		=> __( 'Paragraph', 'wpv-views' ),
										'span'	=> __( 'Span', 'wpv-views' ),
									),
									'default'		=> 'div',
									'description'	=> __( 'You can display your spinner inside different kind of HTML elements', 'wpv-views' )
								),
								'position' => array(
									'pseudolabel'	=> __( 'Spinner placement ', 'wpv-views' ),
									'type'			=> 'select',
									'options'		=> array(
										'none'		=> __( 'Do not show the spinner', 'wpv-views' ),
										'before'	=> __( 'Before the text', 'wpv-views' ),
										'after'		=> __( 'After the text', 'wpv-views' ),
									),
									'default'		=> 'before',
									'description'	=> __( 'Whether the spinner should be added at the beginning or the end of the container', 'wpv-views' )
								),
							)
						),
						'spinner' => array(
							'label'		=> __( 'Spinner image', 'wpv-views' ),
							'type'		=> 'radiohtml',
							'class'		=> 'wpv-mightlong-list',
							'options'	=> $spinner_options,
							'default_force'	=> $spinner_default,
						),
						'class_style_combo' => array(
							'label'		=> __( 'Element styling', 'wpv-views' ),
							'type'		=> 'grouped',
							'fields'	=> array(
								'class' => array(
									'pseudolabel'	=> __( 'Container classnames', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Space-separated list of classnames to apply. For example: classone classtwo', 'wpv-views' )
								),
								'style' => array(
									'pseudolabel'	=> __( 'Container style', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Raw inline styles to apply. For example: color:red;background:none;', 'wpv-views' )
								),
							),
						),
					),
					'content' => array(
						'label'			=> __( 'Container text', 'wpv-views' ),
						'type'			=> 'textarea',
						'description'	=> __( 'This will be shown inside the container and along with the spinner', 'wpv-views' )
					)
				),
			),
		);

		$dialog_label = __( 'Spinner container for this custom search', 'wpv-views' );

		$data['name']	= $dialog_label;
		$data['label']	= $dialog_label;

		return $data;
	}


	/**
	 * Shortcode to display a reset button on parametric search
	 *
	 * @param $atts (array) optios for this shortcode
	 *    'name' => __('Reset', 'wpv-views')
	 *    'class' => additional classnames to be used
	 *    'type' => HTML tag to use, input|button
	 *
	 * @return string|void
	 */
	public function wpv_filter_reset_shortcode_callback( $atts ) {

		if ( ! $this->is_form_required() ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'reset_label' => __( 'Reset', 'wpv-views' ),
				'type'		=> 'input',
				'class'		=> '',
				'style'		=> '',
				'output'	=> 'legacy',
				'hide'		=> '',
				'is_translated' => '0',
			),
			$atts
		);

		$view_id    = apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_name  = get_post_field( 'post_name', $view_id );
		$item_value = $atts['is_translated'] == '0' ?
			wpv_translate( 'button_reset_label', $atts['reset_label'], false, 'View ' . $view_name ) :
			$atts['reset_label'];

		$item_attributes = array(
			'type'		=> 'button',
			'class'		=> ( empty( $atts['class'] ) ) ? array() : explode( ' ', $atts['class'] ),
			'style'		=> $atts['style']
		);

		if ( 'true' == $atts['hide'] ) {
			$item_attributes['style'] .= 'display:none;';
		}

		$item_attributes['class'][] = 'wpv-reset-trigger js-wpv-reset-trigger';
		if ( 'bootstrap' == $atts['output'] ) {
			$item_attributes['class'][] = 'btn';
		}

		$out = '';

		switch ( $atts['type'] ) {
			case 'button':
				$out .= '<button';
				foreach ( $item_attributes as $att_key => $att_value ) {
					if (
						in_array( $att_key, array( 'style', 'class' ) )
						&& empty( $att_value )
					) {
						continue;
					}
					$out .= ' ' . $att_key . '="';
					if ( is_array( $att_value ) ) {
						$att_value = array_unique( $att_value );
						$att_real_value = implode( ' ', $att_value );
						$out .= $att_real_value;
					} else {
						$out .= $att_value;
					}
					$out .= '"';
				}
				$out .= '>';
				$out .= $item_value;
				$out .= '</button>';

				break;
			case 'anchor':
				$out .= '<a href="#"';
				if ( 'bootstrap' == $atts['output'] ) {
					$item_attributes['role'] = 'button';
				}
				foreach ( $item_attributes as $att_key => $att_value ) {
					if (
						in_array( $att_key, array( 'style', 'class' ) )
						&& empty( $att_value )
					) {
						continue;
					}
					$out .= ' ' . $att_key . '="';
					if ( is_array( $att_value ) ) {
						$att_value = array_unique( $att_value );
						$att_real_value = implode( ' ', $att_value );
						$out .= $att_real_value;
					} else {
						$out .= $att_value;
					}
					$out .= '"';
				}
				$out .= '>';
				$out .= $item_value;
				$out .= '</a>';
				break;
			case 'input':
			default:
				$item_attributes['name'] = 'wpv_filter_reset';
				$item_attributes['value'] = esc_attr( $item_value );
				$out .= '<input';
				foreach ( $item_attributes as $att_key => $att_value ) {
					if (
						in_array( $att_key, array( 'style', 'class' ) )
						&& empty( $att_value )
					) {
						continue;
					}
					$out .= ' ' . $att_key . '="';
					if ( is_array( $att_value ) ) {
						$att_value = array_unique( $att_value );
						$att_value = array_unique( $att_value );
						$att_real_value = implode( ' ', $att_value );
						$out .= $att_real_value;
					} else {
						$out .= $att_value;
					}
					$out .= '"';
				}
				$out .= ' />';
				break;
		}

		return $out;

	}

	public function wpv_filter_reset_shortcode_register_gui_data( $views_shortcodes ) {
		$views_shortcodes['wpv-filter-reset'] = array(
			'callback' => array( $this, 'wpv_filter_reset_shortcode_get_gui_data' )
		);
		return $views_shortcodes;
	}

	public function wpv_filter_reset_shortcode_get_gui_data( $parameters = array(), $overrides = array() ) {
		$data = array(
			'attributes' => array(
				'display-options' => array(
					'label' => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'name_type_combo' => array(
							'label'		=> __( 'Element output', 'wpv-views' ),
							'type'		=> 'grouped',
							'fields'	=> array(
								'reset_label' => array(
									'pseudolabel'	=> __( 'Element label', 'wpv-views'),
									'type'			=> 'text',
									'default'		=> __( 'Reset', 'wpv-views' )
								),
								'type' => array(
									'pseudolabel'	=> __( 'Element HTML tag ', 'wpv-views'),
									'type'			=> 'select',
									'options'		=> array(
										'input'		=> __( 'Input', 'wpv-views' ),
										'button'	=> __( 'Button', 'wpv-views' ),
										'anchor'	=> __( 'Link', 'wpv-views' )
									),
									'default'		=> 'input'
								),
							),
						),
						'output' => array(
							'label'		=> __( 'Output style', 'wpv-views' ),
							'type'		=> 'radio',
							'options'	=> array(
								'bootstrap'	=> __( 'Styled reset button', 'wpv-views' ),
								'legacy'	=> __( 'Raw reset button', 'wpv-views' ),
							),
							'default_force'	=> 'bootstrap'
						),
						'class_style_combo' => array(
							'label'		=> __( 'Element styling', 'wpv-views' ),
							'type'		=> 'grouped',
							'fields'	=> array(
								'class' => array(
									'pseudolabel'	=> __( 'Button classnames', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Space-separated list of classnames to apply. For example: classone classtwo', 'wpv-views' )
								),
								'style' => array(
									'pseudolabel'	=> __( 'Button style', 'wpv-views'),
									'type'			=> 'text',
									'description'	=> __( 'Raw inline styles to apply. For example: color:red;background:none;', 'wpv-views' )
								),
							),
						),
					),
				),
			),
		);

		$dialog_label = __( 'Reset button for this custom search', 'wpv-views' );

		$data['name']	= $dialog_label;
		$data['label']	= $dialog_label;

		return $data;
	}

	/**
	 * Render the element label.
	 *
	 * @param string $label      The label text
	 * @param array  $attributes The label HTML tag attrbutes
	 *
	 * @return string The label HTML tag
	 *
	 * @since 2.4.0
	 */
	public static function get_label( $label, $attributes = array() ) {
		$output = '<label';
		foreach ( $attributes as $att_key => $att_value ) {
			if (
				in_array( $att_key, array( 'style', 'class' ) )
				&& empty( $att_value )
			) {
				continue;
			}
			$output .= ' ' . $att_key . '="';
			if ( is_array( $att_value ) ) {
				$att_value = array_unique( $att_value );
				$output .= implode( ' ', $att_value );
			} else {
				$output .= $att_value;
			}
			$output .= '"';
		}
		$output .= '>';
		$output .= $label;
		$output .= '</label>';
		return $output;
	}

    /**
     * Render the element input.
     *
     * @param array $attributes The input HTML tag attrbutes
	 *
	 * @return string The input HTML tag
	 *
	 * @since 2.4.0
     */
	public static function get_input( $attributes = array() ) {
		$output = '<input';
		foreach ( $attributes as $att_key => $att_value ) {
			if (
				in_array( $att_key, array( 'style', 'class' ) )
				&& empty( $att_value )
			) {
				continue;
			}
			$output .= ' ' . $att_key . '="';
			if ( is_array( $att_value ) ) {
				$att_value = array_unique( $att_value );
				$output .= implode( ' ', $att_value );
			} else {
				$output .= $att_value;
			}
			$output .= '"';
		}
		$output .= ' />';
		return $output;
	}

	/**
     * Render the element option.
     *
     * @param array $attributes The option HTML tag attrbutes
	 *
	 * @return string The option HTML tag
	 *
	 * @since 2.4.0
     */
	public static function get_select( $options, $attributes = array() ) {
		$output = '<select';
		foreach ( $attributes as $att_key => $att_value ) {
			if (
				in_array( $att_key, array( 'style', 'class' ) )
				&& empty( $att_value )
			) {
				continue;
			}
			$output .= ' ' . $att_key . '="';
			if ( is_array( $att_value ) ) {
				$att_value = array_unique( $att_value );
				$output .= implode( ' ', $att_value );
			} else {
				$output .= $att_value;
			}
			$output .= '"';
		}
		$output .= '>';
		$output .= $options;
		$output .= '</select>';
		return $output;
	}

	/**
     * Render the element option.
     *
     * @param array $attributes The option HTML tag attrbutes
	 *
	 * @return string The option HTML tag
	 *
	 * @since 2.4.0
     */
	public static function get_option( $label, $attributes = array() ) {
		$output = '<option';
		foreach ( $attributes as $att_key => $att_value ) {
			if (
				in_array( $att_key, array( 'style', 'class' ) )
				&& empty( $att_value )
			) {
				continue;
			}
			$output .= ' ' . $att_key . '="';
			if ( is_array( $att_value ) ) {
				$att_value = array_unique( $att_value );
				$output .= implode( ' ', $att_value );
			} else {
				$output .= $att_value;
			}
			$output .= '"';
		}
		$output .= '>';
		$output .= $label;
		$output .= '</option>';
		return $output;
	}

	/**
	 * Function to check if the filter editor content matches the default content.
	 *
	 * @param bool      $state          The state of the condition.
	 * @param array     $view_settings  The View settings array.
     * @param integer   $view_id        The View id.
     *
     * @return bool     $state          The state of the condition.
	 *
	 * @since 2.4.1
	 */
	public function has_default_filter_editor_content( $state, $view_settings = array(), $view_id = 0 ) {
		if ( empty( $view_settings ) && $view_id > 0 ) {
			$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id );
		}

		if ( is_array( $view_settings ) && ! empty( $view_settings ) ) {
		    $filter_editor_content = isset( $view_settings['filter_meta_html'] ) ? $view_settings['filter_meta_html'] : '';
		    $view_purpose = isset( $view_settings['view_purpose'] ) ?  $view_settings['view_purpose'] : 'full';
			$view_settings_default = wpv_view_default_settings( $view_purpose );
			$filter_editor_default_content = $view_settings_default['filter_meta_html'];
			if ( $filter_editor_content == $filter_editor_default_content ) {
				$state = true;
			}
		}
		return $state;
	}

	/**
	 * Entry point to fake query filters associated to frontend search shortcodes.
	 * Note that the filter by post relationship is managed differently,
	 * and that in the future all those listed here should get managed like it.
	 *
	 * @param array $view_settings
	 * @return array
	 * @since 3.0
	 */
	public function fake_url_query_filters( $view_settings ) {
		$view_settings = WPV_Taxonomy_Frontend_Filter::maybe_force_fake_query_filter( $view_settings );
		$view_settings = WPV_Meta_Frontend_Filter::maybe_force_fake_query_filter( $view_settings );
		$view_settings = WPV_Search_Frontend_Filter::maybe_force_fake_query_filter( $view_settings );
		return $view_settings;
	}

}

new WPV_Frontend_Filter();


/* Handle the short codes for creating a user query form

  [wpv-filter-start]
  [wpv-filter-end]
  [wpv-filter-submit]

*/

/**
* Views-Shortcode: wpv-filter-start
*
* Description: The [wpv-filter-start] shortcode specifies the start point
* for any controls that the views filter generates. Example controls are
* pagination controls and search forms. This shortcode is usually added
* automatically to the Views Meta HTML.
*
* @param	hide	"true"	Optional, will add a display:none style to the form HTML element
*
* @todo Switch .js-wpv-filter-data-for-this-form into a form data attribute that we can init on document.ready and refresh when neeeded
*/
add_shortcode( 'wpv-filter-start', 'wpv_filter_shortcode_start' );

function wpv_filter_shortcode_start( $atts ) {
	$view_id				= apply_filters( 'wpv_filter_wpv_get_current_view', null );
	$view_settings			= apply_filters( 'wpv_filter_wpv_get_view_settings', array() );

	/**
	 * Adjust the View settings just before the main Views query.
	 *
	 * @param array $view_settings
	 * @return $view_settings
	 * @since 3.0
	 */
	$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );

	$view_layout_settings	= apply_filters( 'wpv_filter_wpv_get_view_layout_settings', array() );
	$view_attrs				= apply_filters( 'wpv_filter_wpv_get_view_shortcodes_attributes', array() );
	$view_count				= apply_filters( 'wpv_filter_wpv_get_object_unique_hash', '', $view_settings );
	$widget_view_id			= apply_filters( 'wpv_filter_wpv_get_widget_view_id', 0 );
	$view_max_pages			= apply_filters( 'wpv_filter_wpv_get_max_pages', 1 );
	$is_required			= apply_filters( 'wpv_filter_wpv_is_form_required', false );
	$dps_enabled			= false;
	$counters_enabled		= false;
	$out = '';

    if ( $is_required ) {
        extract(
            shortcode_atts( array(), $atts )
        );

        $hide = '';
        if (
			(
				isset( $atts['hide'] )
				&& $atts['hide'] == 'true'
			) || (
				isset( $view_attrs['view_display'] )
				&& $view_attrs['view_display'] == 'layout'
			)
		) {
            $hide = ' style="display:none;"';
        }

        $form_class = array( 'js-wpv-form-full' );
        // Dependant stuf
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
		if ( !isset( $view_settings['filter_meta_html'] ) ) {
			$view_settings['filter_meta_html'] = '';
		}
		if ( strpos( $view_settings['filter_meta_html'], '%%COUNT%%' ) !== false ) {
			$counters_enabled = true;
		}
		if (
			$dps_enabled
			|| $counters_enabled
		) {
			// @todo review this, makes little sense
			if ( $dps_enabled ) {
				$form_class[] = 'js-wpv-dps-enabled';
			}
		} else {
			do_action( 'wpv_action_wpv_force_disable_dps', true );
		}
        if ( ! isset( $view_settings['dps']['ajax_results'] ) ) {
			$view_settings['dps']['ajax_results'] = 'disable';
		}
		if ( ! isset( $view_settings['dps']['ajax_results_submit'] ) ) {
			$view_settings['dps']['ajax_results_submit'] = 'reload';
		}
		$ajax = $view_settings['dps']['ajax_results'];
		$ajax_submit = $view_settings['dps']['ajax_results_submit'];
		if ( $ajax == 'enable' ) {
			$form_class[] = 'js-wpv-ajax-results-enabled';
		} else if (
			$ajax == 'disable'
			&& $ajax_submit == 'ajaxed'
		) {
			$form_class[] = 'js-wpv-ajax-results-submit-enabled';
		}

		// @todo review this, seems like not used anywhere
        $page = 1;

        $effect = 'fade';
		$ajax_pre_before = '';
		if ( isset( $view_settings['dps']['ajax_results_pre_before'] ) ) {
			$ajax_pre_before = esc_attr( $view_settings['dps']['ajax_results_pre_before'] );
		}
        $ajax_before = '';
        if ( isset( $view_settings['dps']['ajax_results_before'] ) ) {
			$ajax_before = esc_attr( $view_settings['dps']['ajax_results_before'] );
        }
        $ajax_after = '';
        if ( isset( $view_settings['dps']['ajax_results_after'] ) ) {
			$ajax_after = esc_attr( $view_settings['dps']['ajax_results_after'] );
        }

        //$url = get_permalink();

		$pagination_permalinks = apply_filters( 'wpv_filter_wpv_get_pagination_permalinks', array(), $view_settings, $view_id );
		$url = $pagination_permalinks['first'];

		$view_url_data			= get_view_allowed_url_parameters( $view_id );
		$query_args_remove		= wp_list_pluck( $view_url_data, 'attribute' );
		/**
         * For views-1411 (https://onthegosystems.myjetbrains.com/youtrack/issue/views-1411) we needed to comment out the
         * line below as it was creating an issue when performing a custom search on a View while it is displayed on a
         * secondary language. Normally the "lang" query argument should not be removed.
         */
		$query_args_remove[]	= 'wpv_sort_orderby';
		$query_args_remove[]	= 'wpv_sort_order';
		$query_args_remove[]	= 'wpv_sort_orderby_as';
		$query_args_remove[]	= 'wpv_sort_orderby_second';
		$query_args_remove[]	= 'wpv_sort_order_second';
		$query_args_remove[]	= 'wpv_aux_current_post_id';
		$query_args_remove[]	= 'wpv_aux_parent_post_id';
		$query_args_remove[]	= 'wpv_aux_parent_term_id';
		$query_args_remove[]	= 'wpv_aux_parent_user_id';
	    $query_args_remove[]	= 'wpv_filter_submit';

		$url = remove_query_arg(
			$query_args_remove,
			$url
		);

		$sort_orderby			= '';
		$sort_order				= '';
		$sort_orderby_as		= '';
		$sort_orderby_second	= '';
		$sort_order_second		= '';

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
			isset( $_GET['wpv_view_count'] )
			&& esc_attr( $_GET['wpv_view_count'] ) == $view_count
		) {
			if (
				isset( $_GET['wpv_sort_orderby'] )
				&& esc_attr( $_GET['wpv_sort_orderby'] ) != ''
			) {
				$sort_orderby = esc_attr( $_GET['wpv_sort_orderby'] );
			}
			if (
				isset( $_GET['wpv_sort_order'] )
				&& esc_attr( $_GET['wpv_sort_order'] ) != ''
			) {
				$sort_order = esc_attr( $_GET['wpv_sort_order'] );
			}
			if (
				isset( $_GET['wpv_sort_orderby_as'] )
				&& esc_attr( $_GET['wpv_sort_orderby_as'] ) != ''
			) {
				$sort_orderby_as = esc_attr( $_GET['wpv_sort_orderby_as'] );
			}
			// Secondary sorting
			if (
				isset( $_GET['wpv_sort_order_second'] )
				&& in_array( strtoupper( esc_attr( $_GET['wpv_sort_order_second'] ) ), array( 'ASC', 'DESC' ) )
			) {
				$sort_order_second = strtoupper( esc_attr( $_GET['wpv_sort_order_second'] ) );
			}
			if (
				isset( $_GET['wpv_sort_orderby_second'] )
				&& esc_attr( $_GET['wpv_sort_orderby_second'] ) != 'undefined'
				&& esc_attr( $_GET['wpv_sort_orderby_second'] ) != ''
				&& in_array( $_GET['wpv_sort_orderby_second'], array( 'post_date', 'post_title', 'ID', 'modified', 'menu_order', 'rand' ) )
			) {
				$sort_orderby_second = esc_attr( $_GET['wpv_sort_orderby_second'] );
			}
		}

		// @todo Switch .js-wpv-filter-data-for-this-form into a form data attribute that we can init on document.ready and refresh when neeeded
		// @note Mind that this could be served from cache, so we better have a caching cleaering mechanism that runs only once...

		$parametric_data = array(
			'query'				=> $query_mode,
			'id'				=> $view_id,
			'view_id'			=> $view_id,
			'widget_id'			=> $widget_view_id,
			'view_hash'			=> $view_count,
			'action'			=> esc_url( $url ),
			'sort'				=> array(
									'orderby'		=> $sort_orderby,
									'order'			=> $sort_order,
									'orderby_as'	=> $sort_orderby_as,
									'orderby_second'	=> $sort_orderby_second,
									'order_second'		=> $sort_order_second
									),
			'orderby'			=> $sort_orderby,
			'order'				=> $sort_order,
			'orderby_as'		=> $sort_orderby_as,
			'orderby_second'	=> $sort_orderby_second,
			'order_second'		=> $sort_order_second,
			'ajax_form'			=> '',// 'disabled'|'enabled'
			'ajax_results'		=> '',// 'disabled'|'onsubmit'|'enabled'
			'effect'			=> 'fade',
			'prebefore'			=> $ajax_pre_before,
			'before'			=> $ajax_before,
			'after'				=> $ajax_after
		);

		$view_attrs_to_keep = $view_attrs;
		if ( isset( $view_attrs_to_keep['name'] ) ) {
			unset( $view_attrs_to_keep['name'] );
		}

		$parametric_data['attributes'] = $view_attrs_to_keep;

		$view_auxiliar_requires = array(
			'current_post_id'	=> 0,
			'parent_post_id'	=> 0,
			'parent_term_id'	=> 0,
			'parent_user_id'	=> 0
		);

		// Fill environmental data for AJAXed operations:
		// Top current post
		$current_post = apply_filters( 'wpv_filter_wpv_get_top_current_post', null );
		if (
			$current_post
			&& isset( $current_post->ID )
		) {
			$view_auxiliar_requires['current_post_id'] = $current_post->ID;
		}

		// Parent post
		$current_post = apply_filters( 'wpv_filter_wpv_get_current_post', null );
		if (
			$current_post
			&& isset( $current_post->ID )
		) {
			$view_auxiliar_requires['parent_post_id'] = $current_post->ID;
		}

		// Parent term
		$parent_term_id = apply_filters( 'wpv_filter_wpv_get_parent_view_taxonomy', null );
		if ( $parent_term_id ) {
			$view_auxiliar_requires['parent_term_id'] = $parent_term_id;
		}

		// Parent user
		$parent_user_id = apply_filters( 'wpv_filter_wpv_get_parent_view_user', null );
		if ( $parent_user_id ) {
			$view_auxiliar_requires['parent_user_id'] = $parent_user_id;
		}

		$archive_environment = apply_filters( 'wpv_filter_wpv_get_current_archive_loop', array() );
		$view_auxiliar_requires['archive'] = array(
			'type'	=> $archive_environment['type'],
			'name'	=> $archive_environment['name'],
			'data'	=> $archive_environment['data'],
		);

		$parametric_data['environment'] = $view_auxiliar_requires;

		$parametric_data = apply_filters( 'wpv_filter_wpv_get_parametric_settings', $parametric_data, $view_settings );

        $out = '<form'
			. $hide
			. ' autocomplete="off"'
			. ' name="wpv-filter-' . $view_count . '"'
			. ' action="' . esc_url( $url ) . '"'
			. ' method="get"'
			. ' class="wpv-filter-form js-wpv-filter-form js-wpv-filter-form-' . $view_count . ' ' . implode( ' ', $form_class ) . '"'
			. ' data-viewnumber="' . $view_count . '"'
			. ' data-viewid="' . $view_id . '"'
			. ' data-viewhash="' . base64_encode( json_encode( $view_attrs ) ) . '"'
			. ' data-viewwidgetid="' . intval( $widget_view_id ) . '"'
			. ' data-orderby="' . $sort_orderby . '"'
			. ' data-order="' . $sort_order . '"'
			. ' data-orderbyas="' . $sort_orderby_as . '"'
			. ' data-orderbysecond="' . $sort_orderby_second . '"'
			. ' data-ordersecond="' . $sort_order_second . '"'
			. ' data-parametric="' . esc_attr( wp_json_encode( $parametric_data ) ) . '"'
			. ' data-attributes="' . esc_attr( wp_json_encode( $view_attrs_to_keep ) ) . '"'
			. ' data-environment="' . esc_js( wp_json_encode( $view_auxiliar_requires ) ) . '"'
			. '>';

        $out .= '<input'
			. ' type="hidden"'
			. ' class="js-wpv-dps-filter-data js-wpv-filter-data-for-this-form"'
			. ' data-action="' . esc_url( $url ) . '"'
			. ' data-page="' . $page . '"'
			. ' data-ajax="' . $ajax . '"'
			. ' data-effect="' . $effect . '"'
			. ' data-maxpages="' . $view_max_pages . '"'
			. ' data-ajaxprebefore="' . $ajax_pre_before . '"'
			. ' data-ajaxbefore="' . $ajax_before . '"'
			. ' data-ajaxafter="' . $ajax_after . '"'
			. ' />';

        // add hidden inputs for any url parameters.
        // We need these for when the form is submitted.
		// @todo this is soooo wrong.... what happens with arrays?
        $url_query = parse_url( $url, PHP_URL_QUERY );
        if ( $url_query != '' ) {
			$url_query_args = wp_parse_args( $url_query );
			$out .= wpv_filter_recursive_add_extra_parameters( $url_query_args );
        }

        /**
        * Add other hidden fields for:
        *
        * View count for multiple Views per page
        * current post ID when needed
		*
		* @todo move to a data attribute, or use directly from there
        */

        $out .= '<input class="wpv_view_count wpv_view_count-' . $view_count . '" type="hidden" name="wpv_view_count" value="' . $view_count . '" />';

    }

	/**
	* Filter wpv_filter_start_filter_form
	*
	* @param $out the default form opening tag followed by the required hidden input tags needed for pagination and table sorting
	* @param $view_settings the current View settings
	* @param $view_id the ID of the View being displayed
	* @param $is_required [true|false] whether this View requires a form to be displayed (has a parametric search OR uses table sorting OR uses pagination)
	*
	* This can be useful to create additional inputs for the current form without needing to add them to the Filter HTML textarea
	* Also, can help users having formatting issues
	*
	* @return $out
	*
	* Since 1.5.1
	*
	*/

	$out = apply_filters( 'wpv_filter_start_filter_form', $out, $view_settings, $view_id, $is_required );

    return $out;
}

/**
 * Generate hidden inputs for already existing URL parameters.
 *
 * @param array $url_query_args Current URL parameters.
 * @param array $keychain Recursion control.
 * @return string
 */
function wpv_filter_recursive_add_extra_parameters( $url_query_args = array(), $keychain = array() ) {
	$out = '';
	foreach ( $url_query_args as $url_key => $url_value ) {
		if (
			// Do not manage native parameters as "extra".
			strpos( $url_key, 'wpv_' ) !== 0
			// Do not adjust for backend, widgets-management entries.
			&& false === in_array( $url_key, ['legacy-widget-preview'], true )
		) {
			if ( is_array( $url_value ) ) {
				$keychain[] = $url_key;
				$out .= wpv_filter_recursive_add_extra_parameters( $url_value, $keychain );
			} else if ( is_string( $url_value ) ) {
				$out .= '<input class="js-wpv-extra-url-param" '
					. 'id="wpv_param_' . wpv_filter_recursive_add_extra_parameters_id( $url_key, $keychain ) . '" '
					. 'type="hidden" '
					. 'name="' . wpv_filter_recursive_add_extra_parameters_name( $url_key, $keychain ) . '" '
					. 'value="' . esc_attr( $url_value ) . '" />';
			}
		}
	}
	return $out;
}

/**
 * Calculate name attributes for hidden inputs on already existing URL parameters.
 *
 * @param string $url_key
 * @param array $keychain
 * @return string
 */
function wpv_filter_recursive_add_extra_parameters_name( $url_key, $keychain ) {
	if ( empty( $keychain ) ) {
		return esc_attr( $url_key );
	}

	$out = '';
	$root = true;
	foreach ( $keychain as $extra_key ) {
		$out .= ( $root ? '' : '[' ) . esc_attr( $extra_key ) . ( $root ? '' : ']' );
		$root = false;
	}
	$out .= '[' . esc_attr( $url_key ) . ']';
	return $out;
}

/**
 * Calculate ID attributes for hidden inputs on already existing URL parameters.
 *
 * @param string $url_key
 * @param array $keychain
 * @return string
 */
function wpv_filter_recursive_add_extra_parameters_id( $url_key, $keychain ) {
	$out = '';
	foreach ( $keychain as $extra_key ) {
		$out .= esc_attr( $extra_key ) . '_';
	}
	$out .= esc_attr( $url_key );
	return $out;
}

/**
 * Check whether rndering the filter form for the current View is needed.
 *
 * @since unknown
 * @deprecated 2.3.1 Use apply_filters( 'wpv_filter_wpv_is_form_required', false ) instead.
 */
function _wpv_filter_is_form_required() {

	_doing_it_wrong(
		'_wpv_filter_is_form_required',
		__( 'This function was deprecated in Views 2.3.2. Use apply_filters( "wpv_filter_wpv_is_form_required", false ) instead.', 'wpv-views' ),
		'2.3.2'
	);

	return apply_filters( 'wpv_filter_wpv_is_form_required', false );

}


/**
 * Views-Shortcode: wpv-post-count
 *
 * Description: The [wpv-post-count] shortcode displays the number of posts
 * that will be displayed on the page. When using pagination, this value will
 * be limited by the page size and the number of remaining results.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * Showing [wpv-post-count] posts of [wpv-found-count] posts found
 *
 * Link:
 *
 * Note:
 * This shortcode is deprecated in favor of [wpv-items-count]
 *
 */

add_shortcode('wpv-post-count', 'wpv_post_count');
function wpv_post_count($atts){
    extract(
        shortcode_atts( array(), $atts )
    );

    $post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );

    if ( $post_query ) {
        return $post_query->post_count;
    } else {
        return '';
    }
}


/**
 * Views-Shortcode: wpv-items-count
 *
 * Description: The [wpv-items-count] shortcode displays the number of items (posts/taxonomy terms/users)
 * that will be displayed on the page. When using pagination, this value will
 * be limited by the page size and the number of remaining results.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * Showing [wpv-items-count] posts of [wpv-found-count] posts found
 *
 * Link:
 *
 * Note:
 *
 */

add_shortcode('wpv-items-count', 'wpv_items_count');
function wpv_items_count($atts){
     extract(
        shortcode_atts( array(), $atts )
    );

	$current_view = apply_filters( 'wpv_filter_wpv_get_current_view', null );
	$view_settings = array();
	if ( isset( $current_view ) ) {
		$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array(), $current_view );
    }
	$out = '';

	$query_type = ( isset( $view_settings['query_type'][0] ) ) ? $view_settings['query_type'][0] : 'posts';

	switch ( $query_type ) {
		case 'taxonomy':
			$taxonomy_query = apply_filters( 'wpv_filter_wpv_get_taxonomy_query', array() );
			if ( isset( $taxonomy_query['item_count_this_page'] ) ) {
				$out = $taxonomy_query['item_count_this_page'];
			}
			break;
		case 'users':
			$user_query = apply_filters( 'wpv_filter_wpv_get_user_query', array() );
			if ( isset( $user_query['item_count_this_page'] ) ) {
				$out = $user_query['item_count_this_page'];
			}
			break;
		case 'posts':
		default:
			$post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
			if ( isset( $post_query->post_count ) ) {
				$out = $post_query->post_count;
			}
			break;
	}

	return $out;
}



/**
 * Views-Shortcode: wpv-found-count
 *
 * Description: The [wpv-found-count] shortcode displays the total number of
 * items (posts/taxonomy terms/users) that have been found by the Views query. This value is calculated
 * before pagination, so even if you are using pagination, it will return
 * the total number of posts matching the query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * Showing [wpv-post-count] posts of [wpv-found-count] posts found
 *
 * Link:
 *
 * Note:
 *
 */


add_shortcode('wpv-found-count', 'wpv_found_count');
function wpv_found_count($atts){
    extract(
        shortcode_atts( array(), $atts )
    );

	$current_view = apply_filters( 'wpv_filter_wpv_get_current_view', null );
	$view_settings = array();
	if ( isset( $current_view ) ) {
		$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array(), $current_view );
    }
	$out = '';

	$query_type = ( isset( $view_settings['query_type'][0] ) ) ? $view_settings['query_type'][0] : 'posts';

	switch ( $query_type ) {
		case 'taxonomy':
			$taxonomy_query = apply_filters( 'wpv_filter_wpv_get_taxonomy_query', array() );
			if ( isset( $taxonomy_query['item_count'] ) ) {
				$out = $taxonomy_query['item_count'];
			}
			break;
		case 'users':
			$user_query = apply_filters( 'wpv_filter_wpv_get_user_query', array() );
			if ( isset( $user_query['item_count'] ) ) {
				$out = $user_query['item_count'];
			}
			break;
		case 'posts':
		default:
			$post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
			if ( isset( $post_query->found_posts ) ) {
				$out = $post_query->found_posts;
			}
			break;
	}

	return $out;
}

/**
 * Views-Shortcode: wpv-posts-found
 *
 * Description: The wpv-posts-found shortcode will display the text inside
 * the shortcode if there are posts found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-posts-found]Some posts were found[/wpv-posts-found]
 *
 * Link:
 *
 * Note:
 * This shortcode is deprecated in favour of the new [wpv-items-found]
 *
 */

add_shortcode('wpv-posts-found', 'wpv_posts_found');
function wpv_posts_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

	$post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );

    if (
		$post_query
		&& (
			$post_query->found_posts != 0
			|| $post_query->post_count != 0
		)
	) {
        // display the message when posts are found.
        return wpv_do_shortcode($value);
    } else {
        return '';
    }

}

/**
 * Views-Shortcode: wpv-no-posts-found
 *
 * Description: The wpv-no-posts-found shortcode will display the text inside
 * the shortcode if there are no posts found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-no-posts-found]No posts found[/wpv-no-posts-found]
 *
 * Link:
 *
 * Note:
 * This shortcode is deprecated in favour of the new [wpv-no-items-found]
 *
 */

add_shortcode('wpv-no-posts-found', 'wpv_no_posts_found');
function wpv_no_posts_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

    $post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );

    if (
		$post_query
		&& $post_query->found_posts == 0
		&& $post_query->post_count == 0
	) {
        // display the message when no posts are found.
        return wpv_do_shortcode($value);
    } else {
        return '';
    }

}

/**
 * Views-Shortcode: wpv-items-found
 *
 * Description: The wpv-items-found shortcode will display the text inside
 * the shortcode if there are items found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-items-found]Some posts/taxonomy terms/users were found[/wpv-items-found]
 *
 * Link:
 *
 * Note:
 *
 */

add_shortcode('wpv-items-found', 'wpv_items_found');
function wpv_items_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

    $view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
	$out = '';

	$query_type = ( isset( $view_settings['query_type'][0] ) ) ? $view_settings['query_type'][0] : 'posts';

	switch ( $query_type ) {
		case 'taxonomy':
			$number = apply_filters( 'wpv_filter_wpv_get_taxonomy_found_count', 0 );
			if (
				$number
				&& $number != 0
			) {
				// display the message when posts are found.
				$out = wpv_do_shortcode( $value );
			}
			break;
		case 'users':
			$number = apply_filters( 'wpv_filter_wpv_get_users_found_count', 0 );
			if (
				$number
				&& $number != 0
			) {
				// display the message when posts are found.
				$out = wpv_do_shortcode( $value );
			}
			break;
		case 'posts':
		default:
			$post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
			if (
				$post_query
				&& (
					$post_query->found_posts != 0
					|| $post_query->post_count != 0
				)
			) {
				$out = wpv_do_shortcode( $value );
			}
			break;
	}

	return $out;

}

/**
 * Views-Shortcode: wpv-no-items-found
 *
 * Description: The wpv-no-items-found shortcode will display the text inside
 * the shortcode if there are no items found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-no-items-found]No items found[/wpv-no-items-found]
 *
 * Link:
 *
 * Note:
 *
 */

add_shortcode('wpv-no-items-found', 'wpv_no_items_found');
function wpv_no_items_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

	$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
	$out = '';

	$query_type = ( isset( $view_settings['query_type'][0] ) ) ? $view_settings['query_type'][0] : 'posts';

	switch ( $query_type ) {
		case 'taxonomy':
			$number = apply_filters( 'wpv_filter_wpv_get_taxonomy_found_count', 0 );
			if (
				isset( $number )
				&& $number === 0
			) {
				// display the message when posts are found.
				$out = wpv_do_shortcode( $value );
			}
			break;
		case 'users':
			$number = apply_filters( 'wpv_filter_wpv_get_users_found_count', 0 );
			if (
				isset( $number )
				&& $number === 0
			) {
				// display the message when posts are found.
				$out = wpv_do_shortcode( $value );
			}
			break;
		case 'posts':
		default:
			$post_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
			if (
				$post_query
				&& $post_query->found_posts == 0
				&& $post_query->post_count == 0
			) {
				$out = wpv_do_shortcode( $value );
			}
			break;
	}

	return $out;

}

/*

    This shows the user interface to the end user on page
    that contains the view.

*/

function wpv_filter_show_user_interface($name, $values, $selected, $style) {
    $out = '';
    $out .= "<div>\n";

    if ($style == 'drop_down') {
        $out .= '<select name="'. $name . '[]">' . "\n";
    }

    foreach($values as $v) {
        switch ($style) {
            case "checkboxes":
                if (is_array($selected)) {
                    $checked = @in_array($v, $selected) ? ' checked="checked"' : '';
                } else {
                    $checked = $v == $selected ? ' checked="checked"' : '';
                }
                $out .= '<label><input type="checkbox" name="' . $name. '[]" value="' . $v . '" ' . $checked . ' />&nbsp;' . $v . "</label>\n";
                break;

            case "radios":
                if (is_array($selected)) {
                    $checked = @in_array($v, $selected) ? ' checked="checked"' : '';
                } else {
                    $checked = $v == $selected ? ' checked="checked"' : '';
                }
                $out .= '<label><input type="radio" name="' . $name. '[]" value="' . $v . '" ' . $checked . ' />&nbsp;' . $v . "</label>\n";
                break;

            case "drop_down":
                if (is_array($selected)) {
                    $is_selected = @in_array($v, $selected) ? ' selected="selected"' : '';
                } else {
                    $is_selected = $v == $selected ? ' selected="selected"' : '';
                }
                $out .= '<option value="' . $v . '" ' . $is_selected . '>' . $v . "</option>\n";
                break;
        }
    }

    if ($style == 'drop_down') {
        $out .= "</select>\n";
    }

    $out .= "</div>\n";

    return $out;
}


/**
 *
 * Views-Shortcode: wpv-control
 *
 * Description: Add filters for View
 *
 * Parameters:
 * type: Type of retrieved field layout 'radio', 'checkbox', 'select', 'multi-select', 'textfield', 'checkboxes', 'datepicker'
 * url_param: The URL parameter passed as an argument
 * values: Optional, a list of supplied values
 * display_values: Optional, a list of values to display for the corresponding values
 * auto_fill: Optional, when set to a "field-slug" the control will be populated with custom field values from the database.
 * auto_fill_default: Optional, use to set the default, unselected, value of the control. eg Ignore or Don't care
 * auto_fill_sort: Optional. 'asc', 'desc', 'ascnum', 'descnum', 'none'. Defaults to ascending.
 * field: Optional, a custom field to retrieve values from
 * title: Optional, use for the checkbox title
 * taxonomy: Optional, use when a taxonomy control should be displayed.
 * taxonomy_orderby. Optional. 'name', 'id', 'count', 'slug', 'term_group', 'none'. Defaults to 'name'
 * taxonomy_order: Optional 'ASC', 'DESC'. Defaults to ascending.
 * default_label: Optional, use when a taxonomy control should be displayed using select input type.
 * date_format: Optional, use for a datepicker control
 *
 * Example usage:
 *
 * Link:
 * More details about this shortcode here: <a href="https://toolset.com/documentation/wpv-control-fields-in-front-end-filters/?utm_source=viewsplugin&utm_campaign=views&utm_medium=filter-help-link&utm_term=Displaying fields in frontend filters/" title="wpv-control – Displaying fields in front-end filters">https://toolset.com/documentation/wpv-control-fields-in-front-end-filters/</a>
 *
 * Note:
 *
 */
function wpv_shortcode_wpv_control( $atts ) {

	// First control checks
	if ( !isset( $atts['url_param'] ) ) {
		return __('The url_param is missing from the wpv-control shortcode argument.', 'wpv-views');
	}
	if ( ( !isset( $atts['type'] ) || $atts == '' ) && !isset( $atts['field'] ) ) {
		return __('The "type" or "field" needs to be set in the wpv-control shortcode argument.', 'wpv-views');
	}

	//Start the shortcode management
	global $no_parameter_found;
	$view_id   = apply_filters( 'wpv_filter_wpv_get_current_view', null );
	$view_name = get_post_field( 'post_name', $view_id );

	$adjusted_atts = shortcode_atts( array(
		'type' => '', // select, multi-select, checbox, checkboxes, radio/radios, date/datepicker, textfield
		'values' => array(), // (optional) comma-separated list of user-provided values
		'display_values' => array(), // (optional) comma-separated list of user-provided display values
		'field' => '', // name of the custom field
		'url_param' => '', // URL parameter to be used
		'title' => '', // title to be used on a checkbox field type
		'taxonomy' => '', // name of the taxonomy for taxonomies filter controls
		'taxonomy_orderby' => 'name', // order of the terms for taxonomies filter controls
		'taxonomy_order' => 'ASC', // orderby of the terms for taxonomies filter controls
		'format' => '%%NAME%%', // format of the display value, use %%NAME%% or %%COUNT%% as placeholders
		'default_label' => '', // default label for taxonomies filter controls when using select input type
		'hide_empty' => 'false', // option to hide empty terms for taxonomies filter controls
		'auto_fill' => '', // options to auto fill values for custom fields filter controls - provide the field name
		'auto_fill_default' => '', // default value when using auto_fill for custom fields filter controls
		'auto_fill_sort' => '', // order when using auto_fill for custom fields filter controls
		'date_format' => '', // date format for date controls
		'default_date' => '',  // default date for date controls
		'force_zero' => 'false',
		'style' => '', // inline styles for input
		'class' => '', // input classes
		'label_style' => '', // inline styles for input label
		'label_class' => '', // classes for input label
		'output' => 'legacy'
	), $atts );

	extract( $adjusted_atts );

	// @todo do NOT escape here, do it JIT
    $style = esc_attr( $style );
    $class = esc_attr( $class );
    $label_style = esc_attr( $label_style );
    $label_class = esc_attr( $label_class );

	// First, parametric search control for taxonomy
	if ( '' !== $adjusted_atts['taxonomy'] ) {
		// Render the taxonomy control
		// Translate the sorting attributes to the ones expected by the callback
		$adjusted_atts['orderby'] = $adjusted_atts['taxonomy_orderby'];
		$adjusted_atts['order'] = $adjusted_atts['taxonomy_order'];
		return WPV_Taxonomy_Frontend_Filter::wpv_shortcode_wpv_control_post_taxonomy( $adjusted_atts );
	}

	// @todo use the WPV_Meta_Frontend_Filter::wpv_shortcode_wpv_control_postmeta method even with legacy output
	if ( 'bootstrap' == $output ) {
		$compatibility_args = array(
			'type'				=> $type,
			'values'			=> $values,
			'display_values'	=> $display_values,
			'field'				=> $field,
			'source'			=> 'database',
			'url_param'			=> $url_param,
			'title'				=> $title,
			'format'			=> $format,
			'default_label'		=> $auto_fill_default,
			'order'				=> $auto_fill_sort,
			'date_format'		=> $date_format,
			'default_date'		=> $default_date,
			'force_zero'		=> $force_zero,
			'style'				=> $style,
			'class'				=> $class,
			'label_style'		=> $label_style,
			'label_class'		=> $label_class,
			'output' 			=> $output
		);
		if ( ! empty( $auto_fill ) ) {
			$compatibility_args['field'] = $auto_fill;
		}
		if ( ! empty( $values ) ) {
			$compatibility_args['source'] = 'custom';
		}

		return WPV_Meta_Frontend_Filter::wpv_shortcode_wpv_control_postmeta( $compatibility_args );
	}


	// Before doing anything else, rule out textfields
	if ( $type == 'textfield' ) {
		// Textfield field
		$default_value = '';
		if ( isset( $_GET[ $url_param ] ) ) {
			$default_value = esc_attr( wp_unslash( $_GET[ $url_param ] ) );
		}

		// Prepend classname with an empty space for string concatenation
		$class = empty( $class ) ? $class : ' ' . $class;

		$element = '<input'
			. ' type="text"'
			. ' id="wpv_control_textfield_' . esc_attr( $url_param ) . '"'
			. ' name="' . esc_attr( $url_param ) . '"'
			. ' value="' . $default_value . '"'// This is escaped just above
			. ' class="js-wpv-filter-trigger-delayed wpcf-form-textfield form-textfield textfield' . $class . '"'// This is escaped just above
			. ' style="' . $style  . '"'// This is escaped just above
			. ' /> ';

		return $element;
	}

	// Check if the View has dependency enabled
	$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );

	/**
	 * Adjust the View settings just before the main Views query.
	 *
	 * @param array $view_settings
	 * @return $view_settings
	 * @since 3.0
	 */
	$view_settings = apply_filters( 'wpv_filter_object_settings_for_fake_url_query_filters', $view_settings );

	$dependant = false;
	$counters = ( $format && strpos( $format, '%%COUNT%%' ) !== false ) ? true : false;
	$empty_action = array();
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

	// Some basic values
	if ( empty( $field ) ) {
		if ( empty( $auto_fill ) ) {
			// In this case, the shortcode is not about a custom field filter but a generic one without taxonomy, field or auto_fill attributes
			// It can be used to generate custom form inputs, given that the user provides values (and maybe display_values)
			// So we need to disable dependency
			$dependant = false;
			$counters = false;
		} else {
			$field_real_name = _wpv_get_field_real_slug( $auto_fill );
		}
	} else {
		$field_real_name = _wpv_get_field_real_slug( $field );
	}

	$display_values_trans = false; // flag to whether the display_values need to be translated
	$out = '';

	// If dependency is ON, build the basic data and cache
	if ( $dependant || $counters ) {
		$empty_default = 'hide';
		$empty_alt = 'disable';
		$empty_options = array( 'select', 'radios', 'checkboxes' ); // multi-select is a special case because of dashes and underscores
		foreach ( $empty_options as $empty_opt ) {
			if ( isset( $view_settings['dps'][ 'empty_' . $empty_opt ] )
				&& $view_settings['dps'][ 'empty_' . $empty_opt ] == $empty_alt )
			{
				$empty_action[ $empty_opt ] = $empty_alt;
			} else {
				$empty_action[ $empty_opt ] = $empty_default;
			}
		}
		if ( isset( $view_settings['dps']['empty_multi_select'] )
			&& $view_settings['dps']['empty_multi_select'] == $empty_alt )
		{
			$empty_action['multi-select'] = $empty_alt;
		} else {
			$empty_action['multi-select'] = $empty_default;
		}
		$wpv_data_cache = array();
		$original_value = isset( $view_settings[ 'custom-field-' . $field_real_name . '_value' ] ) ? $view_settings[ 'custom-field-' . $field_real_name . '_value' ] : '';
		$processed_value = wpv_apply_user_functions( $original_value );
		$compare_function = isset( $view_settings[ 'custom-field-' . $field_real_name . '_compare' ] ) ? $view_settings[ 'custom-field-' . $field_real_name . '_compare' ] : '=';
		$current_value_key = false;
		// @todo check IN, NOT IN and != compare functions
		$comparator = 'equal';
		if ( $compare_function == 'BETWEEN' ) {
			$original_value_array = array_map( 'trim', explode( ',', $original_value ) );
			$processed_value_array = array_map( 'trim', explode( ',', $processed_value ) );
			$current_value_key = array_search( 'URL_PARAM(' . $url_param . ')', $original_value_array );
			if ( $current_value_key !== false ) {
				$processed_value = isset( $processed_value_array[ $current_value_key ] ) ? $processed_value_array[ $current_value_key ] : $no_parameter_found;
				if ( $current_value_key < 1 ) {
					$comparator = 'lower-equal-than';
				} else if ( $current_value_key > 0 ) {
					$comparator = 'greater-equal-than';
				}
			}
		} else if ( $compare_function == '>' ) {
			$comparator = 'lower-than';
		} else if ( $compare_function == '>=' ) {
			$comparator = 'lower-equal-than';
		} else if ( $compare_function == '<' ) {
			$comparator = 'greater-than';
		} else if ( $compare_function == '<=' ) {
			$comparator = 'greater-equal-than';
		}
		// Construct $wpv_data_cache['post_meta']
		if ( $processed_value == $no_parameter_found ) {
			$wpv_data_cache = WPV_Cache::$stored_cache;
			$aux_query_count = null;
		} else {
			// When there is a selected value, create a pseudo-cache based on all the other filters
			// Note that checkboxes filters can generate nested meta_query entries
			$query = apply_filters( 'wpv_filter_wpv_get_dependant_extended_query_args', array(), $view_settings, array( 'postmeta' => $field_real_name ) );
			$aux_cache_query = null;
			if ( isset( $query['meta_query'] ) && is_array( $query['meta_query'] ) ) {
				foreach ( $query['meta_query'] as $qt_index => $qt_val ) {
					if ( is_array( $qt_val ) ) {
						foreach ( $qt_val as $qt_val_key => $qt_val_val ) {
							if (
								$qt_val_key == 'key'
								&& $qt_val_val == $field_real_name
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
											$query['meta_query'][ $qt_index ]['value']= $passed_values[1];
										} else if ( $current_value_key > 0 && isset( $passed_values[0] ) ) {
											$query['meta_query'][ $qt_index ]['compare'] = '>=';
											$query['meta_query'][ $qt_index ]['value']= $passed_values[0];
										}
									} else {
										unset( $query['meta_query'][ $qt_index ] );
									}
									// if $compare_function is BETWEEN and we have a meta_query not using BETWEEN, we have a partial query here, so keep it
								} else {
									unset( $query['meta_query'][$qt_index] );
								}
							} else if (
								is_array( $qt_val_val )
								&& isset( $qt_val_val['key'] )
								&& $qt_val_val['key'] == $field_real_name
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
											$query['meta_query'][ $qt_index ][ $qt_val_key ]['value']= $passed_values[1];
										} else if ( $current_value_key > 0 && isset( $passed_values[0] ) ) {
											$query['meta_query'][ $qt_index ][ $qt_val_key ]['compare'] = '>=';
											$query['meta_query'][ $qt_index ][ $qt_val_key ]['value']= $passed_values[0];
										}
									} else {
										unset( $query['meta_query'][ $qt_index ][ $qt_val_key ] );
									}
									// if $compare_function is BETWEEN and we have a meta_query not using BETWEEN, we have a partial query here, so keep it
								} else {
									unset( $query['meta_query'][$qt_index][ $qt_val_key ] );
								}
							}
						}
					}
				}
			}
			$aux_cache_query = new WP_Query($query);
			if ( is_array( $aux_cache_query->posts ) && !empty( $aux_cache_query->posts ) ) {
				$aux_query_count = count( $aux_cache_query->posts );
				$f_fields = array( $field_real_name );
				$wpv_data_cache = WPV_Cache::generate_cache( $aux_cache_query->posts, array( 'cf' => $f_fields ) );
			}
		}
		if ( !isset( $wpv_data_cache['post_meta'] ) ) {
			$wpv_data_cache['post_meta'] = array();
		}

		// OK, for checkboxes custom fields the stored value is NOT the one we use for filtering
		// So instead of filtering $wpv_data_cache['post_meta'] we will loop it to see if the $field_real_name key exists
		// AND check the serialized value to see if it contains the given real value (warning, not the label!)
		// AND break as soon as true because we need no counters
		// Expensive, but not sure if more than wp_list_filter though
	}

	// Management of multiselect
	$multi = '';
	if ( $type == 'multi-select') {
		$type = 'select';
		$multi = 'multiple';
	}

	//  $filter_check_type = _wpv_is_field_of_type( $auto_fill, 'checkboxes' ) ? 'checkboxes' : 'other';
	$filter_check_type = wpv_types_get_field_type( $field );

	if ( $auto_fill != '' ) {
		/**
		* If using auto_fill, populate the values and display_values arrays
		*/

        /**
        * First we are going to populate those variables
        */
        $fields = array(); // this will hold the Types fields from the Options
        $db_values = array(); // this will hold the field values from Types options or from the database
        $display_text = array(); // this will hold the field values pretty display text, if it is a Types field with options
        $auto_fill_default_trans = false; // flag to whether the auto_fill_default has translated, based on whether it is one of the existing values

        if ( !function_exists( 'wpcf_admin_fields_get_fields' ) ) {
			if( defined( 'WPCF_EMBEDDED_ABSPATH' ) ) {
				include WPCF_EMBEDDED_ABSPATH . '/includes/fields.php';
			}
		}
		if ( function_exists( 'wpcf_admin_fields_get_fields' ) ) {
			$fields = wpcf_admin_fields_get_fields();
		}
		// $field_name = substr($auto_fill, 5); // TODO DONE check this for fields created outside of Types and brought under Types control
		if ( strpos( $auto_fill, 'wpcf-' ) === 0 ) {
			$field_name = substr( $auto_fill, 5 );
        } else {
			$field_name = $auto_fill;
        }

        // If it is a Types field with options
        if ( isset( $fields[ $field_name ] ) && isset( $fields[ $field_name ]['data']['options'] ) ) {
			// If it is a checkboxes Types field
			if ( _wpv_is_field_of_type( $auto_fill, 'checkboxes' ) ) {
				$options = $fields[ $field_name ]['data']['options'];
				foreach( $options as $field_key => $option ) {
					// Fill the db_values and display_text (translated if needed) arrays
					$db_values[] = $option['title'];
					$display_text[ $option['title'] ] = wpv_translate( 'field '. $fields[ $field_name ]['id'] .' option '. $field_key .' title', $option['title'], false, 'plugin Types' );
				}
			} else {
				// If it is a Types field different from checkboxes but with options
				$options = $fields[ $field_name ]['data']['options'];
				if ( isset( $options['default'] ) ) {
					// remove the default option from the array
					unset( $options['default'] );
				}
				if ( isset( $fields[ $field_name ]['data']['display'] ) ) {
					$display_option =  $fields[ $field_name ]['data']['display'];
				}
				foreach ( $options as $field_key => $option ) {
					if ( isset( $option['value'] ) ) {
						$db_values[] = $option['value'];
					}
					if ( isset( $display_option )
						&& 'value' == $display_option
						&& isset( $option['display_value'] ) )
					{
						$display_text[ $option['value'] ] = wpv_translate( 'field '. $fields[ $field_name ]['id'] .' option '. $field_key .' title', $option['display_value'], false, 'plugin Types' );
					} else {
						$display_text[ $option['value'] ] = wpv_translate( 'field '. $fields[ $field_name ]['id'] .' option '. $field_key .' title', $option['title'], false, 'plugin Types' );
					}
					if ( $auto_fill_default != '' ) {
						// translate the auto_fill_default option if needed, just when it's one of the existing options
						$auto_fill_default = str_replace( array( '%%COMMA%%', '\,' ), ',', $auto_fill_default );
						if ( $auto_fill_default == $option['title'] ) {
							$auto_fill_default = wpv_translate( 'field '. $fields[ $field_name ]['id'] .' option '. $field_key .' title', $option['title'], false, 'plugin Types' );
							// set this flat to true: we already have translated auto_fill_default
							$auto_fill_default_trans = true;
						}
						$auto_fill_default = str_replace( ',', '%%COMMA%%', $auto_fill_default );
					}
				}
			}

			// Now sort the values based on auto_fill_sort
			switch ( strtolower( $auto_fill_sort ) ) {
				case 'desc':
					sort( $db_values );
					$db_values = array_reverse( $db_values );
					break;
				case 'descnum':
					sort( $db_values, SORT_NUMERIC );
					$db_values = array_reverse( $db_values );
					break;
				case 'none':
					break;
				case 'ascnum':
					sort( $db_values, SORT_NUMERIC );
					break;
				default:
					sort( $db_values );
					break;
			}

        } else {
			// If it is not a Types field OR is a Types field without options

			global $wpdb;
			$values_to_prepare = array();
			$values_to_prepare[] = $auto_fill;
			$wpdb_where = '';
			if ( isset( $view_settings['post_type'] )
				&& is_array( $view_settings['post_type'] )
				&& ! empty( $view_settings['post_type'] )
				&& ! in_array( 'any', $view_settings['post_type'] )
			) {
				$post_type_count = count( $view_settings['post_type'] );
				$post_type_placeholders = array_fill( 0, $post_type_count, '%s' );
				$wpdb_where .= " AND p.post_type IN (" . implode( ",", $post_type_placeholders ) . ") ";
				foreach ( $view_settings['post_type'] as $pt ) {
					$values_to_prepare[] = $pt;
				}
			}
			if (
				isset( $view_settings['post_status'] )
				&& is_array( $view_settings['post_status'] )
				&& ! empty( $view_settings['post_status'] )
			) {
				if ( ! in_array( 'any', $view_settings['post_status'] ) ) {
					$post_status_count = count( $view_settings['post_status'] );
					$post_status_placeholders = array_fill( 0, $post_status_count, '%s' );
					$wpdb_where .= " AND p.post_status IN (" . implode( ",", $post_status_placeholders ) . ") ";
					foreach ( $view_settings['post_status'] as $ps ) {
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
			$wpdb_orderby = '';
			switch ( strtolower( $auto_fill_sort ) ) {
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
			$db_values = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
					WHERE pm.meta_key = %s AND pm.meta_value IS NOT NULL AND pm.meta_value != ''
					{$wpdb_where}
					{$wpdb_orderby}",
					$values_to_prepare
				)
			);
        }

        /**
        * Now we are going to fill the $values and $display_values comma-separated strings based on $db_values and, in case, $display_text
        * NOTE if $auto_fill_default_trans is FALSE then the auto_fill_default is NOT one of the existing option titles so we will translate it
        */
        if ( $auto_fill_default != '' ) {
			// If auto_fill_default is not empty, adjust and translate when needed
			if ( !$auto_fill_default_trans ) {
				// translate the auto_fill_default option when it's not one of the existing options
				$auto_fill_default = str_replace( array( '%%COMMA%%', '\,' ), ',', $auto_fill_default );
				$auto_fill_default = wpv_translate( $url_param . '_auto_fill_default', stripslashes( $auto_fill_default ), false, 'View ' . $view_name );
				$auto_fill_default = str_replace( ',', '%%COMMA%%', $auto_fill_default );
			}
            $values = '';
            $display_values = str_replace( '\,', '%%COMMA%%', $auto_fill_default );
            // flag to whether there is an auto_fill_default value that we ad at the beginning of the $display_value string
            $first = false;
        } else {
            $values = '';
            $display_values = '';
            $first = true;
        }
        foreach( $db_values as $value ) {
            if ( $value !== false ) {
                if ( !$first ) {
                    $values .= ',';
                    $display_values .= ',';
                }
                // HACK to handle commas in values
                $values .= str_replace( ',', '%%COMMA%%', $value );
                if ( isset( $display_text[$value] ) ) {
					// HACK to handle commas in display_values
					$display_values .= str_replace( ',', '%%COMMA%%', $display_text[ $value ] );
				} else {
					// HACK to handle commas in display_values
					$display_values .= str_replace( ',', '%%COMMA%%', $value );
				}
                $first = false;
            }
        }
    // If not using auto_fill, check if there are manually added display_values
    } else if ( !empty( $display_values ) ) {
		// mark that the display_values need to be translated
		$display_values_trans = true;
    }

	/*
	* Now we have a comma-separated list of $values and $display_values, hopefully ;-D
	* In fact, we count with a $values comma-separated list
	* We will fill the $values_arr array and transform $display_values into an array
	*/

	if( !empty( $values ) ) {
		// When values attributes are manually defined, the inner commas are formatted as \, and we need to apply the same HACK as for the automatically set values
		$values_fix = str_replace( '\,', '%%COMMA%%', $values );
		// Now, get the $values_arr array of values
		$values_arr = explode( ',', $values_fix );
		// And undo the comma HACK
		$values_arr = str_replace( array( '%%COMMA%%', '%comma%' ), ',', $values_arr );
        if ( !empty( $display_values ) ) {
			// If there are display_values,again sync the comma HACK
			$display_values = str_replace( '\,', '%%COMMA%%', $display_values );
			// Get an array of $display_values
			$display_values = explode( ',', $display_values );
			// And undo the comma HACK
			$display_values = str_replace( array( '%%COMMA%%', '%comma%' ), ',', $display_values );
			if ( $display_values_trans ) {
				// If we need to translate the $display_values
				$translated_values = array();
				foreach ( $display_values as $index => $valuetrans ) {
					$translated_values[ $index ] = wpv_translate( $url_param . '_display_values_' . ( $index + 1 ), stripslashes( $valuetrans ), false, 'View ' . $view_name );
				}
				$display_values = $translated_values;
			}
        }

		// Parse date expressions in values.
		$values_count = count( $values_arr );
		for( $i = 0; $i < $values_count; ++$i ) {
			$values_arr[ $i ] = wpv_filter_parse_date( $values_arr[ $i ] );
		}

		/**
		* Now that we have the $values_arr and $display_values we focus on the kind of output
		* Based on $type we will popuate an $options variable and use the wpv_form_control() function
		*/

        if( !in_array( $type, array( 'radio', 'radios', 'select', 'checkboxes' ) ) ) {
            // For wpv-control shortcodes using auto_fill or values/display_values we only allow those kind of types
            $type = 'select';
        }
        if ( $type == 'radio' ) {
            // In fact, radios == radio
            $type = 'radios';
        }
        $options = array();
		// Now, depending on $type
        switch ( $type ) {

            case 'checkboxes':
                // If we need to render CHECKBOXES
                $defaults = array();
                $original_get = null;
                if ( isset( $auto_fill_default ) ) {
					// First, check if the defaul value already exists and set the appropriate arrays and values
					$num_auto_fill_default_display = array_count_values( $display_values );
					$auto_fill_default_trans = str_replace( array( '%%COMMA%%', '\,' ), ',', $auto_fill_default );
					if (
							// if the auto_fill_default is one of the display_values
							( isset( $num_auto_fill_default_display[ $auto_fill_default_trans ] )
							&& $num_auto_fill_default_display[ $auto_fill_default_trans ] > 1 )
						||
							// OR if the auto_fill_default is one of the values
							in_array( $auto_fill_default_trans, $values_arr ) )
					{
						// Take out the first element of the $values_arr and the $display_values, which holds and empty string and the auto_fill_default value
						$values_arr_def = array_shift( $values_arr );
						$display_values_def = array_shift( $display_values );
					}
					// Then, set the preliminary $defaults value based on auto_fill_default
					$defaults = str_replace( '\,', '%%COMMA%%', $auto_fill_default );
					$defaults = explode( ',', $defaults );
					$defaults = str_replace( array( '%%COMMA%%', '%comma%' ), ',', $defaults );
					$defaults = array_map( 'trim', $defaults );
                }
                if ( isset( $_GET[ $url_param ] ) ) {
                    // Override $defaults if a set of values is coming from the URL parameter
                    $original_get = $_GET[ $url_param ];
                    $defaults = $_GET[ $url_param ];
                    if ( is_string( $defaults ) ) {
						$defaults = explode( ',',$defaults );
					}
                    unset( $_GET[ $url_param ] );
                }
                $count_values_array = count( $values_arr );
                for( $i = 0; $i < $count_values_array; $i++ ) {
                    // Loop through the $values_arr
                    $value = $values_arr[ $i ];
                    $value = trim( $value );
                    // Check for a display value
                    if ( isset( $display_values[ $i ] ) ) {
                        $display_value = $display_values[ $i ];
                    } else {
                        $display_value = $value;
                    }
                    // Compose the $options for this value
                    $options[ $value ]['#name'] = $url_param . '[]';
                    $options[ $value ]['#title'] = $display_value;
                    $options[ $value ]['#value'] = $value;
                    // set default using option titles too
                    $options[ $value ]['#default_value'] = in_array( $value, $defaults ) || in_array( $options[ $value ]['#title'], $defaults);
                    $options[ $value ]['#attributes']['class'] = 'js-wpv-filter-trigger ' . $class;
                    $options[ $value ]['#attributes']['style'] = $style;
                    $options[ $value ]['#labelclass'] = $label_class;
                    $options[ $value ]['#labelstyle'] = $label_style;

					if ( $format ) {
						$display_value_formatted_name = str_replace( '%%NAME%%', $options[ $value ]['#title'], $format );
						$options[ $value ]['#title'] = $display_value_formatted_name;
					}
                    // Dependant stuff
                    if ( $dependant || $counters ) {
						$meta_criteria_to_filter = array( $field_real_name => array( $value ) );
						$this_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
						if ( empty( $value ) && !is_numeric( $value ) && is_object( $this_query ) ) {
							if ( isset( $aux_query_count ) ) {
								$this_checker = $aux_query_count;
							} else {
								$this_checker = $this_query->found_posts;
							}
						} else {
							$data = array();
							$data['list'] = $wpv_data_cache['post_meta'];
							$data['args'] = $meta_criteria_to_filter;
							$data['kind'] = $filter_check_type;
							$data['comparator'] = $comparator;
							if ( $counters ) {
								$data['count_matches'] = true;
							}
							$this_checker = wpv_list_filter_checker( $data );
						}
						if ( $counters ) {
							$display_value_formatted_name = str_replace( '%%COUNT%%', $this_checker, $options[ $value ]['#title'] );
							$options[ $value ]['#title'] = $display_value_formatted_name;
						}
						if ( !$this_checker && ( !empty( $value ) || is_numeric( $value ) ) && !$options[ $value ]['#default_value'] && $dependant ) {
							$options[ $value ]['#attributes']['#disabled'] = 'true';
							$options[ $value ]['#labelclass'] .= ' wpv-parametric-disabled ';
							if ( isset( $empty_action['checkboxes'] ) && $empty_action['checkboxes'] == 'hide' ) {
								unset( $options[ $value ] );
							}
						}
					}
//                    $options[$value]['#inline'] = true;
//                    $options[$value]['#after'] = '&nbsp;&nbsp;';
                }
                // Render the form control element
               	$element = wpv_form_control( array(
						'field' => array(
				                '#type' => $type,
				                '#id' => 'wpv_control_' . $type . '_' . $url_param,
				                '#name' => $url_param . '[]',
				                '#attributes' => array( 'style' => '' ),
				                '#inline' => true,
				                '#options' => $options,
								'#before' => '<div class="wpcf-checkboxes-group">', //we need to wrap them for js purposes
								'#after' => '</div>' ) ) );

                if ( $original_get ) {
                    $_GET[ $url_param ] = $original_get;
                }
                break;

            default:
                // If we need to check any other field with values and a type that is not checkboxes (radios or select)
                $options_array = array();

                // This one will hold options in a display_vaue => value format so we can use it to compose the default_value later
                $options = array();

                $count_values_array = count( $values_arr );
                for( $i = 0; $i < $count_values_array; $i++ ) {
                    // Loop through the $values_arr
                    $value = $values_arr[ $i ];
                    $value = trim( $value );
                    // Check for a display value
                    if ( isset( $display_values[ $i ] ) ) {
                        $display_value = $display_values[ $i ];
                    } else {
                        $display_value = $value;
                    }
                    // Compose the $options for this value
                    $options[ $display_value ] = $value;
                    $options_array[ $display_value ] = array(
							'#title' => $display_value,
							'#value' => $value,
							'#inline' => true,
							'#after' => '<br />' );
                    $options_array[ $display_value ]['#attributes']['class'] = 'js-wpv-filter-trigger';

                    if ( $type == 'radios' ) {
                        $options_array[ $display_value ]['#attributes']['class'] .= ' ' . $class;
                        $options_array[ $display_value ]['#attributes']['style'] = $style;
                        $options_array[ $display_value ]['#labelclass'] = $label_class;
                        $options_array[ $display_value ]['#labelstyle'] = $label_style;
                    }

					if ( $format ) {
						$display_value_formatted_name = str_replace( '%%NAME%%', $options_array[ $display_value ]['#title'], $format );
						$options_array[ $display_value ]['#title'] = $display_value_formatted_name;
					}
                    // Dependant stuff
					if ( $dependant || $counters ) {
						$this_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
						if ( empty( $value ) && !is_numeric( $value ) && is_object( $this_query ) ) {
							if ( isset( $aux_query_count ) ) {
								$this_checker = $aux_query_count;
							} else {
								$this_checker = $this_query->found_posts;
							}
						} else {
							$meta_criteria_to_filter = array( $field_real_name => array( $value ) );
							$data = array();
							$data['list'] = $wpv_data_cache['post_meta'];
							$data['args'] = $meta_criteria_to_filter;
							$data['kind'] = $filter_check_type;
							$data['comparator'] = $comparator;
							if ( $counters ) {
								$data['count_matches'] = true;
							}
							$this_checker = wpv_list_filter_checker( $data );
						}
						if ( $counters ) {
							$display_value_formatted_counter = str_replace( '%%COUNT%%', $this_checker, $options_array[ $display_value ]['#title'] );
							$options_array[ $display_value ]['#title'] = $display_value_formatted_counter;
						}

						if ( !$this_checker && ( !empty( $value ) || is_numeric( $value ) ) && $dependant ) {
							// TODO DONE need to merge this with the default_value below, to avoid hiddin or disabling selected items
							$options_array[ $display_value ]['#disable'] = 'true';
							$options_array[ $display_value ]['#labelclass'] = 'wpv-parametric-disabled';
							if ( $type == 'select' && $multi == 'multiple' ) {
								if ( isset( $empty_action['multi-select'] ) && $empty_action['multi-select'] == 'hide' ) {
									unset( $options_array[ $display_value ] );
								}
							} else if ( isset( $empty_action[ $type ] ) && $empty_action[ $type ] == 'hide' ) {
								unset( $options_array[ $display_value ] );
							}
						}
					}
                }

                if ( count( $values_arr ) != count( $options ) ) {
					// if the $values_arr has one more item than $options, there is a repeating value: the default one added to the beginning
					$default_value = reset( $options );
				} else {
					if (
						$type == 'radios'
						|| $multi == 'multiple'
					) {
						$default_value = '';
					} else {
						// so the default value in this case is the first element in $values_arr
						$default_value = isset( $values_arr[0] ) ? $values_arr[0] : '';
					}
				}
				if ( $type == 'radios' ) {
					if ( isset( $_GET[ $url_param ] ) && in_array( $_GET[ $url_param ], $options ) ) {
						$default_value = $_GET[ $url_param ];
					}
					$name_aux = $url_param;
				} else {
					// Basically, if $type == 'select'
					if ( isset( $_GET[ $url_param ] ) ) {
						if ( is_array( $_GET[ $url_param ] ) ) {
							if ( count( array_intersect($_GET[ $url_param ], $options) ) > 0 ) {
								$default_value = $_GET[ $url_param ];
							}
						} else {
							if ( in_array( $_GET[ $url_param ], $options ) ) {
								$default_value = $_GET[ $url_param ];
							}
						}
					}
					$name_aux = $url_param . '[]';
				}

				// Now we need to recreate the $options_array element if it is a default one and is disabled or removed
				if ( is_array( $default_value ) ) {
					foreach ( $default_value as $dv ) {
						$aux_display_values = array_keys( $options, $dv, true );
						foreach ( $aux_display_values as $aux_dv ) {
							// TODO where is $aux_dv defined??
							if ( isset( $options_array[ $aux_dv ] ) ) {
								if ( isset( $options_array[ $aux_dv ]['#disable'] ) ) {
									unset( $options_array[ $aux_dv ]['#disable'] );
								}
								$options_array[ $aux_dv ]['#labelclass'] = '';
							} else {
								$options_array[ $aux_dv ] = array(
										'#title' => $aux_dv,
										'#value' => $dv,
										'#inline' => true,
										'#after' => '<br />' );
								$options_array[ $aux_dv ]['#attributes']['class'] = 'js-wpv-filter-trigger ';
							}
						}
					}
				} else {
					$aux_display_values = array_keys( $options, $default_value, true );
					foreach ( $aux_display_values as $aux_dv ) {
						if ( isset( $options_array[ $aux_dv ] ) ) {
							if ( isset( $options_array[$aux_dv]['#disable'] ) ) {
								unset( $options_array[$aux_dv]['#disable'] );
							}
							$options_array[ $aux_dv ]['#labelclass'] = '';
						} else {
							$options_array[ $aux_dv ] = array(
									'#title' => $aux_dv,
									'#value' => $default_value,
									'#inline' => true,
									'#after' => '<br />' );
							$options_array[ $aux_dv ]['#attributes']['class'] = 'js-wpv-filter-trigger ';
						}
					}
				}

				$element = wpv_form_control( array(
						'field' => array(
								'#type' => $type,
								'#id' => 'wpv_control_' . $type . '_' . $url_param,
								'#name' => $name_aux,
								'#attributes' => array('style' => $style, 'class' => 'js-wpv-filter-trigger ' . $class ),
								'#inline' => true,
								'#options' => $options_array, // NOTE this was originally $options but as it's not an array I can not set a "disabled" option
								'#default_value' => $default_value,
								'#multiple' => $multi // NOTE I'd say that radios do not need multiple but it should do no harm
								) )	);
				break;
        }
		return $element;

	} else if ( !empty( $field ) ) {
		/**
		* When field attribute is defined but we do not have auto_fill nor manually entered values
		* In this case, we display the control input based on $type or the field type itself if needed (mainly for Types auto style, but we can expect other combinations)
		*/

		// Check if Types is active because we are using wpcf_admin_fields_get_field()
		if ( !function_exists( 'wpcf_admin_fields_get_field' ) ) {
			if ( defined( 'WPCF_EMBEDDED_ABSPATH' ) ) {
				include WPCF_EMBEDDED_ABSPATH . '/includes/fields.php';
			} else {
				return __( 'Types plugin is required.', 'wpv-views' );
			}
		}

		//This is important cause wpcf_admin_fields_get_field works with id: $field - 'wpcf-' and search with 'wpcf-'.$field
		/*if( strpos($field, 'wpcf-') !== false ) {
			$tmp = explode('wpcf-', $field);
			$field = $tmp[1];
		}*/
		// Get field options and translate name if needed
		$field_options = wpcf_admin_fields_get_field( $field );
		if ( empty( $field_options ) ) {
			return __( 'Empty field values or incorrect field defined. ', 'wpv-views' );
		}
        $field_options['name'] = wpv_translate( 'field ' . $field_options['id'] . ' name', $field_options['name'], false, 'plugin Types' );
		// Get field type, override if $type exists and default it to textfield if needed
		$field_type = $field_options['type'];
		if ( !empty( $type ) ) {
			// Watch out: this is where we can override the field type itself
			$field_type = $type;
		}
        if ( !in_array( $field_type, array( 'radio', 'checkbox', 'checkboxes', 'select', 'textfield', 'date', 'datepicker' ) ) ) {
            $field_type = 'textfield';
        }
		// Display time!!
		if ( $field_type == 'radio' ) {
			// Radio field
			$field_radio_options = isset( $field_options['data']['options'] ) ? $field_options['data']['options'] : array();
			$options = array();
			foreach ( $field_radio_options as $key => $opts ) {
				if ( is_array( $opts ) ) {

					if ( isset( $field_options['data']['display'] )
						&& 'value' == $field_options['data']['display']
						&& isset( $opts['display_value'] ) )
					{
						// if we have an actual display value and is set to be used, use it
						$display_value = $opts['display_value'];
						$value = $opts['value'];
					} else {
						// else, use the field value title and watch out because checkboxes fields need their titles as values
						$display_value = wpv_translate( 'field '. $field_options['id'] .' option '. $key .' title', $opts['title'], false, 'plugin Types' );
						if ( _wpv_is_field_of_type( 'wpcf-' . $field, 'checkboxes' ) ) {
							$value = $opts['title'];
						} else {
							$value = $opts['value'];
						}
					}
					$options[ $display_value ] = array(
						'#title' => $display_value,
						'#value' => $value,
						'#inline' => true,
						'#after' => '<br />'
                    );
                    $options[ $display_value ]['#attributes']['class'] = 'js-wpv-filter-trigger ' . $class;
                    $options[ $display_value ]['#attributes']['style'] = $style;

					if ( $format ) {
						$display_value_formatted_name = str_replace( '%%NAME%%', $options[ $display_value ]['#title'], $format );
						$options[ $display_value ]['#title'] = $display_value_formatted_name;
					}
					// Dependant stuff
					if ( $dependant || $counters ) {
						$this_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
						if ( empty( $value ) && !is_numeric( $value ) && is_object( $this_query ) ) {
							if ( isset( $aux_query_count ) ) {
								$this_checker = $aux_query_count;
							} else {
								$this_checker = $this_query->found_posts;
							}
						} else {
							$meta_criteria_to_filter = array( $field_real_name => array( $value ) );
							$data = array();
							$data['list'] = $wpv_data_cache['post_meta'];
							$data['args'] = $meta_criteria_to_filter;
							$data['kind'] = $filter_check_type;
							$data['comparator'] = $comparator;
							if ( $counters ) {
								$data['count_matches'] = true;
							}
							$this_checker = wpv_list_filter_checker( $data );
						}
						if ( $counters ) {
							$display_value_formatted_counter = str_replace( '%%COUNT%%', $this_checker, $options[ $display_value ]['#title'] );
							$options[ $display_value ]['#title'] = $display_value_formatted_counter;
						}
						if ( !$this_checker
							&& ( !empty( $value ) || is_numeric( $value ) )
							&& ( !isset( $_GET[$url_param] ) || $_GET[$url_param] !== $value )
							&& $dependant )
						{
							$options[ $display_value ]['#disable'] = 'true';
							$options[ $display_value ]['#labelclass'] = 'wpv-parametric-disabled';
							if ( isset( $empty_action['radios'] ) && $empty_action['radios'] == 'hide' ) {
								unset( $options[ $display_value ] );
							}
						}
					}
				}
			}
			// Render the form content
			$element = wpv_form_control( array(
					'field' => array(
							'#type' => 'radios',
							'#id' => 'wpv_control_radio_' . $field,
							'#name' => $url_param,
							'#attributes' => array( 'style' => $style, 'class' => $class ),
							'#inline' => true,
							'#options' => $options,
							'#default_value' => isset( $_GET[ $url_param ] ) ? $_GET[ $url_param ] : '' ) ) );

            return $element;
		} else if ( $field_type == 'checkbox' ) {
            // Checkbox field
            // Populate the $checkbox_name with the wpv-control title attribute OR the field name itself
            if ( isset( $atts['title'] ) ) {
                $checkbox_name =  wpv_translate( $url_param . '_title', $title, false, 'View ' . $view_name );
            } else {
				// NOTE mmmmmm we seem to have translated this $field_options['name'] right above...
                $checkbox_name = wpv_translate( 'field ' . $field_options['name'] . ' name', $field_options['name'], false, 'plugin Types' );
            }

            $value = $field_options['data']['set_value'];
            $coming_value = '';
			if ( isset( $_GET[ $url_param ] ) && !empty( $_GET[ $url_param ] ) ) {
				$value = esc_attr( $_GET[ $url_param ] );
				$coming_value = esc_attr( $_GET[ $url_param ] );
			} else if ( isset( $_GET[ $url_param ] ) && is_numeric( $_GET[ $url_param ] ) ) {
				// this only happens when the value to store when checked is actually zero - nonsense
				$value = 0;
				$coming_value = 0;
			} else if ( empty( $_GET[ $url_param ] ) ) {
				unset( $_GET[ $url_param ] );
			}
			$attributes = array( 'style' => '', 'class' => 'js-wpv-filter-trigger ' );
            $labelclass = '';
            $show_checkbox = true;

			if ( $format ) {
				$display_value_formatted_name = str_replace( '%%NAME%%', $checkbox_name, $format );
				$checkbox_name = $display_value_formatted_name;
			}
            // Dependant stuff
			if ( $dependant || $counters ) {
				$meta_criteria_to_filter = array( $field_real_name => array( $value ) );
				$data = array();
				$data['list'] = $wpv_data_cache['post_meta'];
				$data['args'] = $meta_criteria_to_filter;
				$data['kind'] = $filter_check_type;
				$data['comparator'] = $comparator;
				if ( $counters ) {
					$data['count_matches'] = true;
				}
				$this_checker = wpv_list_filter_checker( $data );
				if ( $counters ) {
					$display_value_formatted_count = str_replace( '%%COUNT%%', $this_checker, $checkbox_name );
					$checkbox_name = $display_value_formatted_count;
				}
				if ( !$this_checker && empty( $coming_value ) && $dependant ) {
					$attributes['#disabled'] = 'true';
					$labelclass = 'wpv-parametric-disabled';
					if ( isset( $empty_action['checkboxes'] ) && $empty_action['checkboxes'] == 'hide' ) {
						$show_checkbox = false;
					}
				}
			}
            if ( $show_checkbox ) {
				// Render the form content
                $attributes['class'] .= ' ' . $class;
                $attributes['style'] = $style;

				$element = wpv_form_control( array(
						'field' => array(
								'#type' => 'checkbox',
								'#id' => 'wpv_control_checkbox_' . $field,
								'#name' => $url_param,
								'#attributes' => $attributes,
								'#inline' => true,
								'#title' => $checkbox_name,
								'#labelclass' => $labelclass . ' ' . $label_class,
                                '#labelstyle' => $label_style,
								'#value' => $field_options['data']['set_value'],
								'#default_value' => 0 ) ) );
				if ( isset( $field_options['data']['save_empty'] ) && $field_options['data']['save_empty'] == 'yes' && $force_zero == 'true' ) {
					$attributes['class'] = '';
					$attributes['checked'] = 'checked';
					$element .= wpv_form_control( array(
							'field' => array(
									'#type' => 'hidden',
									'#id' => 'wpv_control_checkbox_' . $field . '_fakezero',
									'#name' => $url_param . '_fakezero',
									'#attributes' => $attributes,
									'#inline' => true,
									'#value' => 'yes',
									'#default_value' => 0 ) ) );
				}
			} else {
				$element = '';
			}
            return $element;

		} else if ( $field_type == 'checkboxes' ) {

            // Checkboxes field
            $defaults = array();
            $original_get = null;
            if ( isset( $_GET[ $url_param ] ) ) {
                $original_get = $_GET[ $url_param ];
                $defaults = $_GET[ $url_param ];
                if ( is_string( $defaults ) ) {
					$defaults = explode( ',',$defaults );
				}
                unset( $_GET[ $url_param ] );
            }
            $field_checkboxes_options = isset( $field_options['data']['options'] ) ? $field_options['data']['options'] : array();
            if ( isset( $field_checkboxes_options['default'] ) ) {
				// Remove the default option from the array because it breaks the loop below
				unset( $field_checkboxes_options['default'] );
			}
            foreach( $field_checkboxes_options as $key => $value ) {
                $display_value = wpv_translate( 'field '. $field_options['id'] .' option '. $key .' title', trim( $value['title'] ), false, 'plugin Types' );
                if ( _wpv_is_field_of_type( 'wpcf-' . $field, 'checkboxes' ) ) {
					$value = trim( $value['title'] );
				} else {
					$value = trim( $value['value'] );
                }

                $options[ $value ]['#name'] = $url_param . '[]';
                $options[ $value ]['#title'] = $display_value;
                $options[ $value ]['#value'] = $value;
                $options[ $value ]['#default_value'] = in_array( $value, $defaults );
                //$options[$value]['#inline'] = true;
                //$options[$value]['#after'] = '&nbsp;&nbsp;';
                $options[ $value ]['#attributes']['class'] = 'js-wpv-filter-trigger ' . $class;
                $options[ $value ]['#attributes']['style'] = $style;
                $options[ $value ]['#labelclass'] = $label_class;
                $options[ $value ]['#labelstyle'] = $label_style;

				if ( $format ) {
					$display_value_formatted_name = str_replace( '%%NAME%%', $options[ $value ]['#title'], $format );
					$options[ $value ]['#title'] = $display_value_formatted_name;
				}
                // Dependant stuff
				if ( $dependant || $counters ) {
					$meta_criteria_to_filter = array( $field_real_name => array( $value ) ); // TODO DONE IMPORTANT check what is coming here as value, maybe $opts['title'] sometimes
					$this_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
					if ( empty( $value ) && !is_numeric( $value ) && is_object( $this_query ) ) {
						if ( isset( $aux_query_count ) ) {
							$this_checker = $aux_query_count;
						} else {
							$this_checker = $this_query->found_posts;
						}
					} else {
						$data = array();
						$data['list'] = $wpv_data_cache['post_meta'];
						$data['args'] = $meta_criteria_to_filter;
						$data['kind'] = $filter_check_type;
						$data['comparator'] = $comparator;
						if ( $counters ) {
							$data['count_matches'] = true;
						}
						$this_checker = wpv_list_filter_checker( $data );
					}
					if ( $counters ) {
						$display_value_formatted = str_replace( '%%COUNT%%', $this_checker, $options[ $value ]['#title'] );
						$options[ $value ]['#title'] = $display_value_formatted;
					}
					if ( !$this_checker && ( !empty( $value ) || is_numeric( $value ) ) && !$options[ $value ]['#default_value'] && $dependant ) {
						$options[ $value ]['#attributes']['#disabled'] = 'true';
						$options[ $value ]['#labelclass'] .= ' wpv-parametric-disabled';
						if ( isset( $empty_action['checkboxes'] ) && $empty_action['checkboxes'] == 'hide' ) {
							unset( $options[ $value ] );
						}
					}
				}
            }
            // Render the form content
            $element = wpv_form_control( array(
					'field' => array(
                            '#type' => 'checkboxes',
                            '#id' => 'wpv_control_checkbox_' . $field,
                            '#name' => $url_param . '[]',
                            '#attributes' => array( 'style' => '' ),
                            '#inline' => true,
                            '#options' => $options ) ) );
            if ( $original_get ) {
                $_GET[ $url_param ] = $original_get;
            }
            return $element;
		} else if ( $field_type == 'select' ) {
			// Select field
			$field_select_options = isset( $field_options['data']['options'] ) ? $field_options['data']['options'] : array();;
			$options = array();
			$opt_aux = array();
			foreach ( $field_select_options as $key => $opts ) {
				if ( is_array( $opts ) ) {

					$display_value = wpv_translate( 'field '. $field_options['id'] .' option '. $key .' title', $opts['title'], false, 'plugin Types' );
					if ( _wpv_is_field_of_type( 'wpcf-' . $field, 'checkboxes' ) ) {
						$value = $opts['title'];
					} else {
						$value = $opts['value'];
					}

					$options[ $display_value ] = array(
						'#title' => $display_value,
						'#value' => $value,
						'#inline' => true,
						'#after' => '<br />'
                    );
                    $opt_aux[ $display_value ] = $value;
					$options[ $display_value ]['#attributes']['class'] = 'js-wpv-filter-trigger ';

					if ( $format ) {
						$display_value_formatted_name = str_replace( '%%NAME%%', $options[ $display_value ]['#title'], $format );
						$options[ $display_value ]['#title'] = $display_value_formatted_name;
					}
					// Dependant stuff
					if ( $dependant || $counters ) {
						$this_query = apply_filters( 'wpv_filter_wpv_get_post_query', null );
						if ( empty( $value ) && !is_numeric( $value ) && is_object( $this_query ) ) {
							if ( isset( $aux_query_count ) ) {
								$this_checker = $aux_query_count;
							} else {
								$this_checker = $this_query->found_posts;
							}
						} else {
							$meta_criteria_to_filter = array( $field_real_name => array( $value ) );
							$data = array();
							$data['list'] = $wpv_data_cache['post_meta'];
							$data['args'] = $meta_criteria_to_filter;
							$data['kind'] = $filter_check_type;
							$data['comparator'] = $comparator;
							if ( $counters ) {
								$data['count_matches'] = true;
							}
							$this_checker = wpv_list_filter_checker( $data );
						}
						if ( $counters ) {
							$display_value_formatted_counter = str_replace( '%%COUNT%%', $this_checker, $options[ $display_value ]['#title'] );
							$options[ $display_value ]['#title'] = $display_value_formatted_counter;
						}
						if ( !$this_checker && ( !empty( $value ) || is_numeric( $value ) ) && $dependant ) {
							// TODO DONE we need to adjust this with the $default_value below
							$options[ $display_value ]['#disable'] = 'true';
							$options[ $display_value ]['#labelclass'] = 'wpv-parametric-disabled';
							if ( $multi == 'multiple' ) {
								if ( isset( $empty_action['multi-select'] ) && $empty_action['multi-select'] == 'hide' ) {
									unset( $options[ $display_value ] );
								}
							} else if ( isset( $empty_action['select'] ) && $empty_action['select'] == 'hide' ) {
								unset( $options[ $display_value ] );
							}
						}
					}
				}
			}
			$default_value = false;
			if ( isset( $_GET[ $url_param ] ) ) {
				if ( is_array( $_GET[ $url_param ] ) ) {
					if ( count( array_intersect($_GET[ $url_param ], $opt_aux) ) > 0 ) {
						$default_value = $_GET[ $url_param ];
					}
				} else {
					if ( in_array( $_GET[ $url_param ], $opt_aux ) ) {
						$default_value = $_GET[ $url_param ];
					}
				}
			}

			// Now we need to recreate the $options element if it is a default one and is disabled or removed
			if ( $default_value !== false && is_array( $default_value ) ) {
				foreach ( $default_value as $dv ) {
					$aux_display_values = array_keys( $opt_aux, $dv, true );
					foreach ( $aux_display_values as $aux_dv ) {
						if ( isset( $options[ $aux_dv ] ) ) {
							if ( isset( $options[ $aux_dv ]['#disable'] ) ) {
								unset( $options[ $aux_dv ]['#disable'] );
							}
							$options[ $aux_dv ]['#labelclass'] = '';
						} else {
							$options[ $aux_dv ] = array(
								'#title' => $aux_dv,
								'#value' => $dv,
								'#inline' => true,
								'#after' => '<br />'
							);
							$options[ $aux_dv ]['#attributes']['class'] = 'js-wpv-filter-trigger ';
						}
					}
				}
			} else if ( $default_value !== false ) {
				$aux_display_values = array_keys( $opt_aux, $default_value, true );
				foreach ( $aux_display_values as $aux_dv ) {
					if ( isset( $options[ $aux_dv ] ) ) {
						if ( isset( $options[ $aux_dv ]['#disable'] ) ) {
							unset( $options[ $aux_dv ]['#disable'] );
						}
						$options[ $aux_dv ]['#labelclass'] = '';
					} else {
						$options[ $aux_dv ] = array(
							'#title' => $aux_dv,
							'#value' => $default_value,
							'#inline' => true,
							'#after' => '<br />'
						);
						$options[ $aux_dv ]['#attributes']['class'] = 'js-wpv-filter-trigger ';
					}
				}
			}


			// Render the form content
			$element = wpv_form_control( array(
					'field' => array(
	                        '#type' => 'select',
	                        '#id' => 'wpv_control_select_' . $url_param,
	                        '#name' => $url_param . '[]',
	                        '#attributes' => array( 'style' => $style, 'class' => 'js-wpv-filter-trigger ' . $class ),
	                        '#inline' => true,
							'#options' => $options,
							'#default_value' => $default_value,
							'#multiple' => $multi ) ) );
	        return $element;

		} else if ( $field_type == 'textfield' ) {
			// Textfield field
			$default_value = '';
			if ( isset( $_GET[ $url_param ] ) ) {
				$default_value = esc_attr( wp_unslash( $_GET[ $url_param ] ) );
			}

			// Prepend classname with an empty space for string concatenation
			$class = empty( $class ) ? $class : ' ' . $class;

			$element = '<input'
				. ' type="text"'
				. ' id="wpv_control_textfield_' . esc_attr( $url_param ) . '"'
				. ' name="' . esc_attr( $url_param ) . '"'
				. ' value="' . $default_value . '"'// This is escaped just above
				. ' class="js-wpv-filter-trigger-delayed wpcf-form-textfield form-textfield textfield' . $class . '"'// This is escaped just above
				. ' style="' . $style  . '"'// This is escaped just above
				. ' /> ';

	        return $element;

		} else if ( $field_type == 'date' || $field_type == 'datepicker' ) {
			// Date or datepicker field
			$out = wpv_render_datepicker( $url_param, $date_format, $default_date );
            return $out;
        }
        // In case we have a field attribute but it does not match any vaid type, return nothing
		return '';

	} else {
        // When there is a type attribute without field or auto_fill or values attributes it's likely for a checkbox or a datepicker
        // But I'm not sure what is this used for, because it really does not filter by any field
        $default_value = '';
        if ( isset( $_GET[ $url_param ] ) ) {
            $default_value = $_GET[ $url_param ];
        }
        switch ( $type ) {
            case 'checkbox':
                // In this case, there is no way to implement dependant parametric search, because we have no field to check against
                $element = array(
						'field' => array(
                                '#type' => $type,
                                '#id' => 'wpv_control_' . $type . '_' . $url_param,
                                '#name' => $url_param,
                                '#attributes' => array( 'style' => $style, 'class' => 'js-wpv-filter-trigger ' . $class ),
                                '#inline' => true,
                                '#value' => $default_value ) );
                $element['field']['#title'] = wpv_translate( $url_param . '_title', $title, false, 'View ' . $view_name );
                $element = wpv_form_control( $element );
                break;
            case 'datepicker':
                $element = wpv_render_datepicker( $url_param, $date_format, $default_date );
                break;
			case 'tetfield':
				$default_value = '';
				if ( isset( $_GET[ $url_param ] ) ) {
					$default_value = esc_attr( wp_unslash( $_GET[ $url_param ] ) );
				}
				// Prepend classname with an empty space for string concatenation
				$class = empty( $class ) ? $class : ' ' . $class;

				$element = '<input'
					. ' type="text"'
					. ' id="wpv_control_textfield_' . esc_attr( $url_param ) . '"'
					. ' name="' . esc_attr( $url_param ) . '"'
					. ' value="' . $default_value . '"'// This is escaped just above
					. ' class="js-wpv-filter-trigger-delayed wpcf-form-textfield form-textfield textfield' . $class . '"'// This is escaped just above
					. ' style="' . $style  . '"'// This is escaped just above
					. ' /> ';
				break;
            default:
                $element = array(
						'field' => array(
                                '#type' => $type,
                                '#id' => 'wpv_control_' . $type . '_' . $url_param,
                                '#name' => $url_param,
                                '#attributes' => array( 'style' => $style, 'class' => $class ),
                                '#inline' => true,
                                '#value' => $default_value ) );
                $element = wpv_form_control( $element );
                break;
        }
        return $element;
    }
}

/**
* Auxiliar functions for the wpv-control shortcode
*/

/**
* _wpv_is_field_of_type
*
* Checks if a field is a Types field of a given type
*
* @note $field_name must start with the wpcf- prefix so for fields created outside Types and then under Types control you need to add it on origin
*
* @param $field_name (string) the field name
* @param $type (string) the Types type to check against
*
* @return (bool)
*
* @since unknown
*
*/

function _wpv_is_field_of_type($field_name, $type) {
    $opt = get_option('wpcf-fields');
    if( $opt ) {
        if ( strpos( $field_name, 'wpcf-' ) === 0 ) {
			$field_name = substr( $field_name, 5 );
        }
        if ( isset( $opt[$field_name] ) && is_array( $opt[$field_name] ) && isset( $opt[$field_name]['type'] ) ) {
            $field_type = strtolower( $opt[$field_name]['type'] );
            if ( $field_type == $type ) {
                return true;
            }
        }
    }
    return false;
}

/**
* _wpv_get_field_real_slug
*
* For a Types field,takes the field name and returns the field real meta_key
*
* @param $field_name (string) the field name
*
* @return (string)
*
* @since unknown
*
*/

function _wpv_get_field_real_slug($field_name) {
	$real_slug = $field_name;
	$opt = get_option('wpcf-fields');
    if($opt) {
        if ( isset( $opt[$field_name] ) && isset( $opt[$field_name]['meta_key'] ) ) {
            $real_slug = $opt[$field_name]['meta_key'];
        }

    }
	return $real_slug;
}

/**
* wpv_render_datepicker
*
* Renders the datepicker for date based frontend filters
*
* @param $url_param (string) the URL parameter used on the frontend filter
* @param $date_format (string) the date format to use when displaying the selected date besides the datepicker
* @param $default_date (string) the default date to be used when there is no value passed by the URL parameter - special case NONE
*
* @return string containing the needed inputs
*
* @since unknown
*
* @note $default_date default value was changed in 1.7 from NOW() to NONE; empty will mean NONE too
*
* @todo add an attribute for themes http://rtsinani.github.io/jquery-datepicker-skins/
* OR better an option in the Views settings, because we can not have two different styles for two datepickers
*/

function wpv_render_datepicker( $url_param, $date_format, $default_date = '' ) {

	$display_date = $datepicker_date = '';

    if ( $date_format == '' ) {
        $date_format = get_option( 'date_format' );
    }

	$clear_button_style = '';

    if( isset( $_GET[$url_param] ) ) {
        if ( $_GET[$url_param] == '' || $_GET[$url_param] == '0' ) {
			$date = '';
		} else {
			$date = $_GET[$url_param];
		}
    } else {
		if (
			$default_date == ''
			|| $default_date == 'NONE'
		) {
			$date = '';
		} else {
			$date = wpv_filter_parse_date( $default_date );
		}
    }

	if ( is_numeric( $date ) ) {
		if (
			$date < -12219292800
			|| $date > 32535215940
		) {
			$date = '';
		}
	} else {
		$date = '';
	}

	if ( $date != '' ) {
    	$display_date = adodb_date( $date_format, $date );
	} else {
		$clear_button_style = ' style="display:none"';
	}


    $out = '';
    $out .= '<span class="wpv_date_input js-wpv-date-param-' . $url_param . ' js-wpv-date-display" data-param="' . $url_param . '">' . $display_date . '</span> ';
    $out .= '<input type="hidden" class="js-wpv-date-param-' . $url_param . '-value js-wpv-filter-trigger" name="' . $url_param . '" value="' . $date . '" />';
    $out .= '<input type="hidden" class="js-wpv-date-param-' . $url_param . '-format" name="' . $url_param . '-format" value="' . $date_format . '" />';

	if ( $date != '' ) {
    	$datepicker_date = adodb_date( 'dmY', $date );
	}
    $out .= '<input type="hidden" data-param="' . $url_param . '" class="wpv-date-front-end js-wpv-frontend-datepicker js-wpv-date-front-end-' . $url_param . '" value="' . $datepicker_date . '"/>';

	$delete_date_image = WPV_URL_EMBEDDED_FRONTEND . '/res/img/delete.png';
	$delete_date_image = apply_filters( 'wpv_filter_wpv_delete_date_image', $delete_date_image );
	$delete_date_image = apply_filters( 'wptoolset_filter_wptoolset_delete_date_image', $delete_date_image );

	$out .= '<img src="' . $delete_date_image . '" title="' . esc_attr( __( 'Clear date', 'wpv-views' ) ) . '" alt="' . esc_attr( __( 'Clear date', 'wpv-views' ) ) . '" class="wpv-date-front-end-clear js-wpv-date-front-end-clear js-wpv-date-front-end-clear-' . $url_param . '" data-param="' . $url_param . '"' . $clear_button_style . ' />';

    return $out;
}

/**
* Custom Walkers used in taxonomy parametric searches
*/

/**
* Walker_Category_select
*
* Walker to return select or multi-select options when walking taxonomies
*
* @param $selected_id (int|array) selected term or array of selected terms
* @param $slug_mode (bool) true uses term slugs, false uses term names
* @param $format (string|false) structure of the option label, use %%NAME%% or %%COUNT%% as placeholders
* @param $taxonomy (string) relevant taxonomy
*
* @since unknown
* @deprecated 2.3.2 Can be deleted :-)
*/

class Walker_Category_select extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

    function __construct($selected_id, $slug_mode = false, $format = false, $taxonomy = 'category', $type = 'select' ){
		$this->selected = $selected_id;
        $this->slug_mode = $slug_mode;
        $this->format = $format;
		$this->counters = ( $this->format && strpos( $this->format, '%%COUNT%%' ) !== false ) ? true : false;
        $view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
        $this->dependant = false;
        $this->empty_action = 'none';
        if ( isset( $view_settings['dps'] ) && is_array( $view_settings['dps'] ) && isset( $view_settings['dps']['enable_dependency'] ) && $view_settings['dps']['enable_dependency'] == 'enable' ) {
			$this->dependant = true;
			$force_disable_dependant = apply_filters( 'wpv_filter_wpv_get_force_disable_dps', false );
			if ( $force_disable_dependant ) {
				$this->dependant = false;
			} else {
				$this->empty_action = 'hide';
				if ( $type == 'select' && isset( $view_settings['dps']['empty_select'] ) && $view_settings['dps']['empty_select'] == 'disable' ) {
					$this->empty_action = 'disable';
				} else if ( $type == 'multi-select' && isset( $view_settings['dps']['empty_multi_select'] ) && $view_settings['dps']['empty_multi_select'] == 'disable' ) {
					$this->empty_action = 'disable';
				}
			}
		}

        global $wp_query;
        $this->in_this_tax_archive_page = false;
        $this->tax_archive_term = null;

        if (
            is_tax()
            || is_category()
            || is_tag()
        ) {
            $term = $wp_query->get_queried_object();

            if ( $term
                && isset( $term->taxonomy )
                && $term->taxonomy == $taxonomy
            ) {
                $this->in_this_tax_archive_page = true;
                $this->tax_archive_term = $term;
            }
        }

		$this->show = true;
		$this->posts_to_taxes = array();
		if ( $this->dependant || $this->counters ) {
			$operator = isset( $view_settings['taxonomy-' . $taxonomy . '-attribute-operator'] ) ? $view_settings['taxonomy-' . $taxonomy . '-attribute-operator'] : 'IN';
			// Construct $this->posts_to_taxes
			if (
				empty( $this->selected )
				|| $this->selected === 0
				|| (
					is_array( $this->selected )
					&& in_array( (string) 0, $this->selected )
				) || (
					$type == 'multi-select'
					&& $operator == 'AND'
				)
			) {
				// This is when there is no non-default selected
				$wpv_data_cache = $wpv_data_cache = WPV_Cache::$stored_cache;
				if (
					isset( $wpv_data_cache[$taxonomy . '_relationships'] )
					&& is_array( $wpv_data_cache[$taxonomy . '_relationships'] )
				) {
					foreach ( $wpv_data_cache[$taxonomy . '_relationships'] as $pid => $tax_array ) {
						if (
							is_array( $tax_array )
							&& count( $tax_array ) > 0
						) {
							$this_post_taxes = wp_list_pluck( $tax_array, 'term_id', 'term_id' );
							$this->posts_to_taxes[$pid] = $this_post_taxes;
						}
					}
				}
			} else {
				// When there is a selected value, create a pseudo-cache based on all the other filters
				$query = apply_filters( 'wpv_filter_wpv_get_dependant_extended_query_args', array(), $view_settings, array( 'taxonomy' => $taxonomy ) );
				$aux_cache_query = null;
				if ( isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ) {
					foreach ( $query['tax_query'] as $qt_index => $qt_val ) {
						if ( is_array( $qt_val ) && isset( $qt_val['taxonomy'] ) && $qt_val['taxonomy'] == $taxonomy ) {
							unset( $query['tax_query'][$qt_index] );
						}
					}
				}
				$aux_cache_query = new WP_Query($query);
				if ( is_array( $aux_cache_query->posts ) && !empty( $aux_cache_query->posts ) ) {
					$f_taxes = array( $taxonomy );
					$wpv_data_cache = WPV_Cache::generate_cache( $aux_cache_query->posts, array( 'tax' => $f_taxes ) );
					if ( isset( $wpv_data_cache[$taxonomy . '_relationships'] ) && is_array( $wpv_data_cache[$taxonomy . '_relationships'] ) ) {
						foreach ( $wpv_data_cache[$taxonomy . '_relationships'] as $pid => $tax_array ) {
							if ( is_array( $tax_array ) && count( $tax_array ) > 0 ) {
								//$this_post_taxes = array_combine( array_values( array_keys( $tax_array ) ) , array_keys( $tax_array ) );
								$this_post_taxes = wp_list_pluck( $tax_array, 'term_id', 'term_id' );
								$this->posts_to_taxes[$pid] = $this_post_taxes;
							}
						}
					}
				}
			}
		}
	}

	function start_lvl(&$output, $depth = 0, $args = array()) {
	}

	function end_lvl(&$output, $depth = 0, $args = array()) {
	}

	function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0) {
		extract($args);
		$selected = '';

		$indent = str_repeat('-', $depth);
		if ($indent != '') {
			$indent = '&nbsp;' . str_repeat('&nbsp;', $depth) . $indent;
		}

		$tax_option = $category->name;
		if ($this->format) {
			$tax_option = str_replace( '%%NAME%%', $category->name, $this->format );
		}

		$this->show = true;
		if ( $this->dependant || $this->counters ) {
			$wpv_tax_criteria_matching_posts = array();
			$wpv_tax_criteria_to_filter = array($category->term_id => $category->term_id);
			$wpv_tax_criteria_matching_posts = wp_list_filter($this->posts_to_taxes, $wpv_tax_criteria_to_filter);
			if ( count( $wpv_tax_criteria_matching_posts ) == 0 && $this->dependant ) {
				$this->show = false;
			}
			if ( $this->counters ) {
				$tax_option = str_replace( '%%COUNT%%', count( $wpv_tax_criteria_matching_posts ), $tax_option );
			}
		}

		$real_value = $category->name;
		if ( $this->slug_mode ) {
			$real_value = urldecode( $category->slug );
		}

		// If the current page is a taxonomy page for the taxonomy the filter refers to
        if ( $this->in_this_tax_archive_page ) {
		    // ... and if the querried taxonomy term is the current term rendered in the filter
            if ( $this->tax_archive_term->slug == $category->slug ) {
                // ... display the term and make it selected
                $output .= '<option value="' . $real_value . '" selected="selected">' . $indent . $tax_option . "</option>\n";
            }
            // ... else disregard this taxonomy term option for the filter
        } else {
		    // ... else let the normal procedures decide whether to display the option or not.
            if (is_array($this->selected)) {
                foreach ($this->selected as $sel) {
                    $selected .= $sel == $real_value ? ' selected="selected"' : '';
                }
            } else {
                $selected .= $this->selected == $real_value ? ' selected="selected"' : '';
            }

            if ($this->show || !empty($selected)) {
                $output .= '<option value="' . $real_value . '"' . $selected . '>' . $indent . $tax_option . "</option>\n";
            } else if ($this->empty_action != 'hide') {
                $output .= '<option value="' . $real_value . '"' . $selected . ' disabled="disabled">' . $indent . $tax_option . "</option>\n";
            }
        }
	}

	function end_el(&$output, $category, $depth = 0, $args = array()) {
	}
}

/**
* Walker_Category_radios
*
* Walker to return radios when walking taxonomies
*
* @param $selected_id (int|array) selected term (or array of selected terms although this does not allow for multiple selected items, just in case)
* @param $slug_mode (bool) true uses term slugs, false uses term names
* @param $format (string|false) structure of the option label
*    use %%NAME%% or %%COUNT%% as placeholders
* @param $taxonomy (string) relevant taxonomy
*
* @since unknown
* @deprecated 2.3.2 Can be deleted :-)
*/

class Walker_Category_radios extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

    function __construct($selected_id, $slug_mode = false, $format = false, $taxonomy = 'category', $style = '', $class = '', $label_style = '', $label_class = ''){
		$this->selected = $selected_id;
        $this->slug_mode = $slug_mode;
        $this->format = $format;
        $this->style = $style;
        $this->input_class = $class;
        $this->label_style = $label_style;
        $this->label_class = $label_class;
		$this->counters = ( $this->format && strpos( $this->format, '%%COUNT%%' ) !== false ) ? true : false;
        $view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
        $this->dependant = false;
        $this->empty_action = 'none';
        if ( isset( $view_settings['dps'] ) && is_array( $view_settings['dps'] ) && isset( $view_settings['dps']['enable_dependency'] ) && $view_settings['dps']['enable_dependency'] == 'enable' ) {
			$this->dependant = true;
			$force_disable_dependant = apply_filters( 'wpv_filter_wpv_get_force_disable_dps', false );
			if ( $force_disable_dependant ) {
				$this->dependant = false;
			} else {
				$this->empty_action = 'hide';
				if ( isset( $view_settings['dps']['empty_radios'] ) && $view_settings['dps']['empty_radios'] == 'disable' ) {
					$this->empty_action = 'disable';
				}
			}
		}

        global $wp_query;
        $this->in_this_tax_archive_page = false;
        $this->tax_archive_term = null;

        if (
            is_tax()
            || is_category()
            || is_tag()
        ) {
            $term = $wp_query->get_queried_object();

            if ( $term
                && isset( $term->taxonomy )
                && $term->taxonomy == $taxonomy
            ) {
                $this->in_this_tax_archive_page = true;
                $this->tax_archive_term = $term;
            }
        }


		$this->show = true;
		$this->posts_to_taxes = array();
		if ( $this->dependant || $this->counters  ) {
			// Construct $this->posts_to_taxes
			if (
				empty( $this->selected )
				|| $this->selected === 0
				|| (
					is_array( $this->selected )
					&& in_array( (string) 0, $this->selected )
				)
			) {
				// This is when there is no non-default selected
				$wpv_data_cache = $wpv_data_cache = WPV_Cache::$stored_cache;
				if (
					isset( $wpv_data_cache[$taxonomy . '_relationships'] )
					&& is_array( $wpv_data_cache[$taxonomy . '_relationships'] )
				) {
					foreach ( $wpv_data_cache[$taxonomy . '_relationships'] as $pid => $tax_array ) {
						if (
							is_array( $tax_array )
							&& count( $tax_array ) > 0
						) {
							$this_post_taxes = wp_list_pluck( $tax_array, 'term_id', 'term_id' );
							$this->posts_to_taxes[$pid] = $this_post_taxes;
						}
					}
				}
			} else {
				// When there is a selected value, create a pseudo-cache based on all the other filters
				$query = apply_filters( 'wpv_filter_wpv_get_dependant_extended_query_args', array(), $view_settings, array( 'taxonomy' => $taxonomy ) );
				$aux_cache_query = null;
				if ( isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ) {
					foreach ( $query['tax_query'] as $qt_index => $qt_val ) {
						if ( is_array( $qt_val ) && isset( $qt_val['taxonomy'] ) && $qt_val['taxonomy'] == $taxonomy ) {
							unset( $query['tax_query'][$qt_index] );
						}
					}
				}
				$aux_cache_query = new WP_Query($query);
				if ( is_array( $aux_cache_query->posts ) && !empty( $aux_cache_query->posts ) ) {
					$f_taxes = array( $taxonomy );
					$wpv_data_cache = WPV_Cache::generate_cache( $aux_cache_query->posts, array( 'tax' => $f_taxes ) );
					if ( isset( $wpv_data_cache[$taxonomy . '_relationships'] ) && is_array( $wpv_data_cache[$taxonomy . '_relationships'] ) ) {
						foreach ( $wpv_data_cache[$taxonomy . '_relationships'] as $pid => $tax_array ) {
							if ( is_array( $tax_array ) && count( $tax_array ) > 0 ) {
								//$this_post_taxes = array_combine( array_values( array_keys( $tax_array ) ) , array_keys( $tax_array ) );
								$this_post_taxes = wp_list_pluck( $tax_array, 'term_id', 'term_id' );
								$this->posts_to_taxes[$pid] = $this_post_taxes;
							}
						}
					}
				}
			}
		}
	}

	function start_lvl(&$output, $depth = 0, $args = array()) {
	}

	function end_lvl(&$output, $depth = 0, $args = array()) {
	}

	function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0) {
		extract($args);
		$selected = '';

		if ( empty($taxonomy) )
            $taxonomy = 'category';

        if ( $taxonomy == 'category' ) {
            $name = 'post_category';
        } else {
            $name = $taxonomy;
		}

		$indent = str_repeat('-', $depth);
		if ( $indent != '' ) {
			$indent = '&nbsp;' . str_repeat('&nbsp;', $depth) . $indent;
		}

		$tax_option = $category->name;
		if ( $this->format ) {
			$tax_option = str_replace( '%%NAME%%', $category->name, $this->format );
		}

		$this->show = true;
		if ( $this->dependant || $this->counters ) {
			$wpv_tax_criteria_matching_posts = array();
			$wpv_tax_criteria_to_filter = array($category->term_id => $category->term_id);
			$wpv_tax_criteria_matching_posts = wp_list_filter($this->posts_to_taxes, $wpv_tax_criteria_to_filter);
			if ( count( $wpv_tax_criteria_matching_posts ) == 0 && $this->dependant ) {
				$this->show = false;
			}
			if ( $this->counters ) {
				$tax_option = str_replace( '%%COUNT%%', count( $wpv_tax_criteria_matching_posts ), $tax_option );
			}
		}

		$tmp = is_array( $this->selected ) ? $this->selected[0] : $this->selected;
		$real_value = $category->name;
		if ( $this->slug_mode ) {
			$real_value = urldecode( $category->slug );
		}
		$selected .= ( $tmp == $real_value ) ? ' checked' : '';

        // If the current page is a taxonomy page for the taxonomy the filter refers to
        if ( $this->in_this_tax_archive_page ) {
            // ... and if the querried taxonomy term is the current term rendered in the filter
            if ( $this->tax_archive_term->slug == $category->slug ) {
                // ... display the term and make it checked
                $output .= '<input id="' . $name . '-' . $category->slug . '"' . (!empty($this->style) ? ' style="' . $this->style . '"' : '') . ' class="js-wpv-filter-trigger' . (!empty($this->input_class) ? ' ' . $this->input_class : '') . '" name="' . $name . '" type="radio" value="' . $real_value . '" checked />
            <label for="' . $name . '-' . $category->slug . '"' . (!empty($this->label_style) ? ' style="' . $this->label_style . '"' : '') . ' class="radios-taxonomies-title' . (!empty($this->label_class) ? ' ' . $this->label_class : '') . '">' . $indent . $tax_option . '</label>';
            }
            // ... else disregard this taxonomy term option for the filter
        } else {
            // ... else let the normal procedures decide whether to display the option or not.
            if ($this->show || !empty($selected)) {
                $output .= '<input id="' . $name . '-' . $category->slug . '"' . (!empty($this->style) ? ' style="' . $this->style . '"' : '') . ' class="js-wpv-filter-trigger' . (!empty($this->input_class) ? ' ' . $this->input_class : '') . '" name="' . $name . '" type="radio" value="' . $real_value . '"' . $selected . '/>
            <label for="' . $name . '-' . $category->slug . '"' . (!empty($this->label_style) ? ' style="' . $this->label_style . '"' : '') . ' class="radios-taxonomies-title' . (!empty($this->label_class) ? ' ' . $this->label_class : '') . '">' . $indent . $tax_option . '</label>';
            } else if ($this->empty_action != 'hide') {
                $output .= '<input id="' . $name . '-' . $category->slug . '"' . (!empty($this->style) ? ' style="' . $this->style . '"' : '') . ' class="js-wpv-filter-trigger' . (!empty($this->input_class) ? ' ' . $this->input_class : '') . '" name="' . $name . '" type="radio" value="' . $real_value . '"' . $selected . ' disabled="disabled" />
            <label for="' . $name . '-' . $category->slug . '"' . (!empty($this->label_style) ? ' style="' . $this->label_style . '"' : '') . ' class="radios-taxonomies-title wpv-parametric-disabled' . (!empty($this->label_class) ? ' ' . $this->label_class : '') . '">' . $indent . $tax_option . '</label>';
            }
        }
	}

	function end_el(&$output, $category, $depth = 0, $args = array()) {
		if ( $this->show ) {
			$output .= "\n";
		}
	}
}

/**
* Walker_Category_id_select
*
* Walker to return radios when walking taxonomies
*
* @param $selected_id (int|array) selected term (or array of selected terms although this does not allow for multiple selected items, just in case)
*
* @note check where is this used (I think on the backend) and why it has only one @param
*
* @since unknown
*/

class Walker_Category_id_select extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

    function __construct($selected_id){
		$this->selected = $selected_id;
	}

	function start_lvl(&$output, $depth = 0, $args = array()) {
	}

	function end_lvl(&$output, $depth = 0, $args = array()) {
	}

	function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0) {
		extract($args);

		$indent = str_repeat('-', $depth);
		if ($indent != '') {
			$indent = '&nbsp;' . str_repeat('&nbsp;', $depth) . $indent;
		}

        $selected = $this->selected == $category->term_id ? ' selected="selected"' : '';
    		$output .= '<option value="' . $category->term_id. '"' . $selected . '>' . $indent . $category->name . "</option>\n";
	}

	function end_el(&$output, $category, $depth = 0, $args = array()) {
	}
}

if ( !class_exists( 'WPV_Walker_Category_Checklist' ) ) {

	/**
	* WPV_Walker_Category_Checklist
	*
	* Walker to return checkboxes when walking taxonomies
	*
	* @param $slug_mode (bool) true uses term slugs, false uses term names
	* @param $format (string|false) structure of the option label
	*    use %%NAME%% or %%COUNT%% as placeholders
	* @param $taxonomy (string) relevant taxonomy
	*
	* @since unknown
	* @deprecated 2.3.2 Can be deleted :-) Kept in wpv_terms_checklist :-/
	*/

	class WPV_Walker_Category_Checklist extends Walker {
		var $tree_type = 'category';
		var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

		function __construct($slug_mode = false, $format = false, $taxonomy = 'category', $selected_cats = array(), $style = '', $class = '', $label_style = '', $label_class = '' ) {
			$this->slug_mode = $slug_mode;
			$this->format = $format;
			$this->counters = ( $this->format && strpos( $this->format, '%%COUNT%%' ) !== false ) ? true : false;
			$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );
			$this->dependant = false;
			$this->empty_action = 'none';
            $this->style = $style;
            $this->input_class = $class;
            $this->label_style = $label_style;
            $this->label_class = $label_class;
			if ( isset( $view_settings['dps'] ) && is_array( $view_settings['dps'] ) && isset( $view_settings['dps']['enable_dependency'] ) && $view_settings['dps']['enable_dependency'] == 'enable' ) {
				$this->dependant = true;
				$force_disable_dependant = apply_filters( 'wpv_filter_wpv_get_force_disable_dps', false );
				if ( $force_disable_dependant ) {
					$this->dependant = false;
				} else {
					$this->empty_action = 'hide';
					if ( isset( $view_settings['dps']['empty_checkboxes'] ) && $view_settings['dps']['empty_checkboxes'] == 'disable' ) {
						$this->empty_action = 'disable';
					}
				}
			}

            global $wp_query;
            $this->in_this_tax_archive_page = false;
            $this->tax_archive_term = null;

            if (
                is_tax()
                || is_category()
                || is_tag()
            ) {
                $term = $wp_query->get_queried_object();

                if ( $term
                    && isset( $term->taxonomy )
                    && $term->taxonomy == $taxonomy
                ) {
                    $this->in_this_tax_archive_page = true;
                    $this->tax_archive_term = $term;
                }
            }

			$this->show = true;
			$this->posts_to_taxes = array();
			if ( $this->dependant || $this->counters ) {
				// Construct $this->posts_to_taxes
				$operator = isset( $view_settings['taxonomy-' . $taxonomy . '-attribute-operator'] ) ? $view_settings['taxonomy-' . $taxonomy . '-attribute-operator'] : 'IN';
				if ( empty( $selected_cats ) || $operator == 'AND' ) {
					// This is when there is no non-default selected
					$wpv_data_cache = $wpv_data_cache = WPV_Cache::$stored_cache;
					if ( isset( $wpv_data_cache[$taxonomy . '_relationships'] ) && is_array( $wpv_data_cache[$taxonomy . '_relationships'] ) ) {
						foreach ( $wpv_data_cache[$taxonomy . '_relationships'] as $pid => $tax_array ) {
							if (
								is_array( $tax_array )
								&& count( $tax_array ) > 0
							) {
								$this_post_taxes = wp_list_pluck( $tax_array, 'term_id', 'term_id' );
								$this->posts_to_taxes[$pid] = $this_post_taxes;
							}
						}
					}
				} else {
					// When there is a selected value, create a pseudo-cache based on all the other filters
					$query = apply_filters( 'wpv_filter_wpv_get_dependant_extended_query_args', array(), $view_settings, array( 'taxonomy' => $taxonomy ) );
					$aux_cache_query = null;
					if ( isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ) {
						foreach ( $query['tax_query'] as $qt_index => $qt_val ) {
							if ( is_array( $qt_val ) && isset( $qt_val['taxonomy'] ) && $qt_val['taxonomy'] == $taxonomy ) {
								unset( $query['tax_query'][$qt_index] );
							}
						}
					}
					$aux_cache_query = new WP_Query($query);
					if ( is_array( $aux_cache_query->posts ) && !empty( $aux_cache_query->posts ) ) {
						$f_taxes = array( $taxonomy );
						$wpv_data_cache = WPV_Cache::generate_cache( $aux_cache_query->posts, array( 'tax' => $f_taxes ) );
						if ( isset( $wpv_data_cache[$taxonomy . '_relationships'] ) && is_array( $wpv_data_cache[$taxonomy . '_relationships'] ) ) {
							foreach ( $wpv_data_cache[$taxonomy . '_relationships'] as $pid => $tax_array ) {
								if ( is_array( $tax_array ) && count( $tax_array ) > 0 ) {
									$this_post_taxes = wp_list_pluck( $tax_array, 'term_id', 'term_id' );
									$this->posts_to_taxes[$pid] = $this_post_taxes;
								}
							}
						}
					}
				}
			}
		}

		function start_lvl(&$output, $depth = 0, $args = array() ) {
			$indent = str_repeat("\t", $depth);
			$output .= "$indent<ul class='children'>\n";
					//$output .= "$indent\n";
		}

		function end_lvl(&$output, $depth = 0, $args = array() ) {
			$indent = str_repeat("\t", $depth);
			$output .= "$indent</ul>\n";
					//$output .= "$indent\n";
		}

		function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0 ) {
			extract($args);
			if ( empty($taxonomy) )
				$taxonomy = 'category';

			if ( $taxonomy == 'category' )
				$name = 'post_category';
			else
				$name = $taxonomy;

			$tax_option = esc_html( apply_filters('the_category', $category->name ));
			if ($this->format) {
				$tax_option = str_replace( '%%NAME%%', $category->name, $this->format );
			}

			$this->show = true;
			if ( $this->dependant || $this->counters ) {
				$wpv_tax_criteria_matching_posts = array();
				$wpv_tax_criteria_to_filter = array($category->term_id => $category->term_id);
				// $criteria_real = array_combine( array_values( $selected_cats ) , array_values( $selected_cats ) );
				$wpv_tax_criteria_matching_posts = wp_list_filter($this->posts_to_taxes, $wpv_tax_criteria_to_filter);
				if ( count( $wpv_tax_criteria_matching_posts ) == 0 && $this->dependant ) {
					$this->show = false;
				}
				if ( $this->counters ) {
					$tax_option = str_replace( '%%COUNT%%', count( $wpv_tax_criteria_matching_posts ), $tax_option );
				}
			}

			if ( $this->slug_mode ) {
				$real_value = urldecode( $category->slug );
			} else {
				$real_value = $category->name;
			}

			$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';

            // If the current page is a taxonomy page for the taxonomy the filter refers to
            if ( $this->in_this_tax_archive_page ) {
                // ... and if the querried taxonomy term is the current term rendered in the filter
                if ( $this->tax_archive_term->slug == $category->slug ) {
                    // ... display the term and make it selected
                    $output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit'. ( !empty($this->label_class) ? ' '. $this->label_class : '' ) .'"'. ( !empty($this->label_style) ? ' style="' . $this->label_style . '"' : '' ) .'><input'. ( !empty($this->style) ? ' style="' . $this->style . '"' : '' ) .' value="' . $real_value . '" type="checkbox" class="js-wpv-filter-trigger'. ( !empty($this->input_class) ? ' '. $this->input_class : '' ) .'" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '" checked /> ' . $tax_option . '</label>';
                }
                // ... else disregard this taxonomy term option for the filter
            }
            else {
                // ... else let the normal procedures decide whether to display the option or not.
                // NOTE: were outputing the "slug" and not the "term-id".
                // WP outputs the "term-id"
                if ( $this->show || in_array( $real_value, $selected_cats ) ) {
                    $output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit'. ( !empty($this->label_class) ? ' '. $this->label_class : '' ) .'"'. ( !empty($this->label_style) ? ' style="' . $this->label_style . '"' : '' ) .'><input'. ( !empty($this->style) ? ' style="' . $this->style . '"' : '' ) .' value="' . $real_value . '" type="checkbox" class="js-wpv-filter-trigger'. ( !empty($this->input_class) ? ' '. $this->input_class : '' ) .'" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $real_value, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . $tax_option . '</label>';
                } else if ( $this->empty_action != 'hide' ) {
                    $disabled = '';
                    $disabled_class = '';
                    if ( !in_array( $real_value, $selected_cats ) ) {
                        $disabled = ' disabled="disabled"';
                        $disabled_class = ' wpv-parametric-disabled';
                        $args['disabled'] = 'disabled';
                    }
                    $output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit' . $disabled_class . ( !empty($this->label_class) ? ' '. $this->label_class : '' ) .'"'. ( !empty($this->label_style) ? ' style="' . $this->label_style . '"' : '' ) .'><input'. ( !empty($this->style) ? ' style="' . $this->style . '"' : '' ) .' value="' . $real_value . '" type="checkbox" class="js-wpv-filter-trigger'. ( !empty($this->input_class) ? ' '. $this->input_class : '' ) .'" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $real_value, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . $tax_option . '</label>';
                }
            }
		}

		function end_el(&$output, $category, $depth = 0, $args = array()) {
			if ( $this->show ) {
				$output .= "</li>\n";
			}
		}
	}
}

add_shortcode('wpv-control', 'wpv_shortcode_wpv_control');

//not in use anymore - leave it for retro-compatibility
add_shortcode('wpv-filter-controls', 'wpv_shortcode_wpv_filter_controls');

function wpv_shortcode_wpv_filter_controls($atts, $value) {

    /**
     *
     * This is a do nothing shortcode. It's just a place holder for putting the
     * wpv-control shortcodes and allows for easier editing inside the meta HTML
     *
     * This shortcode now has a function: when hide="true"
     * it does not display the wpv-control shortcodes
     * This is usefull if you need to show pagination controls but not filter controls
     * For View Forms, this hide parameter is overriden and controls are always shown
     */

	$value = str_replace("<!-- ADD USER CONTROLS HERE -->", '', $value);

	if (
		(
			isset( $atts['hide'] ) &&
			$atts['hide'] == 'true'
		)
		|| ! apply_filters( 'wpv_filter_wpv_is_form_required', false ) // Check if the View wrapper is disabled in the View settings.
	) {
		return '';
    } else {
		return wpv_do_shortcode($value);
    }
}

add_filter( 'wpv_filter_wpv_global_parametric_search_manage_history_status', 'wpv_global_parametric_search_manage_history_status' );

function wpv_global_parametric_search_manage_history_status( $status ) {
	$settings = WPV_Settings::get_instance();
	if ( $settings->wpv_enable_parametric_search_manage_history ) {
		$status = true;
	} else {
		$status = false;
	}
	return $status;
}

function wpv_parametric_search_triggers_history( $view_id = null ) {
	$global_enable_manage_history = apply_filters( 'wpv_filter_wpv_global_parametric_search_manage_history_status', true );
	if ( ! $global_enable_manage_history ) {
		return false;
	}
	$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id );
	if (
		isset( $view_settings['dps']['enable_history'] )
		&& $view_settings['dps']['enable_history'] == 'disable'
	) {
		return false;
	}
	return true;
}


/**
* wpv_list_filter_checker
*
* Walks an array of postmeta looking for a given value.
*
* @param array $data {
* 		@type array $list The array of postmeta to walk indexed by post IDs.
*  		@type array $args The data to check against indexed by field names.
*  		@type string $kind The Types postmeta type, mainly whether it is checkboxes or not.
*  		@type string $comparator What kind of value is called valid, useful for between, greater than and lower than filters.
*  		@type bool $count_matches Whether we bail at first finding or we need to return a number of matches.
* }
*
* @return (boolean|integer) Whether the args condition is met or how many times it is met.
*
* @since 1.6.1
*/

function wpv_list_filter_checker( $data ) {
	// Extract the values in $args
	$list = isset( $data['list'] ) ? $data['list'] : array();
	$args = isset( $data['args'] ) ? $data['args'] : array();
	$kind = isset( $data['kind'] ) ? $data['kind'] : '';
	$comparator = isset( $data['comparator'] ) ? $data['comparator'] : 'equal';
	$count_matches = isset( $data['count_matches'] ) ? $data['count_matches'] : false;

	// Now, let's play
	$return = ( $count_matches ) ? 0 : false;
	if ( ! is_array( $list ) ) {
		return $return;
	}
	if ( empty( $args ) ) {
		return $return;
	}
	$types_opt = get_option( 'wpcf-fields' );

	//global $WP_Views;

	// Iterate through all posts' meta
	foreach ( $list as $key => $obj ) {
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$key = str_replace( $blog_id . ':', '', $key );
		}
		/*
		if ( !$filter_full_list ) {
			if ( !in_array( $key, $WP_Views->returned_ids_for_parametric_search ) ) {
				// @todo check if $WP_Views->returned_ids_for_parametric_search is stil needed, surely not
				continue;
			}
		}
		*/
		$to_match = (array) $obj;

		// Iterate through all fields that should be compared.
		foreach ( $args as $m_key => $m_value ) {

			if ( array_key_exists( $m_key, $to_match ) ) {

				if ( $kind == 'checkboxes' && is_array( $to_match[ $m_key ] ) && is_array( $m_value ) && $types_opt ) {
					/* Special case.
					 * We're dealing with checkboxes, field exists and we are comparing two arrays of values.
					 * Actually, in this case we are given option *titles* in $m_value, so we have to get titles
					 * from field options. Also note that the "comparator" argument makes no sense here and is ignored.
					 */

					// $m_key: name of the field
					// $m_value: array of value (option title) we want to find in postmeta. There is never than one element.

					if ( strpos( $m_key, 'wpcf-' ) === 0 ) {
						$field_name = substr( $m_key, 5 );
					} else {
						$field_name = $m_key;
					}

					// Get field options from Types options (if they exist).
					$field_opt = ( isset( $types_opt[ $field_name ] )
								&& is_array( $types_opt[ $field_name ] )
								&& isset( $types_opt[ $field_name ]['data'] )
								&& is_array( $types_opt[ $field_name ]['data'] )
								&& isset( $types_opt[ $field_name ]['data']['options'] ) )
							? $types_opt[ $field_name ]['data']['options']
							: array();

					// Iterate through values in the postmeta field that should be compared.
					foreach ( $to_match[ $m_key ] as $opt ) {

						// This will either be false or an array holding slugs of options "checked" in the postmeta.
						$opt_data = false;
						$opt_array = maybe_unserialize( $opt );
						if ( $opt_array && is_array( $opt_array ) ) {
							// Get keys for values that are arrays
							// Because sometimes checkbox(es) store zero when unchecked.
							// For string values that are no zero, also use them:
							// Types recognizes checkbozes in multiple formats due to hell.
							$opt_data = array();
							foreach ( $opt_array as $opt_array_key => $opt_array_value ) {
								if ( is_array( $opt_array_value ) ) {
									$opt_data[] = $opt_array_key;
								} else if (
									! is_numeric( $opt_array_value )
									|| 0 !== (int) $opt_array_value
								) {
									$opt_data[] = $opt_array_key;
								}
							}
						}

						// Build array of titles of options "checked" in postmeta.
						$opt_checked_titles = array();
						if( $opt_data && ! empty( $opt_data ) && is_array( $field_opt ) ) {
							foreach( $opt_data as $option_slug ) {
								// We don't assume anything here
								if( isset( $field_opt[ $option_slug ], $field_opt[ $option_slug ]['title'] ) ) {
									$opt_checked_titles[] = $field_opt[ $option_slug ]['title'];
								}
							}
						}

						// Is there a match?
						if( in_array( $m_value[0], $opt_checked_titles ) ) {
							if ( $count_matches ) {
								$return = $return + 1;
							} else {
								return true;
							}
						}
					}

				} else {
					if ( is_array( $m_value ) ) {
						$real_value = $m_value[0];
					} else {
						$real_value = $m_value;
					}
					if ( is_array( $to_match[ $m_key ] ) ) {
						foreach ( $to_match[ $m_key ] as $test_value ) {
							if ( !empty( $test_value ) || is_numeric( $test_value ) ) {
								if ( $comparator == 'greater-than' && $real_value > $test_value ) {
									if ( $count_matches ) {
										$return = $return + 1;
									} else {
										return true;
									}
								} else if ( $comparator == 'greater-equal-than' && $real_value >= $test_value ) {
									if ( $count_matches ) {
										$return = $return + 1;
									} else {
										return true;
									}
								} else if ( $comparator == 'lower-than' && $real_value < $test_value ) {
									if ( $count_matches ) {
										$return = $return + 1;
									} else {
										return true;
									}
								} else if ( $comparator == 'lower-equal-than' && $real_value <= $test_value ) {
									if ( $count_matches ) {
										$return = $return + 1;
									} else {
										return true;
									}
								} else if ( $comparator == 'equal' && $real_value == $test_value ) {
									if ( $count_matches ) {
										$return = $return + 1;
									} else {
										return true;
									}
								}
							}
						}
					} else {
						if ( $comparator == 'greater-than' && $real_value > $to_match[ $m_key ] ) {
							if ( $count_matches ) {
								$return = $return + 1;
							} else {
								return true;
							}
						} else if ( $comparator == 'greater-equal-than' && $real_value >= $to_match[ $m_key ] ) {
							if ( $count_matches ) {
								$return = $return + 1;
							} else {
								return true;
							}
						} else if ( $comparator == 'lower-than' && $real_value < $to_match[ $m_key ] ) {
							if ( $count_matches ) {
								$return = $return + 1;
							} else {
								return true;
							}
						} else if ( $comparator == 'lower-equal-than' && $real_value <= $to_match[ $m_key ] ) {
							if ( $count_matches ) {
								$return = $return + 1;
							} else {
								return true;
							}
						} else if ( $comparator == 'equal' && $real_value == $to_match[ $m_key ] ) {
							if ( $count_matches ) {
								$return = $return + 1;
							} else {
								return true;
							}
						}
					}
				}
			}
		}
	}
	return $return;
}

