<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta;

/**
 * Termmeta cache controller.
 *
 * @since 2.8.1
 */
class Term extends Base {

	const VISIBLE_KEY = 'wpv_transient_termmeta_keys_visible512';
	const HIDDEN_KEY = 'wpv_transient_termmeta_keys_hidden512';

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\Term\Manager $manager
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\Term\Invalidator $invalidator
	 * @since 2.8.1
	 */
	public function __construct(
		\OTGS\Toolset\Views\Controller\Cache\Meta\Term\Manager $manager,
		\OTGS\Toolset\Views\Controller\Cache\Meta\Term\Invalidator $invalidator
	) {
		$this->manager = $manager;
		$this->invalidator = $invalidator;
	}

}
