<?php

/**
 * Interface CRED_Shortcode_Interface_View
 *
 * @since m2m
 */
interface CRED_Shortcode_Interface_View {
	/**
	 * @param $atts
	 * @param $content
	 *
	 * @return mixed
	 */
	public function render( $atts, $content );
}