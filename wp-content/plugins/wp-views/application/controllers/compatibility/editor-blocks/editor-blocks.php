<?php
namespace OTGS\Toolset\Views\Controller\Compatibility\EditorBlocks;

use \OTGS\Toolset\Views\Controller\Compatibility as Compatibility;

/**
 * Class ViewsEditorBlocks
 *
 * Handles the creation and initialization of the all the new editor (Gutenberg) integration stuff.
 *
 * @package OTGS\Toolset\Views\Controller\Compatibility
 *
 * @since 2.6.0
 * @since 2.7.0 Moved here from Toolset Common.
 */
class Blocks extends Compatibility\Base {
	/**
	 * Initializes the Views Gutenberg blocks.
	 */
	public function initialize() {
		$gutenberg_active = new \Toolset_Condition_Plugin_Gutenberg_Active();

		if ( ! $gutenberg_active->is_met() ) {
			return;
		}

		$toolset_blocks = array(
			ContentTemplate\Block::BLOCK_NAME,
		);

		$factory = new ViewsEditorBlockFactory();

		foreach ( $toolset_blocks as $toolset_block_name ) {
			$block = $factory->get_block( $toolset_block_name );
			if ( null !== $block ) {
				$block->init_hooks();
			};
		}
	}
}
