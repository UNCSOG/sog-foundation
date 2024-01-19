<?php

namespace OTGS\Toolset\Views\Controllers;

/**
 * A type of controller that registers to filters/actions.
 *
 * Interface RestControllerInterface
 */
interface HookControllerInterface {

	/**
	 * Registers controller hooks.
	 */
	public function register_hooks();
}
