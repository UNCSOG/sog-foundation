<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class TextDecoration extends AAttribute {
	private $text_decoration;

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

		$this->text_decoration = is_array( $input ) ? implode( ' ', $input ) : $input;
	}

	public function get_name() {
		return 'text-decoration';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( empty( $this->text_decoration ) ) {
			return '';
		}

		return "text-decoration: $this->text_decoration;";
	}
}
