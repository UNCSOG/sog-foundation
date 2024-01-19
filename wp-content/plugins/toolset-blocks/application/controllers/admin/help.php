<?php

class WPV_Controller_Admin_Help {

	/**
	 * Initialization function. Sets the templates' path and adds the help button content callback on the "admin_hear" hook.
	 *
	 * @since 2.5.0
	 */
	public function init() {
		add_action( 'admin_head', array( $this, 'admin_add_help' ), 10, 2 );
	}

	/**
	 * Creates and adds the content of the Admin Help button.
	 *
	 * @since 2.5.0
	 */
	public function admin_add_help( $arg = '', Toolset_Constants $constants = null ) {
		$screen = get_current_screen();

		if ( is_null( $screen ) ) {
			return;
		}

		$all_views_pages_slugs = WPV_Page_Slug::all();
		if ( ! in_array( $screen->id, $all_views_pages_slugs ) ) {
			return;
		}
		
		$template_repository = WPV_Output_Template_Repository::get_instance();
		$renderer = Toolset_Renderer::get_instance();

		switch ( $screen->id ) {
			case WPV_Page_Slug::VIEWS_LISTING_PAGE_DEPRECATED:// DERPECATED
			case WPV_Page_Slug::VIEWS_LISTING_PAGE:
				$help = $renderer->render(
					$template_repository->get( WPV_Output_Template_Repository::VIEWS_LISTING_PAGE_HELP ),
					null,
					false
				);
				$screen->add_help_tab(
					array(
						'id' => 'views-help',
						'title' => __( 'Views', 'wpv-views' ),
						'content' => $help,
					)
				);
				break;
			case WPV_Page_Slug::VIEWS_EDIT_PAGE_DEPRECATED:// DERPECATED
			case WPV_Page_Slug::VIEWS_EDIT_PAGE:
				$help = $renderer->render(
					$template_repository->get( WPV_Output_Template_Repository::VIEWS_EDIT_PAGE_HELP ),
					null,
					false
				);
				$screen->add_help_tab(
					array(
						'id' => 'views-help',
						'title' => __( 'Views', 'wpv-views' ),
						'content' => $help,
					)
				);
				break;
			case WPV_Page_Slug::CONTENT_TEMPLATES_LISTING_PAGE_DEPRECATED:// DERPECATED
			case WPV_Page_Slug::CONTENT_TEMPLATES_LISTING_PAGE:
				$help = $renderer->render(
					$template_repository->get( WPV_Output_Template_Repository::CONTENT_TEMPLATES_LISTING_PAGE_HELP ),
					null,
					false
				);
				$screen->add_help_tab(
					array(
						'id' => 'views-help',
						'title' => __( 'Content Templates', 'wpv-views' ),
						'content' => $help,
					)
				);
				break;
			case WPV_Page_Slug::CONTENT_TEMPLATES_EDIT_PAGE_DEPRECATED:// DERPECATED
			case WPV_Page_Slug::CONTENT_TEMPLATES_EDIT_PAGE:
				$help = $renderer->render(
					$template_repository->get( WPV_Output_Template_Repository::CONTENT_TEMPLATES_EDIT_PAGE_HELP ),
					null,
					false
				);
				$screen->add_help_tab(
					array(
						'id' => 'views-help',
						'title' => __( 'Content Templates', 'wpv-views' ),
						'content' => $help,
					)
				);
				break;
			case WPV_Page_Slug::WORDPRESS_ARCHIVES_LISTING_PAGE_DEPRECATED:// DERPECATED
			case WPV_Page_Slug::WORDPRESS_ARCHIVES_LISTING_PAGE:
				$help = $renderer->render(
					$template_repository->get( WPV_Output_Template_Repository::WORDPRESS_ARCHIVES_LISTING_PAGE_HELP ),
					null,
					false
				);
				$screen->add_help_tab(
					array(
						'id' => 'views-help',
						'title' => __( 'WordPress Archives', 'wpv-views' ),
						'content' => $help,
					)
				);
				break;
			case WPV_Page_Slug::WORDPRESS_ARCHIVES_EDIT_PAGE_DEPRECATED:// DERPECATED
			case WPV_Page_Slug::WORDPRESS_ARCHIVES_EDIT_PAGE:
				$help = $renderer->render(
					$template_repository->get( WPV_Output_Template_Repository::WORDPRESS_ARCHIVES_EDIT_PAGE_HELP ),
					null,
					false
				);
				$screen->add_help_tab(
					array(
						'id' => 'views-help',
						'title' => __( 'WordPress Archives', 'wpv-views' ),
						'content' => $help,
					)
				);
				break;
			case WPV_Page_Slug::VIEWS_SETTINGS_DEPRECATED:// DERPECATED
			case WPV_Page_Slug::VIEWS_IMPORT_EXPORT_DEPRECATED:// DEPRECATED
			case WPV_Page_Slug::VIEWS_FRAMEWORK_INTEGRATION_DEPRECATED:// DEPRECATED
				break;
		}
	}
}