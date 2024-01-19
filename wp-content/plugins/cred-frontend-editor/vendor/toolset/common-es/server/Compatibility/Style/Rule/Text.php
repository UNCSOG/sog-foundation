<?php

namespace ToolsetCommonEs\Compatibility\Style\Rule;

use ToolsetCommonEs\Compatibility\ISettings;
use ToolsetCommonEs\Compatibility\IRule;

/**
 * Class Text
 *
 * Theoretically not needed if the theme follows the standards.
 *
 * @package ToolsetCommonEs\Compatibility\Style\Rule
 */
class Text implements IRule {
	public function get_as_string( ISettings $settings, $base_selector = '' ) {
		// No explicit selector for text. Use 'body' if no base_selector is provided.
		$selector = ! empty( $base_selector ) ? $base_selector : 'body';
		$properties = [];

		if ( $color = $settings->get_text_color() ) {
			$properties[] = 'color: ' . $color . ';';
		}

		if( $font_family = $settings->get_text_font_family() ) {
			$properties[] = 'font-family: ' . $font_family . ';';
		}

		if( empty( $properties ) ) {
			return '';
		}

		return $selector . '{ ' . implode( ' ', $properties ) . ' }';
	}
}
