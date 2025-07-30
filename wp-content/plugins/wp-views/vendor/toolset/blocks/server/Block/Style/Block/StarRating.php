<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Class StarRating
 *
 * @package ToolsetBlocks\Block\Style\Block
 */
class StarRating extends ABlock {
	const KEY_STYLES_FOR_STAR_RATING = 'star-rating';
	const KEY_STYLES_FOR_STAR_RATING_PROGRESS = 'star-rating-progress';

	const FONT_CODE_DASHICONS_EMPTY = '\\f154';
	const FONT_CODE_DASHICONS_FILLED = '\\f155';
	const FONT_CODE_UNICODE_EMPTY = '\\2606';
	const FONT_CODE_UNICODE_FILLED = '\\2605';

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'tb-rating' ] );
	}

	/**
	 * @param FactoryStyleAttribute $factory
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();

		$icon = isset( $config['icon'] ) ?
			$config['icon'] :
			'dashicon'; // default

		$star_type = isset( $config['starType'] ) ?
			$config['starType'] :
			'filled'; // default

		$number_of_icons = isset( $config['numberOfStars'] ) ?
			$config['numberOfStars'] :
			5; // default

		switch ( $icon ) {
			case 'custom':
				// custom icon font family
				if ( isset( $config['customFontFamilyName'] ) ) {
					if ( $style = $factory->get_attribute( 'font-family', $config['customFontFamilyName'] ) ) {
						$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STAR_RATING );
					}
				}

				// custom icon font code
				if ( isset( $config['customFontCode'] ) ) {
					$font_code = $config['customFontCode'];
				}
				break;
			case 'unicode':
				$font_code = $star_type == 'empty' ?
					self::FONT_CODE_UNICODE_EMPTY :
					self::FONT_CODE_UNICODE_FILLED;
				break;
			default:
				if ( $style = $factory->get_attribute( 'font-family', 'dashicons, sans-serif;' ) ) {
					$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STAR_RATING );
				}
				$font_code = $star_type == 'empty' ?
					self::FONT_CODE_DASHICONS_EMPTY :
					self::FONT_CODE_DASHICONS_FILLED;
				break;
		}

		if ( isset( $font_code ) ) {
			if ( $style = $factory->get_attribute( 'content', $font_code ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STAR_RATING );
			}

			if ( $style = $factory->get_attribute( 'content', str_repeat( $font_code, $number_of_icons ) ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STAR_RATING_PROGRESS );
			}
		}

		// The BackgroundColor needs to be applied as text color for the root element.
		if ( isset( $config['style'] ) && isset( $config['style']['backgroundColor'] ) ) {
			if ( $style = $factory->get_attribute( 'color', $config['style']['backgroundColor'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STAR_RATING );
			}
		}
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/star-rating.css' );
		$parent_css = parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	public function filter_content( $content ) {
		// Apply dynamic rating value.
		$content = preg_replace_callback(
			'/(data-tb-star-rating-progress-width)=\"([-0-9\ \*\.]*)\"/',
			function( $matches ) {
				return 'style="width: calc(' . $matches[2] . '%);"';
			},
			do_shortcode( $content )
		);

		return $content;
	}

	public function filter_block_content( $content, MobileDetect $device_detect ) {
		// Block Alignment.
		return $this->common_filter_block_content_by_block_css_class(
			'tb-rating',
			$content,
			$device_detect
		);
	}

	private function get_css_config() {
		return array(
			parent::CSS_SELECTOR_ROOT => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array(
					'font-size',
					'margin',
					'padding',
					'border',
					'border-radius',
					'box-shadow',
					'line-height',
					'display',
					'text-align',
				),
				self::KEY_STYLES_FOR_STAR_RATING => array(
					'color',
					'font-family',
				),
			),

			'.tb-rating__star:before' => array(
				self::KEY_STYLES_FOR_STAR_RATING => array(
					'content',
				),
			),
			'.tb-rating__rating:after' => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array(
					'color',
				),
				self::KEY_STYLES_FOR_STAR_RATING_PROGRESS => array(
					'content',
				),
			),
		);
	}
}
