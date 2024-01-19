<?php

/**
 * Class WPV_Editor_View_Wrapper
 *
 * Handles the display of the View Wrapper section in the View editor and the saving of the option to enable/disable it.
 *
 * @since 2.6.4
 */
class WPV_Editor_Section_View_Wrapper {

	/**
	 * Renderer
	 *
	 * @var Toolset_Renderer
	 */
	private $renderer;

	/**
	 * Constructor
	 *
	 * @param Toolset_Renderer $renderer_di For testing purposes.
	 *
	 * @since 2.6.4
	 */
	public function __construct( Toolset_Renderer $renderer_di = null ) {
		$this->renderer = null === $renderer_di ? Toolset_Renderer::get_instance() : $renderer_di;
	}

	/**
	 * Add hooks for the WPV_Editor_View_Wrapper class.
	 */
	public function add_hooks() {
		// Register the section in the editor pages.
		add_action( 'wpv_action_view_editor_section_view_wrapper', array( $this, 'editor_section_view_wrapper' ), 40, 2 );
	}

	/**
	 * Handles the template loading for the View editor section that manages the disabling of the View wrapper DIV.
	 *
	 * @param array $view_settings The array containing the View settings.
	 * @param int   $view_id       The ID of the View.
	 */
	public function editor_section_view_wrapper( $view_settings, $view_id ) {
		$this->render_section( $view_settings );
	}

	/**
	 * Echoes the section content.
	 *
	 * @param array $view_settings The array containing the View settings.
	 * @param bool  $echo          Determines if the section content will be echoed or will be returned.
	 *
	 * @return string
	 */
	public function render_section( $view_settings, $echo = true ) {
		$renderer = $this->renderer;

		$context = array(
			'section_help_pointer' => WPV_Admin_Messages::edit_section_help_pointer( 'view_wrapper' ),
			'view_settings' => $view_settings, // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		);

		$template_repository = WPV_Output_Template_Repository::get_instance();

		$html = $renderer->render(
			$template_repository->get( WPV_Output_Template_Repository::VIEWS_EDITOR_VIEW_WRAPPER_SECTION ),
			$context,
			false
		);

		if ( false !== $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}