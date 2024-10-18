<?php
namespace ToolsetCommonEs\Compatibility;

interface ISettings {
	/**
	 * Returns the link color.
	 *
	 * @return string
	 */
	public function get_link_color();

	/**
	 * Returns the link hover color.
	 *
	 * @return string
	 */
	public function get_link_color_hover();

	/**
	 * Returns the text color.
	 *
	 * @return string
	 */
	public function get_text_color();

	/**
	 * Returns the text font family.
	 *
	 * @return string
	 */
	public function get_text_font_family();

	/**
	 * Returns the headline color.
	 *
	 * @return string
	 */
	public function get_headline_color();


	/**
	 * Returns the headline font family.
	 *
	 * @return string
	 */
	public function get_headline_font_family();
	public function get_headline_h1_font_family();
	public function get_headline_h2_font_family();
	public function get_headline_h3_font_family();
	public function get_headline_h4_font_family();
	public function get_headline_h5_font_family();
	public function get_headline_h6_font_family();

	/**
	 *
	 * @return mixed
	 */
	public function	get_button_properties();

	/**
	 * Apply custom fonts.
	 */
	public function apply_custom_fonts();

	/**
	 * Returns the primary color.
	 *
	 * If available use a more explicit method like get_link_color() or get_headline_color().
	 *
	 * @return string
	 */
	public function get_primary_color();

	/**
	 * Returns the secondary color.
	 *
	 * If available use a more explicit method like get_link_color() or get_headline_color().
	 *
	 * @return string|null
	 */
	public function get_secondary_color();

	/**
	 * Return a specific theme setting.
	 *
	 * If available use a more explicit method like get_link_color() or get_headline_color().
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get_setting( $key );
}

