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
class Grid extends Common {
	const KEY_STYLES_FOR_INNER = 'inner';

	protected $devices = array(
		Devices::DEVICE_DESKTOP,
		Devices::DEVICE_TABLET,
		Devices::DEVICE_PHONE,
	);

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'wp-block-toolset-blocks-grid', 'tb-grid' ] );
	}

	public function get_css( $config = array(), $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/grid.css' );
		$parent_css = parent::get_css( $this->css_config( $responsive_device ), $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	/**
	 * For some reason, the default columns for desktop are different for the
	 * natural Grid block and for the Views loop:
	 * - the natural grid block is supposed to pack 4 columns by default: see public_src\block\grid\attributes.js.
	 * - the View loop using a Grid layour packs 3 columns by default: see public_src\blocks\editor\index.js in Views.
	 *
	 * Since WordPress 6.4, setting that default value means that no value is saved.
	 * Because of this, we need to adjust defaults here based on the block generating the grid.
	 *
	 * @return float[]
	 */
	private function get_default_columns_for_desktop() {
		$block_name = $this->get_name();
		if ( 'toolset-views/view-editor' === $block_name ) {
			return [ 0.3333, 0.3333, 0.3333 ];
		}
		if ( 'toolset-views/wpa-editor' === $block_name ) {
			return [ 0.3333, 0.3333, 0.3333 ];
		}
		return [ 0.25, 0.25, 0.25, 0.25 ];
	}

	protected function get_config_with_grid() {
		$config = $this->get_block_config();
		$name = $this->get_name();
		// Let's set different columnsDesktop defaults:
		// - for normal grid the count is 4.
		// - for Views grid the count is 3.
		if ( ! isset( $config['columnsDesktop'] ) ) {
			$config['columnsDesktop'] = $this->get_default_columns_for_desktop();
		}
		if ( ! isset( $config['columnsTablet'] ) ) {
			if ( count( $config['columnsDesktop'] ) > 2 ) {
				// Same as in public_src\block\grid\attributes.js.
				$config['columnsTablet'] = [ 0.3333, 0.3333, 0.3333 ];
			} else {
				// Check public_src/block/grid/edit.js on allocateColumns.
				$config['columnsTablet'] = [ 0.5, 0.5 ];
			}
		}
		if ( ! isset( $config['columnsPhone'] ) ) {
			// Same as in public_src\block\grid\attributes.js.
			$config['columnsPhone'] = [ 1 ];
		}
		if ( ! isset( $config['rowsDesktop'] ) ) {
			// Same as in public_src\block\grid\attributes.js.
			$config['rowsDesktop'] = 1;
		}
		return $config;
	}

	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_config_with_grid();
		foreach ( $this->devices as $device ) {
			$uDevice = ucfirst( $device );
			if ( isset( $config[ 'columns' . $uDevice ] ) && $style = $factory->get_attribute( 'gridtemplatecolumns', $config[ 'columns' . $uDevice ] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_COMMON_STYLES, $device );
			}
			if ( isset( $config[ 'columnGap' . $uDevice ] ) && $style = $factory->get_attribute( 'gridcolumngap', $config[ 'columnGap' . $uDevice ] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_COMMON_STYLES, $device );
			}
			if ( isset( $config[ 'rowGap' . $uDevice ] ) && $style = $factory->get_attribute( 'gridrowgap', $config[ 'rowGap' . $uDevice ] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_COMMON_STYLES, $device );
			}
			$flow = 'row';
			if ( isset( $config[ 'reverseColumns' . $uDevice ] ) && count( $config[ 'columns' . $uDevice ] ) > 1 ) {
				$flow = $config[ 'reverseColumns' . $uDevice ] ? 'dense' : 'row';
			}
			if ( $style = $factory->get_attribute( 'grid-auto-flow', $flow ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_COMMON_STYLES, $device );
			}
			if ( isset( $config[ 'columns' . $uDevice ] ) ) {
				$reverse = isset( $config[ 'reverseColumns' . $uDevice ] ) ? $config[ 'reverseColumns' . $uDevice ] : false;
				$cnt = count( $config[ 'columns' . $uDevice ] );
				if ( 1 === $cnt ) {
					$style = $factory->get_attribute( 'grid-column', '1' );
					$this->add_style_attribute( $style, 'col' . $uDevice, $device );
					if ( $reverse ) {
						$delta = 0;
						$cols = count( $config['columnsDesktop'] );
						for ( $i = 0; $i < $cols * $config['rowsDesktop']; $i++ ) {
							$ind = $i;
							if ( $i >= $cols ) {
								$ind = $i % $cols;
							}
							if ( $ind === $cols ) {
								$ind = 0;
							}
							$style = $factory->get_attribute( 'order', $delta + $cols - $ind );
							if ( ( $i + 1 ) % $cols === 0 ) {
								$delta = $i + 1;
							}
							$this->add_style_attribute( $style, 'single_col' . $uDevice . ( $i + 1 ), $device );
						}
					}
				} else {
					for ( $i = 0; $i < $cnt; $i++ ) {
						$col = $reverse ? $cnt - $i : $i + 1;
						$style = $factory->get_attribute( 'grid-column', $col );
						$this->add_style_attribute( $style, 'col' . $uDevice . ( $i + 1 ), $device );
					}
				}
			}
		}
	}

	/**
	 * Gets CSS Config
	 *
	 * @param string $responsive_device Device.
	 * @return array
	 */
	public function css_config( $responsive_device = null ) {
		$config = $this->get_config_with_grid();

		$result = array(
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
					'grid-template-columns',
					'grid-column-gap',
					'grid-row-gap',
					'grid-auto-flow',
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
			' > .tb-grid-column:nth-of-type(1n+1)' => array(
				'colDesktop' => array(
					'grid-column',
				),
				'colTablet' => array(
					'grid-column',
				),
				'colPhone' => array(
					'grid-column',
				),
			),
		);

		foreach ( $this->devices as $device ) {
			$uDevice = ucfirst( $device );
			if ( isset( $config[ 'columns' . $uDevice ] ) ) {
				$cnt = count( $config[ 'columns' . $uDevice ] );
				if ( 1 === $cnt ) {
					// if we have only one column, we'll probably need to reorder it
					$cnt = count( $config['columnsDesktop'] ) * $config['rowsDesktop'];
					for ( $i = 0; $i < $cnt; $i++ ) {
						$result[ '> .tb-grid-column:nth-of-type(' . ( $i + 1 ) . ')' ] = array(
							'single_col' . $uDevice . ( $i + 1 ) => array(
								'order',
							),
						);
					}
					continue;
				}
				for ( $i = 0; $i < $cnt; $i++ ) {
					if ( ! array_key_exists( '> .tb-grid-column:nth-of-type(' . $cnt . 'n + ' . ( $i + 1 ) . ')', $result ) ) {
						$result[ '> .tb-grid-column:nth-of-type(' . $cnt . 'n + ' . ( $i + 1 ) . ')' ] = [];
					}

					$result[ '> .tb-grid-column:nth-of-type(' . $cnt . 'n + ' . ( $i + 1 ) . ')' ][ 'col' . $uDevice . ( $i + 1 ) ] = array(
						'grid-column',
					);
				}
			}
		}

		return $result;
	}
}
