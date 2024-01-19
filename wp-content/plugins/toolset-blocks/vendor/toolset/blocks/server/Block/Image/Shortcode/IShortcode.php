<?php

namespace ToolsetBlocks\Block\Image\Shortcode;

/**
 * Interface IShortcode
 * @package ToolsetBlocks\Block\Image\Shortcode
 */
interface IShortcode {
	/**
	 * Shortcode Tag.
	 * @return string
	 */
	public function get_tag_name();

	/**
	 * Attribute key for width.
	 * @return string
	 */
	public function get_width_attribute_key();

	/**
	 * Attribute key for height.
	 * @return string
	 */
	public function get_height_attribute_key();

	/**
	 * Attribute key for crop enabled or not.
	 * @return string
	 */
	public function get_crop_attribute_key();
}
