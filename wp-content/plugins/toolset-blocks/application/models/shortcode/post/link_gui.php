<?php

/**
 * Class WPV_Shortcode_Post_Link_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Link_GUI extends WPV_Shortcode_Base_GUI {

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
		$views_shortcodes[ WPV_Shortcode_Post_Link::SHORTCODE_NAME ] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}

	/**
	 * Get the wpv-post-link shortcode attributes data.
	 *
	 * @return array
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post link', 'wpv-views' ),
			'label'          => __( 'Post link', 'wpv-views' ),
			'post-selection' => true,
			'attributes'     => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
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
		return $data;
	}

}
