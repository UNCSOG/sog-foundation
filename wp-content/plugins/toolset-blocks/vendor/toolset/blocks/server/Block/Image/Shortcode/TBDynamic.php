<?php

namespace ToolsetBlocks\Block\Image\Shortcode;

class TBDynamic implements IShortcode {
	/**
	 * Shortcode Tag.
	 * @return string
	 */
	public function get_tag_name() {
		return 'tb-dynamic';
	}

	/**
	 * Attribute key for width.
	 * @return string
	 */
	public function get_width_attribute_key() {
		return 'width';
	}

	/**
	 * Attribute key for height.
	 * @return string
	 */
	public function get_height_attribute_key() {
		return 'height';
	}

	/**
	 * Attribute key for crop enabled or not.
	 * @return string
	 */
	public function get_crop_attribute_key() {
		return 'crop';
	}
}
