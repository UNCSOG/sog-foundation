<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Handle actions with relationship filters in the view edit page.
 *
 * @since m2m
 */
class WPV_Ajax_Handler_Filter_Post_Relationship_Update extends Toolset_Ajax_Handler_Abstract {

	/**
	 * WP Nonce.
	 *
	 * @var string
	 * @since m2m
	 */
	const NONCE = 'wpv_view_filter_post_relationship_nonce';

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

		$filter_options = toolset_getpost( 'filter_options' );

		if ( empty( $filter_options ) ) {
			$data = array(
				'type' => 'data_missing',
				'message' => __( 'Wrong or missing data.', 'wpv-views' )
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

		parse_str( $filter_options, $filter_relationship );

		try {

			$view->begin_modifying_view_settings();

			$settings_to_save = array(
				'post_relationship_mode' => toolset_getarr( $filter_relationship, 'post_relationship_mode' ),
				'post_relationship_id' => toolset_getarr( $filter_relationship, 'post_relationship_id' ),
				'post_relationship_shortcode_attribute' => toolset_getarr( $filter_relationship, 'post_relationship_shortcode_attribute' ),
				'post_relationship_url_parameter' => toolset_getarr( $filter_relationship, 'post_relationship_url_parameter' ),
				'post_relationship_framework' => toolset_getarr( $filter_relationship, 'post_relationship_framework' ),
				// @since m2m.
				'post_relationship_slug' => toolset_getarr( $filter_relationship, 'post_relationship_slug' ),
				'post_relationship_role' => toolset_getarr( $filter_relationship, 'post_relationship_role' )
			);

			$view->set_view_settings( $settings_to_save );

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
			'message'		=> __( 'Specific users filter saved', 'wpv-views' ),
			'summary'		=> \WPV_Filter_Manager::get_instance()
				->get_filter( \Toolset_Element_Domain::POSTS, 'relationship' )
				->get_gui()
				->get_filter_summary( array( \WPV_Filter_Manager::SETTING_KEY => $filter_relationship ) ),
			'parametric'	=> wpv_get_parametric_search_hints_data( $view_id )
		);
		$ajax_manager->ajax_finish(
			$data,
			true
		);

	}
}
