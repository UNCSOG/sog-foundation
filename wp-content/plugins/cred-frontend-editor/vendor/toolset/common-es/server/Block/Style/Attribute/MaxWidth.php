<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

/**
 * Class MaxWidth
 *
 * Factory has a helper method get_attribute_max_width( $width, $unit).
 * Or see Width class for the expected config keys.
 *
 * @package ToolsetCommonEs\Block\Style\Attribute
 */
class MaxWidth extends Width {
	public function __construct( $settings ) {
		if( is_array( $settings ) && array_key_exists( 'value', $settings ) && array_key_exists( 'unit', $settings ) ) {
			// New structure of maxWidth setting.
			parent::__construct( [ 'width' => $settings['value'], 'widthUnit' => $settings['unit'] ] );
		} else {
			parent::__construct( $settings );
		}

	}
	public function get_name() {
		return 'max-width';
	}
}
