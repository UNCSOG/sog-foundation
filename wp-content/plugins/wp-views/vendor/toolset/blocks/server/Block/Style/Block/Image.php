<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

class Image extends ABlock {
	const KEY_STYLES_FOR_IMAGE = 'img';
	const KEY_STYLES_FOR_IMAGE_HOVER = 'img-hover';
	const KEY_STYLES_FOR_CAPTION = 'caption';

	const FRAME_NONE = 'none';
	const FRAME_POLAROID = 'polaroid';
	const FRAME_SHADOW_1 = 'shadow1';

	/** @var string[] CSS classes which are added to the data selector. */
	private $css_classes_before_data_attribute = [ 'wp-block-image', 'tb-image' ];

	/**
	 * Image constructor.
	 *
	 * @param mixed[] $block_config
	 * @param string $block_name_for_id_generation
	 */
	public function __construct( $block_config, $block_name_for_id_generation = 'unknown', \ToolsetCommonEs\Assets\Loader $assets_loader = null ) {
		$block_config = $this->apply_defaults( $block_config );

		parent::__construct( $block_config, $block_name_for_id_generation, $assets_loader );

		/*
		 The image block already had an blockId before getting rid of inline css.
		   But old saved blocks are not valid. Luckily the old id is below 10 digits, while the new is above. */
		if ( strlen( $this->get_id() ) < 10 ) {
			throw new \InvalidArgumentException( 'Old Image block.' . $this->get_id() );
		}
	}

	private function apply_defaults( $block_config ) {
		// ApplyMaxWidth is true by default.
		$is_wide_or_full = isset( $block_config['align'] ) && in_array( $block_config['align'], [ 'wide', 'full' ] );
		if ( ! $is_wide_or_full &&
			( ! isset( $block_config['style'] ) || ! isset( $block_config['style']['applyMaxWidth'] ) ) ) {
			$block_config['style']['applyMaxWidth'] = true;
		}

		return $block_config;
	}

	/**
	 * @param array $config
	 *
	 * @param bool $force_apply
	 *
	 * @return string
	 */
	public function get_css( $config = array(), $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/image.css' );
		$content = $this->get_content();

		if ( strpos( $content, 'tb-image-shadow-1' ) ) {
			$this->css_print( TB_PATH_CSS . '/image-shadow-1.css' );
		} elseif ( strpos( $content, 'tb-image-polaroid' ) ) {
			$this->css_print( TB_PATH_CSS . '/image-polaroid.css' );
		}

		$config = $this->get_block_config();

		$frame = isset( $config['frame'] ) ? $config['frame'] : 'none';
		$frame_config = $this->get_frame_config( $frame );

		$parent_css = parent::get_css( $frame_config, $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	/**
	 * @param FactoryStyleAttribute $factory
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();

		// caption color
		if ( isset( $config['style'] ) ) {
			$factory->apply_style_to_block_for_all_devices(
				$this,
				$config['style'],
				'color',
				self::KEY_STYLES_FOR_CAPTION,
				'captionColor'
			);
		}

		/*
		 * Hover Styles
		 */
		if ( ! isset( $config['hover'] ) || ! is_array( $config['hover'] ) ) {
			return;
		}

		$hover = $config['hover'];

		// scale
		if ( isset( $hover['scale'] ) ) {
			if ( $style = $factory->get_attribute( 'scale', $hover['scale'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_IMAGE_HOVER );
			}
		}

		// rotate
		if ( isset( $hover['rotate'] ) ) {
			if ( $style = $factory->get_attribute( 'rotate', $hover['rotate'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_IMAGE_HOVER );
			}
		}

		// z-index
		if ( isset( $hover['zIndex'] ) ) {
			if ( $style = $factory->get_attribute( 'zindex', $hover['zIndex'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_IMAGE_HOVER );
			}
		}
	}

	public function filter_block_content( $content, MobileDetect $device_detect ) {
		$config = $this->get_block_config();
		$style = isset( $config['style'] ) ? $config['style'] : [];

		if ( isset( $style['lazyLoad'] ) ) {
			// This currently has no GUI but can be applied manually (using Code Editor instead of Visual).
			$content = str_replace( ' src="', ' data-src="', $content );
		}

		// Apply browser lazy loading.
		$content = str_replace( ' src="', ' loading="lazy" src="', $content );

		$block_alignment = $this->get_block_alignment( $style, $device_detect );

		if ( ! $block_alignment ) {
			return $content;
		}

		$alignments_require_container = [ 'left', 'center', 'right' ];

		if ( in_array( $block_alignment, $alignments_require_container ) ) {
			// Remove wp-block-image from figure tag.
			$content = preg_replace(
				'/(figure.*?class=[\"\'](?:.*?))(wp-block-image)(.*?)([\"\'])/',
				'$1$3$4',
				$content,
				1
			);

			// Add container.
			$content = '<div class="wp-block-image">' . $content . '</div>';

			// With this markup only 'tb-image' can be prepended to the data selector.
			$this->css_classes_before_data_attribute = [ 'tb-image' ];
		}

		// Add align class.
		return $this->common_filter_block_content_by_block_css_class(
			'tb-image',
			$content,
			$device_detect
		);
	}


	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( $this->css_classes_before_data_attribute );
	}

	private function get_frame_config( $frame = 'none' ) {
		switch ( $frame ) {
			case self::FRAME_POLAROID:
				return $this->get_frame_polaroid_config();
			case self::FRAME_SHADOW_1:
				return $this->get_frame_shadow_1_config();
			default:
				return $this->get_frame_none_config();
		}
	}

	private function get_frame_none_config() {
		return array(
			self::CSS_SELECTOR_ROOT => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'rotate',
					'z-index',
					'display',
					'width',
					'max-width',
				),
			),
			'figcaption' => array(
				self::KEY_STYLES_FOR_CAPTION => array(
					'color',
				),
			),
			'img' => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'box-shadow',
					'border-radius',
					'background-color',
					'padding',
					'margin',
					'border',
					'height',
				),
			),
			':hover' => array(
				self::KEY_STYLES_FOR_IMAGE_HOVER => array(
					'rotate',
					'z-index',
				),
			),
			':hover img' => array(
				self::KEY_STYLES_FOR_IMAGE_HOVER => array(
					'scale',
				),
			),
		);
	}

	private function get_frame_polaroid_config() {
		return array(
			self::CSS_SELECTOR_ROOT => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'rotate',
					'z-index',
					'width',
					'max-width',
				),
			),
			'figcaption' => array(
				self::KEY_STYLES_FOR_CAPTION => array(
					'color',
				),
			),
			'.tb-image-polaroid' => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'margin',
				),
			),
			'.tb-image-polaroid-inner' => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'background-color',
					'border',
					'border-radius',
					'box-shadow',
					'padding',
				),
			),
			'img' => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'height',
				),
			),
			':hover' => array(
				self::KEY_STYLES_FOR_IMAGE_HOVER => array(
					'rotate',
					'scale',
					'z-index',
				),
			),

		);
	}

	private function get_frame_shadow_1_config() {
		return array(
			self::CSS_SELECTOR_ROOT => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'rotate',
					'z-index',
					'width',
					'max-width',
				),
			),
			'figcaption' => array(
				self::KEY_STYLES_FOR_CAPTION => array(
					'color',
				),
			),
			'.tb-image-shadow-1' => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'margin',
				),
			),
			'.tb-image-shadow-1-inner' => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'background-color',
					'border',
					'border-radius',
					'box-shadow',
					'padding',
				),
			),
			'img' => array(
				self::KEY_STYLES_FOR_COMMON_STYLES => array(
					'height',
				),
			),
			':hover' => array(
				self::KEY_STYLES_FOR_IMAGE_HOVER => array(
					'rotate',
					'scale',
					'z-index',
				),
			),

		);
	}
}
