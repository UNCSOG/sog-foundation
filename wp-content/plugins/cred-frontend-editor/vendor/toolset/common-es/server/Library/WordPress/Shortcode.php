<?php

namespace ToolsetCommonEs\Library\WordPress;

/**
 * Class Shortcode
 *
 * No need to comment wp 1:1 aliases:
 * phpcs:disable Squiz.Commenting.FunctionComment.Missing
 */
class Shortcode {
	public function add_shortcode( $tag, $callback ) {
		add_shortcode( $tag, $callback );
	}

	public function do_shortcode( $content, $ignore_html = false ) {
		return do_shortcode( $content, $ignore_html );
	}
}
