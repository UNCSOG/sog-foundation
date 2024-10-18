<?php

/**
 * GUI data for the frontend loop index shortcode.
 *
 * @since 2.7.3
 */
class WPV_Shortcode_Loop_Index_GUI extends WPV_Shortcode_Base_GUI {

	/**
	 * Register the wpv-loop index shortcode in the GUI API.
	 * Note that this shortcode is only available in:
	 * - the loop wizard.
	 * - the conditional shortcode GUI.
	 *
	 * @param array $views_shortcodes
	 * @return array
	 * @since 2.7.3
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes[ WPV_Shortcode_Loop_Index::SHORTCODE_NAME ] = array(
			'callback' => array( $this, 'get_shortcode_data' ),
		);
		return $views_shortcodes;
	}

	/**
	 * Get the wpv-post-author shortcode attributes data.
	 *
	 * @return array
	 * @since 2.7.3
	 */
	public function get_shortcode_data() {
		$data = array(
			/* translators: Title for the dialog for setting options on the shortcode to render the loop index for the current View */
			'name' => __( 'View loop index', 'wpv-views' ),
			/* translators: Title for the dialog for setting options on the shortcode to render the loop index for the current View */
			'label' => __( 'View loop index', 'wpv-views' ),
			'attributes' => array(
				'options' => array(
					/* translators: Title for the options section of dialog to set attributes for a shortcode */
					'label' => __( 'Options', 'wpv-views' ),
					/* translators: Title for the options section of dialog to set attributes for a shortcode */
					'header' => __( 'Options', 'wpv-views' ),
					'fields' => array(
						'group' => array(
							'type' => 'grouped',
							'fields' => array(
								'accumulate' => array(
									/* translators: Title for the option about including items from previous pages, in the dialog to set attributes for the shortcode rendering a View loop index */
									'label' => __( 'Include paginated items', 'wpv-views' ),
									'type' => 'radiohtml',
									'options' => array(
										'true' => $this->get_attribute_option_icon( 'accumulate', 'true' )
											/* translators: Label for the option to include previous pages when calculating the current index of a View */
											. __( 'Calculate the loop index including the items from previous pages', 'wpv-views' ),
										'false' => $this->get_attribute_option_icon( 'accumulate', 'false' )
											/* translators: Label for the option to exclude previous pages when calculating the current index of a View */
											. __( 'Calculate the loop index including the items from the current page only', 'wpv-views' ),
									),
									'default' => 'true',
									/* translators: Description for the option to include or exclude previous pages when calculating the current index of a View */
									'description' => __( 'Calculate the loop index considering all the View results or just the ones in the current page.', 'wpv-views' ),
								),
								'pad' => array(
									/* translators: Label for the option about including pad items from previous pages, in the dialog to set attributes for the shortcode rendering a View loop index */
									'label' => __( 'Include pad items', 'wpv-views' ),
									'type' => 'radiohtml',
									'options' => array(
										'true' => $this->get_attribute_option_icon( 'pad', 'true' )
											/* translators: Label for the option to include pad items from previous pages when calculating the current index of a View */
											. __( 'Include pad elements from previous pages', 'wpv-views' ),
										'false' => $this->get_attribute_option_icon( 'pad', 'false' )
											/* translators: Label for the option to exclude pad items from previous pages when calculating the current index of a View */
											. __( 'Ignore pad elements from previous pages', 'wpv-views' ),
									),
									'default' => 'true',
									/* translators: Description for the option to include or exclude pad items from previous pages when calculating the current index of a View */
									'description'   => __( 'When calculating the index including the items from previous pages in a View rendered as a grid, you can include or exclude the optional pad items generated to complete the last row.', 'wpv-views' ),
								),
							),
						),
						'offset' => array(
							/* translators: Title for the option about modifying the initial count which should start at 0, in the dialog to set attributes for the shortcode rendering a View loop index */
							'label' => __( 'Index offset', 'wpv-views' ),
							'type' => 'integer',
							'default' => '0',
							/* translators: Description for the option about modifying the initial count when calculating the current index of a View */
							'description' => __( 'The loop index starts at 1, but this value can be changed. For example, an offset of -1 will make the loop index start at 0.', 'wpv-views' ),
						),
					),
				),
			),
		);
		return $data;
	}

	/**
	 * Generate the IMG tag for the icon on each attribute option.
	 *
	 * @param string $attribute
	 * @param string $option
	 * @return string
	 * @since 2.7.3
	 */
	private function get_attribute_option_icon( $attribute, $option ) {
		return '<img src="' . esc_url( $this->get_attribute_option_icon_source( $attribute, $option ) ) . '" width="64" height="64" style="vertical-align:middle;padding:0 5px;">';
	}

	/**
	 * Generate the URL for the icon on each attribute option.
	 *
	 * @param string $attribute
	 * @param string $option
	 * @return string
	 * @since 2.7.3
	 */
	private function get_attribute_option_icon_source( $attribute, $option ) {
		return WPV_URL . '/public/img/shortcodes/wpv-loop-index/' . $attribute . '-' . $option . '.svg';
	}

}
