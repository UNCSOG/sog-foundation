<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Block\Style\Block\IBlock;

/**
 * Pagination Type 'previousNextPageButton'.
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType
 */
class PreviousNext extends Dropdown {
	const KEY_STYLE_LINKS_CONTAINER = 'links_container';
	const KEY_STYLE_LINKS = 'links';
	const KEY_STYLE_LINKS_HOVER = 'hover';

	public function get_type_name() {
		return 'previousNextPageButton';
	}

	public function get_css_block_class() {
		return '.wpv-pagination-previous-next-buttons';
	}

	/**
	 * Justify-content and link styles.
	 *
	 * @param IBlock $block
	 * @param FactoryStyleAttribute $factory
	 * @param $attributes
	 */
	public function get_specific_style_attributes( IBlock $block, FactoryStyleAttribute $factory, $attributes ) {
		// Container style.
		$align = isset( $attributes['align'] ) ? $attributes['align'] : 'left';

		if( $align === 'spaceBetween' ) {
			if( $style = $factory->get_attribute( 'display', 'flex' ) ) {
				$block->add_style_attribute( $style, self::KEY_STYLE_LINKS_CONTAINER );
			}
			if( $style = $factory->get_attribute( 'justify-content', 'space-between' ) ) {
				$block->add_style_attribute( $style, self::KEY_STYLE_LINKS_CONTAINER );
			}
			if( $style = $factory->get_attribute( 'align-items', 'flex-start' ) ) {
				$block->add_style_attribute( $style, self::KEY_STYLE_LINKS_CONTAINER );
			}
		} else {
			if( $style = $factory->get_attribute( 'text-align', $align ) ) {
				$block->add_style_attribute( $style, self::KEY_STYLE_LINKS_CONTAINER );
			}
		}

		// Links style.
		$factory->apply_common_styles_to_block( $block, $attributes, 'previousNextStyle', null, self::KEY_STYLE_LINKS );

		// Hover style.
		$factory->apply_common_styles_to_block( $block, $attributes, 'previousNextStyle', 'hover', self::KEY_STYLE_LINKS_HOVER );
	}

	public function get_css_config() {
		// Apply all CSS to the root element.
		return [
			ABlock::CSS_SELECTOR_ROOT => [
				ABlock::KEY_STYLES_FOR_COMMON_STYLES  => [ 'text-align' ],
				self::KEY_STYLE_LINKS_CONTAINER => 'all',
			],
			'> a' .
			'!> span' => [
				self::KEY_STYLE_LINKS => 'all'
			],
			'> a:hover' => [
				self::KEY_STYLE_LINKS_HOVER => 'all'
			],
		];
	}
}
