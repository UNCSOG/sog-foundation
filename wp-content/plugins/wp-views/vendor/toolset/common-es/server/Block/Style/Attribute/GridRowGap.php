<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class GridRowGap extends AAttribute {
	private $grid_row_gap;

	public function __construct( $value ) {
		$this->grid_row_gap = $value;
	}

	public function get_name() {
		return 'grid-row-gap';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! is_array( $this->grid_row_gap ) ) {
			return $this->get_css_for_grid_block();
		}

		if( ! array_key_exists( 'value', $this->grid_row_gap ) || $this->grid_row_gap['value'] === null ) {
			return '';
		}

		$unit = array_key_exists( 'unit', $this->grid_row_gap ) ? $this->grid_row_gap['unit'] : 'px';
		$value = $this->grid_row_gap['value'] . $unit;
		return 'grid-row-gap:'.$value.';row-gap:'.$value.';';
	}

	private function get_css_for_grid_block() {
		if ( 0 !== $this->grid_row_gap && empty( $this->grid_row_gap ) ) {
			return '';
		}

		return 'grid-row-gap: ' . $this->grid_row_gap . 'px;';
	}
}
