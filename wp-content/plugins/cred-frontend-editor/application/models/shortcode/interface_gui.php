<?php

/**
 * Interface CRED_Shortcode_Interface_GUI
 *
 * @since m2m
 */
interface CRED_Shortcode_Interface_GUI {
	/**
	 * @param $cred_shortcodes
	 *
	 * @return array
	 */
	public function register_shortcode_data( $cred_shortcodes );
	
	/**
	 * @return array
	 */
	public function get_shortcode_data();
}