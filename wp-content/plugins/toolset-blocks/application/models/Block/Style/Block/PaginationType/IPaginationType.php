<?php
namespace OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Attribute\IAttribute;
use ToolsetCommonEs\Block\Style\Block\IBlock;

/**
 * Interface IPaginationType
 * @package OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType
 */
interface IPaginationType {
	/**
	 * @return string
	 */
	public function get_type_name();

	/**
	 * @return string
	 */
	public function get_css_block_class();

	/**
	 * @param IBlock $block
	 * @param FactoryStyleAttribute $factory
	 * @param array $attributes
	 */
	public function get_specific_style_attributes( IBlock $block, FactoryStyleAttribute $factory, $attributes );

	/**
	 * @return array
	 */
	public function get_css_config();
}
