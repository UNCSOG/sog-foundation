<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Common;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Frontend Styles for Image Slider block.
 *
 * @package ToolsetBlocks\Block\Style\Block
 */
class ImageSlider extends Common {
	const KEY_STYLES_FOR_CAPTION = 'caption';

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'tb-image-slider' ] );
	}

	/**
	 * Returns CSS styles
	 *
	 * @param array   $config Block config.
	 * @param boolean $force_apply Forces to apply it.
	 * @param string  $responsive_device Device.
	 * @return string
	 */
	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/image-slider.css' );
		$parent_css = parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	/**
	 * Returns used font. Currently this only supports to have ONE font per block.
	 *
	 * @param array $devices List of devices.
	 * @param string $attribute Block config attribute name.
	 *
	 * @return array
	 */
	public function get_font( $devices = [ Devices::DEVICE_DESKTOP => true ], $attribute = 'style' ) {
		return parent::get_font( $devices, 'styleCaption' );
	}

	/**
	 * Loads css specific for this block
	 *
	 * @param FactoryStyleAttribute $factory Style Factory.
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();
		$devices = array( Devices::DEVICE_DESKTOP, Devices::DEVICE_TABLET, Devices::DEVICE_PHONE );

		foreach ( $devices as $device ) {
			$styles = $factory->load_common_attributes_by_array(
				$config,
				'styleCaption',
				null,
				$device
			);
			if ( ! empty( $styles ) ) {
				foreach ( $styles as $style ) {
					$this->add_style_attribute( $style, self::KEY_STYLES_FOR_CAPTION, $device );
				}
			}
		}
	}

	/**
	 * Gets CSS Config
	 *
	 * @return array
	 */
	private function get_css_config() {
		return [
			// This is a fix for 2020 theme problem with margins applied to topmost block div. Luckily, this block has
			// an inner div in which the whole slider is rendered, so we can apply all the styles on this div instead of
			// parent::CSS_SELECTOR_ROOT. It doesn't have a negative effect on other themes.
			'.tb-image-slider--carousel' => [
				parent::KEY_STYLES_FOR_COMMON_STYLES => [
					'background-color',
					'border-radius',
					'padding',
					'margin',
					'box-shadow',
					'border',
					'display',
				],
			],
			'.tb-image-slider__caption' => array(
				self::KEY_STYLES_FOR_CAPTION => array(
					'font-size',
					'font-weight',
					'font-style',
					'line-height',
					'letter-spacing',
					'text-color',
					'text-transform',
					'text-shadow',
					'text-decoration',
					'background',
					'bottom',
				),
			),
			// This is a 2020 theme fix. Other themes are just fine with all styles applied to __caption div, but 2020
			// has some aggressive CSS of its own for figcaption, so we have to be more specific in this way. (While
			// others, like background, need to stay on div in order to work properly.)
			'.tb-image-slider__caption figcaption' => array(
				self::KEY_STYLES_FOR_CAPTION => array(
					'font-family',
					'color',
				),
			),
		];
	}
}
