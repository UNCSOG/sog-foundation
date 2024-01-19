<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * Hacks for shortcode [wpv-woo-list_attributes]
 *
 * Needs:
 * - Default value
 */
class ListAttributes extends AHack {
	public function has_default_content() {
		return true;
	}

	public function get_default_content() {
		return '<span class="tb-fields-and-text-shortcode-render-too-complex">' . __( 'The list of attributes may be displayed in the frontend', 'wpv-views' ) . '</span>';
	}
}
