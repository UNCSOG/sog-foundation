<?php

namespace OTGS\Toolset\Views\Controller\Upgrade;

/**
 * Upgrade database to 2080300 (Views 2.8.3)
 *
 * Delete the postmeta keys cache to purge some unwanted values.
 *
 * @since 2.1.2
 */
class Routine2080300DbUpgrade implements IRoutine {

	/**
	 * Execute database upgrade up to 2.1.2
	 *
	 * @param array $args
	 * @since 2.1.2
	 */
	// phpcd:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	public function execute_routine( $args = array() ) {
		do_action( \OTGS\Toolset\Views\Controller\Cache\Meta\Post\Invalidator::FORCE_DELETE_ACTION );
	}

}
