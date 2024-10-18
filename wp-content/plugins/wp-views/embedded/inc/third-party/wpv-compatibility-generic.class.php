<?php

/**
 * Miscellanneous Views-specific adjustments for compatibility with third-party software.
 *
 * @since 2.3
 */
class WPV_Compatibility_Generic {

	private static $instance;


	/**
	 * Activate the compatibility adjustments.
	 *
	 * Note: There is purposefully no get_instance because there should be no need for accessing this class from other code.
	 */
	public static function initialize() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
	}


	private function __clone() { }

	private function __construct() {
		add_action( 'init', array( $this, 'on_init' ) );
	}


	public function on_init() {
		$this->fix_download_manager_content_rendering();
	}


	/**
	 * Check if the Download Manager plugin is active (https://wordpress.org/plugins/download-manager/).
	 * @return bool
	 * @since 2.3
	 */
	private function is_download_manager_active() {
		return defined( 'WPDM_Version' );
	}


	/**
	 * Symptoms: When you use the Download Manager plugin, it uses a CPT called downloads. This CPT is recognized by
	 * Views but when you create a Content Template and assign it to posts, it is not being used.
	 * Plugin page: https://wordpress.org/plugins/download-manager/
	 * Plugin version: 2.9.0
	 *
	 * Cause: Download Manager hooks to the_content at the default priority and simply overwrites it with its own
	 * template (see wpdm_downloadable()), while Views hooks into the_content with priority 1 (in WPV_template::init()).
	 *
	 * Solution: Removing the Download Manager hook and re-adding it with priority lower than 1 solves the issue.
	 *
	 * @since 2.3
	 */
	private function fix_download_manager_content_rendering() {
		if( $this->is_download_manager_active() ) {
			remove_filter( 'the_content', 'wpdm_downloadable' );
			add_filter( 'the_content', 'wpdm_downloadable', 0 );
		}
	}

}