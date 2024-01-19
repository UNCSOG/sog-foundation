<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class BorderRadius extends AAttribute {
	private $top_left;
	private $top_right;
	private $bottom_left;
	private $bottom_right;

	public function __construct( $settings ) {
		if(
			! is_array( $settings ) ||
			! array_key_exists( 'topLeft', $settings ) ||
			! array_key_exists( 'topRight', $settings ) ||
			! array_key_exists( 'bottomLeft', $settings ) ||
			! array_key_exists( 'bottomRight', $settings )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}

		$this->top_left = is_numeric( $settings['topLeft'] ) ? intval( $settings['topLeft'] ) : null;
		$this->top_right = is_numeric( $settings['topRight'] ) ? intval( $settings['topRight'] ) : null;
		$this->bottom_left = is_numeric( $settings['bottomLeft'] ) ? intval( $settings['bottomLeft'] ) : null;
		$this->bottom_right = is_numeric( $settings['bottomRight'] ) ? intval( $settings['bottomRight'] ) : null;

		if( is_rtl() ) {
			// Flip right and left.
			$top_left = $this->top_left;
			$this->top_left = $this->top_right;
			$this->top_right = $top_left;

			$bottom_left = $this->bottom_left;
			$this->bottom_left = $this->bottom_right;
			$this->bottom_right = $bottom_left;
		}
	}

	public function get_name() {
		return 'border-radius';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( $this->top_right === null &&
			$this->top_left === null &&
			$this->bottom_left === null &&
			$this->bottom_right === null
		) {
			// no border radius
			return '';
		}

		if( $this->top_right === $this->top_left &&
			$this->top_left === $this->bottom_right &&
			$this->bottom_right === $this->bottom_left
		) {
			// all corners have the same value
			return 'border-radius: ' . $this->zero_or_with_px( $this->top_left ). ';';
		}

		if( $this->top_right !== null &&
			$this->top_left !== null &&
			$this->bottom_left !== null &&
			$this->bottom_right !== null
		) {
			// all corners are set, but different
			return 'border-radius: ' . $this->zero_or_with_px( $this->top_left )
				. ' ' . $this->zero_or_with_px( $this->top_right )
				. ' ' . $this->zero_or_with_px( $this->bottom_right )
				. ' ' . $this->zero_or_with_px( $this->bottom_left )
				. ';';
		}

		// each corner is different and not all are set, check one by one.
		$individual_styles = '';

		if( $this->top_left !== null ) {
			$individual_styles .= 'border-top-left-radius: ' . $this->zero_or_with_px( $this->top_left ) . ';';
		}
		if( $this->top_right !== null ) {
			$individual_styles .= 'border-top-right-radius: ' . $this->zero_or_with_px( $this->top_right ) . ';';
		}
		if( $this->bottom_right !== null ) {
			$individual_styles .= 'border-bottom-right-radius: ' . $this->zero_or_with_px( $this->bottom_right ) . ';';
		}
		if( $this->bottom_left !== null ) {
			$individual_styles .= 'border-bottom-left-radius: ' . $this->zero_or_with_px( $this->bottom_left ) . ';';
		}

		return $individual_styles;
	}
}
