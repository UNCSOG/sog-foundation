<?php

namespace ToolsetCommonEs\Compatibility\Style\Rule;

use ToolsetCommonEs\Compatibility\ISettings;
use ToolsetCommonEs\Compatibility\IRule;


/**
 * Class Heading
 *
 * Theoretically not needed if the theme follows the standards.
 *
 * @package ToolsetCommonEs\Compatibility\Style\Rule
 */
class Heading implements IRule {
	public function get_as_string( ISettings $settings, $base_selector = '' ) {

		$css = '';

		// All Headings.
		$properties = [];

		// Color for all Headings.
		if ( $color = $settings->get_headline_color() ) {
			$properties[] = 'color: ' . $color . ';';
		}

		// Font family for all Headings.
		if( $font_family = $settings->get_headline_font_family() ) {
			$properties[] = 'font-family: ' . $font_family . ';';
		}

		if( ! empty( $properties ) ) {
			$css .= $base_selector . 'h1,' .
					  $base_selector . 'h2,' .
					  $base_selector . 'h3,' .
					  $base_selector . 'h4,' .
					  $base_selector . 'h5,' .
					  $base_selector . 'h6 { ' . implode( ' ', $properties ) . '; }';
		}

		// Specific styles for H1, H2, H3, H4, H5, H6
		foreach( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] as $tag ) {
			$properties = [];

			// Todo: Color.

			// Font Family
			if( $font_family = call_user_func( [ $settings, 'get_headline_' . $tag . '_font_family'] ) ) {
				$properties[] = 'font-family: ' . $font_family . ';';
			}

			// Build Css Rule
			if( ! empty( $properties ) ) {
				$css .= $base_selector . $tag . ' { ' . implode( ' ', $properties ) . '; }';
			}
		}

		return $css;
	}
}
