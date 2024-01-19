<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Width extends AAttribute {
	private $width;
	private $unit;

	public function __construct( $settings ) {
		if(
			! is_array( $settings ) ||
			! array_key_exists( 'width', $settings )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}

		if ( preg_match( '/calc\([^\)]+\)/', $settings['width'] ) ) {
			$this->width = $settings['width'];
			$this->unit = '';
		} else {
			$this->width = is_numeric( $settings['width'] ) ? intval( $settings['width'] ) : null;
			$this->unit = array_key_exists( 'widthUnit', $settings ) && $settings['widthUnit']
				? $settings['widthUnit']
				: 'px';
		}
	}

	public function get_name() {
		return 'width';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->width ) || $this->width === 0 ) {
			return $this->get_name() . ': ' . $this->width . $this->unit . ';';
		}
		// no width defined
		return '';
	}
}
