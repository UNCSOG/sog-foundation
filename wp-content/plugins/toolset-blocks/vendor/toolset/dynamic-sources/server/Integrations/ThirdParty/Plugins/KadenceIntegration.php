<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty\Plugins;

/**
 * Handles the automatic Dynamic Sources integration for the "Kadence Blocks" plugin's blocks that require
 * server-side operations.
 */
class KadenceIntegration extends PluginIntegration {
	/**
	 * Initializes the class by hooking the relevant hooks.
	 */
	public function initialize() {
		parent::initialize();

		add_filter( 'kadence_blocks_frontend_build_css', array( $this, 'maybe_replace_attributes_with_shortcodes_for_server_side_use' ) );

		add_filter( 'kadence_render_row_layout_css_block_attributes', array( $this, 'maybe_replace_rowlayout_block_attributes_with_shortcodes_for_server_side_use' ) );

		add_filter( 'kadence_render_column_layout_css_block_attributes', array( $this, 'maybe_replace_column_block_attributes_with_shortcodes_for_server_side_use' ) );
	}

	/**
	 * Injects the Dynamic Sources shortcode into the block attributes array, that are required for server-side operations.
	 *
	 * @param array $block
	 * @param bool $do_shortcode
	 *
	 * @return array
	 */
	public function maybe_replace_attributes_with_shortcodes_for_server_side_use( $block, $do_shortcode = true ) {
		if (
			! isset( $block['blockName'] ) ||
			strpos( $block['blockName'], 'kadence/' ) === false ||
			! isset( $block['attrs'] ) ||
			! is_array( $block['attrs'] )
		) {
			return $block;
		}

		$block['attrs'] = $this->replace_attributes_with_shortcodes_for_server_side_use( $block['attrs'], $block['blockName'], $do_shortcode );

		return $block;
	}

	/**
	 * Injects the Dynamic Sources shortcode into the block attributes array, that are required for server-side operations
	 * for the Row Layout block.
	 *
	 * @param array $block_attributes
	 *
	 * @return array
	 */
	public function maybe_replace_rowlayout_block_attributes_with_shortcodes_for_server_side_use( $block_attributes ) {
		$block = array(
			'blockName' => 'kadence/rowlayout',
			'attrs' => $block_attributes,
		);

		return $this->maybe_replace_block_attributes_with_shortcodes_for_server_side_use( $block, $block_attributes );
	}

	/**
	 * Injects the Dynamic Sources shortcode into the block attributes array, that are required for server-side operations
	 * for the Column block.
	 *
	 * @param array $block_attributes
	 *
	 * @return array
	 */
	public function maybe_replace_column_block_attributes_with_shortcodes_for_server_side_use( $block_attributes ) {
		$block = array(
			'blockName' => 'kadence/column',
			'attrs' => $block_attributes,
		);

		return $this->maybe_replace_block_attributes_with_shortcodes_for_server_side_use( $block, $block_attributes );
	}

	/**
	 * Injects the Dynamic Sources shortcode into the block attributes array, that are required for server-side operations.
	 *
	 * @param array $block_attributes
	 *
	 * @return array
	 */
	private function maybe_replace_block_attributes_with_shortcodes_for_server_side_use( $block, $block_attributes ) {

		$block = $this->maybe_replace_attributes_with_shortcodes_for_server_side_use( $block, false );

		return $block['attrs'];
	}
}
