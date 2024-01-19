<?php

namespace ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks;

/**
 * Hacks for shortcode [wpv-control-post-relationship]
 *
 * Needs:
 * - Hack view id
 */
class ControlPostRelationship extends AHack {
	private $view_id;

	public function __construct( $view_id ) {
		$this->view_id = $view_id;
	}

	public function do_hack() {
		$this->hack_views();
	}

	/**
	 * Sets view_id
	 */
	private function hack_views() {
		do_action( 'wpv_action_wpv_set_current_view', $this->view_id );
	}
}
