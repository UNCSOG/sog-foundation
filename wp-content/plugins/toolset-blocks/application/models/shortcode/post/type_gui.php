<?php

/**
 * Class WPV_Shortcode_Post_Type_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Type_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-type shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-type'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-type shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post type', 'wpv-views' ),
			'label'          => __( 'Post type', 'wpv-views' ),
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'show' => array(
							'label'   => __( 'Post type information', 'wpv-views'),
							'type'    => 'radio',
							'options' => array(
								'slug'   => __( 'Post type slug', 'wpv-views' ),
								'single' => __( 'Post type singular name', 'wpv-views' ),
								'plural' => __( 'Post type plural name', 'wpv-views' ),
							),
							'default' => 'slug',
						),
					),
				),
			),
		);
		return $data;
	}
	
	
}