<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\IBlock;

/**
 * Pagination Type 'dropdown'.
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType
 */
class Dropdown implements IPaginationType {
	public function get_type_name() {
		return 'dropdown';
	}

	public function get_css_block_class() {
		return '';
	}

	/**
	 * The dropdown is using Bootstrap classes 'form-inline', 'form-group'. These using flex display on the frontend
	 * and cannot be aligned by using text-align, adding justify content for it.
	 *
	 * @param IBlock $block
	 * @param FactoryStyleAttribute $factory
	 * @param $attributes
	 */
	public function get_specific_style_attributes( IBlock $block, FactoryStyleAttribute $factory, $attributes ) {
		$text_align = isset( $attributes['align'] ) ? $attributes['align'] : 'left';
		$justify_content = false;

		switch( $text_align ) {
			case 'left':
				$justify_content = 'flex-start';
				break;
			case 'right':
				$justify_content = 'flex-end';
				break;
			case 'center':
				$justify_content = 'center';
				break;
		}

		if( $justify_content ) {
			if( $style = $factory->get_attribute( 'justify-content', $justify_content ) ) {
				$block->add_style_attribute( $style );
			}
		}
	}

	public function get_css_config() {
		// Apply all CSS to the root element.
		return [];
	}
}
