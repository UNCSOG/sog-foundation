<?php

namespace OTGS\Toolset\Views\Controller\Upgrade;

use OTGS\Toolset\Views\Controller\Upgrade\Setup\DefaultSettingsIn2070000;
use OTGS\Toolset\Views\Controller\Upgrade\Setup\UpdateToolsetDynamicSourcesConfig;

/**
 * Setup initial database
 *
 * @since 3.0
 */
class Setup implements IRoutine {

	/**
	 * @var DefaultSettingsIn2070000
	 */
	private $default_settings_in_2070000;

	/** @var UpdateToolsetDynamicSourcesConfig */
	private $update_toolset_dynamic_sources_config;


	/**
	 * Constructor.
	 *
	 * @param DefaultSettingsIn2070000 $default_settings_in_2070000
	 * @param UpdateToolsetDynamicSourcesConfig $update_toolset_dynamic_sources_config
	 */
	public function __construct(
		DefaultSettingsIn2070000 $default_settings_in_2070000,
		UpdateToolsetDynamicSourcesConfig  $update_toolset_dynamic_sources_config
	) {
		$this->default_settings_in_2070000 = $default_settings_in_2070000;
		$this->update_toolset_dynamic_sources_config = $update_toolset_dynamic_sources_config;
	}

	/**
	 * Execute database setup
	 *
	 * @param array $args
	 * @since 3.0
	 * @note The routine related to 3.0 has been disabled until we properly define what version of Views/Blocks
	 *     will be offered to existing and new users, and how we set that separation.
	 */
	public function execute_routine( $args = array() ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$this->default_settings_in_2070000->execute_routine();
		$this->update_toolset_dynamic_sources_config->execute_routine();
	}

}
