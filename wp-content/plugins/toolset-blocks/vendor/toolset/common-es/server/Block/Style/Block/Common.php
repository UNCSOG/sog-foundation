<?php

namespace ToolsetCommonEs\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;

/**
 * Class Common
 *
 * A couple of blocks don't have anything specific and just using Style Settings and Container.
 *
 * @package ToolsetCommonEs\Block\Style\Block
 */
class Common extends ABlock {
	/**
	 * @param FactoryStyleAttribute $factory
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		// no block specific styles yet
		return;
	}
}
