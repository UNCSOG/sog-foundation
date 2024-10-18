<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Left extends AAttribute {
	private $left;
	private $unit;

	public function __construct( $settings ) {
		if(
			! is_array( $settings ) ||
			(
				! array_key_exists( 'left', $settings ) &&
				! array_key_exists( 'value', $settings )
			)
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}
		// New structure, which uses { value: 10, unit: 'px' }.
		if( is_array( $settings ) && array_key_exists( 'value', $settings ) ) {
			$this->left = is_numeric( $settings['value'] ) ? intval( $settings['value'] ) : null;
			$this->unit = array_key_exists( 'unit', $settings ) && $settings['unit'] ? $settings['unit'] : 'px';

		// Old structure, which uses { left: 10, unit: 'px' }.
		} else {
			$this->left = is_numeric( $settings['left'] ) ? intval( $settings['left'] ) : null;
			$this->unit = array_key_exists( 'unit', $settings ) && $settings['unit'] == '%' ? '%' : 'px';
		}
	}

	public function get_name() {
		return 'left';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->left ) || $this->left === 0 ) {
			return 'left: ' . $this->left . $this->unit . ';';
		}
		// no width defined
		return '';
	}
}
