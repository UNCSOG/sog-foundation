<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * Hacks for shortcode [wpv-woo-onsale]
 *
 * Needs:
 * - Default value
 */
class Onsale extends AHack {
	public function has_default_content() {
		return true;
	}

	public function get_default_content() {
		return '<span class="onsale">' . __( 'Badge', 'wpv-views' ) . '</span>';
	}
}
