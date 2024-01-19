<?php

namespace ToolsetCommonEs\Block\Style\Responsive\Devices\Themes;

interface ITheme {
	public function is_active();
	public function get_devices();
}
