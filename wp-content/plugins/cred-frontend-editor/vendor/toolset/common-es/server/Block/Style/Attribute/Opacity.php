<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Opacity extends AAttribute {
	private $opacity;

	public function __construct( $value  ) {
		$this->opacity = is_numeric( $value) ? intval( $value ) : null;
	}

	public function get_name() {
		return 'opacity';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->opacity ) || $this->opacity === 0 ) {
			return 'opacity: ' . $this->opacity / 100 . ';';
		}

		// no z-index defined
		return '';
	}
}
