<?php
namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener\Helper;

/**
 * Class PostActions
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener\Helper
 *
 * @codeCoverageIgnore Only aliases in this class.
 */
class PostActions {

	/**
	 * @param $post
	 *
	 * @return array|\WP_Post|null
	 */
	public function get_post( $post ) {
		return get_post( $post );
	}

	/**
	 * @param $post_id
	 *
	 * @return int
	 */
	public function has_wpv_content_template( $post_id ) {
		return has_wpv_content_template( $post_id );
	}


	/**
	 * @param $content
	 *
	 * @return array[]
	 */
	public function parse_blocks( $content ) {
		return parse_blocks( $content );
	}
}
