<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Content extends AAttribute {
	private $content;

	public function __construct( $value ) {
		$this->content = $value;
	}

	public function get_name() {
		return 'content';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->content ) ) {
			return '';
		}

		return "content: '$this->content';";
	}
}
