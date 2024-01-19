<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class ZIndex extends AAttribute {
	private $zindex;

	public function __construct( $value  ) {
		$this->zindex = is_numeric( $value) ? intval( $value ) : null;
	}

	public function get_name() {
		return 'z-index';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->zindex ) || $this->zindex === 0 ) {
			return 'z-index: ' . $this->zindex . ';';
		}

		// no z-index defined
		return '';
	}
}
