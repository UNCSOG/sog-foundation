<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Block\Style\Block\IBlock;

/**
 * Pagination Type 'link'.
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType
 */
class Links extends Dropdown {
	const KEY_STYLES_LINKS_CONTAINER = 'links_container';
	const KEY_STYLES_LINKS = 'links';
	const KEY_STYLES_LINKS_HOVER = 'hover';
	const KEY_STYLES_LINKS_CURRENT = 'current';

	public function get_type_name() {
		return 'link';
	}

	public function get_css_block_class() {
		return '.wpv-pagination-nav-links';
	}

	/**
	 * Justify-content and link styles.
	 *
	 * @param IBlock $block
	 * @param FactoryStyleAttribute $factory
	 * @param $attributes
	 */
	public function get_specific_style_attributes( IBlock $block, FactoryStyleAttribute $factory, $attributes ) {
		// Get justify-content from Dropdown.
		parent::get_specific_style_attributes( $block, $factory, $attributes );

		// Links container style.
		$factory->apply_common_styles_to_block( $block, $attributes, 'linksStyle', 'container', self::KEY_STYLES_LINKS_CONTAINER );

		// Links style.
		$factory->apply_common_styles_to_block( $block, $attributes, 'linksStyle', null, self::KEY_STYLES_LINKS );

		// Hover style.
		$factory->apply_common_styles_to_block( $block, $attributes, 'linksStyle', 'hover', self::KEY_STYLES_LINKS_HOVER );

		// Current active link style.
		$factory->apply_common_styles_to_block( $block, $attributes, 'linksStyle', 'current', self::KEY_STYLES_LINKS_CURRENT );
	}

	public function get_css_config() {
		// Apply all CSS to the root element.
		return [
			ABlock::CSS_SELECTOR_ROOT => [
				ABlock::KEY_STYLES_FOR_COMMON_STYLES => 'all',
				self::KEY_STYLES_LINKS_CONTAINER     => 'all',
			],
			'.page-item a.page-link' .
			'!.wpv-filter-pagination-link' .
			'!span.wpv-filter-previous-link' .
			'!span.wpv-filter-next-link' => [
				self::KEY_STYLES_LINKS => 'all'
			],
			'.page-item a.page-link:hover' .
			'!a.wpv-filter-pagination-link:hover' => [
				self::KEY_STYLES_LINKS_HOVER => 'all'
			],
			'.page-item.active span.page-link' .
			'!.wpv-pagination-nav-links-item-current span.wpv-filter-pagination-link' => [
				self::KEY_STYLES_LINKS_CURRENT => 'all'
			]
		];
	}
}
