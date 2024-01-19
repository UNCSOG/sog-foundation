<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Handles AJAX calls to get the Content Template block preview.
 *
 * @since m2m
 */
class WPV_Ajax_Handler_Get_Content_Template_Block_Preview extends Toolset_Ajax_Handler_Abstract {
	/** @var Toolset_Constants */
	private $constants;

	/** @var Toolset_Renderer */
	private $toolset_renderer;

	/** @var Toolset_Output_Template_Repository */
	private $template_repository;

	/**
	 * WPV_Ajax_Handler_Get_Content_Template_Block_Preview constructor.
	 *
	 * @param \WPV_Ajax                           $ajax_manager
	 * @param \Toolset_Constants                  $constants
	 * @param \Toolset_Renderer                   $toolset_renderer
	 * @param \Toolset_Output_Template_Repository $template_repository
	 */
	public function __construct(
		\WPV_Ajax $ajax_manager,
		\Toolset_Constants $constants,
		\Toolset_Renderer $toolset_renderer,
		\Toolset_Output_Template_Repository $template_repository
	) {
		parent::__construct( $ajax_manager );

		$this->constants = $constants;
		$this->toolset_renderer = $toolset_renderer;
		$this->template_repository = $template_repository;
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
				'nonce' => WPV_Ajax::CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW,
				'is_public' => true,
			)
		);

		$ct_post_name = sanitize_text_field( toolset_getpost( 'ct_post_name', '' ) );

		if ( empty( $ct_post_name ) ) {
			$this->ajax_finish( array( 'message' => __( 'Content Template not set.', 'wpv-views' ) ), false );
		}

		$args = array(
			'name' => $ct_post_name,
			'posts_per_page' => 1,
			'post_type' => \WPV_Content_Template::POST_TYPE,
			'post_status' => 'publish',
		);

		$ct = get_posts( $args );

		if (
			null !== $ct
			&& count( $ct ) === 1
		) {
			$ct_post_content = str_replace( "\t", '&nbsp;&nbsp;&nbsp;&nbsp;', str_replace( "\n", '<br />', $ct[0]->post_content ) );

			$ct_post_content .= $this->render_ct_block_overlay( $ct[0]->ID, $ct[0]->post_title );

			$this->ajax_finish( $ct_post_content, true );
		}

		/* translators: Error message for when the Content Template block fails to render its preview. */
		$this->ajax_finish( array( 'message' => sprintf( __( 'Error while retrieving the Content Template preview. The selected Content Template (Slug: "%s") was not found.', 'wpv-views' ), $ct_post_name ) ), false );
	}

	/**
	 * Renders the Toolset Content Template Gutenberg block overlay for the block preview on the editor.
	 *
	 * @param string $ct_id    The ID of the selected Content Template.
	 * @param string $ct_title The title of the selected Content Template.
	 *
	 * @return bool|string
	 */
	public function render_ct_block_overlay( $ct_id, $ct_title ) {
		$renderer = $this->toolset_renderer;
		$context = array(
			'module_title' => $ct_title,
			'module_type' => __( 'Content Template', 'wpv-view' ),
			'icon' => 'icon-content-template',
		);

		// The edit link is only offered for users with proper permissions.
		if ( current_user_can( EDIT_VIEWS ) ) {
			$context['edit_link'] = admin_url( 'admin.php?page=ct-editor&ct_id=' . $ct_id );
		}

		return $renderer->render(
			$this->template_repository->get( $this->constants->constant( 'Toolset_Output_Template_Repository::PAGE_BUILDER_MODULES_OVERLAY' ) ),
			$context,
			false
		);
	}
}
