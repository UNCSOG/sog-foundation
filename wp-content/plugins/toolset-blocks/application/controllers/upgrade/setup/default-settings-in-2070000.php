<?php

namespace OTGS\Toolset\Views\Controller\Upgrade\Setup;

use OTGS\Toolset\Views\Controller\Upgrade\IRoutine;

/**
 * Setup initial database
 *
 * @since 3.0
 */
class DefaultSettingsIn2070000 implements IRoutine {

	/**
	 * @var \WPV_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param \WPV_Settings $settings
	 */
	public function __construct( \WPV_Settings $settings ) {
		$this->settings = $settings;
	}

	public function execute_routine( $args = array() ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// New sites should not support query filters by meta with spaces in the meta key.
		$this->settings->set( 'support_spaces_in_meta_filters', false );
		$this->settings->set( 'allow_views_wp_widgets_in_elementor', false );
		$this->settings->save();
	}

}
