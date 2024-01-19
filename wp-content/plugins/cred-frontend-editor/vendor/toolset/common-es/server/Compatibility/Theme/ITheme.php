<?php

namespace ToolsetCommonEs\Compatibility\Theme;

interface ITheme {
	/**
	 * Returns true if the theme is active.
	 *
	 * @return boolean
	 */
	public function is_active();
}
