<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class TextTransform extends AAttribute {
	private $text_transform;

	public function __construct( $value ) {
		$this->text_transform = $value;
	}

	public function get_name() {
		return 'text-transform';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->text_transform ) ) {
			return '';
		}

		return "text-transform: $this->text_transform;";
	}
}
