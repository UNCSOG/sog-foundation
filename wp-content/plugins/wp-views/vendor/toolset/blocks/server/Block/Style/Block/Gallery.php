<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Block\Common;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Class FieldsAndText
 */
class Gallery extends ABlock {
	const LAYOUT_MASONRY = 'masonry';
	const LAYOUT_COLLAGE = 'collage';
	const LAYOUT_GRID = 'grid';
	const KEY_STYLES_FOR_CAPTION = 'caption';
	const KEY_STYLES_FOR_MASONRY_AREA = 'masonry';
	const KEY_STYLES_FOR_MASONRY_AREA_CONTENT = 'masonry-content';
	const KEY_STYLES_FOR_COLLAGE_GRID_AREA = 'collage-grid-area';
	const KEY_STYLES_FOR_COLLAGE = 'collage-div';
	const KEY_STYLES_FOR_GRID = 'grid-div';

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
		return $this->get_existing_block_classes_as_selector( [ 'tb-gallery' ] );
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
		$css = $this->get_css_file_content( TB_PATH_CSS . '/gallery.css' );
		$parent_css = parent::get_css( $this->get_css_config( $responsive_device ), $force_apply, $responsive_device );

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

		if ( isset( $config['type'] ) && self::LAYOUT_MASONRY === $config['type'] ) {
			// Default values.
			if ( ! isset( $config[ self::LAYOUT_MASONRY ] ) ) {
				$config[ self::LAYOUT_MASONRY ] = array(
					'desktop' => array(
						'cellSpacing' => 5,
						'numberOfColumns' => 3,
					),
				);
			}
		}

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

			if ( isset( $config['type'] ) && self::LAYOUT_MASONRY === $config['type'] ) {
				$spacing = isset( $config[ self::LAYOUT_MASONRY ][ $device ] ) && isset( $config[ self::LAYOUT_MASONRY ][ $device ]['cellSpacing'] )
					? $config[ self::LAYOUT_MASONRY ][ $device ]['cellSpacing']
					: $config[ self::LAYOUT_MASONRY ][ Devices::DEVICE_DESKTOP ]['cellSpacing'];

				$style_bottom = $factory->get_attribute( 'bottom', $spacing );
				if ( $style_bottom ) {
					$this->add_style_attribute( $style_bottom, self::KEY_STYLES_FOR_CAPTION, $device );
				}
			}
		}

		if ( isset( $config['type'] ) && self::LAYOUT_MASONRY === $config['type'] ) {
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
		} elseif ( isset( $config['type'] ) && self::LAYOUT_GRID === $config['type'] ) {
			// Default values.
			if ( ! isset( $config[ self::LAYOUT_GRID ] ) ) {
				$config[ self::LAYOUT_GRID ] = array(
					'desktop' => array(
						'cellSpacing' => 15,
						'numberOfColumns' => 4,
					),
					'tablet' => array(
						'cellSpacing' => 10,
						'numberOfColumns' => 3,
					),
					'phone' => array(
						'cellSpacing' => 5,
						'numberOfColumns' => 2,
					),
				);
			}
			foreach ( $devices as $device ) {
				$style_column_gap = $factory->get_attribute( 'grid-column-gap', $config[ self::LAYOUT_GRID ][ $device ]['cellSpacing'] );
				if ( $style_column_gap ) {
					$this->add_style_attribute( $style_column_gap, self::KEY_STYLES_FOR_GRID, $device );
				}
				$style_row_gap = $factory->get_attribute( 'grid-row-gap', $config[ self::LAYOUT_GRID ][ $device ]['cellSpacing'] );
				if ( $style_row_gap ) {
					$this->add_style_attribute( $style_row_gap, self::KEY_STYLES_FOR_GRID, $device );
				}

				$grid_template_columns = array_fill( 0, $config[ self::LAYOUT_GRID ][ $device ]['numberOfColumns'], 1 );
				$style_columns = $factory->get_attribute( 'grid-template-columns', $grid_template_columns );
				if ( $style_columns ) {
					$this->add_style_attribute( $style_columns, self::KEY_STYLES_FOR_GRID, $device );
				}
			}
		} elseif ( isset( $config['type'] ) && self::LAYOUT_COLLAGE === $config['type'] && isset( $config[ self::LAYOUT_COLLAGE ] ) ) {
			$devices = Devices::get_list_devices();
			foreach ( $devices as $device ) {
				if ( ! isset( $config[ self::LAYOUT_COLLAGE ][ $device ] ) ) {
					continue;
				}
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
			'.tb-gallery__caption' => array(
				self::KEY_STYLES_FOR_CAPTION => array(
					'font-family',
					'font-size',
					'font-style',
					'font-weight',
					'text-decoration',
					'line-height',
					'letter-spacing',
					'text-color',
					'text-transform',
					'text-shadow',
					'background',
					'color',
					'bottom',
				),
			),
			'.tb-gallery--grid' => array(
				self::KEY_STYLES_FOR_GRID => array(
					'grid-template-columns',
					'grid-row-gap',
					'grid-column-gap',
				),
			),
			'.tb-gallery--masonry' => array(
				self::KEY_STYLES_FOR_MASONRY_AREA => array(
					'grid-template-columns',
					'grid-column-gap',
				),
			),
			'.tb-gallery--masonry .tb-brick__content' => array(
				self::KEY_STYLES_FOR_MASONRY_AREA_CONTENT => array(
					'padding',
				),
			),
			'.tb-gallery--collage' => array(
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
				$rule = '.tb-gallery--collage li:nth-child(' . $number_of_cells . 'n + ' . ( intval( $index ) + 1 ) . ')';
				$config[ $rule ] = array();
				$config[ $rule ][ self::KEY_STYLES_FOR_COLLAGE_GRID_AREA . $index ] = array(
					'grid-area',
				);
			}
		}

		return $config;
	}
}
