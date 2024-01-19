<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class FontStyle extends AAttribute {
	private $font_style;

	public function __construct( $input ) {
		if( is_array( $input ) && ! empty( $input ) ) {
			// Remove all 'not-...' values which are needed for responsive devices to deselect parents value.
			$input = array_filter( $input, function( $value ) {
				return substr( $value, 0, 4 ) !== 'not-';
			} );

			if( empty( $input ) ) {
				// When the input is now empty (after it had value) it means all styles are deselected.
				$input = 'inherit';
			}
		}

		$this->font_style = is_array( $input ) ? implode( ' ', $input ) : $input;
	}

	public function get_name() {
		return 'font-style';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->font_style ) ) {
			return '';
		}

		return "font-style: $this->font_style;";
	}
}
