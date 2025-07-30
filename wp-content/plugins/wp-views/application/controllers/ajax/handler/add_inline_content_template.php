<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Class WPV_Ajax_Handler_Add_Inline_Content_Template
 *
 * Handles the creation of a new or assign an existing Content Template as an inline Template for a View or WPA.
 *
 * @since 2.7.0
 */
class WPV_Ajax_Handler_Add_Inline_Content_Template extends Toolset_Ajax_Handler_Abstract {
	private $ct;

	private $view_settings;

	private $view_meta;

	private $ct_id_by_ct_title;

	public function __construct(
		Toolset_Ajax $ajax_manager,
		$ct = null,
		$view_settings = null,
		$view_meta = null,
		$ct_id_by_ct_title = null
	) {
		parent::__construct( $ajax_manager );

		$this->ct = $ct;

		$this->view_settings = $view_settings;

		$this->view_meta = $view_meta;

		$this->ct_id_by_ct_title = $ct_id_by_ct_title;
	}

	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_ADD_INLINE_CONTENT_TEMPLATE,
			'public' => false,
			'capability_needed' => EDIT_VIEWS,
		) );

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'capability',
					/* translators: Text for the case where the user doesn't have the necessary permissions to perform an action. */
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

		$ct = null;
		$ct_id = 0;
		$template_name = sanitize_text_field( toolset_getpost( 'template_name', '' ) );
		$template_id = sanitize_text_field( toolset_getpost( 'template_id', '' ) );
		$mode = sanitize_text_field( toolset_getpost( 'mode', '', array( 'assign', 'create' ) ) );

		switch ( $mode ) {
			case 'assign':
				if ( '' !== $template_id ) {
					$ct_id = $template_id;
				} elseif ( '' !== $template_name ) {
					$ct_id = $this->ct_id_by_ct_title
						?: WPV_Content_Template::get_template_id_by_name( $template_name );
				}

				$ct = $this->ct ?
					$this->ct :
					WPV_Content_Template::get_instance( $ct_id );
				break;
			case 'create':
				if ( '' === $template_name ) {
					$ajax_manager->ajax_finish(
						array(
							'type' => 'name',
							/* translators: Error message text for the case where the Content Template name is not passed as an argument for the AJAX callback. */
							'message' => __( 'The Content Template name cannot be empty.', 'wpv-views' ),
						),
						false
					);
					return;
				}

				$ct = $this->ct ?
					$this->ct :
					WPV_Content_Template::create( $template_name, false );

				if ( ! $ct instanceof WPV_Content_Template ) {
					$type = 'error';
					/* translators: Error message text for the case where Content Template cannot be created for an unknown reason. */
					$error_message = __( 'Could not create a Content Template for this Loop. Please reload the page and try again.', 'wpv-views' );

					if (
						is_array( $ct ) &&
						isset( $ct['error'] )
					) {
						// Another Content Template with that title or name already exists
						$type = 'name';
						$error_message = $ct['error'];
					}

					$ajax_manager->ajax_finish(
						array(
							'type' => $type,
							'message' => $error_message,
						),
						false
					);
					return;
				}

				$ct_id = $ct->post()->ID;
				break;
			default:
				$ajax_manager->ajax_finish(
					array(
						'type' => 'mode',
						/* translators: Error message text for the case where the mode ("create" or "assign") of Content Template addition is not passed as an argument for the AJAX callback.. */
						'message' => __( 'No mode is set.', 'wpv-views' ),
					),
					false
				);
				// Using "return" here instead of "break" as this "return" will only be taken into account when unit testing this.
				return;
		}

		if ( null === $ct->post() ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'data',
					/* translators: Error message text for the case where the Content Template cannot be retrieved from the database for an unknown reason, possibly because of wrong arguments passed for the AJAX callback. */
					'message' => __( 'Wrong data.', 'wpv-views' ),
				),
				false
			);
			return;
		}

		$template_name = $ct->post()->post_name;

		$view_settings = null !== $this->view_settings ? $this->view_settings : apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id );
		$meta = null !== $this->view_meta ? $this->view_meta : apply_filters( 'wpv_filter_wpv_get_view_layout_settings', array(), $view_id );

		if (
			! isset( $view_settings['view-query-mode'] )
			|| ( 'normal' == $view_settings['view-query-mode'] )
		) {
			$query_mode = 'normal';
		} else {
			$query_mode = 'archive';
		}

		$reg_templates = array();
		if ( isset( $meta['included_ct_ids'] ) ) {
			$reg_templates = explode( ',', $meta['included_ct_ids'] );
			$reg_templates = array_map( 'esc_attr', $reg_templates );
			$reg_templates = array_map( 'trim', $reg_templates );
			// is_numeric does sanitization
			$reg_templates = array_filter( $reg_templates, 'is_numeric' );
			$reg_templates = array_map( 'intval', $reg_templates );
		}

		if ( in_array( $ct_id, $reg_templates ) ) {
			// The Content Template was already on the inline list
			$data = array(
				'type' => 'already',
				'ct_id' => $ct_id,
				'ct_name' => $template_name,
				'message' => __( 'This Content Template is already assigned to this View.', 'wpv-views' )
			);
			if ( 'archive' === $query_mode ) {
				$data['message'] = __( 'This Content Template is already assigned to this WordPress Archive.', 'wpv-views' );
			}
		} else {
			// Add the Content Template to the inline list and save it
			$reg_templates[] = $ct_id;
			$meta['included_ct_ids'] = implode( ',', $reg_templates );
			update_post_meta( $view_id, '_wpv_layout_settings', $meta );
			do_action( 'wpv_action_wpv_save_item', $view_id );
			$data = array(
				'type' => 'insert',
				'ct_id' => $ct_id,
				'ct_name' => $template_name,
			);
			$ct_post_object = $ct->post();
			// When the user has disabled the rich editing on his profile, the original post content contains HTML entities.
			// In order to prevent this, we need to decode its content before assigning it the duplicated post content.
			$ct_post_object->post_content = user_can_richedit()
				? $ct_post_object->post_content
				: html_entity_decode( $ct_post_object->post_content );
			ob_start();
			wpv_list_view_ct_item( $ct_post_object, $ct_id, $view_id, true );
			$data['message'] = ob_get_clean();
		}

		$ajax_manager->ajax_finish(
			$data,
			true
		);
	}
}
