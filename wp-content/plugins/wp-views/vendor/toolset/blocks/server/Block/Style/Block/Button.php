<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Class Button
 *
 * @package ToolsetBlocks\Block\Style\Block
 */
class Button extends ABlock {
	const KEY_STYLES_FOR_ICON = 'icon';

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'tb-button' ] );
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/button.css' );
		$parent_css = parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	/**
	 * The position of the data-toolset-blocks-button attribute has changed from 1.1.3 to 1.2.
	 * To have the styles also applied to 1.1.3 saved buttons we need to move the attribute to the root element.
	 *
	 * @param string $content
	 * @param MobileDetect $device_detect
	 *
	 * @return string
	 */
	public function filter_block_content( $content, MobileDetect $device_detect ) {
		$content = parent::filter_block_content( $content, $device_detect );

		if ( strpos( $content, 'data-toolset-blocks-button' ) < strpos( $content, '<a ' ) ) {
			// The data-toolset-blocks-button is already in the root element.
			// This is not necessary but way faster than preg_replace and the preg_replace is
			// not needed for all fresh installations which have no buttons created with 1.1.3.
			return $content;
		}

		// Move the data-toolset-blocks-button attribute to the root element.
		return preg_replace(
			'/(<div.*?class="tb-button.*?").*?(>.*?)([ ]?)(data-toolset-blocks-button=".*?")(.*)/ism',
			'$1 $4$2$5',
			$content
		);
	}

	/**
	 * @param FactoryStyleAttribute $factory
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		// icon styles
		$config = $this->get_block_config();

		$icon_styles = isset( $config['icon'] ) && is_array( $config['icon'] ) ? $config['icon'] : false;

		if ( empty( $icon_styles ) ) {
			return;
		}

		// font family
		if ( isset( $icon_styles['fontFamily'] ) ) {
			if ( $style = $factory->get_attribute( 'font-family', $icon_styles['fontFamily'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_ICON );
			}
		}

		// font code
		$icon_style_font_code =
			isset( $icon_styles['fontCode'] ) &&
			'' !== $icon_styles['fontCode'] ?
				$icon_styles['fontCode'] :
				false;
		if ( $icon_style_font_code ) {
			// I don't know why, font codes like '\f11f' are translated to \f => form feed (FF or 0x0C (12) in ASCII), breaking all CSS rules
			// I wasn't able to figure out why sometimes json_decode translates it properly and in a different WP site it doesn't wrongly
			// Solution: replace it :(
			$font_code = str_replace( "\f", '\f', $icon_styles['fontCode'] );
			if ( $style = $factory->get_attribute( 'content', $font_code ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_ICON );
			}
		}

		// spacing
		$icon_style_spacing =
			$icon_style_font_code && // Spacing only makes sense when an icon is present.
			isset( $icon_styles['spacing'] ) &&
			'' !== $icon_styles['spacing']
			? $icon_styles['spacing'] :
			false;
		if ( $icon_style_spacing ) {
			$position = isset( $icon_styles['position'] ) ? $icon_styles['position'] : 'left';
			$margin = array(
				'enabled' => true,
				'marginTop' => null,
				'marginBottom' => null,
				'marginLeft' => null,
				'marginRight' => null,
			);

			if ( $position === 'left' ) {
				$margin['marginRight'] = $icon_styles['spacing'] . 'px';
			} else {
				$margin['marginLeft'] = $icon_styles['spacing'] . 'px';
			}

			if ( $style = $factory->get_attribute( 'margin', $margin ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_ICON );
			}
		}
	}

	private function get_css_config() {
		$styling_styles = [
			'background-color',
			'border-radius',
			'color',
			'padding',
			'margin',
			'box-shadow',
			'border',
		];
		$fonts_styles = [
			'font-family',
			'font-style',
			'font-weight',
			'letter-spacing',
			'text-decoration',
			'text-shadow',
			'text-transform',
			'color',
		];

		return array(
			parent::CSS_SELECTOR_ROOT => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array_merge( [ 'display', 'text-align' ] ),
			),
			'.tb-button__link' => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array_merge( $styling_styles, array( 'font-size', 'line-height' ), $fonts_styles ),
			),
			// :visited has some restrictions
			// @link https://developer.mozilla.org/en-US/docs/Web/CSS/:visited#Privacy_restrictions
			'.tb-button__link:visited' => array(
				parent::KEY_STYLES_FOR_VISITED => array_merge( $styling_styles, $fonts_styles ),
			),
			'.tb-button__link:hover' => array(
				parent::KEY_STYLES_FOR_HOVER => array_merge( $styling_styles, array( 'font-size', 'line-height' ), $fonts_styles ),
			),
			'.tb-button__link:focus' => array(
				parent::KEY_STYLES_FOR_FOCUS => array_merge( $styling_styles, $fonts_styles ),
			),
			'.tb-button__link:active' => array(
				parent::KEY_STYLES_FOR_ACTIVE => array_merge( $styling_styles, $fonts_styles ),
			),
			'.tb-button__icon' => array(
				self::KEY_STYLES_FOR_ICON => array(
					'font-family',
					'margin',
				),
			),

			'.tb-button__icon::before' => array(
				self::KEY_STYLES_FOR_ICON => array(
					'content',
				),
			),
		);
	}
}
