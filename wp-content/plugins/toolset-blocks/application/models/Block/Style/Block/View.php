<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Common;
use ToolsetBlocks\Block\Style\Block\Grid;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Loop Item Styles
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block
 */
class View extends Grid {
	const KEY_STYLES_FOR_MASONRY_AREA = 'masonry';
	const KEY_STYLES_FOR_MASONRY_AREA_CONTENT = 'masonry-content';
	const KEY_STYLES_FOR_COLLAGE_GRID_AREA = 'collage-grid-area';
	const KEY_STYLES_FOR_COLLAGE = 'collage-div';

	/**
	 * Contains the list of grid areas for collage layout
	 *
	 * @var array
	 */
	private $grid_areas = array();

	public function get_css_block_class() {
		return '.wpv-view-output';
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		return parent::get_css( $this->css_config( $responsive_device ), $force_apply, $responsive_device );
	}

	public function filter_block_content( $content, MobileDetect $device_detect ) {
		return $this->common_filter_block_content_by_block_css_class(
			'wpv-view-output',
			$content,
			$device_detect
		);
	}

	/**
	 * Loads Collage specific css for this block
	 *
	 * @param FactoryStyleAttribute $factory Style Factory.
	 * @param array $config Block config.
	 */
	private function load_collage_style_attributes( FactoryStyleAttribute $factory, $config ) {
		$devices = Devices::get_list_devices();
		foreach ( $devices as $device ) {
			if ( isset( $config['collage'][ $device ]['cellSpacing'] ) ) {
				$style_column_gap = $factory->get_attribute( 'grid-column-gap', $config['collage'][ $device ]['cellSpacing'] );
				if ( $style_column_gap ) {
					$this->add_style_attribute( $style_column_gap, self::KEY_STYLES_FOR_COLLAGE, $device );
				}
				$style_row_gap = $factory->get_attribute( 'grid-row-gap', $config['collage'][ $device ]['cellSpacing'] );
				if ( $style_row_gap ) {
					$this->add_style_attribute( $style_row_gap, self::KEY_STYLES_FOR_COLLAGE, $device );
				}
			}
			if ( isset( $config['collage'][ $device ]['height'] ) ) {
				$style_auto_rows = $factory->get_attribute( 'grid-auto-rows', $config['collage'][ $device ]['height'] / $config['collage']['numberOfRows'] );
				if ( $style_auto_rows ) {
					$this->add_style_attribute( $style_auto_rows, self::KEY_STYLES_FOR_COLLAGE , $device );
				}
			}
			if ( isset( $config['collage'][ $device ]['settings'] ) ) {
				$this->grid_areas[ $device ] = array_map(
					function( $item ) {
						return ' auto / auto / ' .
							( isset( $item['height'] ) ? 'span ' . $item['height'] : 'auto' ) . ' / ' .
							( isset( $item['width'] ) ? 'span ' . $item['width'] : 'auto' );
					},
					array_filter( $config['collage'][ $device ]['settings'] )
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

	/**
	 * Loads css specific for this block
	 *
	 * @param FactoryStyleAttribute $factory Style Factory.
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();
		$is_using_grid = false;
		$is_using_masonry = false;
		$is_using_collage = false;

		if ( is_array( $config ) && array_key_exists( 'viewId', $config ) ) {
			$view_layout_settings = get_post_meta( $config['viewId'], '_wpv_layout_settings', true );
			$view_layout = is_array( $view_layout_settings ) &&
			array_key_exists( 'layout_meta_html', $view_layout_settings ) ?
				$view_layout_settings['layout_meta_html'] :
				'';

			$is_using_grid = strpos( $view_layout, 'tb-grid-column' );
			$is_using_masonry = strpos( $view_layout, 'tb-masonry' );
			$is_using_collage = strpos( $view_layout, 'wpv-collage' );
		}

		if ( $is_using_grid || $is_using_collage || $is_using_masonry ) {
			$config = $this->get_config_with_grid();
		}

		if ( $is_using_grid || $is_using_masonry ) {
			parent::load_block_specific_style_attributes( $factory );
		}

		if ( $is_using_masonry ) {
			if ( ! isset( $config['masonry'] ) ) {
				$config['masonry'] = array(
					'desktop' => array(
						'cellSpacing' => 5,
						'numberOfColumns' => 3,
					),
				);
			}

			$devices = Devices::get_list_devices();
			foreach ( $devices as $device ) {
				$spacing = isset( $config['masonry'][ $device ] ) && isset( $config['masonry'][ $device ]['cellSpacing'] )
					? $config['masonry'][ $device ]['cellSpacing']
					: $config['masonry'][ Devices::DEVICE_DEFAULT ]['cellSpacing'];
				$style_gap = $factory->get_attribute( 'grid-column-gap', $spacing );
				if ( $style_gap ) {
					$this->add_style_attribute( $style_gap, self::KEY_STYLES_FOR_MASONRY_AREA, $device );
				}

				$padding = array(
					'enabled' => true,
					'paddingTop' => 0,
					'paddingRight' => 0,
					'paddingBottom' => $spacing,
					'paddingLeft' => 0,
				);
				$style_padding = $factory->get_attribute( 'padding', $padding );
				if ( $style_padding ) {
					$this->add_style_attribute( $style_padding, self::KEY_STYLES_FOR_MASONRY_AREA_CONTENT, $device );
				}

				$number_columns = isset( $config['masonry'][ $device ] ) && isset( $config['masonry'][ $device ]['numberOfColumns'] )
					? $config['masonry'][ $device ]['numberOfColumns']
					: $config['masonry'][ Devices::DEVICE_DEFAULT ]['numberOfColumns'];
				$grid_template_columns = array_fill( 0, $number_columns, 1 );
				$style_columns = $factory->get_attribute( 'grid-template-columns', $grid_template_columns );
				if ( $style_columns ) {
					$this->add_style_attribute( $style_columns, self::KEY_STYLES_FOR_MASONRY_AREA, $device );
				}
			}
		}


		if ( $is_using_collage && ! isset( $config['collage'] ) ) {
			$config['collage'] = array(
				'numberOfColumns' => 12,
				'numberOfRows' => 6,
				'desktop' => array(
					'advanced' => false,
					'settings' => array(),
					'height' => null,
					'cellSpacing' => 15,
				),
			);
		}

		$this->load_collage_style_attributes( $factory, $config );
	}

	/**
	 * Gets CSS Config
	 *
	 * @param string $device Device.
	 * @return array
	 */
	public function css_config( $device = null ) {
		$result = parent::css_config( $device );
		// modify grid output to attach styles not to the root selector, but to nested .tb-grid
		$result[ '.js-wpv-loop-wrapper > .tb-grid' ] = $result[ parent::CSS_SELECTOR_ROOT ];
		unset( $result[ parent::CSS_SELECTOR_ROOT ] );

		$result['.tb-masonry'] = array(
			self::KEY_STYLES_FOR_MASONRY_AREA => array(
				'grid-template-columns',
				'grid-column-gap',
			),
		);
		$result['.tb-masonry .tb-brick__content'] = array(
			self::KEY_STYLES_FOR_MASONRY_AREA_CONTENT => array(
				'padding',
			),
		);
		$result['.wpv-collage'] = array(
			self::KEY_STYLES_FOR_COLLAGE => array(
				'grid-column-gap',
				'grid-row-gap',
				'grid-auto-rows',
			),
		);

		if ( ! empty( $this->grid_areas ) ) {
			$areas = isset( $this->grid_areas[ $device ] ) ? $this->grid_areas[ $device ] : $this->grid_areas[ Devices::DEVICE_DEFAULT ];
			$number_of_cells = count( $areas );
			foreach ( $areas as $index => $area ) {
				$rule = '.wpv-collage > div:nth-child(' . $number_of_cells . 'n + ' . ( intval( $index ) + 1 ) . ')';
				$result[ $rule ] = array();
				$result[ $rule ][ self::KEY_STYLES_FOR_COLLAGE_GRID_AREA . $index ] = array(
					'grid-area',
				);
			}
		}

		return $result;
	}
}
