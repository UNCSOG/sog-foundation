<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;

/**
 * Class Progress
 *
 * @package ToolsetBlocks\Block\Style\Block
 */
class Progress extends ABlock {
	const KEY_STYLES_FOR_DATA = 'data';
	const KEY_STYLES_FOR_TRAIL = 'trail';
	const KEY_STYLES_FOR_STROKE = 'stroke';
	const KEY_STYLES_FOR_TEXT = 'text';

	private $default_values = [
		'trailWidth' => 12,
		'strokeWidth' => 12,
		'strokeLinecap' => 'square',
		'autoFontSize' => true,
		'overlayText' => '[p]%',
		'showPercent' => true,
		'animate' => false,
		'isFontColorSet' => false,
		'isStrokeColorSet' => false,
		'isTrailColorSet' => false,
		'isStrokeLinecapSet' => false,
		'trailColor' => null,
		'strokeColor' => null,
	];

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'wp-block-toolset-blocks-progress', 'tb-progress' ] );
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/progress.css' );
		$parent_css = parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	/**
	 * @param FactoryStyleAttribute $factory
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		// icon styles
		$config = array_merge( $this->default_values, $this->get_block_config() );

		$trail_width = $config['trailWidth'];
		$stroke_width = $config['strokeWidth'];

		// Data styling
		$max_height = max( $trail_width, $stroke_width ) * 2;
		$min_height = min( $trail_width, $stroke_width ) * 2;
		if ( $style = $factory->get_attribute( 'height', [ 'height' => $max_height ] ) ) {
			$this->add_style_attribute( $style, self::KEY_STYLES_FOR_DATA );
		}

		// Text
		if ( isset( $config['fontColor'] ) ) {
			if ( $style = $factory->get_attribute( 'color', $config['fontColor'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_TEXT );
			}
		}
		if ( isset( $config['fontSize'] ) || ( isset( $config['autoFontSize'] ) && $config['autoFontSize'] ) ) {
			$fontSize = $config['autoFontSize'] ? $min_height : $config['fontSize'];
			if ( $style = $factory->get_attribute( 'font-size', $fontSize ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_TEXT );
			}
		}
		if ( $max_height ) {
			if ( $style = $factory->get_attribute( 'height', [ 'height' => $max_height ] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_TEXT );
			}
		}

		// Stroke
		if (
			isset( $config['isStrokeColorSet'] ) &&
			$config['isStrokeColorSet'] &&
			isset( $config['strokeColor'] )
		) {
			if ( $style = $factory->get_attribute( 'background-color', $config['strokeColor'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STROKE );
			}
		}
		if ( isset( $config['strokeLinecap'] ) && $config['strokeLinecap'] === 'round' ) {
			if ( $style = $factory->get_attribute( 'border-radius', [
				'topLeft' => $config['strokeWidth'],
				'topRight' => $config['strokeWidth'],
				'bottomLeft' => $config['strokeWidth'],
				'bottomRight' => $config['strokeWidth'],
			] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STROKE );
			}
		}
		if ( isset( $config['strokeWidth'] ) ) {
			if ( $style = $factory->get_attribute( 'height', [
				'height' => $config['strokeWidth'] * 2,
			] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STROKE );
			}
		}
		if ( isset( $config['percent'] ) && ! $config['animate'] ) {
			if ( $style = $factory->get_attribute( 'width', [
				'width' => $config['percent'],
				'widthUnit' => '%',
			] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STROKE );
			}
		}
		if ( $config['strokeWidth'] < $config['trailWidth'] ) {
			$width_difference = $config['trailWidth'] - $config['strokeWidth'];
			if ( $style = $factory->get_attribute( 'margin', [
				'enabled' => true,
				'marginRight' => $width_difference . 'px',
				'marginLeft' => $width_difference . 'px',
				'marginTop' => '0px',
				'marginBottom' => '0px',
			] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STROKE );
			}
		}
		if ( $config['strokeWidth'] < $config['trailWidth'] ) {
			if ( $style = $factory->get_attribute( 'top', [
				'top' => $config['trailWidth'] - $config['strokeWidth'],
			] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_STROKE );
			}
		}

		// Trail
		if (
			isset( $config['isTrailColorSet'] ) &&
			$config['isTrailColorSet'] &&
			isset( $config['trailColor'] )
		) {
			if ( $style = $factory->get_attribute( 'background-color', $config['trailColor'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_TRAIL );
			}
		}
		if ( isset( $config['strokeLinecap'] ) && $config['strokeLinecap'] === 'round' ) {
			if ( $style = $factory->get_attribute( 'border-radius', [
				'topLeft' => $config['trailWidth'],
				'topRight' => $config['trailWidth'],
				'bottomLeft' => $config['trailWidth'],
				'bottomRight' => $config['trailWidth'],
			] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_TRAIL );
			}
		}
		if ( isset( $config['trailWidth'] ) ) {
			if ( $style = $factory->get_attribute( 'height', [
				'height' => $config['trailWidth'] * 2,
			] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_TRAIL );
			}
		}
		if ( $config['trailWidth'] < $config['strokeWidth'] ) {
			if ( $style = $factory->get_attribute( 'top', [
				'top' => $config['strokeWidth'] - $config['trailWidth'],
			] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_TRAIL );
			}
		}
	}

	private function get_css_config() {
		return array(
			parent::CSS_SELECTOR_ROOT => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array(
					'background-color',
					'border-radius',
					'font-size',
					'color',
					'padding',
					'margin',
					'box-shadow',
					'border',
					'display',
				),
			),
			'.tb-progress-data' => array(
				self::KEY_STYLES_FOR_DATA => array(
					'height',
				),
			),
			'.tb-progress__text' => array(
				self::KEY_STYLES_FOR_TEXT => array(
					'color',
					'font-size',
					'height',
				),
			),
			'.tb-progress__stroke' => array(
				self::KEY_STYLES_FOR_STROKE => array(
					'background-color',
					'border-radius',
					'height',
					'width',
					'margin',
					'top',
				),
			),
			'.tb-progress__trail' => array(
				self::KEY_STYLES_FOR_TRAIL => array(
					'background-color',
					'border-radius',
					'height',
					'top',
				),
			),
		);
	}
}
