<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class FontWeight extends AAttribute {
	private $font_weight;

	public function __construct( $value ) {
		$this->font_weight = $value;
	}

	public function get_name() {
		return 'font-weight';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->font_weight ) ) {
			return '';
		}

		return "font-weight: $this->font_weight;";
	}
}
