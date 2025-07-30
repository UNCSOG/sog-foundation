<?php

/**
 * Class WPV_Shortcode_Post_Format_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Format_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-format shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-format'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-format shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post format', 'wpv-views' ),
			'label'          => __( 'Post format', 'wpv-views' ),
			'post-selection' => true
		);
		return $data;
	}
	
	
}