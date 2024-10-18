<?php

namespace OTGS\Toolset\Views\Controller\Admin\Section;

/**
 * Top bar for Views and WordPress Archives.
 *
 * @since 2.7
 */
class TopBar {

	/**
	 * @var string
	 */
	private $object_type = 'view';

	/**
	 * @var string
	 */
	private $query_mode = 'normal';

	/**
	 * @var \Toolset_Renderer
	 */
	private $renderer = null;

	/**
	 * @var \WPV_Output_Template_Repository
	 */
	private $template_repository = null;

	/**
	 * Create a new top bar object.
	 *
	 * @param \Toolset_Renderer $renderer_di
	 * @param \WPV_Output_Template_Repository $template_repository_di
	 */
	public function __construct(
		\Toolset_Renderer $renderer_di = null,
		\WPV_Output_Template_Repository $template_repository_di = null
	) {
		$this->renderer = ( null === $renderer_di )
			? \Toolset_Renderer::get_instance()
			: $renderer_di;
		$this->template_repository = ( null === $template_repository_di )
			? \WPV_Output_Template_Repository::get_instance()
			: $template_repository_di;
	}

	/**
	 * Initialize the top bar.
	 *
	 * @since 2.7
	 */
	public function initialize() {
		// Top bar
		add_action( 'wpv_action_view_editor_top_bar', array( $this, 'render_view_top_bar' ), 10, 4 );
		add_action( 'wpv_action_wpa_editor_top_bar', array( $this, 'render_wpa_top_bar' ), 10, 4 );
		// Render the trash button
		add_filter( 'wpv_filter_wpv_admin_add_editor_trash_button', array( $this, 'maybe_add_trash_button' ) );
	}

	/**
	 * Render the top bar for the Views editor.
	 *
	 * @param array $view_settings
	 * @param int $view_id
	 * @param int $user_id
	 * @param \WP_Post $view
	 *
	 * @since 2.7
	 */
	public function render_view_top_bar( $view_settings, $view_id, $user_id, $view ) {
		$this->render_top_bar( $view_settings, $view_id, $user_id, $view );
	}

	/**
	 * Render the top bar for the WPAs editor.
	 *
	 * @param array $view_settings
	 * @param int $view_id
	 * @param int $user_id
	 * @param \WP_Post $view
	 *
	 * @since 2.7
	 */
	public function render_wpa_top_bar( $view_settings, $view_id, $user_id, $view ) {
		$this->object_type = 'archive';
		$this->query_mode = toolset_getarr( $view_settings, 'view-query-mode' );
		$this->render_top_bar( $view_settings, $view_id, $user_id, $view );
	}

	/**
	 * Render the top bar for the editor.
	 *
	 * @param array $view_settings
	 * @param int $view_id
	 * @param int $user_id
	 * @param \WP_Post $view
	 *
	 * @since 2.7
	 */
	private function render_top_bar( $view_settings, $view_id, $user_id, $view ) {
		$context = array(
			'view_settings' => $view_settings,
			'view_id' => $view_id,
			'user_id' => $user_id,
			'view' => $view,
			'data' => array(
				'title_label' => $this->get_title_label(),
				'save_button_label' => $this->get_save_button_label(),
				'trash_redirect' => $this->get_trash_redirect(),
				'page_creation_trigger' => $this->get_page_creation_trigger(),
				'extra_buttons' => $this->get_extra_buttons( $view_id ),
			),
		);
		?>
		<div id="js-wpv-general-actions-bar" class="wpv-settings-save-all wpv-general-actions-bar js-wpv-no-lock js-wpv-general-actions-bar">
			<?php
			$this->renderer->render(
				$this->template_repository->get( \WPV_Output_Template_Repository::EDITOR_SECTION_SHARED_TOP_BAR_TITLE ),
				$context
			);
			$this->renderer->render(
				$this->template_repository->get( \WPV_Output_Template_Repository::EDITOR_SECTION_SHARED_TOP_BAR_SAVE_FORM_ACTIONS ),
				$context
			);
			$this->renderer->render(
				$this->template_repository->get( \WPV_Output_Template_Repository::EDITOR_SECTION_SHARED_TOP_BAR_DESCRIBE_ACTIONS ),
				$context
			);
			?>
			<div class="wpv-message-container js-wpv-message-container"></div>
			<div class="wpv-view-save-all-progress js-wpv-view-save-all-progress"></div>
		</div>
		<?php
	}

	/**
	 * Get the edit page title.
	 *
	 * @return string
	 *
	 * @since 2.7
	 */
	private function get_title_label() {
		if ( 'archive' === $this->object_type ) {
			if ( 'archive' === $this->query_mode ) {
				/* translators: Page title when editing a WordPress Archive */
				return __( 'Edit WordPress Archive', 'wpv-views' );
			} else {
				/* translators: Page title when editing a WordPress Archive generated as a Layouts cell */
				return __( 'Edit Layouts Loop View', 'wpv-views' );
			}
		}
		/* translators: Page title when editing a View */
		return __( 'Edit View', 'wpv-views' );
	}

	/**
	 * Get the label for the button to save all sections.
	 *
	 * @return string
	 *
	 * @since 2.7
	 */
	private function get_save_button_label() {
		if ( 'archive' === $this->object_type ) {
			if ( 'archive' === $this->query_mode ) {
				/* translators: Label of the save button when editing a WordPress Archive */
				return __( 'Save the WordPress Archive', 'wpv-views' );
			} else {
				/* translators: Label of the save button when editing a WordPress Archive generated as a Layouts cell */
				return __( 'Save', 'wpv-views' );
			}
		}
		/* translators: Label of the save button when editing a View */
		return __( 'Save the View', 'wpv-views' );
	}

	/**
	 * Get the URL to redirect to after trashing.
	 *
	 * @return string
	 *
	 * @since 2.7
	 */
	private function get_trash_redirect() {
		if ( 'dashboard' === toolset_getget( 'ref' ) ) {
			return admin_url( 'admin.php?page=toolset-dashboard' );
		}
		if ( 'archive' === $this->object_type ) {
			return admin_url( 'admin.php?page=view-archives' );
		}
		return admin_url( 'admin.php?page=views' );
	}

	/**
	 * Get the trigger to create a page for the current View, if needed.
	 *
	 * @return string
	 *
	 * @since 2.7
	 */
	private function get_page_creation_trigger() {
		if ( defined( 'WPDDL_VERSION' ) ) {
			return '';
		}
		if ( 'archive' === $this->object_type ) {
			return '';
		}
		return '<a href="#" class="submit-create-page js-wpv-view-create-page"'
			/* translators: Error message when failing to create a page with the currently editing View */
			. ' data-error="' . __( 'An error occurred, try again.', 'wpv-views' ) . '">'
			/* translators: Label for the link to create a page with the currently editing View */
			. __( 'Create a page with this View', 'wpv-views' )
			. '</a>';
	}

	/**
	 * Filter the state of appearance of the trash button based on the 'in-iframe-for-layout' URL parameter that comes from Layouts.
	 *
	 * @param bool $state
	 * @return bool
	 *
	 * @since 2.3.0
	 */
	public function maybe_add_trash_button( $state ) {
		if ( isset( $_GET['in-iframe-for-layout'] ) ) {
			$state = false;
		}
		return $state;
	}

	/**
	 * Generic method for getting extra buttons
	 *
	 * @param int $view_id View ID.
	 * @return array
	 *
	 * @since 2.7
	 */
	private function get_extra_buttons( $view_id ) {
		$buttons = [];
		if (
			'archive' === $this->object_type
			&& 'archive' === $this->query_mode
			// Whether in a mixed editing experience.
			&& 'mixed' === wpv_get_views_editing_experience()
		) {
			$renderer = \Toolset_Renderer::get_instance();
			$buttons[] = $renderer->render(
				$this->template_repository->get( \WPV_Output_Template_Repository::WPA_EDITOR_BLOCK_SWITCH_EDITOR_GUTENBERG ),
				array(
					'admin_url' => admin_url( 'admin.php?page=view-archives-editor&view_id=' . $view_id . '&wpa_change_editor=basic' ),
				),
				false
			);
		}
		return $buttons;
	}
}
