<?php
namespace ToolsetCommonEs\Compatibility;

interface ILocation {

	/**
	 * Returns true if the location is currently open.
	 *
	 * @return boolean
	 */
	public function is_open();

	/**
	 * Apply given css rules to location.
	 *
	 * @param string $css_rules
	 * @param string $id
	 *
	 * @return void
	 */
	public function apply_css_rules( $css_rules, $id = null );


	/**
	 * Returns the base css selector of the location.
	 *
	 * @return string
	 */
	public function get_css_selector();
}
