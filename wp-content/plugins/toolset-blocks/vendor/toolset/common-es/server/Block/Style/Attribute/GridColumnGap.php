<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class GridColumnGap extends AAttribute {
	private $grid_column_gap;

	public function __construct( $value ) {
		$this->grid_column_gap = $value;
	}

	public function get_name() {
		return 'grid-column-gap';
	}

	public function get_css() {
		if( ! is_array( $this->grid_column_gap ) ) {
			return $this->get_css_for_grid_block();
		}

		if( ! array_key_exists( 'value', $this->grid_column_gap ) || $this->grid_column_gap['value'] === null ) {
			return '';
		}

		$unit = array_key_exists( 'unit', $this->grid_column_gap ) ? $this->grid_column_gap['unit'] : 'px';
		$value = $this->grid_column_gap['value'] . $unit;
		return 'grid-column-gap:'.$value.';column-gap:'.$value.';';
	}

	private function get_css_for_grid_block() {
		if ( 0 !== $this->grid_column_gap && empty( $this->grid_column_gap ) ) {
			return '';
		}
		return 'grid-column-gap: ' . $this->grid_column_gap . 'px;';
	}
}
