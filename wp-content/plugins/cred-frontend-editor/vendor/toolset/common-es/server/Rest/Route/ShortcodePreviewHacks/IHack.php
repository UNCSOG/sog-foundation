<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * Interface for Shortcode Preview Hacks
 */
interface IHack {
	/** Do neccesary hacks */
	public function do_hack();
	/** Restore previous status if some changes may affect other shortcodes */
	public function restore();
	/** Checks if the shortcode has a default content */
	public function has_default_content();
	/** Returns default content */
	public function get_default_content();
	/** Forces shortcode content output */
	public function maybe_force_content( $content );
}
