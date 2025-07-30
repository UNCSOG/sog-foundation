<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Margin extends AAttribute {
	protected $enabled;
	protected $top;
	protected $right;
	protected $bottom;
	protected $left;

	public function __construct( $settings ) {
		if( ! is_array( $settings ) || ! array_key_exists( 'enabled', $settings ) ) {
			throw new \InvalidArgumentException( 'Invalid attribtue array.' . print_r( $settings, true ) );
		}

		$this->enabled = $settings['enabled'] ? true : false;
		$this->top = $this->get_settings_number_or_null( $settings, 'marginTop' );
		$this->right = $this->get_settings_number_or_null( $settings, 'marginRight' );
		$this->bottom = $this->get_settings_number_or_null( $settings, 'marginBottom' );
		$this->left = $this->get_settings_number_or_null( $settings, 'marginLeft' );


		if( is_rtl() ) {
			// Flip right and left.
			$left = $this->left;
			$this->left = $this->right;
			$this->right = $left;
		}
	}

	public function get_name() {
		return 'margin';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if( ! $this->enabled ) {
			return '';
		}

		if( $this->top === null &&
			$this->right === null &&
			$this->bottom === null &&
			$this->left === null
		) {
			// no margin
			return '';
		}

		if( $this->top === $this->right &&
			$this->right === $this->left &&
			$this->left === $this->bottom
		) {
			// all sides have the same value
			return $this->get_name() . ': ' . $this->top. ';';
		}

		if( $this->top !== null &&
			$this->right !== null &&
			$this->bottom !== null &&
			$this->left !== null
		) {
			// all corners are set, but different
			return $this->get_name() . ': ' . $this->top
				. ' ' . $this->right
				. ' ' . $this->bottom
				. ' ' . $this->left
				. ';';
		}

		// each side is different and not all are set, check one by one.
		$individual_styles = '';

		if( $this->top !== null ) {
			$individual_styles .= $this->get_name() . '-top: ' . $this->top . ';';
		}
		if( $this->right !== null ) {
			$individual_styles .= $this->get_name() . '-right: ' . $this->right . ';';
		}
		if( $this->bottom !== null ) {
			$individual_styles .= $this->get_name() . '-bottom: ' . $this->bottom . ';';
		}
		if( $this->left !== null ) {
			$individual_styles .= $this->get_name() . '-left: ' . $this->left . ';';
		}

		return $individual_styles;
	}

	/**
	 * Helper function to get the $settings[$key] number.
	 * Returns null if it's not set or if it has an empty value, except for 0.
	 *
	 * @param array $settings
	 * @param string $key
	 *
	 * @return int|null
	 */
	protected function get_settings_number_or_null( $settings, $key ) {
		if( ! array_key_exists( $key, $settings ) ) {
			// No user setting at all.
			return null;
		}

		// Important to use == and not === as it can be int or string.
		if( $settings[$key] == 0 && $settings[$key] !== null && $settings[$key] !== '' ) {
			// 0 does not require a unit.
			return 0;
		}

		if( empty( $settings[$key] ) ) {
			// Not 0 and any other "empty" case should be null.
			return null;
		}

		// Return number with unit.
		return $this->string_as_number_with_unit( $settings[ $key ] );
	}
}
