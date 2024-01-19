<?php

namespace ToolsetCommonEs\Library\WordPress;

class Blocks {
	/**
	 * @param int|string|\WP_Post|null $post Optional. Post content, post ID, or post object. Defaults to global $post.
	 *
	 * @return bool
	 */
	public function has_blocks( $post = null ) {
		return has_blocks( $post );
	}

	/**
	 * @param string $content Post content.
	 *
	 * @return \WP_Block_Parser_Block[]
	 */
	public function parse_blocks( $content ) {
		return parse_blocks( $content );
	}
}
