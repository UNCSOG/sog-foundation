<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty;

/**
 * Defines the list of active plugins to be served to the third-party blocks configuration updater.
 */
class ActivePluginsProvider {
	/**
	 * Returns the names of the active plugins.
	 *
	 * @return array
	 */
	public function get_active_plugin_names() {
		return wp_list_pluck( $this->get_active_plugins(), 'Name' );
	}

	/**
	 * Retrieves the list of active plugins.
	 *
	 * @return array
	 */
	private function get_active_plugins() {
		$active_plugin_names = array();

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			if ( is_plugin_active( $plugin_file ) ) {
				$active_plugin_names[] = $plugin_data;
			}
		}

		return $active_plugin_names;
	}
}
