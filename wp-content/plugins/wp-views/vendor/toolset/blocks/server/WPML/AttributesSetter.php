<?php

namespace ToolsetBlocks\WPML;

class AttributesSetter {
	const BLOCKS_FOR_CONTENT_SAVE = [
		'toolset-blocks/progress',
		'toolset-blocks/image',
	];

	public function init() {
		// Add a filter to set block attributes when block is being translated by WPML
		add_filter( 'wpml_update_strings_in_block', array( $this, 'set_block_attributes' ), 10, 3 );
	}

	public function set_block_attributes( \WP_Block_Parser_Block $block, $strings, $lang ) {
		// for some blocks we need to save the whole content because it's not enough to
		// have only translated attributes since block content is something more complicated
		// than attributes inserted into HTML - attributes are combined in complex way
		if ( in_array( $block->blockName, self::BLOCKS_FOR_CONTENT_SAVE ) ) {
			$block->innerHTML = html_entity_decode( $block->innerHTML );
			$block->attrs['wpmlTranslatedContent'] = base64_encode( html_entity_decode( $block->innerHTML ) );
		} else {
			// otherwise we're saving just a flag
			$block->attrs['wpmlTranslatedContent'] = '1';
		}

		return $block;
	}
}

$setter = new AttributesSetter();
$setter->init();
