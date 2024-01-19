<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Height extends AAttribute {
	private $height;
	private $unit;

	public function __construct( $settings ) {
		if(
			! is_array( $settings ) ||
			! array_key_exists( 'height', $settings )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}

		$this->height = is_numeric( $settings['height'] ) ? intval( $settings['height'] ) : null;
		$this->unit = array_key_exists( 'heightUnit', $settings ) && $settings['heightUnit']
			? $settings['heightUnit']
			: 'px';
	}

	public function get_name() {
		return 'height';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->height ) || $this->height === 0 ) {
			return 'height: ' . $this->height . $this->unit . ';';
		}

		// no height defined
		return '';
	}
}
