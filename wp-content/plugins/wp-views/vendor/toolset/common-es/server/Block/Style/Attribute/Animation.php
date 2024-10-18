<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

/**
 * Animation attribute
 */
class Animation extends AAttribute {
	/** @var String */
	private $animation;

	/**
	 * Constructor
	 *
	 * @param Array $settings An array with animation settings
	 */
	public function __construct( $settings ) {
		if (
			! is_array( $settings ) ||
			! array_key_exists( 'animation', $settings )
		) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' );
		}

		$this->animation = $settings['animation'];
	}

	/**
	 * Gets the name of the attribute
	 *
	 * @return string
	 */
	public function get_name() {
		return 'animation';
	}

	/**
	 * Gets the style
	 *
	 * @return string
	 */
	public function get_css() {
		return 'animation: ' . $this->animation . ';';
	}
}
