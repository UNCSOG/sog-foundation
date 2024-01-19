<?php


namespace ToolsetCommonEs\Rest\Route;

use ToolsetCommonEs\Rest\Route\ShortcodeRender\WithMeta;
use ToolsetCommonEs\Library\WordPress\User;
use ToolsetCommonEs\Rest\Route\ShortcodePreviewHacks\Factory as ShortcodeHackFactory;

class ShortcodeRender extends ARoute {
	protected $name = 'ShortcodeRender';

	protected $version = 1;

	/** @var WithMeta  */
	protected $shortcode_render_with_meta;

	public function __construct( User $wp_user, WithMeta $shortcode_render_with_meta ) {
		parent::__construct( $wp_user );

		$this->shortcode_render_with_meta = $shortcode_render_with_meta;
	}

	public function callback( \WP_REST_Request $rest_request ) {
		$params = $rest_request->get_json_params();

		$result = [];

		// todo Inject ShortcodeHackFactory!
		$hackFactory = new ShortcodeHackFactory();
		foreach( $params as $cachehash => $param ) {
			$current_post_id = isset( $param['current_post_id'] ) ? $param['current_post_id'] : null;
			$hack = $hackFactory->get_hack( $current_post_id, $param['shortcode'] );
			$hack->do_hack();
			if( isset( $param[ 'with_meta'] ) && $param[ 'with_meta' ] ) {
				$shortcode_content = $this->shortcode_render_with_meta->get_response_data( $current_post_id, $param['shortcode'] );
				$shortcode_content = $hack->maybe_force_content( $shortcode_content );
				if ( ! $shortcode_content[ 'content' ] && $hack->has_default_content() ) {
					$shortcode_content[ 'content' ] = $hack->get_default_content();
				}
				$result[ $cachehash ] = $shortcode_content;
				$hack->restore();
				continue;
			}
			// todo use shortcode id if available instead of current_post_id
			$shortcode_content = $this->get_content( $current_post_id, $param['shortcode'] );
			$shortcode_content = $hack->maybe_force_content( $shortcode_content );
			$result[ $cachehash ] = ! $shortcode_content && $hack->has_default_content() ? $hack->get_default_content() : $shortcode_content;
			$hack->restore();
		}

		return $result;
	}

	protected function get_content( $post_id, $shortcode ) {
		global $post;
		// todo extract dependency
		$post = \WP_Post::get_instance( $post_id );

		$content = do_shortcode( $shortcode );

		if( strpos( $content, '[' ) !== false ) {
			$content = do_shortcode( $content );
		}

		return $content;
	}

	public function permission_callback() {
		// @todo check for Toolset Access permissions
		return $this->wp_user->current_user_can( 'edit_posts' );
	}
}
