<?php

/**
 * Class WPV_Shortcode_Post_Title_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Title_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-title shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-title'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-title shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post title', 'wpv-views' ),
			'label'          => __( 'Post title', 'wpv-views' ),
			'post-selection' => true,
			'attributes'     => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'output' => array(
							'label'   => __( 'Output format', 'wpv-views'),
							'type'    => 'radio',
							'options' => array(
								'raw'      => __( 'As stored in the database', 'wpv-views' ),
								'sanitize' => __( 'Sanitize', 'wpv-views' ),
							),
							'default' => 'raw',
							'description' => __( 'Output the post title as is or sanitize it to use as an HTML attribute.', 'wpv-views' ),
						),
					),
				),
			),
		);
		return $data;
	}
	
	
}