<?php


/**
 * Interface CRED_Shortcode_Interface_Conditional
 *
 * @since m2m
 */
interface CRED_Shortcode_Interface_Conditional {
	/**
	 * @return bool
	 */
	public function condition_is_met();
}