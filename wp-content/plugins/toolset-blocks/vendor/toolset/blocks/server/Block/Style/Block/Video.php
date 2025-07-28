<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;


class Video extends ABlock {
	const KEY_STYLES_FOR_VIDEO = 'video';

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		// Space needed here as the block class is not on the same level as the data attribute.
		return $this->get_existing_block_classes_as_selector( [ 'tb-video' ] ) . ' ';
	}

	/**
	 * Css of the block.
	 *
	 * @param array $config
	 * @param false $force_apply
	 * @param null $responsive_device
	 *
	 * @return string
	 */
	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/video.css' );
		$parent_css = parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}


	/**
	 * Filter for the block content.
	 *
	 * @param string $content The content of the block.
	 * @param MobileDetect $device_detect
	 *
	 * @return mixed|string|string[]|null
	 */
	public function filter_block_content( $content, MobileDetect $device_detect ) {
		if ( ! defined( 'TB_SCRIPT_STYLE_LAZY_LOAD' ) || TB_SCRIPT_STYLE_LAZY_LOAD ) {
			// Replace the src by data-src. On user interaction or when the block is in the viewport 'data-src' is
			// replaced via js to 'src' again. This way it does not delay the page load.
			$content = str_replace( ' src="', ' data-src="', $content );
		}

		return parent::filter_block_content( $content, $device_detect );
	}


	/**
	 * @param FactoryStyleAttribute $factory
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();
		// width
		if ( isset( $config['width'] ) ) {
			$unit = isset( $config['widthUnit'] ) ? $config['widthUnit'] : '%';
			if ( $style = $factory->get_attribute_width( $config['width'], $unit ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_VIDEO );
			}
		}

		// height
		if ( isset( $config['height'] ) ) {
			$unit = isset( $config['heightUnit'] ) ? $config['heightUnit'] : 'px';
			if ( $style = $factory->get_attribute_height( $config['height'], $unit ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_VIDEO );
			}
		}
	}

	private function get_css_config() {
		return array(
			self::CSS_SELECTOR_ROOT => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'margin',
					'padding',
					'border',
					'border-radius',
					'box-shadow',
					'display',
				),
				self::KEY_STYLES_FOR_VIDEO => array(
					'width',
					'height',
				),
			),
		);
	}
}
