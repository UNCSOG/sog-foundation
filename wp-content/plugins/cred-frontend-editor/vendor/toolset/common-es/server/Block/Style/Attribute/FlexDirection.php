<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class FlexDirection extends AAttribute {
	/** @var String */
	private $direction;

	/**
	 * Constructor
	 *
	 * @param mixed $value
	 */
	public function __construct( $value ) {
		$valid           = [ 'row', 'row-reverse', 'column', 'column-reverse' ];
		$this->direction = in_array( $value, $valid ) ? $value : null;
	}

	/**
	 * Gets the name of the attribute
	 *
	 * @return string
	 */
	public function get_name() {
		return 'flex-direction';
	}

	/**
	 * Gets the style
	 *
	 * @return string
	 */
	public function get_css() {
		if ( ! is_string( $this->direction ) || empty( $this->direction ) ) {
			return '';
		}

		return $this->get_name() . ": $this->direction;";
	}
}
