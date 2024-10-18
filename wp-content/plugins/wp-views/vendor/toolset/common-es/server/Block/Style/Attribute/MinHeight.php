<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

/**
 * Class MinHeight
 * @package ToolsetCommonEs\Block\Style\Attribute
 */
class MinHeight extends AAttribute {
	private $min_height;
	private $unit;

	public function __construct( $settings ) {
		if(
			! is_array( $settings ) ||
			! array_key_exists( 'minHeight', $settings )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}

		$this->min_height = is_numeric( $settings['minHeight'] ) ? intval( $settings['minHeight'] ) : null;
		$this->unit = array_key_exists( 'minHeightUnit', $settings ) && $settings['minHeightUnit']
			? $settings['minHeightUnit']
			: 'px';
	}

	public function get_name() {
		return 'min-height';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! empty( $this->min_height ) || $this->min_height === 0 ) {
			return $this->get_name() . ': ' . $this->min_height . $this->unit . ';';
		}
		// no min-height defined
		return '';
	}
}
