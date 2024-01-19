<?php

namespace ToolsetCommonEs\Library\WordPress\Page;

class PostEdit {

	/**
	 * Returns true if the current page is the post edit page.
	 *
	 * @return bool
	 */
	public function is_open() {
		global $pagenow;

		return $pagenow === 'post.php';
	}

	/**
	 * Applies given css rules to <head> on post edit page.
	 *
	 * @param string $css_rules
	 * @param null|string $id
	 */
	public function apply_css_rules( $css_rules, $id = null ) {
		if( ! $this->is_open() ) {
			return;
		}

		// ID wanted.
		$id = is_string( $id ) ? ' id="' . $id . '"' : '';

		// Add $style to admin head.
		add_action( 'admin_head', function() use ( $css_rules, $id ) {
			echo '<style' . $id . '>' . $css_rules . '</style>';
		} );
	}
}
