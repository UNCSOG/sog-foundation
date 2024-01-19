<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Display extends AAttribute {
	private $display;

	public function __construct( $value ) {
		$valid         = [ 'block', 'flex', 'inline', 'inline-block', 'table', 'none' ];
		$this->display = in_array( $value, $valid ) ? $value : null;
	}

	public function get_name() {
		return 'display';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! is_string( $this->display ) || empty( $this->display ) ) {
			return '';
		}

		return $this->get_name() . ": $this->display;";
	}
}
