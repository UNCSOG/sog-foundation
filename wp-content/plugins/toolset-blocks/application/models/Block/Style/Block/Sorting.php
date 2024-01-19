<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Common;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Sorting Styles
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block
 */
class Sorting extends Common {
	const KEY_STYLES_LABEL = 'label';

	/**
	 * @return string
	 */
	public function get_css_block_class() {
		return '.wpv-sorting-block';
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		return parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );
	}

	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();

		// Label styles.
		$factory->apply_common_styles_to_block( $this, $config, 'labelStyles', null, self::KEY_STYLES_LABEL );
	}

	public function filter_block_content( $content, MobileDetect $device_detect ) {
		return $this->common_filter_block_content_by_block_css_class(
			'wpv-sorting-block',
			$content,
			$device_detect
		);
	}

	private function get_css_config() {
		return [
			'.wpv-sorting-block-item.wpv-sorting-block-label' => [
				self::KEY_STYLES_LABEL => [
					'font-size',
					'font-style',
					'color',
					'line-height',
					'box-shadow',
					'border-radius',
					'border',
					'background-color',
					'margin',
					'padding',
				]
			]
		];
	}
}
