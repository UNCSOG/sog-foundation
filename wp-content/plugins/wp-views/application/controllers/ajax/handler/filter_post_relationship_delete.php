<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Delete post relationship query filters.
 *
 * @since m2m
 */
class WPV_Ajax_Handler_Filter_Post_Relationship_Delete extends Toolset_Ajax_Handler_Abstract {

	/**
	 * WP Nonce.
	 *
	 * @var string
	 * @since m2m
	 */
	const NONCE = 'wpv_view_filter_post_relationship_delete_nonce';

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => self::NONCE,
			'capability_needed' => EDIT_VIEWS,
		) );

		$view_id = toolset_getpost( 'id' );

		if (
			! is_numeric( $view_id )
			|| intval( $view_id ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		$view = WPV_View_Base::get_instance( $view_id );

		if ( null === $view ) {
			$data = array(
				'type' => '',
				'message' => __( 'Wrong or missing View.', 'wpv-views' )
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		try {

			$view->begin_modifying_view_settings();

			$view->delete_view_settings( array(
				'post_relationship_mode',
				'post_relationship_id',
				'post_relationship_shortcode_attribute',
				'post_relationship_url_parameter',
				'post_relationship_framework',
				// @since m2m.
				'post_relationship_slug',
				'post_relationship_role',
				// Backwards compatibility:
				// those entries existed until m2m
				'post_relationship_url_tree',
				// those entries existed in the View settings up until 2.4.0
				'filter_controls_field_name',
				'filter_controls_mode',
				'filter_controls_label',
				'filter_controls_type',
				'filter_controls_values',
				'filter_controls_enable',
				'filter_controls_param'
			) );

			$view->finish_modifying_view_settings();

		} catch ( WPV_RuntimeExceptionWithMessage $e ) {
			$data = array(
				'type' => '',
				'message' => $e->getUserMessage()
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
		} catch ( Exception $e ) {
			$data = array(
				'type' => '',
				'message' => __( 'An unexpected error ocurred.', 'wpv-views' )
			);
			$ajax_manager->ajax_finish(
				$data,
				false
			);
		}

		$data = array(
			'id'			=> $view_id,
			'parametric'	=> wpv_get_parametric_search_hints_data( $view_id ),
			'message'		=> __( 'Post relationship filter deleted', 'wpv-views' )
		);
		$ajax_manager->ajax_finish(
			$data,
			true
		);

	}

}
