<?php

namespace ToolsetCommonEs\Block\Style\Block\Migration;


use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;

/**
 * Interface ITask
 *
 * @package ToolsetCommonEs\Block\Style\Block\Migration
 */
interface ITask {

	/**
	 * Applies the migration to the passed $block.
	 *
	 * @param ABlock $block
	 * @param FactoryStyleAttribute $factory_style_attribute
	 *
	 * @return void The $block is manipulated.
	 */
	public function migrate( ABlock $block, FactoryStyleAttribute $factory_style_attribute );
}
