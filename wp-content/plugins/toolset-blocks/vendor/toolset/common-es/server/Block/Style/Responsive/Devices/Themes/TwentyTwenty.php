<?php

namespace ToolsetCommonEs\Block\Style\Responsive\Devices\Themes;

use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Class TwentyTwenty
 *
 * @package ToolsetCommonEs\Block\Style\Responsive\Devices\Presets
 */
class TwentyTwenty implements ITheme {
	public function is_active() {
		return function_exists( 'twentytwenty_theme_support' );
	}

	/**
	 * maxWidth / defaultMaxWidth is commented, because we decided to use the WP Columns breakpoints.
	 *
	 * @return array
	 */
	public function get_devices() {
		return [
			Devices::DEVICE_TABLET => [
				'theme' => 'TwentyTwenty',
				// 'maxWidth' => 999,
				// 'defaultMaxWidth' => 999
			],
			Devices::DEVICE_PHONE => [
				'theme' => 'TwentyTwenty',
				// 'maxWidth' => 699,
				// 'defaultMaxWidth' => 699
			]
		];
	}
}
