<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

/**
 * Represents the attribute for CSS Grid grid-area.
 *
 * It may contain different values: https://developer.mozilla.org/en-US/docs/Web/CSS/grid-area#Syntax
 *
 * @since 1.11.0
 */
class GridArea extends AAttribute {
	private $value;

	/**
	 * Constructor
	 *
	 * @param string $value Value.
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	/**
	 * Gets the name of the property
	 *
	 * @return string
	 */
	public function get_name() {
		return 'grid-area';
	}

	/**
	 * Gets the CSS rule
	 *
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->value ) ) {
			return '';
		}

		return $this->get_name() . ": $this->value;";
	}
}
