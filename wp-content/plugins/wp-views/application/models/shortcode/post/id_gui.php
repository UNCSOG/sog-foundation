<?php

/**
 * Class WPV_Shortcode_Post_Id_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Id_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-id shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-id'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-id shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post ID', 'wpv-views' ),
			'label'          => __( 'Post ID', 'wpv-views' ),
			'post-selection' => true
		);
		return $data;
	}
	
	
}