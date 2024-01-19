<?php

namespace ToolsetCommonEs\Compatibility\Theme;

use ToolsetCommonEs\Compatibility\Theme\Astra\Astra;

class FactorySettings {
	/** @var Astra */
	private $astra;

	public function __construct(
		Astra $astra
	) {
		$this->astra = $astra;
	}

	public function get_as_string() {
		if( $this->astra->is_active() ) {
			return '\ToolsetCommonEs\Compatibility\Theme\Astra\Settings';
		}

	}
}
