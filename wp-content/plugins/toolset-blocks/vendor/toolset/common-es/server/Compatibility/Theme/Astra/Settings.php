<?php

namespace ToolsetCommonEs\Compatibility\Theme\Astra;

use ToolsetCommonEs\Compatibility\ISettings;
use ToolsetCommonEs\Library\WordPress\Option as WordpressOption;

class Settings implements ISettings {
	/**  @var WordpressOption */
	private $wp_option;

	/**
	 * Astra constructor.
	 *
	 * @param WordpressOption $wp_option
	 */
	public function __construct( WordpressOption $wp_option ) {
		$this->wp_option = $wp_option;
	}

	public function get_link_color() {
		return $this->get_setting( 'link-color' );
	}

	public function get_link_color_hover() {
		return $this->get_setting( 'link-h-color' );
	}

	public function get_text_color() {
		return $this->get_setting( 'text-color' );
	}

	public function get_headline_color() {
		return $this->get_setting( 'heading-base-color' );
	}

	public function get_primary_color() {
		return $this->get_setting( 'theme-color' );
	}

	public function get_secondary_color() {
		return null;
	}

	public function get_text_font_family() {
		return $this->get_setting( 'body-font-family' );
	}

	public function get_headline_font_family() {
		return $this->get_setting( 'headings-font-family' );
	}

	public function get_headline_h1_font_family() {
		return $this->get_setting( 'font-family-h1' );
	}

	public function get_headline_h2_font_family() {
		return $this->get_setting( 'font-family-h2' );
	}

	public function get_headline_h3_font_family() {
		return $this->get_setting( 'font-family-h3' );
	}

	public function get_headline_h4_font_family() {
		return $this->get_setting( 'font-family-h4' );
	}

	public function get_headline_h5_font_family() {
		return $this->get_setting( 'font-family-h5' );
	}

	public function get_headline_h6_font_family() {
		return $this->get_setting( 'font-family-h6' );
	}

	public function get_button_properties() {
		$style = '';
		// Padding
		$setting = $this->get_setting( 'theme-button-padding' );

		if(
			is_array( $setting ) &&
			array_key_exists( 'desktop', $setting ) &&
			array_key_exists( 'desktop-unit', $setting ) &&
			is_array( $setting[ 'desktop' ] )
		) {
			$padding = $setting['desktop'];
			$paddingUnit = $setting['desktop-unit'];

			if(
				array_key_exists( 'top', $padding ) &&
				array_key_exists( 'right', $padding ) &&
				array_key_exists( 'bottom', $padding ) &&
				array_key_exists( 'left', $padding )
			) {
				$style .= 'padding: ' .
						  $padding['top'] . $paddingUnit . ' ' .
						  $padding['right'] . $paddingUnit . ' ' .
						  $padding['bottom'] . $paddingUnit . ' ' .
						  $padding['left'] . $paddingUnit . ';';
			}
		}


		// BG Color Normal.
		// button-bg-color
		$setting = $this->get_setting( 'button-bg-color' );

		if( ! is_string( $setting ) || empty( $setting ) ) {
			// If there is no explicit button background color, the prime color is used.
			$setting = $this->get_primary_color();
		}

		if( is_string( $setting ) ) {
			$style .= 'background-color: ' . $setting . ';';

			// Text Color.
			if( function_exists( 'astra_get_foreground_color' ) ) {
				$text_color = astra_get_foreground_color( $setting );

				if( ! empty( $text_color ) && is_string( $text_color ) ) {
					$style .= 'color: '. $text_color . ';';
				}
			}
		}

		// BG Color Hover.
		/*
		$setting = $this->get_setting( 'button-bg-h-color' );

		if( ! is_string( $setting ) ) {
			// If there is no explicit hover color, the link hover color is used.
			$setting = $this->get_link_color_hover();
		}

		if( is_string( $setting ) ) {

		}
		*/

		return $style;

	}

	public function apply_custom_fonts() {
		if( ! class_exists( 'Astra_Fonts' ) || ! method_exists( 'Astra_Fonts', 'render_fonts' ) ) {
			// Method no longer exists.
			return;
		}

		$render_fonts = new \ReflectionMethod( 'Astra_Fonts', 'render_fonts');

		if( ! $render_fonts->isStatic() ) {
			// Method is no longer static.
			return;
		}

		foreach( $render_fonts->getParameters() as $parameter ) {
			if( ! $parameter->isOptional() ) {
				// Method has required parameters now.
				return;
			}
		}

		// All good. Load Astra fonts.
		Astra_Fonts::render_fonts();
	}


	public function get_setting( $key ) {
		if( ! defined( 'ASTRA_THEME_SETTINGS' ) ) {
			// Theme not active?!
			return null;
		}

		// Get theme settings from the WordPress options table.
		$theme_settings = $this->wp_option->get_option( ASTRA_THEME_SETTINGS );

		// Check if $key exists and is valid.
		if(
			! is_array( $theme_settings ) ||
			! array_key_exists( $key, $theme_settings )
		) {
			// No color found.
			return null;
		}

		return $theme_settings[ $key ];;
	}
}
