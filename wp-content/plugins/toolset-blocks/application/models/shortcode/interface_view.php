<?php

/**
 * Interface WPV_Shortcode_Interface_View
 *
 * @since 2.5.0
 */
interface WPV_Shortcode_Interface_View {
	/**
	 * @param $atts
	 * @param $content
	 *
	 * @return mixed
	 */
	public function render( $atts, $content );
}