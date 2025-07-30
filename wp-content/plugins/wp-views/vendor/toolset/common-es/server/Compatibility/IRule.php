<?php
namespace ToolsetCommonEs\Compatibility;

interface IRule {

	/**
	 * Returns the rule as a usual css string.
	 *
	 * @param ISettings $settings
	 *
	 * @param string $base_selector
	 *
	 * @return string
	 */
	public function get_as_string( ISettings $settings, $base_selector = '' );

}
