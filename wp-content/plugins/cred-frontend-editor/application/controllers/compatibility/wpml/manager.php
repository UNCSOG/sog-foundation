<?php
/**
 * WPML integration manager.
 *
 * @package Toolset Forms
 * @since 2.6
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml;

/**
 * Manager for the WPML integration.
 *
 * @since 2.6
 */
class Manager {

	private $factory;

	public function __construct( Integration\Factory $factory ) {
		$this->factory = $factory;
	}

	public function initialize() {
		if ( ! apply_filters( 'toolset_is_wpml_active_and_configured', false ) ) {
			$fallback = $this->factory->get_integration( 'fallback' );
			$fallback->initialize();
			return;
		}


		$forms_translation = $this->factory->get_integration( 'forms_translation' );
		$forms_translation->initialize();

		$frontend = $this->factory->get_integration( 'frontend' );
		$frontend->initialize();
	}

}
