<?php
/**
 * WPML integration for translating forms.
 *
 * @package Toolset Forms
 * @since 2.6
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration;

/**
 * Forms translation controller.
 *
 * @since 2.6
 */
class FormsTranslation {

	private $factory;

	public function __construct( FormsTranslation\Factory $factory ) {
		$this->factory = $factory;
	}

	public function initialize() {
		$packages = $this->factory->get_controller( 'packages' );
		$packages->initialize();

		$legacy = $this->factory->get_controller( 'legacy' );
		$legacy->initialize();
	}

}
