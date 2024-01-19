<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class JustifyContent extends AAttribute {
	private $justify_content;

	public function __construct( $value ) {
		$valid                 = array( 'baseline', 'center', 'end', 'flex-end', 'flex-start', 'inherit', 'left', 'normal', 'right',
			'safe', 'space-around', 'space-between', 'space-evenly', 'start', 'stretch', 'unsafe', 'unset' );
		$this->justify_content = in_array( $value, $valid ) ? $value : null;
	}

	public function get_name() {
		return 'justify-content';
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
