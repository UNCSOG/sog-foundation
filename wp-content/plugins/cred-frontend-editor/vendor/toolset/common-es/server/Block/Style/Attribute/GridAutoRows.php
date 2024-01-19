<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class GridAutoRows extends AAttribute {
	private $grid_auto_flow;

	/**
	 * Constructor
	 *
	 * @param string $value Value.
	 */
	public function __construct( $value ) {
		$this->grid_auto_flow = $value;
	}

	/**
	 * Gets the name of the attribute.
	 */
	public function get_name() {
		return 'grid-auto-rows';
	}

	/**
	 * Gets CSS
	 *
	 * @return string
	 */
	public function get_css() {
		if ( empty( $this->grid_auto_flow ) ) {
			return '';
		}

		return 'grid-auto-rows: ' . $this->grid_auto_flow . 'px';
	}
}
