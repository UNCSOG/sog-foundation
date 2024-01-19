<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class LineHeight extends AAttribute {
	private $size;
	private $unit;

	public function __construct( $settings ) {
		// also allow single value
		if( ! is_array( $settings ) && ! is_object( $settings ) ) {
			$settings = array( 'size' => $settings );
		}

		if(
			! is_array( $settings ) ||
			! array_key_exists( 'size', $settings )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}

		$this->size = is_numeric( $settings['size'] ) ? $settings['size'] : null;
		$this->unit = array_key_exists( 'unit', $settings ) && $settings['unit'] ? $settings['unit'] : 'px';
	}

	public function get_name() {
		return 'line-height';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->size ) || $this->size === 0 ) {
			return 'line-height: ' . $this->size . $this->unit . ';';
		}
		// no size defined
		return '';
	}
}
