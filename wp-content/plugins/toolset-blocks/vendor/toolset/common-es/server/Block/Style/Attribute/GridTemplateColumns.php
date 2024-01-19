<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class GridTemplateColumns extends AAttribute {
	private $grid_template_columns;

	public function __construct( $value ) {
		$this->grid_template_columns = $value;
	}

	public function get_name() {
		return 'grid-template-columns';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->grid_template_columns ) ) {
			return '';
		}

		if( is_array( $this->grid_template_columns ) ) {
			return $this->get_css_for_grid_block();
		}

		// Equal Columns count.
		return 'grid-template-columns: ' . str_repeat( '1fr ', $this->grid_template_columns ) . ';';
	}

	private function get_css_for_grid_block() {
		$columns = implode(
			' ',
			array_map(
				function ( $v ) {
					return 'minmax(0, ' . $v . 'fr)'; // No plan why minmax( 0, ...) is used here.
				},
				$this->grid_template_columns
			)
		);

		return "grid-template-columns: $columns;";
	}
}
