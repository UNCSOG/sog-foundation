<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * Hacks for shortcode [wpv-woo-related_products]
 *
 * Needs:
 * - Default value
 */
class RelatedProducts extends AHack {
	public function maybe_force_content( $content ) {
		$new_content = '<span class="tb-fields-and-text-shortcode-render-too-complex tb-element--unclickable"><b>wpv-woo-related_products</b> ' . __( 'is too complex for a preview', 'wpv-views' ) . '</span>';
		if ( is_array( $content ) ) {
			$content['content'] = $new_content;
			return $content;
		}
		return $new_content;
	}
}
