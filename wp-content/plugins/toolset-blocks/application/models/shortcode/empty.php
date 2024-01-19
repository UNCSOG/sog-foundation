<?php

/**
 * Empty shortcode.
 *
 * Provides a clean shortcode callback that rrturns an empty string.
 * Useful when shortcodes are conditionally defined and the condition is not met.
 *
 * @since m2m
 */
class WPV_Shortcode_Empty implements WPV_Shortcode_Interface {
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