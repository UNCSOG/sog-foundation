<?php
if ( ! class_exists( 'Toolset_Theme_Settings_Array_Utils', false ) ) {

	Class Toolset_Theme_Settings_Array_Utils {

		private $value = null;
		private $property = null;

		function __construct( $property = null, $value = null ) {
			$this->value = $value;
			$this->property = $property;
		}

		function filter_array( $element ) {
			if ( is_object( $element ) ) {

				if ( property_exists( $element, $this->property ) === false ) {
					return null;
				}

				return $element->{$this->property} === $this->value;
			} elseif ( is_array( $element ) ) {

				if ( isset( $element[ $this->property ] ) === false ) {
					return null;
				}

				return $element[ $this->property ] === $this->value;
			} else {

				throw new Exception( sprintf( "Element parameter should be an object or an array, %s given.", gettype( $element ) ) );
			}
		}

		public function remap_by_property( $data ) {
			return $data[ $this->property ];
		}

		function value_in_array( $array ) {
			if ( ! is_array( $array ) ) {
				return false;
			}

			return in_array( $this->value, array_values( $array ) );
		}

		function sort_string_ascendant( $a, $b ) {
			return strcmp( $a[ $this->property ], $b[ $this->property ] );
		}

	}

}