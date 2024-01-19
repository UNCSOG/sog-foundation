<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Create a Content Template.
 *
 * Currently only used in the Content Template Listing page.
 *
 * @since unknown
 * @since 2.7.0 Moved the callback here.
 */
class WPV_Ajax_Handler_Create_Content_Template extends Toolset_Ajax_Handler_Abstract {
	private $ct;

	private $settings;

	public function __construct(
		Toolset_Ajax $ajax_manager,
		$ct = null,
		WPV_Settings $settings = null
	) {
		parent::__construct( $ajax_manager );

		$this->ct = $ct;

		$this->settings = $settings;
	}

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_CREATE_CONTENT_TEMPLATE,
				'public' => false,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' ),
				),
				false
			);

			return;
		}

		$title = sanitize_text_field( (string) toolset_getpost( 'title', '' ) );

		if ( empty( $title ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'title',
					'message' => __( 'You can not create a Content Template with an empty name.', 'wpv-views' ),
				),
				false
			);

			return;
		}

		$ct = $this->ct ?
			$this->ct :
			WPV_Content_Template::create( $title, false );

		if ( ! $ct instanceof WPV_Content_Template ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'error',
					'message' => toolset_ensarr( $ct ) && isset( $ct['error'] ) ?
						$ct['error'] :
						__( 'An error occurred while creating a Content Template.', 'wpv-views' ),
				),
				false
			);

			return;
		} else {
			// Success
			$settings = $this->settings ?
				$this->settings :
				WPV_Settings::get_instance();

			$type = toolset_getarr( $_POST, 'type', array() );
			$apply = toolset_getarr( $_POST, 'apply', array() );

			foreach ( $type as $type_to_save ) {
				$type_to_save = sanitize_text_field( $type_to_save );
				$settings[ $type_to_save ] = $ct->id;
			}

			$settings->save();

			$ct->kill_dissident_posts( $apply );

			$ajax_manager->ajax_finish(
				array(
					'id' => $ct->id,
				),
				true
			);
		}
	}
}
