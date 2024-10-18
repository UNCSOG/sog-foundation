<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Handles AJAX calls to get the view block preview.
 *
 * @since m2m
 */
class WPV_Ajax_Handler_Get_View_Block_Preview extends Toolset_Ajax_Handler_Abstract {

	/** @var Toolset_Constants|null */
	private $constants;

	/** @var Toolset_Renderer|null */
	private $toolset_renderer;

	/** @var WP_Views_plugin */
	private $wp_views;

	/** @var Toolset_Output_Template_Repository */
	private $template_repository;

	/** @var callable|null */
	private $wpv_view_instance;

	/**
	 * WPV_Ajax_Handler_Get_View_Block_Preview constructor.
	 *
	 * @param \WPV_ajax                            $ajax_manager
	 * @param \Toolset_Constants                   $constants
	 * @param \Toolset_Renderer                    $toolset_renderer
	 * @param \Toolset_Output_Template_Repository  $template_repository
	 * @param \WP_Views_plugin                     $wp_views
	 * @param callable                             $wpv_view_instance
	 */
	public function __construct(
		\WPV_ajax $ajax_manager,
		\Toolset_Constants $constants = null,
		\Toolset_Renderer $toolset_renderer = null,
		\Toolset_Output_Template_Repository $template_repository = null,
		\WP_Views_plugin $wp_views = null,
		callable $wpv_view_instance = null
	) {
		parent::__construct( $ajax_manager );

		$this->constants = $constants ?: new \Toolset_Constants();
		$this->toolset_renderer = $toolset_renderer ?: new \Toolset_Renderer();
		$this->template_repository = $template_repository ?: new \Toolset_Output_Template_Repository();
		$this->wp_views = $wp_views ?: new \WP_Views_plugin();
		$this->wpv_view_instance = $wpv_view_instance ?: array( \WPV_View::class, 'get_instance' );
	}

	/**
	 * Processes the AJAX call.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => \WPV_Ajax::CALLBACK_GET_VIEW_BLOCK_PREVIEW,
				'is_public' => true,
			)
		);

		$view_id = isset( $_POST['view_id'] ) ? sanitize_text_field( $_POST['view_id'] ) : '';

		if ( empty( $view_id ) ) {
			$this->ajax_finish( array( 'message' => __( 'View ID not set.', 'wpv-views' ) ), false );
			return;
		}

		$view = call_user_func( $this->wpv_view_instance, $view_id );
		// check if view slug match
		if (
			! empty( $_POST['post_name'] )
			&& ( null === $view || $view->post_name !== $_POST['post_name'] )
		) {
			$posts = get_posts( array(
				'name' => $_POST['post_name'],
				'post_type' => 'view',
				'post_status' => 'publish',
				'numberposts' => 1,
			) );
			if ( count( $posts ) > 0 ) {
				$view = call_user_func( $this->wpv_view_instance, $posts[0]->ID );
				$view_id = $posts[0]->ID;
			}
		}
		if ( null !== $view ) {
			$view_slug = $view->slug;
			$override_limit = 'true' === sanitize_text_field( toolset_getpost( 'overrideLimit', 'false' ) );
			$override_offset = 'true' === sanitize_text_field( toolset_getpost( 'overrideOffset', 'false' ) );
			$override_orderby = 'true' === sanitize_text_field( toolset_getpost( 'overrideOrderby', 'false' ) );
			$override_order = 'true' === sanitize_text_field( toolset_getpost( 'overrideOrder', 'false' ) );
			$override_secondary_orderby = 'true' === sanitize_text_field( toolset_getpost( 'overrideSecondaryOrderby', 'false' ) );
			$override_secondary_order = 'true' === sanitize_text_field( toolset_getpost( 'overrideSecondaryOrder', 'false' ) );

			$has_custom_search = $this->wp_views->does_view_have_form_controls( $view_id );
			$has_submit = $this->wp_views->does_view_have_form_control_with_submit( $view_id );
			$has_extra_attributes = get_view_allowed_attributes( $view_id );

			$view_settings = $view->view_settings;
			$view_limit = $view_settings['limit'];

			$view_shortcode_attributes = array(
				'view' => $view_id,
				'limit' => $override_limit ? sanitize_text_field( toolset_getpost( 'limit', -1 ) ) : '',
				'offset' => $override_offset ? sanitize_text_field( toolset_getpost( 'offset', 0 ) ) : '',
				'orderby' => $override_orderby ? sanitize_text_field( toolset_getpost( 'orderby', 0 ) ) : '',
				'order' => $override_order ? sanitize_text_field( toolset_getpost( 'order', 0 ) ) : '',
				'secondaryOrderby' => $override_secondary_orderby ? sanitize_text_field( toolset_getpost( 'secondaryOrderby', '' ) ) : '',
				'secondaryOrder' => $override_secondary_order ? sanitize_text_field( toolset_getpost( 'secondaryOrder', '' ) ) : '',
				'hasCustomSearch' => $has_custom_search,
				'formDisplay' => sanitize_text_field( toolset_getpost( 'formDisplay', '' ) ),
				'hasExtraAttributes' => $has_extra_attributes,
				'queryFilters' => json_decode( sanitize_text_field( wp_unslash( toolset_getpost( 'queryFilters', '{}' ) ) ), true ),

			);

			// The "limit" attribute of the block is set, which means that the block is overriding the View's setting.
			if (
				'' !== $view_shortcode_attributes['limit'] &&
				(
					// The View blocks setting for the "limit" is either greater than 10 or set to "no limit"...
					(int) $view_shortcode_attributes['limit'] > 10 ||
					-1 === (int) $view_shortcode_attributes['limit']
				)
			) {
				// The number of items generated for the preview should be limited to 10.
				$view_shortcode_attributes['limit'] = '10';
			}

			// The "limit" attribute of the block is not set, which means that the block is not overriding the View's setting.
			if (
				'' === $view_shortcode_attributes['limit'] &&
				(
					// The View setting for the "limit" is either greater than 10 or set to "no limit"...
					(int) $view_limit > 10 ||
					-1 === (int) $view_limit
				)
			) {
				// The number of items generated for the preview should be limited to 10.
				$view_shortcode_attributes['limit'] = '10';
			}

			$view_content = do_shortcode( $this->get_view_shortcode( $view_shortcode_attributes ) );

			/**
			 * Filters the extra CSS of the generated preview for the View used in the block. It's used to get the extra
			 * CSS inserted in the View editor in order to render the View preview accordingly.
			 *
			 * @since 2.8.2
			 *
			 * @param string $views_extra_css The extra CSS of the View.
			 */
			$views_extra_css = '';
			$views_extra_css = apply_filters( 'wpv_filter_get_meta_html_extra_css', $views_extra_css );

			if ( '' !== $views_extra_css ) {
				$views_extra_css = '<style>' . $views_extra_css . '</style>';
			}
			$view_content = $view_content . $views_extra_css;

			// Check if this is Gutenberg View
			$meta = get_post_meta( $view_id, '_wpv_is_gutenberg_view' );
			$is_gutenberg_view = false;
			if ( ! empty( $meta ) ) {
				$is_gutenberg_view = $meta[0];
			}
			// If this is a Gutenberg View, we need to fetch a list of pages
			// where it was used and show them on the preview overlay
			// than we also need to get the complete block markup from
			// the view parent post
			$posts = array();
			$block_html = '';
			if ( $is_gutenberg_view ) {
				$used_in_posts = get_post_meta( $view_id, '_wpv_used_in_posts' );
				if ( ! empty( $used_in_posts ) && ! empty( $used_in_posts[0] ) ) {
					$args = array(
						'numberposts' => 50,
						'post_type' => 'any',
						'post__in' => json_decode( $used_in_posts[0], true ),
					);
					$pre_posts = get_posts( $args );
					foreach ( $pre_posts as $pre_post ) {
						$posts[] = array(
							'id' => $pre_post->ID,
							'title' => $pre_post->post_title,
						);
					}
				}
				$meta = get_post_meta( $view_id, '_wpv_view_data' );
				if ( ! empty( $meta ) && ! empty( $meta[0]['general']['initial_parent_post_id'] ) ) {
					$service = new \OTGS\Toolset\Views\Services\ViewParsingService();
					$post = get_post( $meta[0]['general']['initial_parent_post_id'] );
					if ( null !== $post ) {
						$location = $service->find_block_in_text(
							$post->post_content,
							'toolset-views/view-editor',
							function ( $item ) use ( $view_id, $view_slug ) {
								return (
									isset( $item->block->attrs['insertExisting'] ) &&
									'0' === $item->block->attrs['insertExisting'] &&
									isset( $item->block->attrs['viewSlug'] ) &&
									$view_slug === $item->block->attrs['viewSlug']
								);
							}
						);
						if ( null !== $location ) {
							$block_html = substr( $post->post_content, $location['start'], $location['end'] - $location['start'] );
						}
					}
					if ( '' === $block_html ) {
						$block_html = $service->try_to_restore_markup( $view_id );
					}
				}
			}
			$block_data = parse_blocks( $block_html );
			$block_attributes = isset($block_data[0]) && isset($block_data[0]['attrs'] ) ? $block_data[0]['attrs'] : [];


			$output = array(
				'view_id' => $view_id,
				'viewSlug' => $view_slug,
				'hasCustomSearch' => $has_custom_search,
				'hasSubmit' => $has_submit,
				'hasExtraAttributes' => $has_extra_attributes,
				'viewContent' => trim( $view_content ),
				'overlay' => $this->render_view_block_overlay( $view->id, $view->title ),
				'isGutenbergView' => $is_gutenberg_view,
				'editUrl' => admin_url( '/admin.php?page=views-editor&view_id=' . $view_id ),
				'usedInPosts' => $posts,
				'blockHtml' => $block_html,
				'blockAttributes' => $block_attributes,
			);

			$this->ajax_finish( $output, true );
			return;
		}

		$this->ajax_finish( array( 'message' => sprintf( __( 'Error while retrieving the View preview. The selected View (ID: %s) was not found.', 'wpv-views' ), $view_id ) ), false );
	}

	/**
	 * Prepares the View shortcode that will be used to produce the preview rendering of the selected View according to
	 * the rest of the user's choices in the Inspector.
	 *
	 * @param  array $attributes The array with the View attributes that will be used to populate the final shortcode.
	 *
	 * @return string The View shortcode.
	 */
	public function get_view_shortcode( $attributes ) {
		$defaults = array(
			'view' => '',
			'limit' => '',
			'offset' => '',
			'orderby' => '',
			'order' => '',
			'secondaryOrderby' => '',
			'secondaryOrder' => '',
			'hasCustomSearch' => false,
			'formDisplay' => '',
			'hasExtraAttributes' => array(),
		);

		$attributes = wp_parse_args( $attributes, $defaults );

		$view = '';
		if ( '' !== $attributes['view'] ) {
			$view = is_numeric( $attributes['view'] ) ? ' id="' . $attributes['view'] . '"' : ' name="' . $attributes['view'] . '"';
		}
		$shortcode_start = '[wpv-view';
		$shortcode_end = ']';
		$limit = '' !== $attributes['limit'] && (int) $attributes['limit'] >= -1 ? ' limit="' . $attributes['limit'] . '"' : '';
		$offset = '' !== $attributes['offset'] && (int) $attributes['offset'] >= 0 ? ' offset="' . $attributes['offset'] . '"' : '';
		$orderby = '' !== $attributes['orderby'] ? ' orderby="' . $attributes['orderby'] . '"' : '';
		$order = '' !== $attributes['order'] ? ' order="' . $attributes['order'] . '"' : '';
		$secondary_order_by = '' !== $attributes['secondaryOrderby'] ? ' orderby_second="' . $attributes['secondaryOrderby'] . '"' : '';
		$secondary_order = '' !== $attributes['secondaryOrder'] ? ' order_second="' . $attributes['secondaryOrder'] . '"' : '';

		$target = '';
		$view_display = '';

		if (
			isset( $attributes['hasCustomSearch'] )
			&& $attributes['hasCustomSearch']
			&& isset( $attributes['formDisplay'] )
			&& 'form' === $attributes['formDisplay']
		) {
			$shortcode_start = '[wpv-form-view';
			if (
				! isset( $attributes['formOnlyDisplay'] )
				|| 'samePage' === $attributes['formOnlyDisplay']
			) {
				$target = ' target_id="self"';
			} elseif (
				isset( $attributes['formOnlyDisplay'] )
				&& 'otherPage' === $attributes['formOnlyDisplay']
				&& isset( $attributes['hasSubmit'] )
				&& $attributes['hasSubmit']
				&& is_array( $attributes['otherPage'] )
			) {
				$target = ' target_id="' . $attributes['otherPage']['value'] . '"';
			}
		}

		if (
			isset( $attributes['hasCustomSearch'] )
			&& $attributes['hasCustomSearch']
			&& isset( $attributes['formDisplay'] )
			&& 'results' === $attributes['formDisplay']
		) {
			$target = '';
			$view_display = ' view_display="layout"';
		}

		$query_filters = '';
		foreach ( $attributes['hasExtraAttributes'] as $extra_attribute ) {
			if (
				is_array( $attributes['queryFilters'] ) &&
				isset( $extra_attribute['filter_type'] ) &&
				! empty( $attributes['queryFilters'][ $extra_attribute['filter_type'] ] )
			) {
				$query_filters .= ' ' . $extra_attribute['attribute'] . '="' . $attributes['queryFilters'][ $extra_attribute['filter_type'] ] . '"';
			}
		}

		return $shortcode_start . $view . $limit . $offset . $orderby . $order . $secondary_order_by . $secondary_order . $target . $view_display . $query_filters . $shortcode_end;
	}

	/**
	 * Renders the Toolset View Gutenberg block overlay for the block preview on the editor.
	 *
	 * @param string $view_id    The ID of the selected View.
	 * @param string $view_title The title of the selected View.
	 *
	 * @return bool|string
	 */
	public function render_view_block_overlay( $view_id, $view_title ) {
		$renderer = $this->toolset_renderer;
		$context = array(
			'module_title' => $view_title,
			'module_type' => __( 'View', 'wpv-view' ),
		);

		// The edit link is only offered for users with proper permissions.
		if ( current_user_can( EDIT_VIEWS ) ) {
			$context['edit_link'] = admin_url( 'admin.php?page=views-editor&view_id=' . $view_id );
		}

		$html = $renderer->render(
			$this->template_repository->get( $this->constants->constant( 'Toolset_Output_Template_Repository::PAGE_BUILDER_MODULES_OVERLAY' ) ),
			$context,
			false
		);

		return $html;
	}
}
