<?php


/**
 * Interface for shortcodes that depend on a condition to be registered
 *
 * Provides shortcodes with a method to set a condition,
 * which should be used when registering th shortcode,
 * to default to WPV_Shortcode_Emptywhen not met.
 *
 * @since m2m
 */
interface WPV_Shortcode_Interface_Conditional {
	/**
	 * @return bool
	 */
	public function condition_is_met();
}