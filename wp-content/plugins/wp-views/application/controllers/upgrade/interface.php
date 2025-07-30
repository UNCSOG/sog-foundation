<?php

namespace OTGS\Toolset\Views\Controller\Upgrade;

/**
 * Interface for upgrade routines.
 *
 * @since 2.8.3
 */
interface IRoutine {

	/**
	 * @param mixed $arguments Data passed to the relevant upgrade routine.
	 * @return mixed
	 * @since 2.8.3
	 */
	public function execute_routine( $arguments = null );

}
