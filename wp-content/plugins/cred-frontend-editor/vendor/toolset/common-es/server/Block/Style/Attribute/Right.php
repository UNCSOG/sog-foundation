<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Right extends AAttribute {
	private $right;
	private $unit;

	public function __construct( $settings ) {
		if(
			! is_array( $settings ) ||
			(
				! array_key_exists( 'right', $settings ) &&
				! array_key_exists( 'value', $settings )
			)
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}
		// New structure, which uses { value: 10, unit: 'px' }.
		if( is_array( $settings ) && array_key_exists( 'value', $settings ) ) {
			$this->right = is_numeric( $settings['value'] ) ? intval( $settings['value'] ) : null;
			$this->unit  = array_key_exists( 'unit', $settings ) && $settings['unit'] ? $settings['unit'] : 'px';

		// Old structure, which uses { right: 10, unit: 'px' }.
		} else {
			$this->right = is_numeric( $settings['right'] ) ? intval( $settings['right'] ) : null;
			$this->unit  = array_key_exists( 'unit', $settings ) && $settings['unit'] == '%' ? '%' : 'px';
		}
	}

	public function get_name() {
		return 'right';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->right ) || $this->right === 0 ) {
			return 'right: ' . $this->right . $this->unit . ';';
		}
		// no width defined
		return '';
	}
}
