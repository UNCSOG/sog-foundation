<?php

namespace OTGS\Toolset\Views\Controllers;

/**
 * A type of controller that registers REST routes.
 *
 * Interface RestControllerInterface
 */
interface RestControllerInterface {

	/**
	 * Registers the controller REST routes.
	 */
	public function register_routes();
}
