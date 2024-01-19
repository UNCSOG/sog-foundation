<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

/**
 * Represents the attribute for CSS bottom.
 */
class Bottom extends AAttribute {
	private $bottom;
	private $unit;

	/**
	 * Constructor
	 *
	 * @param string $bottom Value.
	 */
	public function __construct( $bottom ) {
		if ( is_array( $bottom ) && array_key_exists( 'value', $bottom ) ) {
			// New structure, which uses top: { value: 10, unit: 'px' }.
			$this->bottom = is_numeric( $bottom['value'] ) ? intval( $bottom['value'] ) : null;
			$this->unit = array_key_exists( 'unit', $bottom ) && $bottom['unit'] ? $bottom['unit'] : 'px';
		} else {
			// Legacy, just supporting value as variable (and using 'px' as one and only unit).
			$this->bottom = is_numeric( $bottom) ? intval( $bottom ) : null;
			$this->unit = 'px';
		}
	}

	public function get_name() {
		return 'bottom';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->bottom ) || $this->bottom === 0 ) {
			return 'bottom: ' . $this->bottom . $this->unit . ';';
		}

		// no bottom defined
		return '';
	}
}
