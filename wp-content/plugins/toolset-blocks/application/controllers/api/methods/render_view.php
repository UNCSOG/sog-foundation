<?php

namespace OTGS\Toolset\Views\Controller\API\Methods;

/**
 * Handles the rendering of a View for the method offered by the Views API.
 */
class RenderViewHandler {
	/** @var \WP_Views_plugin */
	private $wp_views;

	/**
	 * RenderViewHandler constructor.
	 *
	 * @param \WP_Views_plugin $wp_views
	 */
	public function __construct( \WP_Views_plugin $wp_views ) {
		$this->wp_views = $wp_views;
	}

	/**
	 * Processes the API call that renders the specified View.
	 *
	 * @param array $args
	 * @param array $get_override
	 *
	 * @return string
	 */
	public function process_call( $args, $get_override ) {
		if ( did_action( 'init' ) === 0 ) {
			_doing_it_wrong(
				'render_view',
				__( 'Views API functions do not work before the init hook.', 'wpv-views' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'2.2.2'
			);
			return '';
		}

		$id  = $this->get_view_id( $args );
		$out = '';

		$status = get_post_status( $id );

		// Views must be published in order to produce any output
		if (
			intval( $id ) > 0 &&
			'publish' === $status
		) {
			if ( ! empty( $get_override ) ) {
				$post_old = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				foreach ( $get_override as $key => $value ) {
					$_GET[ $key ] = $value;
				}
			}
			$args['id'] = $id;
			array_push( $this->wp_views->view_shortcode_attributes, $args );
			if ( isset( $args['target_id'] ) ) {
				$out = $this->wp_views->short_tag_wpv_view_form( $args );
			} elseif ( get_post_meta( $id, '_wpv_is_gutenberg_view', true ) ) {
				$out = $this->render_block_view( $id );
			} else {
				$out = $this->wp_views->render_view_ex( $id, md5( serialize( $args ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			}
			$this->wp_views->view_used_ids[] = $id;
			array_pop( $this->wp_views->view_shortcode_attributes );
			if ( ! empty( $get_override ) ) {
				$_GET = $post_old;
			}
		}

		$out = apply_filters( 'wpv_filter_wpv_view_shortcode_output', $out, $id );

		return $out;
	}

	/**
	 * @param array $args
	 * @return int
	 */
	private function get_view_id( $args ) {
		if ( isset( $args['id'] ) ) {
			return intval( $args['id'] );
		}
		
		if ( isset( $args['name'] ) ) {
			$post = get_page_by_path( $args['name'], OBJECT, 'view' );
			if ( null === $post ) {
				return 0;
			}
			return $post->ID;
		}
		
		if ( isset( $args['title'] ) ) {
			$posts = get_posts(
				array(
					'post_type'              => 'view',
					'title'                  => $args['title'],
					'post_status'            => 'all',
					'numberposts'            => 1,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,			
					'orderby'                => 'post_date ID',
					'order'                  => 'ASC',
				)
			);
			if ( empty( $posts ) ) {
				return 0;
			}

			$post = $posts[0];
			return $post->ID;
		}
		
		return 0;
	}


	/**
	 * Renders the View for the case it is built using the View block.
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	private function render_block_view( $id ) {
		$view_data = get_post_meta( $id, '_wpv_view_data', true );
		$parent_post_id = toolset_getnest( $view_data, array( 'general', 'parent_post_id' ), 0 );

		// The View built with the block needs to be hosted in a post.
		if ( ! $parent_post_id ) {
			return '';
		}

		// Retrieving the content of the host post from which the View block will be extracted. To properly render a
		// View built with the block, both the block attributes and the Views data stored in the post meta are needed
		// because they both keep valuable information for the proper View rendering.
		$parent_post_content = get_the_content( null, null, $parent_post_id );

		preg_match_all(
			'/<!-- wp:toolset-views\/view-editor(.*?)<!-- \/wp:toolset-views\/view-editor -->/s',
			$parent_post_content,
			$view_blocks_in_parent_post
		);

		$specific_view_block = '';

		// Multiple View blocks might exist in the same page, so a specific one needs to be extracted. To find that specific
		// View block, it's ID is compared with the ID specified in the API method arguments.
		foreach ( $view_blocks_in_parent_post[0] as $view_block ) {
			$block_array = parse_blocks( $view_block );

			if ( (int) toolset_getnest( $block_array, array( '0', 'attrs', 'viewId' ) ) === (int) $id ) {
				$specific_view_block = $view_block;
				break;
			}
		}

		// For all the shortcode to be done and all the style to be built properly, the "the_content" filter needs to be applied
		// to the output of "do_blocks" for the fished View.
		return apply_filters( 'the_content', do_blocks( $specific_view_block ) );
	}
}
