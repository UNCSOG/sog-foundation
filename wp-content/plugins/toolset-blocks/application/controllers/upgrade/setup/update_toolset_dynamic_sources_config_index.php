<?php

namespace OTGS\Toolset\Views\Controller\Upgrade\Setup;

use OTGS\Toolset\Views\Controller\Upgrade\IRoutine;

/**
 * Update the configuration for the Automatic Dynamic Sources integration with third-party
 * block plugins when setting up Views.
 */
class UpdateToolsetDynamicSourcesConfig implements IRoutine {
	/**
	 * The setup routine.
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function execute_routine( $args = array() ) {
		add_action( 'init', array( $this, 'update_toolset_dynamic_sources_config' ), PHP_INT_MAX );
	}

	/**
	 * Triggers an action to update the configuration for the Automatic Dynamic Sources integration with third-party
	 * block plugins.
	 */
	public function update_toolset_dynamic_sources_config() {
		do_action( 'update_toolset_dynamic_sources_config_index' );
	}
}
