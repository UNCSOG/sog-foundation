<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

/**
 * Float attribute, it is named FloatCSS because Float is a reserved word
 */
class FloatCSS extends AAttribute {
	const LEFT = 'left';
	const RIGHT = 'right';
	private $float;

	public function __construct( $settings ) {
		if(
			! is_array( $settings ) ||
			! array_key_exists( 'float', $settings ) ||
			! in_array( $settings['float'], array( self::LEFT, self::RIGHT ) )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}

		$this->float = $settings['float'];
	}

	public function get_name() {
		return 'float';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		return 'float: ' . $this->float . ';';
	}
}
