<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Padding extends Margin {
	public function __construct( $settings ) {
		if( ! is_array( $settings ) || ! array_key_exists( 'enabled', $settings ) ) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' . print_r( $settings, true ) );
		}

		$this->enabled = $settings['enabled'] ? true : false;

		$this->top = $this->get_settings_number_or_null( $settings, 'paddingTop' );
		$this->right = $this->get_settings_number_or_null( $settings, 'paddingRight' );
		$this->bottom = $this->get_settings_number_or_null( $settings, 'paddingBottom' );
		$this->left = $this->get_settings_number_or_null( $settings, 'paddingLeft' );

		if( is_rtl() ) {
			// Flip right and left.
			$left = $this->left;
			$this->left = $this->right;
			$this->right = $left;
		}
	}

	public function get_name() {
		return 'padding';
	}
}
