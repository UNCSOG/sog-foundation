<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Block\Style\Block\IBlock;

/**
 * Pagination Type 'dots'.
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType
 */
class Dots extends Dropdown {
	public function get_type_name() {
		return 'dots';
	}

	public function get_css_block_class() {
		return '';
	}

	public function get_specific_style_attributes( IBlock $block, FactoryStyleAttribute $factory, $attributes ) {
		parent::get_specific_style_attributes( $block, $factory, $attributes );
	}

	public function get_css_config() {
		return [
			ABlock::CSS_SELECTOR_ROOT => [
				ABlock::KEY_STYLES_FOR_COMMON_STYLES => [
					'margin',
				]
			],
			'ul' => [
				ABlock::KEY_STYLES_FOR_COMMON_STYLES => [
					'text-align',
					'justify-content',
				]
			],
		];
	}
}
