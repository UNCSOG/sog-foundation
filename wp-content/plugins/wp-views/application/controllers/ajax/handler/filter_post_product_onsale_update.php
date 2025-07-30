<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;
use \OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;
use \OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale\ProductOnsaleGui;

/**
 * Save the post product on sale query filter.
 */
class WPV_Ajax_Handler_Filter_Post_Product_Onsale_Update extends \Toolset_Ajax_Handler_Abstract {

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 */
	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => ProductOnsaleGui::NONCE,
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

		parse_str( $filter_options, $filter_product_onsale );
		$filter_settings = array();

		try {

			$view->begin_modifying_view_settings();

			$filter_settings = $view->filters;
			$filter_settings = toolset_ensarr( $filter_settings );

			$filter_settings[ ProductOnsale::SLUG ] = array(
				'mode' => toolset_getnest( $filter_product_onsale, array( ProductOnsale::SLUG, 'mode' ), 'query_filter' ),
				'shortcode_attribute' => toolset_getnest( $filter_product_onsale, array( ProductOnsale::SLUG, 'shortcode_attribute' ), 'onsale' ),
				'url_parameter' => toolset_getnest( $filter_product_onsale, array( ProductOnsale::SLUG, 'url_parameter' ), 'wpv-on-sale' ),
			);

			$view->filters = $filter_settings;

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
			'id' => $view_id,
			'message' => __( 'Filter saved', 'wpv-views' ),
			'summary' => \WPV_Filter_Manager::get_instance()
				->get_filter( \Toolset_Element_Domain::POSTS, ProductOnsale::SLUG )
				->get_gui()
				->get_filter_summary( array( \WPV_Filter_Manager::SETTING_KEY => $filter_settings ) ),
			'parametric' => wpv_get_parametric_search_hints_data( $view_id ),
		);
		$ajax_manager->ajax_finish(
			$data,
			true
		);
	}
}
