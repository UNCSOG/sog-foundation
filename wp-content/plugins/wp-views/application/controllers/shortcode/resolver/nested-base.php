<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

use OTGS\Toolset\Views\Controller\Shortcode\InnerShortcodeRegex;

/**
 * Base class for resolvers dealing with nested elements:
 * - HTML attribute values.
 * - shortcode attribute values.
 *
 * @since 3.3.0
 */
class NestedBase {

	/**
	 * @var \WPV_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param \WPV_Settings $settings
	 */
	public function __construct(
		\WPV_Settings $settings
	) {
		$this->settings = $settings;
	}

	use InnerShortcodeRegex;

	/**
	 * Get the list of registered shortcodes to be used inside other shortcodes.
	 *
	 * @return array
	 * @since 3.3.0
	 */
	public function get_custom_inner_shortcodes() {
		$custom_inner_shortcodes = array();

		$custom_inner_stored_shortcodes = $this->settings->get_raw_value( 'wpv_custom_inner_shortcodes' );

		if (
			isset( $custom_inner_stored_shortcodes )
			&& is_array( $custom_inner_stored_shortcodes )
		) {
			$custom_inner_shortcodes = $custom_inner_stored_shortcodes;
		}

		/**
		 * Filter the list of custom shortcodes that can be used inside other shortcodes or as HTML attribute values.
		 *
		 * @param array $custom_inner_shortcodes List of shortcodes.
		 * @return array
		 * @since 1.4.0
		 */
		$custom_inner_shortcodes = apply_filters( 'wpv_custom_inner_shortcodes', $custom_inner_shortcodes );

		return array_unique( $custom_inner_shortcodes );
	}

}
