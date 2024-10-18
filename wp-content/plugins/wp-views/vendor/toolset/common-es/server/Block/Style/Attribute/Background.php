<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Background extends AAttribute {
	private $type;
	private $settings;

	public function __construct( $settings ) {
		if( ! is_array( $settings ) || ! array_key_exists( 'type', $settings ) ) {
			return;
		}

		$this->settings = $settings;
		$this->type = strtolower( $settings['type'] );
	}

	public function get_name() {
		return 'background';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		switch( $this->type ) {
			case 'solid':
				return $this->get_css_solid();
			case 'gradient':
				return $this->get_css_gradient();
			case 'image':
				return $this->get_css_image();
			default:
				return '';
		}
	}

	/**
	 * Solid Background.
	 *
	 * @return string
	 */
	private function get_css_solid() {
		$solid_rgba = $this->get_rgba_string_by_array(
			$this->get_deep_prop_of_settings( array( 'solid', 'color', 'rgb' ), false )
		);

		if( ! empty( $solid_rgba ) ) {
			return "background: $solid_rgba;";
		}

		// Backward compatibility. We had hex color in a first beta release.
		if( $solid_hex = $this->get_deep_prop_of_settings( array( 'solid', 'color', 'hex' ) ) ) {
			return "background: $solid_hex;";
		}

		// No Solid Background at all.
		return '';
	}

	/**
	 * Gradient Background.
	 *
	 * @return string
	 */
	private function get_css_gradient() {
		if( ! $type = $this->get_deep_prop_of_settings( array( 'gradient', 'type' ) ) ) {
			return '';
		}

		$style_type = $type === 'linear' ?
			'linear-gradient' :
			'radial-gradient';

		$style_values = array();

		if( $repeating = $this->get_deep_prop_of_settings( array( 'gradient', 'repeating') ) ) {
			$style_type = 'repeating-' . $style_type;
		}

		if ( $type === 'linear' && $angel = $this->get_deep_prop_of_settings( array( 'gradient', 'angle' ) ) ) {
			array_push( $style_values, $angel . 'deg' );
		}
		else if ( $type === 'radial' && $form = $this->get_deep_prop_of_settings( array( 'gradient', 'form' ) ) ) {
			if( $form !== 'ellipse' ) { // Ellipse is default, no need to apply that.
				array_push( $style_values, $form );
			}
		}

		$colors = $this->get_deep_prop_of_settings( array( 'gradient', 'colors' ) );
		$first_color_stop = 0;
		$colors_last_index = count( $colors ) - 1;

		foreach( $colors as $index => $color ) {
			if( ! array_key_exists( 'rgb', $color ) ) {
				continue;
			}

			$style_color = $this->get_rgba_string_by_array( $color['rgb'] );

			if( empty( $style_color ) ) {
				continue;
			}

			// When repeating is used, it needs to be applied to the last color.
			if ( $repeating && $colors_last_index === $index ) {
				$style_color .= ' ' . ( $repeating + $first_color_stop ) . '%';
			} else if( array_key_exists( 'stop', $color ) ) {
				$stop_with_repeating = $this->stop_position_relative_to_repeating_range( $color['stop'], $repeating );

				if( $index === 0 ) {
					$first_color_stop = $stop_with_repeating;
				}

				$style_color .= ' ' . $stop_with_repeating . '%';
			}

			array_push( $style_values, $style_color );
		}

		return 'background-image:' . $style_type . '( ' . implode( ',', $style_values ) . ' );';
	}

	/**
	 * Gradient Background - color position calculation.
	 *
	 * @param string|int $stop
	 * @param string|int $repeating
	 *
	 * @return int
	 */
	private function stop_position_relative_to_repeating_range( $stop, $repeating ) {
		if ( ! $repeating ) {
			return $stop;
		}

		$intStop = intval( $stop );
		$intRepeating = intval( $repeating );
		$stopRelativeToRepeating = intval( $intRepeating / 100 * $intStop );

		if ( $stopRelativeToRepeating > $intRepeating ) {
			return $intRepeating;
		}

		return $stopRelativeToRepeating;
	}

	/**
	 * Image Background.
	 *
	 * @return string
	 */
	private function get_css_image() {
		// Style Background collection
		$background_styles = array();

		// Image Overlay Color
		$image_has_overlay_color = false;
		$image_overlay_rgba = $this->get_rgba_string_by_array(
			$this->get_deep_prop_of_settings( array( 'image', 'overlayColor', 'rgb' ), false )
		);

		if( ! empty( $image_overlay_rgba ) ) {
			$image_has_overlay_color = true;
			$background_styles[] = 'linear-gradient(' . $image_overlay_rgba . ',' . $image_overlay_rgba . '), ';
		}

		// Image Background Color
		$image_background_rgba = $this->get_rgba_string_by_array(
			$this->get_deep_prop_of_settings( array( 'image', 'color', 'rgb' ), false )
		);

		$background_styles[] = $image_background_rgba;

		// Image URL
		$url = $this->get_deep_prop_of_settings( array( 'image', 'url' ), '' );

		$background_styles[] = "url('$url')";

		// Image Position
		if( $horizontal = $this->get_deep_prop_of_settings( array( 'image', 'horizontal', 'position' ) ) ){
			if( $horizontal === 'custom' ) {
				$value = $this->get_deep_prop_of_settings( array( 'image', 'horizontal', 'value' ) );
				$unit = $this->get_deep_prop_of_settings( array( 'image', 'horizontal', 'unit' ) );

				$horizontal = $value ? $value . $unit : 'center';
			}
		}

		$horizontal = $horizontal ? $horizontal : 'center';

		if( $this->get_deep_prop_of_settings( array( 'image', 'attachment' ) ) === 'fixed' ) {
			$vertical = 'top';
		} else if( $vertical = $this->get_deep_prop_of_settings( array( 'image', 'vertical', 'position' ) ) ) {
			if( $vertical === 'custom' ) {
				$value = $this->get_deep_prop_of_settings( array( 'image', 'vertical', 'value' ) );
				$unit = $this->get_deep_prop_of_settings( array( 'image', 'vertical', 'unit' ) );

				$vertical = $value ? $value . $unit : 'center';
			}
		}

		$vertical = $vertical ? $vertical : 'center';

		if ( $horizontal !== 'left' || $vertical !== 'top' ) {
			$background_styles[] = "$horizontal $vertical";
		}

		// Image Repeat
		if( $repeat = $this->get_deep_prop_of_settings( array( 'image', 'repeat' ), 'no-repeat' ) ){
			$background_styles[] = $repeat;
		}

		// Style 'background' string.
		$background_style = 'background:' . implode( ' ', $background_styles ) . ';';

		if( $this->get_deep_prop_of_settings( array( 'image', 'attachment' ) ) === 'fixed' ) {
			$background_style = $background_style . 'background-attachment: fixed;';
		}

		// Background Size
		if( ! $size = $this->get_deep_prop_of_settings( array( 'image', 'size' ), 'cover' ) ) {
			// No size, return "background" style only.
			return $background_style;
		}

		// Predefined sizes used ("cover", "contain"...).
		if( $size !== 'auto' && $size !== 'custom' ) {
			$size = $image_has_overlay_color ? 'auto, '. $size : $size;
			return $background_style . 'background-size:' . $size . ';';
		}

		// Custom Size.
		$width = $this->get_deep_prop_of_settings( array( 'image', 'width' ), 'auto' );
		$widthUnit = $this->get_deep_prop_of_settings( array( 'image', 'widthUnit' ), 'px' );
		$height = $this->get_deep_prop_of_settings( array( 'image', 'height' ), 'auto' );
		$heightUnit = $this->get_deep_prop_of_settings( array( 'image', 'heightUnit' ), 'px' );

		$styleWidth = $width !== 'auto' ?
			$width . $widthUnit :
			'auto';

		$styleHeight = $height !== 'auto' ?
			$height . $heightUnit :
			'auto';


		if( $styleWidth !== 'auto' || $styleHeight !== 'auto' ) {
			return $background_style . "background-size: $styleWidth $styleHeight;";
		}

		return $background_style;
	}

	/**
	 * @param string[] $route
	 *
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	private function get_deep_prop_of_settings( $route, $default = false ) {
		$settings = $this->settings;
		foreach( $route as $key ) {
			if( ! array_key_exists( $key, $settings ) ) {
				return $default;
			}

			$settings = $settings[ $key ];
		}

		return $settings;
	}
}
