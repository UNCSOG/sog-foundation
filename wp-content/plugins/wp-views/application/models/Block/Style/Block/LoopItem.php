<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Common;

/**
 * Loop Item Styles
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block
 */
class LoopItem extends Common {
	public function get_css_block_class() {
		return '.wpv-block-loop-item';
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		return parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );
	}

	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {}

	private function get_css_config() {
		return [];
	}
}
