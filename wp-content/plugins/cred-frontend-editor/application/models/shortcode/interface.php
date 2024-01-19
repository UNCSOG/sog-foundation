<?php


/**
 * Interface CRED_Shortcode_Interface
 *
 * @since m2m
 */
interface CRED_Shortcode_Interface {
	/**
	 * @param $atts
	 * @param $content
	 *
	 * @return mixed
	 */
	public function get_value( $atts, $content );
}