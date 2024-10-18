<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin;

use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Handles the compatibility between Views and Genesis blocks.
 */
class GenesisCompatibility extends Base {
	/**
	 * Initializes the Genesis blocks integration.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initializes the hooks for the UAG integration.
	 */
	private function init_hooks() {
		add_action( 'wpv_action_wpv_loop_before_post_content_replace', [ $this, 'remove_genesis_blocks_filter_container_block_for_amp' ] );
		add_action( 'wpv_action_wpv_loop_after_post_content_replace', [ $this, 'add_genesis_blocks_filter_container_block_for_amp' ] );
	}

	/**
	 * Removes the filter that is causing the Container block to not work in Views and WPAs.
	 */
	public function remove_genesis_blocks_filter_container_block_for_amp() {
		remove_filter( 'render_block', 'genesis_blocks_filter_container_block_for_amp', 10 );
	}

	/**
	 * Re-hooks the filter that is causing the Container block to not work in Views and WPAs.
	 */
	public function add_genesis_blocks_filter_container_block_for_amp() {
		add_filter( 'render_block', 'genesis_blocks_filter_container_block_for_amp', 10, 2 );
	}
}
