<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class BoxShadow extends AAttribute {
	private $enabled = false;
	private $color_r;
	private $color_g;
	private $color_b;
	private $color_a;
	private $horizontal;
	private $vertical;
	private $blur;
	private $spread;

	public function __construct( $settings ) {
		if(
			! is_array( $settings ) ||
			! array_key_exists( 'enabled', $settings ) ||
			! array_key_exists( 'color', $settings ) ||
			! array_key_exists( 'horizontal', $settings ) ||
			! array_key_exists( 'vertical', $settings ) ||
			! array_key_exists( 'blur', $settings ) ||
			! array_key_exists( 'spread', $settings ) ||
			! is_array( $settings['color'] ) ||
			! array_key_exists( 'rgb', $settings['color'] ) ||
			! is_array( $settings['color']['rgb'] ) ||
			! array_key_exists( 'r', $settings['color']['rgb'] ) ||
			! array_key_exists( 'g', $settings['color']['rgb'] ) ||
			! array_key_exists( 'b', $settings['color']['rgb'] ) ||
			! array_key_exists( 'a', $settings['color']['rgb'] )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}

		$this->enabled = $settings['enabled'] ? true : false;
		$this->color_r = intval( $settings['color']['rgb']['r'] );
		$this->color_g = intval( $settings['color']['rgb']['g'] );
		$this->color_b = intval( $settings['color']['rgb']['b'] );
		$this->color_a = is_numeric( $settings['color']['rgb']['a'] ) ? $settings['color']['rgb']['a'] : 0;
		$this->horizontal = empty( $settings['horizontal'] ) ? 0 : intval( $settings['horizontal'] ) . 'px';
		$this->vertical = empty( $settings['vertical'] ) ? 0 : intval( $settings['vertical'] ) . 'px';
		$this->blur = empty( $settings['blur'] ) ? 0 : intval( $settings['blur'] ) . 'px';
		$this->spread = empty( $settings['spread'] ) ? 0 : intval( $settings['spread'] ) . 'px';
	}

	public function get_name() {
		return 'box-shadow';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! $this->enabled ) {
			return '';
		}

		return $this->get_name() . ": $this->horizontal $this->vertical $this->blur $this->spread " .
			   "rgba( $this->color_r, $this->color_g, $this->color_b, $this->color_a );";
	}
}
