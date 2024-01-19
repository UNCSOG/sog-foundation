<?php
/**
 * WPML integration fallback if WPML is not active.
 *
 * @package Toolset Forms
 * @since 2.6
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration;

/**
 * Fallback for the WPML integration when WPML is not active.
 *
 * Decides whether WPML is active and configured with the right addons.
 *
 * @since 2.6
 */
class Fallback {

	/**
	 * Initialize.
	 *
	 * @since 2.6
	 */
	public function initialize() {
		$this->register_fallback_shortcodes();
	}

	/**
	 * Register dumy callbacks for some dummy shortcodes.
	 *
	 * @since 2.6
	 */
	private function register_fallback_shortcodes() {
		add_shortcode( FormsTranslation\Legacy::SHORTCODE_NAME, array( $this, 'shortcode_callback' ) );
		add_shortcode( FormsTranslation\Packages::SHORTCODE_NAME, array( $this, 'shortcode_callback' ) );
	}

	/**
	 * Public callback for the shortcodes.
	 *
	 * @param array $atts
	 * @param string|null $content
	 * @since 2.6
	 */
	public function shortcode_callback( $atts, $content = null ) {
		return do_shortcode( $content );
	}

}
