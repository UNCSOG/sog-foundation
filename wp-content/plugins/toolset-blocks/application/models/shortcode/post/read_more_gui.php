<?php

/**
 * Class WPV_Shortcode_Post_Read_More_GUI
 *
 * @since 2.8
 */
class WPV_Shortcode_Post_Read_More_GUI extends WPV_Shortcode_Base_GUI {

	/**
	 * Register the wpv-post-link shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes[ WPV_Shortcode_Post_Read_More::SHORTCODE_NAME ] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}

	/**
	 * Get the shortcode data for wpv-post-read-more.
	 *
	 * @return array
	 * @since 2.8
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post read more link', 'wpv-views' ),
			'label'          => __( 'Post read more link', 'wpv-views' ),
			'post-selection' => true,
			'attributes'     => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'label' => array(
							'label' => __( 'Label of the link', 'wpv-views'),
							'type' => 'text',
							'description' => __( 'You can use %%TITLE%% as a placeholder for the post title.', 'wpv-views' ),
							'default' => __( 'Read more', 'wpv-views' ),
						),
						'class' => array(
							'label'       => __( 'Class', 'wpv-views'),
							'type'        => 'text',
							'description' => __( 'Space-separated list of classnames that will be added to the anchor HTML tag.', 'wpv-views' ),
							'placeholder' => 'class1 class2',
						),
						'style' => array(
							'label'       => __( 'Style', 'wpv-views'),
							'type'        => 'text',
							'description' => __( 'Inline styles that will be added to the anchor HTML tag.', 'wpv-views' ),
							'placeholder' => 'border: 1px solid red; font-size: 2em;',
						),
					),
				),
			),
		);

		$data = apply_filters( 'wpv_filter_wpv_shortcodes_gui_wpml_context_data' , $data, WPV_Shortcode_Post_Read_More::SHORTCODE_NAME );

		return $data;
	}

}
