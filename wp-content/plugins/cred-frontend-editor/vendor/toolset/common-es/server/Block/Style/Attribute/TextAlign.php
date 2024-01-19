<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class TextAlign extends AAttribute {
	private $align;

	public function __construct( $value ) {
		$valid = array( 'left', 'right', 'center', 'justify', 'initial', 'inherit' );
		$this->align = in_array( $value, $valid ) ? $value : null;
	}

	public function get_name() {
		return 'text-align';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->align ) ) {
			return '';
		}

		return "text-align: $this->align;";
	}
}
