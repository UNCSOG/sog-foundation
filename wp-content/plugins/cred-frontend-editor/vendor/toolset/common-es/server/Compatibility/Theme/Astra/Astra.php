<?php

namespace ToolsetCommonEs\Compatibility\Theme\Astra;


use ToolsetCommonEs\Compatibility\Theme\ITheme;

class Astra implements ITheme {

	/**
	 * @return bool
	 */
	public function is_active() {
		return defined( 'ASTRA_THEME_VERSION' );
	}
}
