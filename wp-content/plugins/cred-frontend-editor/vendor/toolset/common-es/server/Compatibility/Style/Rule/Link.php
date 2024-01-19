<?php

namespace ToolsetCommonEs\Compatibility\Style\Rule;

use ToolsetCommonEs\Compatibility\ISettings;
use ToolsetCommonEs\Compatibility\IRule;

/**
 * Class Link
 *
 * Load link colors. Currently only normal and hover state.
 *
 * @package ToolsetCommonEs\Compatibility\Style\Rule
 */
class Link implements IRule {
	public function get_as_string( ISettings $settings, $base_selector = '' ) {
		// Collection of rules. Base & Hover.
		$rules = '';

		// Base if $colors provides a link color.
		if ( $color = $settings->get_link_color() ) {
			$rules .= $base_selector . 'a { color: ' . $color . '; }';
		}

		// Hover if $colors provides a hover color.
		if ( $color = $settings->get_link_color_hover() ) {
			$rules .= $base_selector . 'a:hover { color: ' . $color . '; }';
		}

		return $rules;
	}
}
