<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty\Plugins;

/**
 * Handles the automatic Dynamic Sources integration for the "Ultimate Addons for Gutenberg" plugin's blocks that require
 * server-side operations.
 */
class UltimateAddonsGutenbergIntegration extends PluginIntegration {
	/**
	 * Initializes the class by hooking the relevant hooks.
	 */
	public function initialize() {
		parent::initialize();

		add_filter( 'uagb_block_attributes_for_css_and_js', array( $this, 'maybe_replace_attributes_with_shortcodes_for_server_side_use' ), 10, 2 );
	}

	/**
	 * Injects the Dynamic Sources shortcode into the block attributes array, that are required for server-side operations.
	 *
	 * @param array  $block_attributes
	 * @param string $block_name
	 *
	 * @return array
	 */
	public function maybe_replace_attributes_with_shortcodes_for_server_side_use( $block_attributes, $block_name ) {
		return $this->replace_attributes_with_shortcodes_for_server_side_use( $block_attributes, $block_name );
	}
}
