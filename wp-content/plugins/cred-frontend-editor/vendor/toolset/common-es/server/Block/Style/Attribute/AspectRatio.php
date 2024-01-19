<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class AspectRatio extends AAttribute {
	private $setup;
	private $height;
	private $heightUnit;

	public function __construct( $settings ) {
		if( ! is_array( $settings ) ) {
			return;
		}

		if( array_key_exists( 'setup', $settings ) ) {
			$this->setup = $settings['setup'];
		}

		$this->height = array_key_exists( 'height', $settings ) ?
			(int) $settings['height'] :
			0;

		$this->heightUnit = array_key_exists( 'heightUnit', $settings ) ?
			$settings['heightUnit'] :
			'px';
	}

	public function get_name() {
		return 'aspectRatio';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! $this->setup ) {
			return '';
		}

		if( $this->setup === 'fixed-height' ) {
			return "height: $this->height$this->heightUnit;";
		}

		$xy = explode( ':', $this->setup );

		if( count( $xy ) === 2 ) {
			return "padding-top: calc(100%/$xy[0]*$xy[1]);";
		}

		return '';
	}
}
