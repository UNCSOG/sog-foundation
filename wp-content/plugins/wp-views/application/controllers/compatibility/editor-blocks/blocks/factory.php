<?php
namespace OTGS\Toolset\Views\Controller\Compatibility\EditorBlocks;

/**
 * Views editor (Gutenberg) Blocks factory class.
 *
 * @since 2.6.0
 * @since 2.7.0 Moved here from Toolset Common.
 */
class ViewsEditorBlockFactory {
	/**
	 * Get the Toolset Views editor (Gutenberg) Block.
	 *
	 * @param string $block The name of the block.
	 *
	 * @return null|ContentTemplate\Block
	 */
	public function get_block( $block ) {
		$return_block = null;

		switch ( $block ) {
			case ContentTemplate\Block::BLOCK_NAME:
				$return_block = new ContentTemplate\Block();
				break;
		}

		return $return_block;
	}
}
