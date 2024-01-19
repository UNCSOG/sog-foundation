<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Common;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Custom Search Reset Styles
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block
 */
class CustomSearchReset extends Common {
	const KEY_STYLES_ROOT = 'root';

	/**
	 * @return string
	 */
	public function get_css_block_class() {
		return '.wpv-custom-search-filter-reset';
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		return parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );
	}

	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();

		// 'left' is the default value, defined in the attributes.js of pagination.
		// This is needed for all PaginationTypes.
		$align = isset( $config[ 'align' ] ) ? $config['align'] : 'left';
		$text_align_possibilites = [ 'left', 'center', 'right'];

		// 'align' can also hold flex values, e.g. for the Next/Prev option there's 'spaceBetween' as option.
		if( in_array( $align, $text_align_possibilites ) ) {
			if( $style = $factory->get_attribute( 'text-align', $align ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_ROOT );
			}
		}
	}

	public function filter_block_content( $content, MobileDetect $device_detect ) {
		return $this->common_filter_block_content_by_block_css_class(
			'wpv-custom-search-filter-reset',
			$content,
			$device_detect
		);
	}

	private function get_css_config() {
		return [
			parent::CSS_SELECTOR_ROOT => [
				parent::KEY_STYLES_FOR_CONTAINER => 'all', // Just for backwards compatibility.
				self::KEY_STYLES_ROOT => [ 'text-align' ], // Just for backwards compatibility.
				self::KEY_STYLES_FOR_COMMON_STYLES => [ 'text-align' ],
			],
			'.wpv-reset-trigger' => [
				parent::KEY_STYLES_FOR_COMMON_STYLES => 'all'
			]
		];
	}
}
