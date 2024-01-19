<?php

namespace OTGS\Toolset\CRED\Controller\Upgrade;

/**
 * Interface for upgrade routines.
 * 
 * @since 2.1.1
 */
interface IRoutine {

	/**
	 * @param mixed $arguments Data passed to the relevant upgrade routine.
	 * @return mixed
	 * @since 2.1.1
	 */
	public function execute_routine( $arguments = null );

}