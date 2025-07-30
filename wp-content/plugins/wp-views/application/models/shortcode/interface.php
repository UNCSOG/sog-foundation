<?php


/**
 * Interface WPV_Shortcode_Interface
 *
 * @since 2.5.0
 */
interface WPV_Shortcode_Interface {
	/**
	 * @param $atts
	 * @param $content
	 *
	 * @return mixed
	 */
	public function get_value( $atts, $content );
}