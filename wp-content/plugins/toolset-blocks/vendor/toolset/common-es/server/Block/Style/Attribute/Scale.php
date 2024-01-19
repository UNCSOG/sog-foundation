<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Scale extends AAttribute {
	private $scale;

	public function __construct( $value  ) {
		$this->scale = is_numeric( $value) ? intval( $value ) : null;
	}

	public function get_name() {
		return 'scale';
	}

	public function is_transform() {
		return true;
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->scale ) || $this->scale === 0 ) {
			return 'scale(' . $this->scale / 100 . ')';
		}

		// no scale
		return '';
	}
}
