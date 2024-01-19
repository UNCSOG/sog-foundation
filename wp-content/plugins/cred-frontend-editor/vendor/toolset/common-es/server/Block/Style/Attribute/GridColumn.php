<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class GridColumn extends AAttribute {
	private $grid_column;

	public function __construct( $value ) {
		$this->grid_column = $value;
	}

	public function get_name() {
		return 'grid-column';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if ( empty( $this->grid_column ) ) {
			return '';
		}

		return 'grid-column: ' . $this->grid_column;
	}
}
