<?php

namespace ToolsetCommonEs\Block\Style\Responsive\Devices\Themes;

use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Class Astra
 *
 * @package ToolsetCommonEs\Block\Style\Responsive\Devices\Presets
 */
class Astra implements ITheme {
	public function is_active() {
		return defined( 'ASTRA_THEME_VERSION' );
	}

	public function get_devices() {
		return [
			Devices::DEVICE_TABLET => [
				'theme' => 'Astra',
				'columnsPerRow' => 1
			],
			Devices::DEVICE_PHONE => [
				'theme' => 'Astra',
			]
		];
	}
}
