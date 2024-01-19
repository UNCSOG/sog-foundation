<?php

/**
 * Interface WPV_Shortcode_Interface_View
 *
 * @since 2.5.0
 */
interface WPV_Shortcode_Interface_GUI {
	/**
	 * @param $views_shortcodes
	 *
	 * @return array
	 */
	public function register_shortcode_data( $views_shortcodes );
	
	/**
	 * @return array
	 */
	public function get_shortcode_data();
}