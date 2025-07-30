<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * Abstract class for Shortcode Preview Hacks
 */
abstract class AHack implements IHack {
	/** Do neccesary hacks */
	public function do_hack() {}

	/** Restore previous status if some changes may affect other shortcodes */
	public function restore() {}

	/** Checks if the shortcode has a default content */
	public function has_default_content() {
		return false;
	}

	/** Returns default content */
	public function get_default_content() {
		return '';
	}

	/** Forces shortcode content output */
	public function maybe_force_content( $content ) {
		return $content;
	}
}
