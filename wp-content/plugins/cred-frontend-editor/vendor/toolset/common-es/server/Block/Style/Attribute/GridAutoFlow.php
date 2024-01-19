<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class GridAutoFlow extends AAttribute {
	private $grid_auto_flow;

	public function __construct( $value ) {
		$this->grid_auto_flow = $value;
	}

	public function get_name() {
		return 'grid-auto-flow';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if ( empty( $this->grid_auto_flow ) ) {
			return '';
		}

		return 'grid-auto-flow: ' . $this->grid_auto_flow;
	}
}
