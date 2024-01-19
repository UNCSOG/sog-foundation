<?php

/**
 * Interface CRED_Shortcode_Empty
 *
 * @since m2m
 */
class CRED_Shortcode_Empty implements CRED_Shortcode_Interface {
	/**
	 * @param $atts
	 * @param $content
	 *
	 * @return mixed
	 */
	public function get_value( $atts, $content ) {
		return '';
	}
}