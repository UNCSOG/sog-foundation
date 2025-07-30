<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Views Page Builders Frontend settings save callback action.
 *
 * @since 2.6.4
 */
class WPV_Ajax_Handler_Save_Views_Page_Builders_Frontend_Content_Settings extends Toolset_Ajax_Handler_Abstract {
	private $views_settings;

	public function __construct(
		Toolset_Ajax $ajax_manager,
		\WPV_Settings $views_settings = null
	) {
		parent::__construct( $ajax_manager );

		$this->views_settings = $views_settings ?
			$views_settings :
			\WPV_Settings::get_instance();
	}

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_SAVE_VIEWS_PAGE_BUILDERS_FRONTEND_CONTENT_SETTINGS,
				'is_public' => false,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' )
				),
				false
			);

			return;
		}

		/**
		 * Allow Views WordPress widgets in Elementor.
		 */
		$allow_views_wp_widgets_in_elementor = sanitize_text_field( toolset_getpost( 'allow_views_wp_widgets_in_elementor', 'false' ) );
		$this->views_settings->allow_views_wp_widgets_in_elementor = ( 'true' === $allow_views_wp_widgets_in_elementor ) ? 1 : 0;

		// More settings might come here in the future.

		$this->views_settings->save();

		$ajax_manager->ajax_finish(
			array(),
			true
		);
	}
}
