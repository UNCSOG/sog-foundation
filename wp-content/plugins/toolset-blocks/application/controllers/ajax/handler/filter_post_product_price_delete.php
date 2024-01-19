<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;
use \OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;

/**
 * Delete the post product price query filter.
 */
class WPV_Ajax_Handler_Filter_Post_Product_Price_Delete extends \Toolset_Ajax_Handler_Abstract {

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 */
	public function process_call( $arguments ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin( array(
			'nonce' => ProductPrice\ProductPriceGui::NONCE,
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

			$filter_settings = $view->filters;
			$filter_settings = toolset_ensarr( $filter_settings );

			if ( array_key_exists( ProductPrice::SLUG, $filter_settings ) ) {
				unset( $filter_settings[ ProductPrice::SLUG ] );
			}

			$view->filters = $filter_settings;

			// Delete legacy filter by postmeta in case it exists.
			$view->delete_view_settings( array(
				'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_type',
				'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_decimals',
				'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_compare',
				'custom-field-' . ProductPrice::LEGACY_FIELD_SLUG . '_value',
			) );

			$view->finish_modifying_view_settings();

		} catch ( \WPV_RuntimeExceptionWithMessage $e ) {
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
			'parametric' => wpv_get_parametric_search_hints_data( $view_id ),
			'message' => __( 'Filter deleted', 'wpv-views' )
		);
		$ajax_manager->ajax_finish(
			$data,
			true
		);
	}

}
