<?php
/**
 * Remove legacy Views output cache, when updating to Views 3.1.
 *
 * @since 3.1
 * @package Toolset Views
 */

namespace OTGS\Toolset\Views\Controller\Upgrade;

/**
 * Remove legacy Views output cache, when updating to Views 3.1.
 *
 * @since 3.1
 */
class CleanLegacyCacheIndexes implements IRoutine {

	const VIEW_FULL_INDEX = 'wpv_transient_view_index';
	const VIEW_FULL_TRANSIENT_PREFIX = 'wpv_transient_view_';

	const VIEW_FORM_INDEX = 'wpv_transient_viewform_index';
	const VIEW_FORM_TRANSIENT_PREFIX = 'wpv_transient_viewform_';

	/**
	 * Execute routine.
	 *
	 * @param array $args
	 * @since 3.1
	 */
	public function execute_routine( $args = array() ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Clean index and transients of legacy full Views output.
		$cached_output_index = get_option( self::VIEW_FULL_INDEX, array() );
		foreach( $cached_output_index as $cache_id => $v ) {
			$trasient = self::VIEW_FULL_TRANSIENT_PREFIX . $cache_id;
			delete_transient( $trasient );
		}
		delete_option( self::VIEW_FULL_INDEX );

		// Clean index and transients of legacy form Views output.
		$cached_filter_index = get_option( self::VIEW_FORM_INDEX, array() );
		foreach( $cached_filter_index as $cache_id => $v ) {
			$trasient = self::VIEW_FORM_TRANSIENT_PREFIX . $cache_id;
			delete_transient( $trasient );
		}
		delete_option( self::VIEW_FORM_INDEX );
	}

}
