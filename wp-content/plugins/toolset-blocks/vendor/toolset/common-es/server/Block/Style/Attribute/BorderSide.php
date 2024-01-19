<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class BorderSide extends AAttribute {
	private $side;
	private $style;
	private $width;
	private $unit;
	private $color_r;
	private $color_g;
	private $color_b;
	private $color_a;

	public function __construct( $side, $settings ) {
		if( ! in_array( $side, array( 'top', 'left', 'bottom', 'right' ) ) ) {
			throw new \InvalidArgumentException( 'Border side must one of these values: top, right, bottom, left.' );
		}

		$this->side = $side;

		if(
			! is_array( $settings ) ||
			! array_key_exists( 'style', $settings ) ||
			! array_key_exists( 'width', $settings ) ||
			! array_key_exists( 'widthUnit', $settings ) ||
			! array_key_exists( 'color', $settings ) ||
			! is_array( $settings['color'] ) ||
			! array_key_exists( 'rgb', $settings['color'] ) ||
			! is_array( $settings['color']['rgb'] ) ||
			! array_key_exists( 'r', $settings['color']['rgb'] ) ||
			! array_key_exists( 'g', $settings['color']['rgb'] ) ||
			! array_key_exists( 'b', $settings['color']['rgb'] ) ||
			! array_key_exists( 'a', $settings['color']['rgb'] )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' . print_r( $settings, true ) );
		}


		$this->style = $this->get_valid_style( $settings['style'] );
		$this->width = is_numeric( $settings['width'] ) ? intval( $settings['width'] ) : null;
		$this->unit = $this->get_valid_unit( $settings['widthUnit'] );

		$this->color_r = intval( $settings['color']['rgb']['r'] );
		$this->color_g = intval( $settings['color']['rgb']['g'] );
		$this->color_b = intval( $settings['color']['rgb']['b'] );
		$this->color_a = is_numeric( $settings['color']['rgb']['a'] ) ? $settings['color']['rgb']['a'] : 0;
	}

	public function get_name(){
		$side_adjustment = ! is_rtl() ?
			[ 'left' => 'left', 'right' => 'right', 'top' => 'top', 'bottom' => 'bottom' ] :
			[ 'left' => 'right', 'right' => 'left', 'top' => 'top', 'bottom' => 'bottom' ];

		return 'border-' . $side_adjustment[ $this->side ];
	}

	public function is_active() {
		return ! empty( $this->width ) || $this->width === 0;
	}

	private function get_valid_style( $style ) {
		$style = strtolower( $style );
		$valids = array( 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset' );

		return in_array( $style, $valids ) ? $style : 'solid';
	}

	private function get_valid_unit( $unit ) {
		$unit = strtolower( $unit );
		$valids = array( 'px', 'em', 'rem' );

		return in_array( $unit, $valids ) ? $unit : 'px';
	}

	/**
	 * @param bool $only_value
	 *
	 * @return string
	 *
	 */
	public function get_css( $only_value = false ) {
		if( ! $this->is_active() ) {
			return '';
		}

		$value = $this->width . $this->unit . ' ' . $this->style . ' ' .
		         "rgba( $this->color_r, $this->color_g, $this->color_b, $this->color_a )";

		return $only_value ?
			$value :
			$this->get_name() . ': ' . $value . ';';
	}
}
