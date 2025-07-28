<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Block\Common;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Class FieldsAndText
 */
class RepeatingField extends Common {
	const KEY_STYLES_FOR_FLEXBOX_AREA = 'flexbox';
	const KEY_STYLES_FOR_FLEXBOX_AREA_DIV = 'flexbox-div';
	const KEY_STYLES_FOR_MASONRY_AREA = 'masonry';
	const KEY_STYLES_FOR_MASONRY_AREA_CONTENT = 'masonry-content';
	const LAYOUT_FLEXBOX = 'flexbox';
	const LAYOUT_MASONRY = 'masonry';
	const LAYOUT_COLLAGE = 'collage';
	const KEY_STYLES_FOR_COLLAGE_GRID_AREA = 'collage-grid-area';
	const KEY_STYLES_FOR_COLLAGE = 'collage-div';

	/**
	 * Contains the list of grid areas for collage layout
	 *
	 * @var array
	 */
	private $grid_areas = array();

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'tb-repeating-field' ] );
	}

	/**
	 * Returns CSS styles
	 *
	 * @param array   $config Block config.
	 * @param boolean $force_apply Forces to apply it.
	 * @param string  $responsive_device Device.
	 * @return string
	 */
	public function get_css( $config = array(), $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/repeating-fields.css' );
		$parent_css = parent::get_css( $this->get_css_config( $responsive_device ), $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	/**
	 * Loads css specific for this block
	 *
	 * @param FactoryStyleAttribute $factory Style Factory.
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		// icon styles
		$config = $this->get_block_config();

		if ( isset( $config['displayMode'] ) && self::LAYOUT_FLEXBOX === $config['displayMode'] && isset( $config['imageSpacing'] ) && $config['imageSpacing'] ) {
			$spacing = $config['imageSpacing'];
			$margin = array(
				'enabled' => true,
				'marginTop' => -1 * $spacing,
				'marginRight' => 0,
				'marginBottom' => 0,
				'marginLeft' => -1 * $spacing,
			);
			$padding = array(
				'enabled' => true,
				'paddingTop' => $spacing,
				'paddingRight' => 0,
				'paddingBottom' => 0,
				'paddingLeft' => $spacing,
			);
			$style = $factory->get_attribute( 'margin', $margin );
			if ( $style ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_FLEXBOX_AREA );
			}
			$style = $factory->get_attribute( 'padding', $padding );
			if ( $style ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_FLEXBOX_AREA_DIV );
			}
		} elseif ( isset( $config['displayMode'] ) && self::LAYOUT_COLLAGE === $config['displayMode'] && isset( $config[ self::LAYOUT_COLLAGE ] ) ) {
			$devices = Devices::get_list_devices();
			foreach ( $devices as $device ) {
				if ( isset( $config[ self::LAYOUT_COLLAGE ][ $device ]['cellSpacing'] ) ) {
					$style_column_gap = $factory->get_attribute( 'grid-column-gap', $config[ self::LAYOUT_COLLAGE ][ $device ]['cellSpacing'] );
					if ( $style_column_gap ) {
						$this->add_style_attribute( $style_column_gap, self::KEY_STYLES_FOR_COLLAGE, $device );
					}
					$style_row_gap = $factory->get_attribute( 'grid-row-gap', $config[ self::LAYOUT_COLLAGE ][ $device ]['cellSpacing'] );
					if ( $style_row_gap ) {
						$this->add_style_attribute( $style_row_gap, self::KEY_STYLES_FOR_COLLAGE, $device );
					}
				}
				if ( isset( $config[ self::LAYOUT_COLLAGE ][ $device ]['height'] ) ) {
					$style_auto_rows = $factory->get_attribute( 'grid-auto-rows', $config[ self::LAYOUT_COLLAGE ][ $device ]['height'] / $config[ self::LAYOUT_COLLAGE ]['numberOfRows'] );
					if ( $style_auto_rows ) {
						$this->add_style_attribute( $style_auto_rows, self::KEY_STYLES_FOR_COLLAGE, $device );
					}
				}
				if ( isset( $config[ self::LAYOUT_COLLAGE ][ $device ]['settings'] ) ) {
					$this->grid_areas[ $device ] = array_map(
						function( $item ) {
							return ' auto / auto / ' .
								( isset( $item['height'] ) ? 'span ' . $item['height'] : 'auto' ) . ' / ' .
								( isset( $item['width'] ) ? 'span ' . $item['width'] : 'auto' );
						},
						array_filter( $config[ self::LAYOUT_COLLAGE ][ $device ]['settings'] )
					);
					foreach ( $this->grid_areas[ $device ] as $index => $area ) {
						$style_grid_area = $factory->get_attribute( 'grid-area', $area );
						if ( $style_grid_area ) {
							$this->add_style_attribute( $style_grid_area, self::KEY_STYLES_FOR_COLLAGE_GRID_AREA . $index, $device );
						}
					}
				}
			}
		}
		if ( isset( $config['displayMode'] ) && self::LAYOUT_MASONRY === $config['displayMode'] ) {
			// Default values.
			if ( ! isset( $config[ self::LAYOUT_MASONRY ] ) ) {
				$config[ self::LAYOUT_MASONRY ] = array(
					'desktop' => array(
						'cellSpacing' => 5,
						'numberOfColumns' => 3,
					),
				);
			}
			$devices = Devices::get_list_devices();
			foreach ( $devices as $device ) {
				$spacing = isset( $config[ self::LAYOUT_MASONRY ][ $device ] ) && isset( $config[ self::LAYOUT_MASONRY ][ $device ]['cellSpacing'] )
					? $config[ self::LAYOUT_MASONRY ][ $device ]['cellSpacing']
					: $config[ self::LAYOUT_MASONRY ][ Devices::DEVICE_DESKTOP ]['cellSpacing'];
				$style = $factory->get_attribute( 'grid-column-gap', $spacing );
				if ( $style ) {
					$this->add_style_attribute( $style, self::KEY_STYLES_FOR_MASONRY_AREA, $device );
				}

				$padding = array(
					'enabled' => true,
					'paddingTop' => 0,
					'paddingRight' => 0,
					'paddingBottom' => $spacing,
					'paddingLeft' => 0,
				);
				$style = $factory->get_attribute( 'padding', $padding );
				if ( $style ) {
					$this->add_style_attribute( $style, self::KEY_STYLES_FOR_MASONRY_AREA_CONTENT, $device );
				}

				$number_columns = isset( $config[ self::LAYOUT_MASONRY ][ $device ] ) && isset( $config[ self::LAYOUT_MASONRY ][ $device ]['numberOfColumns'] )
					? $config[ self::LAYOUT_MASONRY ][ $device ]['numberOfColumns']
					: $config[ self::LAYOUT_MASONRY ][ Devices::DEVICE_DESKTOP ]['numberOfColumns'];
				$grid_template_columns = array_fill( 0, $number_columns, 1 );
				$style = $factory->get_attribute( 'grid-template-columns', $grid_template_columns );
				if ( $style ) {
					$this->add_style_attribute( $style, self::KEY_STYLES_FOR_MASONRY_AREA, $device );
				}
			}
		}
	}

	/**
	 * Gets CSS Config
	 *
	 * @param string $device Device.
	 * @return array
	 */
	private function get_css_config( $device ) {
		$config = array(
			parent::CSS_SELECTOR_ROOT => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array(
					'background-color',
					'border-radius',
					'padding',
					'margin',
					'box-shadow',
					'border',
					'display',
				),
			),
			parent::CSS_SELECTOR_ROOT . '!li' => array(
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
			'.tb-repeating-field--flexbox' => array(
				self::KEY_STYLES_FOR_FLEXBOX_AREA => array(
					'margin',
				),
			),
			'.tb-repeating-field--flexbox > div' => array(
				self::KEY_STYLES_FOR_FLEXBOX_AREA_DIV => array(
					'padding',
				),
			),
			'.tb-repeating-field--masonry' => array(
				self::KEY_STYLES_FOR_MASONRY_AREA => array(
					'grid-template-columns',
					'grid-column-gap',
				),
			),
			'.tb-repeating-field--masonry .tb-brick__content' => array(
				self::KEY_STYLES_FOR_MASONRY_AREA_CONTENT => array(
					'padding',
				),
			),
			'.tb-repeating-field--collage' => array(
				self::KEY_STYLES_FOR_COLLAGE => array(
					'grid-column-gap',
					'grid-row-gap',
					'grid-auto-rows',
				),
			),
		);

		if ( ! empty( $this->grid_areas ) ) {
			$areas = isset( $this->grid_areas[ $device ] ) ? $this->grid_areas[ $device ] : $this->grid_areas[ Devices::DEVICE_DEFAULT ];
			$number_of_cells = count( $areas );
			foreach ( $areas as $index => $area ) {
				$rule = '.tb-repeating-field--collage div:nth-child(' . $number_of_cells . 'n + ' . ( intval( $index ) + 1 ) . ')';
				$config[ $rule ] = array();
				$config[ $rule ][ self::KEY_STYLES_FOR_COLLAGE_GRID_AREA . $index ] = array(
					'grid-area',
				);
			}
		}

		return $config;
	}
}
