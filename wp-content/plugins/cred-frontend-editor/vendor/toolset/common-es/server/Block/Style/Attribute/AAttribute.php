<?php

namespace ToolsetCommonEs\Block\Style\Attribute;


abstract class AAttribute implements IAttribute {
	public function is_transform() {
		return false;
	}

	protected function zero_or_with_px( $value ) {
		$value = intval( $value );
		return $value > 0 ? $value . 'px' : 0;
	}

	protected function string_as_number_with_unit( $value, $default_unit = 'px' ) {
		$value = strtolower( str_replace( ' ', '', $value ) );
		if( preg_match( '#([\-0-9\.]{1,})(em|ex|%|px|cm|mm|in|pt|pc|ch|rem|vh|vw)?#', $value, $matches ) ) {
			$number = $matches[1];
			$unit = isset( $matches[2] ) ? $matches[2] : $default_unit;

			return $number.$unit;
		}

		return null;
	}

	/**
	 * Get RGBA string: rgba( 123, 123, 123, 0.5 );
	 * If the input is no rgba array an empty string will be returned.
	 *
	 * @param mixed[] $rgba
	 *
	 * @return string
	 */
	protected function get_rgba_string_by_array( $rgba ) {
		if(
			! is_array( $rgba ) ||
			! array_key_exists( 'r', $rgba ) ||
			! array_key_exists( 'g', $rgba ) ||
			! array_key_exists( 'b', $rgba )
		) {
			return '';
		}

		if ( ! array_key_exists( 'a', $rgba ) ) {
			$rgba['a'] = 1;
		}

		return 'rgba( ' . $rgba['r'] . ', ' . $rgba['g'] . ', ' . $rgba['b'] . ', ' . $rgba['a'] . ' )';
	}

	/**
	 * @param string $value
	 *
	 * @return string|null
	 *
	 * @deprecated Only used for backward compatibility. Nowadays we use rgba everywhere.
	 */
	protected function string_as_hex_color( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );
		$length = strlen( $value );

		if ( $length !== 4 && $length !== 7 ) {
			return null;
		}

		$value = strtolower( $value );

		return preg_match( '/#([a-f0-9]{3}){1,2}/', $value ) ? $value : null;
	}
}
