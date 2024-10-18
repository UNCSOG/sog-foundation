<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Block\Common;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Class FieldsAndText
 */
class FieldsAndText extends Common {
	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'tb-fields-and-text' ] );
	}

	/**
	 * Filter block content to prevent apostrophes, which are put directly after a shortcodes, being transformed to
	 * single quotes by WordPress wptexturize method.
	 *
	 * @param string $content The content of the block.
	 * @param MobileDetect $device_detect
	 *
	 * @return mixed|string|string[]|null
	 */
	public function filter_block_content( $content, MobileDetect $device_detect ) {
		$content = parent::filter_block_content( $content, $device_detect );

		return preg_replace( "/(\[.*?\])'/", '$1&#8217;', $content );
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		return parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );
	}

	private function get_css_config() {
		return array(
			parent::CSS_SELECTOR_ROOT => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => 'all',
			),
			'p' => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array(
					'font-size',
					'font-family',
					'font-style',
					'font-weight',
					'line-height',
					'letter-spacing',
					'text-decoration',
					'text-shadow',
					'text-transform',
					'color',
				),
			),
		);
	}
}
