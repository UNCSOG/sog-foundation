<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class AlignItems extends AAttribute {
	private $justify_content;

	public function __construct( $value ) {
		$valid = array( 'baseline', 'center', 'end', 'flex-end', 'flex-start', 'inherit', 'initial', 'left', 'normal',
			'right', 'safe', 'self-end', 'self-start', 'start', 'stretch', 'unsafe', 'unset' );
		$this->justify_content = in_array( $value, $valid ) ? $value : null;
	}

	public function get_name() {
		return 'align-items';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->justify_content ) ) {
			return '';
		}

		return $this->get_name() . ": $this->justify_content;";
	}
}
