<?php

namespace OTGS\Toolset\Views\Controller\Compatibility;

/**
 * Full Views version of WPV_WPML_Integration_Embedded.
 *
 * Currently without any new functionality.
 *
 * @since 1.10
 * @since 2.7.0 Extends the new abstract class for compatibility and the "initialized" method refactored to not being static.
 */
class Wpml extends Base {
	private static $instance;

	public function initialize() {
		self::get_instance();
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new \WPV_WPML_Integration_Embedded();
		}
		return self::$instance;
	}
}
