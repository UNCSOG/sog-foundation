<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Color extends AAttribute {
	private $color;

	public function __construct( $value ) {
		$this->color = $this->get_rgba_string_by_array( $value );

		// Backward compatiblity. Check for old hex value.
		if( empty( $this->color ) ) {
			$this->color = $this->string_as_hex_color( $value );
		}
	}

	public function get_name() {
		return 'color';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->color ) ) {
			return '';
		}

		return "color: $this->color;";
	}
}
