<?php

namespace OTGS\Toolset\Layouts;

/**
 * Plugin cache controller.
 *
 * This sould be the main cache manager for Toolset Layouts.
 *
 * @since 2.6.3
 */
class Cache {

	/**
	 * @var \OTGS\Toolset\Layouts\Cache\EligibleLayoutsForAssignation $elfa_cache
	 */
	private $elfa_cache;

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Layouts\Cache\EligibleLayoutsForAssignation $elfa_cache
	 */
	public function __construct(
		\OTGS\Toolset\Layouts\Cache\EligibleLayoutsForAssignation $elfa_cache
	) {
		$this->elfa_cache = $elfa_cache;
	}

	/**
	 * Initialize the plugin cache management.
	 *
	 * @since 2.6.3
	 */
	public function initialize() {
		$this->elfa_cache->initialize();
	}
}
