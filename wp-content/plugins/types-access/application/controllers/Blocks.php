<?php

namespace OTGS\Toolset\Access\Controllers;

/**
 * Class Blocks
 *
 * Handles all the interaction that Access has with the new editor, related to the blocks.
 *
 * @since 2.6.0
 */
class Blocks {

	/**
	 * Initializes the hooks for the editor blocks affected by Access.
	 */
	public function init_hooks() {
		add_action( 'toolset_filter_extend_the_core_paragraph_block', array(
			$this,
			'extend_the_core_blocks_with_buttons',
		) );

		add_action( 'toolset_filter_extend_the_core_custom_html_block', array(
			$this,
			'extend_the_core_blocks_with_buttons',
		) );
	}


	/**
	 * Filter "toolset_filter_extend_the_core_paragraph_block" & "toolset_filter_extend_the_core_custom_html_block"
	 * callback.
	 *
	 * Provides the extension information needed for the Paragraph & Custom HTML block to be extended with a relevant
	 * buttons.
	 *
	 * @param array $block_buttons The buttons array that will be used to extend the toolbar of the Paragraph & Custom
	 *     HTML block.
	 *
	 * @return mixed
	 */
	public function extend_the_core_blocks_with_buttons( $block_buttons ) {
		$block_buttons['access'] = array(
			'clickCallback' => 'window.OTGAccess.shortcodes_gui.openAccessDialog',
		);

		return $block_buttons;
	}
}
