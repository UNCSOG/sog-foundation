<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class FontFamily extends AAttribute {
	private $font_family;

	public function __construct( $value ) {
		$this->font_family = $value;
	}

	public function get_name() {
		return 'font-family';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->font_family ) ) {
			return '';
		}

		return "font-family: $this->font_family;";
	}
}
