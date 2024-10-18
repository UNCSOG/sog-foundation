<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Class WPV_Ajax_Handler_Create_Layout_Content_Template
 *
 * Handles the creation of a Content Template to wrap each item in the loop.
 *
 * @since 2.7.0
 */
class WPV_Ajax_Handler_Create_Layout_Content_Template extends Toolset_Ajax_Handler_Abstract {
	private $ct;

	public function __construct(
		Toolset_Ajax $ajax_manager,
		$ct = null
	) {
		parent::__construct( $ajax_manager );

		$this->ct = $ct;
	}

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_CREATE_LAYOUT_CONTENT_TEMPLATE,
			'public' => false,
			'capability_needed' => EDIT_VIEWS,
		) );

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'capability',
					/* translators: Error message text for the case where the user doesn't have the necessary permissions to perform an action. */
					'message' => __( 'You do not have permissions for that.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		$view_id = (int) sanitize_text_field( toolset_getpost( 'view_id', '' ) );
		if ( $view_id < 1 ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'id',
					/* translators: Error message text for the case where the View ID is not passed as an argument for the AJAX callback. */
					'message' => __( 'Wrong or missing View ID.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		$view_name = sanitize_text_field( toolset_getpost( 'view_name', '' ) );
		if ( '' === $view_name ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'name',
					/* translators: Error message text for the case where the View name is not passed as an argument for the AJAX callback. */
					'message' => __( 'The View name cannot be empty.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		$ct = $this->ct ?
			$this->ct :
			WPV_Content_Template::create( 'Loop item in '. $view_name );

		if ( ! $ct instanceof WPV_Content_Template ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'error',
					/* translators: Error message text for the case where Content Template cannot be created for an unknown reason. */
					'message' => __( 'Could not create a Content Template for this Loop. Please reload the page and try again.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		update_post_meta( $view_id, '_view_loop_template', $ct->post()->ID );
		update_post_meta( $ct->post()->ID, '_view_loop_id', $view_id );

		$data = array(
			'id' => $view_id,
			/* translators: Informative message text for the case of the successful Content Template creation. */
			'message' => __( 'Content Template for this Loop created', 'wpv-views' ),
			'template_id' => $ct->post()->ID,
			'template_title' => $ct->post()->post_title,
			'template_name' => $ct->post()->post_name,
		);

		$meta = get_post_meta( $view_id, '_wpv_layout_settings', true );
		$reg_templates = array();

		if ( isset( $meta['included_ct_ids'] ) ) {
			$reg_templates = explode( ',', $meta['included_ct_ids'] );
			$reg_templates = array_map( 'esc_attr', $reg_templates );
			$reg_templates = array_map( 'trim', $reg_templates );
			// is_numeric does sanitization
			$reg_templates = array_filter( $reg_templates, 'is_numeric' );
			$reg_templates = array_map( 'intval', $reg_templates );
		}

		if ( ! in_array( $ct->post()->ID, $reg_templates ) ) {
			array_unshift( $reg_templates, $ct->post()->ID );
			$meta['included_ct_ids'] = implode( ',', $reg_templates );
			update_post_meta( $view_id, '_wpv_layout_settings', $meta );
			ob_start();
			wpv_list_view_ct_item( $ct->post(), $ct->post()->ID, $view_id, true );
			$data['template_html'] = ob_get_clean();
		}

		do_action( 'wpv_action_wpv_save_item', $view_id );

		$ajax_manager->ajax_finish(
			$data,
			true
		);
	}
}
