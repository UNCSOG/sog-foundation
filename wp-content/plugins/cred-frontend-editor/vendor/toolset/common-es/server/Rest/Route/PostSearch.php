<?php

namespace ToolsetCommonEs\Rest\Route;

use ToolsetCommonEs\Library\WordPress\User;
use ToolsetCommonEs\Utils\WpQueryFactory;

class PostSearch extends ARoute {
	protected $name = 'PostSearch';
	protected $version = 1;

	/** @var WpQueryFactory|null */
	private $wp_query_factory;

	public function __construct( User $wp_user, WpQueryFactory $wp_query_factory = null ) {
		parent::__construct( $wp_user );

		$this->wp_query_factory = $wp_query_factory ?: new WpQueryFactory();
	}

	public function callback( \WP_REST_Request $rest_request ) {
		$response = array();
		$empty_response = array(
			'id' => '',
			'name' => '',
		);
		$search = '';
		$posts_per_page = 20;
		$ignore_sticky_posts = 1;
		$post_status = 'publish';
		$params = $rest_request->get_params();

		if ( isset( $params['search'] ) ) {
			$search = sanitize_text_field( $params['search'] );
		}

		if ( isset( $params['posts_per_page'] ) ) {
			$posts_per_page = sanitize_text_field( $params['posts_per_page'] );
		}

		if ( isset( $params['ignore_sticky_posts'] ) ) {
			$ignore_sticky_posts = sanitize_text_field( $params['ignore_sticky_posts'] );
		}

		if ( isset( $params['post_status'] ) ) {
			$post_status = sanitize_text_field( $params['post_status'] );
		}

		$query_args = array(
			's' => $search,
			'post_status' => $post_status,
			'ignore_sticky_posts' => $ignore_sticky_posts,
			'posts_per_page' => $posts_per_page,
		);

		if ( isset( $params['post_type'] ) ) {
			$query_args['post_type'] = sanitize_text_field( $params['post_type'] );
		}

		$search_results = $this->wp_query_factory->create( $query_args );

		if ( $search_results->have_posts() ) {
			while ( $search_results->have_posts() ) {
				$search_results->the_post();
				$title = $this->tokenize_string( get_the_title(), 50 ); // Truncate the post title to 50 characters
				$id = get_the_ID();
				$response[] = array(
					'id' => $id,
					'name' => ( $title ? $title : __( '(no title)', 'wpv-views' ) ) . ' (#' . $id . ')',
				);
			}
		} else {
			$response[] = $empty_response;
		}

		wp_reset_postdata();

		return rest_ensure_response( $response );
	}

	public function get_method() {
		return 'GET';
	}

	public function permission_callback() {
		// @todo check for Toolset Access permissions
		return $this->wp_user->current_user_can( 'edit_posts' );
	}

	private function tokenize_string( $string, $char_count = 50 ) {
		return strtok( wordwrap( $string, $char_count, "...\n" ), "\n" );
	}
}
