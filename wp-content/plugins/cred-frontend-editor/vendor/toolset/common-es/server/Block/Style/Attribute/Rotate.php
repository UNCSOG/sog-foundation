<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Rotate extends AAttribute {
	private $rotate;

	public function __construct( $value  ) {
		$this->rotate = is_numeric( $value) ? intval( $value ) : null;
	}

	public function get_name() {
		return 'rotate';
	}

	public function is_transform() {
		return true;
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->rotate ) || $this->rotate === 0 ) {
			return 'rotate(' . $this->rotate . 'deg)';
		}

		// no rotate
		return '';
	}
}
