<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Common;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Class Grid
 *
 * @package ToolsetBlocks\Block\Style\Block
 */
class GridColumn extends Common {
	const KEY_STYLES_FOR_INNER = 'inner';

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector(
			[ 'wp-block-toolset-blocks-grid-column', 'tb-grid-column' ]
		);
	}

	public function get_css( $config = array(), $force_apply = false, $responsive_device = null ) {
		return parent::get_css( $this->css_config(), $force_apply, $responsive_device );
	}

	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();
		$devices = array(
			Devices::DEVICE_DESKTOP,
			Devices::DEVICE_PHONE,
			Devices::DEVICE_TABLET,
		);
		foreach ( $devices as $device ) {
			$uDevice = ucfirst( $device );
			$display = 'flex';
			if ( isset( $config[ 'hide' . $uDevice ] ) && $config[ 'hide' . $uDevice ] ) {
				$display = 'none';
			}
			if ( $style = $factory->get_attribute( 'display', $display ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_COMMON_STYLES, $device );
			}
		}
	}

	private function css_config() {
		return array(
			parent::CSS_SELECTOR_ROOT => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array(
					'background-color',
					'border-radius',
					'background',
					'padding',
					'margin',
					'box-shadow',
					'border',
					'min-height',
					'vertical-align',
					'display',
					'background-image',
				),
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
