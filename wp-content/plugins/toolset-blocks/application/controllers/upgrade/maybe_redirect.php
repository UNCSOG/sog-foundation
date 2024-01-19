<?php

namespace OTGS\Toolset\Views\Controller\Upgrade;

use OTGS\Toolset\Views\Controller\Admin\WelcomeScreen;

/**
 * Setup initial database
 *
 * @since 3.0
 */
class MaybeRedirect implements IRoutine {

	/**
	 * @var \OTGS\Toolset\Views\Model\Wordpress\Transient
	 */
	private $transient_manager;

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Views\Model\Wordpress\Transient $transient_manager
	 */
	public function __construct( \OTGS\Toolset\Views\Model\Wordpress\Transient $transient_manager ) {
		$this->transient_manager = $transient_manager;
	}

	/**
	 * Execute database setup.
	 *
	 * @param array $args
	 * @since 3.0
	 */
	public function execute_routine( $args = array() ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$toolset_views_flavour = wpv_get_views_flavour();

		if ( 'blocks' === $toolset_views_flavour ) {
			$this->transient_manager->set_transient( WelcomeScreen::TRANSIENT_FLAG, true, 30 );
		}
	}

}
